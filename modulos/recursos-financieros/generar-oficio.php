<?php
/**
 * Acción: Generar Oficio de Suficiencia en PDF
 * Ubicación: /modulos/recursos-financieros/generar-oficio.php
 */

ob_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/utils_moneda.php';
require_once __DIR__ . '/../../includes/libs/tcpdf/tcpdf.php';

requireAuth();

$pdo = getConnection();

$id_fua = isset($_GET['id']) ? (int) $_GET['id'] : null;
$doc_id = isset($_GET['doc_id']) ? (int) $_GET['doc_id'] : null;

// Si viene doc_id, buscar el FUA asociado
if ($doc_id && !$id_fua) {
    $stmtDoc = $pdo->prepare("SELECT JSON_UNQUOTE(JSON_EXTRACT(contenido_json, '$.id_fua')) FROM documentos WHERE id = ?");
    $stmtDoc->execute([$doc_id]);
    $id_fua = $stmtDoc->fetchColumn();
}

if (!$id_fua) {
    die("ID de FUA no encontrado o documento no vinculado.");
}

// Obtener datos del FUA, Proyecto y Documento asociado
$stmt = $pdo->prepare("
    SELECT f.*, 
           p.nombre_proyecto, 
           p.ejercicio,
           d.id as documento_id,
           d.folio_sistema
    FROM solicitudes_suficiencia f
    LEFT JOIN proyectos_obra p ON f.id_proyecto = p.id_proyecto
    LEFT JOIN documentos d ON d.tipo_documento_id = 1
        AND JSON_UNQUOTE(JSON_EXTRACT(d.contenido_json, '$.id_fua')) = f.id_fua
    WHERE f.id_fua = ?
");
$stmt->execute([$id_fua]);
$fua = $stmt->fetch();

if (!$fua) {
    die("No se encontró la información solicitada.");
}

$user = getCurrentUser();
$userId = $user['id'];
$esAdmin = isAdmin(); // Función helper si existe, o verificar rol

// --- VALIDACIÓN DE ACCESO ---
// Solo permitir ver el documento si:
// 1. Es Admin.
// 2. Es parte del flujo de firmas (Firmante, Destinatario, Copia).
// 3. Es el generador del documento (si tenemos ese dato, asumimos que quien está en flujo lo cubre).

$tieneAcceso = $esAdmin;
$errorMsg = "No tienes permiso para visualizar este documento. Solo los firmantes o involucrados pueden verlo.";

if (!$tieneAcceso && isset($fua['documento_id'])) {
    // Verificar si está en el flujo y su estatus
    $stmtPermiso = $pdo->prepare("
        SELECT rol_oficio, estatus FROM documento_flujo_firmas 
        WHERE documento_id = ? AND firmante_id = ?
    ");
    $stmtPermiso->execute([$fua['documento_id'], $userId]);
    $perms = $stmtPermiso->fetch(PDO::FETCH_ASSOC);

    if ($perms) {
        // Permitir acceso a todos los involucrados en el flujo (Firmantes, Destinatarios, Copias, Atención)
        $tieneAcceso = true;
    }

    // Verificar si es el creador (usuario_generador_id en documentos)
    if (!$tieneAcceso && $errorMsg != "⛔ DEBES CONFIRMAR DE RECIBIDO ANTES DE VER ESTE DOCUMENTO.<br><br>Por favor, usa el botón 'Confirmar de Recibido' en tu bandeja de entrada.") {
        $stmtCreador = $pdo->prepare("SELECT usuario_generador_id FROM documentos WHERE id = ?");
        $stmtCreador->execute([$fua['documento_id']]);
        if ($stmtCreador->fetchColumn() == $userId) {
            $tieneAcceso = true;
        }
    }
}

// Registro de Lectura
if ($tieneAcceso && isset($fua['documento_id'])) {
    // Marcar como leído en la bandeja del usuario si existe asignación
    $stmtLeido = $pdo->prepare("
        UPDATE usuario_bandeja_documentos 
        SET leido = 1 
        WHERE usuario_id = ? AND documento_id = ? AND leido = 0
    ");
    $stmtLeido->execute([$userId, $fua['documento_id']]);
}

if (!$tieneAcceso) {
    http_response_code(403);
    die($errorMsg);
}

// Obtener firmantes configurados en el flujo de firmas del documento
$firmantes = [];
$destinatario = null;
$firmante_oficio = null;
$atencion = [];
$copias = [];

if ($fua['documento_id']) {
    $stmt_firmantes = $pdo->prepare("
        SELECT 
            dff.firmante_id,
            dff.rol_firmante,
            dff.rol_oficio,
            dff.orden,
            e.id as empleado_id,
            e.nombres,
            e.apellido_paterno,
            e.apellido_materno,
            e.puesto_finanzas as cargo,
            u.usuario,
            dff.estatus
        FROM documento_flujo_firmas dff
        JOIN usuarios_sistema u ON u.id = dff.firmante_id
        JOIN empleados e ON e.id = u.id_empleado
        WHERE dff.documento_id = ?
        ORDER BY dff.orden ASC
    ");
    $stmt_firmantes->execute([$fua['documento_id']]);
    $firmantes = $stmt_firmantes->fetchAll();

    // Clasificar por rol en oficio
    foreach ($firmantes as $f) {
        $nombre_completo = trim($f['nombres'] . ' ' . $f['apellido_paterno'] . ' ' . ($f['apellido_materno'] ?: ''));
        $cargo = $f['cargo'] ?: $f['rol_firmante'];

        $persona = [
            'nombre' => $nombre_completo,
            'cargo' => $cargo,
            'orden' => $f['orden']
        ];

        if ($f['rol_oficio'] == 'DESTINATARIO') {
            $destinatario = $persona;
        } elseif ($f['rol_oficio'] == 'FIRMANTE') {
            $firmante_oficio = $persona;
        } elseif ($f['rol_oficio'] == 'COPIA') {
            $copias[] = $persona;
        } elseif ($f['rol_oficio'] == 'ATENCION') {
            $atencion[] = $persona;
        }
    }
}

// --- ACTUALIZAR FECHA DE AUTORIZACIÓN "AL VUELO" ---
$user = getCurrentUser();
$nombre_autorizador = getNombreCompleto($user);

$stmt_upd = $pdo->prepare("
    UPDATE solicitudes_suficiencia 
    SET fecha_autorizacion = NOW(), 
        autorizado_por = ? 
    WHERE id_fua = ? AND fecha_autorizacion IS NULL
");
$stmt_upd->execute([$nombre_autorizador, $id_fua]);

// --- OBTENER FIRMA DIGITAL DEL USUARIO EN SESIÓN ---
// Usar la tabla empleado_firmas que contiene las firmas autógrafas registradas
$stmt_firma = $pdo->prepare("
    SELECT ef.firma_imagen 
    FROM empleado_firmas ef
    JOIN usuarios_sistema u ON u.id_empleado = ef.empleado_id
    WHERE u.id = ?
");
$stmt_firma->execute([$user['id']]);
$firma_base64 = $stmt_firma->fetchColumn();

// La firma viene en formato base64 (data:image/png;base64,...)
// TCPDF puede usar directamente la imagen base64 con el método Image()
$tiene_firma = !empty($firma_base64);

// Datos para el oficio
$num_oficio = $fua['num_oficio_tramite'] ?: 'DC/____/' . date('Y');

// --- Usar nombres de firmantes configurados o valores por defecto ---
// DESTINATARIO: A quien va dirigido el oficio
$destinatario_nombre = $destinatario ? $destinatario['nombre'] : 'C.P. MARLEN SÁNCHEZ GARCÍA';
$destinatario_cargo = $destinatario ? $destinatario['cargo'] : 'DIRECTORA DE ADMINISTRACIÓN';

// FIRMANTE (REMITENTE): Quien firma el oficio
$remitente_nombre = $firmante_oficio ? $firmante_oficio['nombre'] : 'ING. CÉSAR OTHÓN RODRÍGUEZ GÓMEZ';
$remitente_cargo = $firmante_oficio ? $firmante_oficio['cargo'] : 'SUBSECRETARIO DE INFRAESTRUCTURA CARRETERA';

// Permitir override por parámetros GET (para casos especiales)
$destinatario_nombre = $_GET['dest_nom'] ?? $destinatario_nombre;
$destinatario_cargo = $_GET['dest_car'] ?? $destinatario_cargo;
$remitente_nombre = $_GET['rem_nom'] ?? $remitente_nombre;
$remitente_cargo = $_GET['rem_car'] ?? $remitente_cargo;

$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fecha_formateada = date('d') . ' de ' . $meses[date('n') - 1] . ' de ' . date('Y');

$importe_letras = NumeroALetras::convertir($fua['monto_total_solicitado']);
$proyecto_nombre = $fua['nombre_proyecto_accion'] ?: $fua['nombre_proyecto'];

// --- CONFIGURACIÓN PDF ---
$pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
$pdf->SetCreator('SIS-PAO');
$pdf->SetAuthor('SECOPE');
$pdf->SetTitle('Oficio de Suficiencia - ' . $fua['id_fua']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(25, 30, 25);
$pdf->AddPage();

// --- LOGO (CORREGIDO) ---
$logo_secope = __DIR__ . '/../../assets/img/logoSecope.svg';
if (file_exists($logo_secope)) {
    // Usar ImageSVG para archivos vectoriales
    $pdf->ImageSVG($logo_secope, 150, 10, 50, 0, '', '', '', 0, false);
} else {
    // Fallback a PNG antiguo si existiera
    $logo_png = __DIR__ . '/../../img/logo_secope.png';
    if (file_exists($logo_png)) {
        $pdf->Image($logo_png, 150, 10, 50, 0, 'PNG');
    }
}

// --- ENCABEZADO DERECHA ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetY(30);
$pdf->Cell(0, 5, 'DIRECCIÓN DE CAMINOS', 0, 1, 'R');
$pdf->Cell(0, 5, 'Oficio No. ' . $num_oficio, 0, 1, 'R');
$pdf->Ln(10);

// --- DESTINATARIO ---
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, mb_strtoupper($destinatario_nombre), 0, 1, 'L');
$pdf->Cell(0, 5, mb_strtoupper($destinatario_cargo), 0, 1, 'L');

// Mostrar ATENCIÓN aquí si existe
if (!empty($atencion)) {
    $pdf->Ln(2);
    foreach ($atencion as $aten) {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'CON ATENCIÓN A: ' . mb_strtoupper($aten['nombre']), 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, mb_strtoupper($aten['cargo']), 0, 1, 'L');
    }
    $pdf->Ln(2);
}

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, 'P R E S E N T E .', 0, 1, 'L');
$pdf->Ln(10);

// --- CUERPO ---
$pdf->SetFont('helvetica', '', 11);
$texto_cuerpo = "POR MEDIO DE LA PRESENTE ME PERMITO SOLICITAR SUFICIENCIA PRESUPUESTAL PARA \"" . mb_strtoupper($proyecto_nombre) . "\", ";
$texto_cuerpo .= "POR UN IMPORTE DE \$" . number_format($fua['monto_total_solicitado'], 2) . " (" . mb_strtoupper($importe_letras) . "), ";
$texto_cuerpo .= "A EFECTO DE QUE ESTA DIRECCIÓN ESTÉ EN CONDICIONES DE INICIAR LOS PROCEDIMIENTOS LEGALES APLICABLES Y AUTORIZACIONES QUE RESULTEN NECESARIOS.";

$pdf->MultiCell(0, 7, $texto_cuerpo, 0, 'J');
$pdf->Ln(10);

$pdf->MultiCell(0, 7, "SIN MÁS POR EL MOMENTO ME DESPIDO DE USTED, QUEDANDO A SUS ÓRDENES PARA CUALQUIER DUDA O ACLARACIÓN.", 0, 'J');
$pdf->Ln(15);

// --- FIRMA ---
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, 'A T E N T A M E N T E', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Victoria de Durango, Dgo., a ' . $fecha_formateada, 0, 1, 'C');
$pdf->Ln(25);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, mb_strtoupper($remitente_nombre), 0, 1, 'C');
$pdf->Cell(0, 5, mb_strtoupper($remitente_cargo), 0, 1, 'C');

// --- SELLO / FIRMA DIGITAL SI EXISTE (CON VALIDACIÓN ESTRICTA) ---
if ($tiene_firma) {
    try {
        // 1. Limpieza y Decodificación
        $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $firma_base64);
        $imageData = base64_decode($base64Data, true); // true para strict mode

        if ($imageData === false) {
            throw new Exception("Base64 inválido");
        }

        // 2. Validación de Imagen Real
        $infoImagen = @getimagesizefromstring($imageData);
        if ($infoImagen === false) {
            throw new Exception("El archivo de firma no es una imagen válida o está corrupto.");
        }

        // 3. Inserción Segura
        // Usamos '@' para silenciar errores internos de TCPDF que a veces saltan como Warnings
        @$pdf->Image('@' . $imageData, 88, 145, 40, 0, 'PNG', '', '', false, 300, '', false, false, 0);

    } catch (Throwable $e) { // Captura Exception y Error (PHP 7+)
        // Loguear error internamente
        error_log("Fallo al procesar firma Usuario ID " . $user['id'] . ": " . $e->getMessage());

        // Mostrar aviso visual en el PDF en lugar de romperlo
        $pdf->SetXY(88, 145);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Cell(40, 10, 'FIRMA INVÁLIDA O CORRUPTA', 1, 0, 'C');
        $pdf->SetTextColor(0, 0, 0); // Restaurar color
    }
}

// --- C.C.P. / ATENCIÓN ---
$pdf->SetY(230);
$pdf->SetFont('helvetica', '', 7);

if (!empty($copias)) {
    // Mostrar personas configuradas con rol de COPIA
    foreach ($copias as $copia) {
        $pdf->Cell(0, 3, 'C.c.p. ' . mb_strtoupper($copia['nombre']) . '. ' . mb_strtoupper($copia['cargo']), 0, 1, 'L');
    }
    $pdf->Cell(0, 3, 'Archivo.', 0, 1, 'L');
} else {
    // Copias por defecto si no hay configuradas
    $pdf->Cell(0, 3, 'C.c.p. Arq. Ana Rosa Hernández Rentería. Secretaria de la SECOPE', 0, 1, 'L');
    $pdf->Cell(0, 3, 'Secretaría Técnica SECOPE', 0, 1, 'L');
    $pdf->Cell(0, 3, 'Dirección de Caminos Durango', 0, 1, 'L');
    $pdf->Cell(0, 3, 'Depto. de Conservación de Caminos', 0, 1, 'L');
    $pdf->Cell(0, 3, 'Archivo.', 0, 1, 'L');
}

// Generar
ob_end_clean();
$pdf->Output('Oficio_Suficiencia_' . $fua['id_fua'] . '.pdf', 'I');
exit;
