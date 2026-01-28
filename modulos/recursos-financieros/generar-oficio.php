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

if (!$id_fua) {
    die("ID de FUA no proporcionado.");
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
    LEFT JOIN documentos d ON d.tipo_documento_id = (SELECT id FROM cat_tipos_documento WHERE codigo = 'SUFI')
        AND JSON_EXTRACT(d.contenido_json, '$.id_fua') = f.id_fua
    WHERE f.id_fua = ?
");
$stmt->execute([$id_fua]);
$fua = $stmt->fetch();

if (!$fua) {
    die("No se encontró la información solicitada.");
}

// Obtener firmantes configurados en el flujo de firmas del documento
$firmantes = [];
if ($fua['documento_id']) {
    $stmt_firmantes = $pdo->prepare("
        SELECT 
            dff.orden,
            dff.rol_firmante,
            e.nombres,
            e.apellido_paterno,
            e.apellido_materno,
            e.cargo,
            u.usuario,
            dff.estatus
        FROM documento_flujo_firmas dff
        JOIN empleados e ON e.id = dff.firmante_id
        LEFT JOIN usuarios_sistema u ON u.id_empleado = e.id
        WHERE dff.documento_id = ?
        ORDER BY dff.orden ASC
    ");
    $stmt_firmantes->execute([$fua['documento_id']]);
    $firmantes = $stmt_firmantes->fetchAll();
}

// --- ACTUALIZAR FECHA DE AUTORIZACIÓN \"AL VUELO\" ---
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

// --- Obtener nombres de firmantes o usar valores por defecto ---
// El REMITENTE es el primer firmante (quien solicita/genera)
// El DESTINATARIO es típicamente el último firmante (quien autoriza)
$remitente_nombre = '';
$remitente_cargo = '';
$destinatario_nombre = '';
$destinatario_cargo = '';

if (count($firmantes) > 0) {
    // Primer firmante = REMITENTE (quien solicita)
    $primer_firmante = $firmantes[0];
    $remitente_nombre = trim($primer_firmante['nombres'] . ' ' .
        $primer_firmante['apellido_paterno'] . ' ' .
        ($primer_firmante['apellido_materno'] ?: ''));
    $remitente_cargo = $primer_firmante['cargo'] ?: $primer_firmante['rol_firmante'];

    // Último firmante = DESTINATARIO (quien autoriza)
    $ultimo_firmante = $firmantes[count($firmantes) - 1];
    $destinatario_nombre = trim($ultimo_firmante['nombres'] . ' ' .
        $ultimo_firmante['apellido_paterno'] . ' ' .
        ($ultimo_firmante['apellido_materno'] ?: ''));
    $destinatario_cargo = $ultimo_firmante['cargo'] ?: $ultimo_firmante['rol_firmante'];
}

// Permitir override por parámetros GET (para casos especiales)
$destinatario_nombre = $_GET['dest_nom'] ?? $destinatario_nombre ?: 'C.P. MARLEN SÁNCHEZ GARCÍA';
$destinatario_cargo = $_GET['dest_car'] ?? $destinatario_cargo ?: 'DIRECTORA DE ADMINISTRACIÓN';
$remitente_nombre = $_GET['rem_nom'] ?? $remitente_nombre ?: 'ING. CÉSAR OTHÓN RODRÍGUEZ GÓMEZ';
$remitente_cargo = $_GET['rem_car'] ?? $remitente_cargo ?: 'SUBSECRETARIO DE INFRAESTRUCTURA CARRETERA';

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

// --- LOGO ---
$logo_secope = __DIR__ . '/../../img/logo_secope.png';
if (file_exists($logo_secope)) {
    $pdf->Image($logo_secope, 150, 10, 50, 0, 'PNG');
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

// --- SELLO / FIRMA DIGITAL SI EXISTE ---
if ($tiene_firma) {
    // Extraer datos binarios del base64
    $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $firma_base64);
    $imageData = base64_decode($base64Data);

    // TCPDF acepta datos binarios con el prefijo '@'
    // Este es el método más confiable y compatible
    $pdf->Image('@' . $imageData, 88, 145, 40, 0, 'PNG', '', '', false, 300, '', false, false, 0);
}

// --- C.C.P. ---
$pdf->SetY(230);
$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(0, 3, 'C.c.p. Arq. Ana Rosa Hernández Rentería. Secretaria de la SECOPE', 0, 1, 'L');
$pdf->Cell(0, 3, 'Secretaría Técnica SECOPE', 0, 1, 'L');
$pdf->Cell(0, 3, 'Dirección de Caminos Durango', 0, 1, 'L');
$pdf->Cell(0, 3, 'Depto. de Conservación de Caminos', 0, 1, 'L');
$pdf->Cell(0, 3, 'Archivo.', 0, 1, 'L');

// Generar
ob_end_clean();
$pdf->Output('Oficio_Suficiencia_' . $fua['id_fua'] . '.pdf', 'I');
exit;
