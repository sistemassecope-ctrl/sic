<?php
// generar_oficio.php - Genera el Oficio de Solicitud de Suficiencia Presupuestal
ob_start();

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/utils_moneda.php';
require_once __DIR__ . '/../../../includes/libs/tcpdf/tcpdf.php';

$db = (new Database())->getConnection();

$id_fua = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$id_fua) {
    die("ID de FUA no proporcionado.");
}

// Obtener datos del FUA y Proyecto
$stmt = $db->prepare("
    SELECT f.*, p.nombre_proyecto, p.ejercicio
    FROM fuas f
    LEFT JOIN proyectos_obra p ON f.id_proyecto = p.id_proyecto
    WHERE f.id_fua = ?
");
$stmt->execute([$id_fua]);
$fua = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fua) {
    die("No se encontró la información solicitada.");
}

// Datos para el oficio
$num_oficio = $fua['no_oficio_entrada'] ?: 'DC/____/2026';

// --- Parámetros "al vuelo" ---
$destinatario_nombre = isset($_GET['dest_nom']) ? $_GET['dest_nom'] : 'C.P. MARLEN SÁNCHEZ GARCÍA';
$destinatario_cargo = isset($_GET['dest_car']) ? $_GET['dest_car'] : 'DIRECTORA DE ADMINISTRACIÓN';
$remitente_nombre = isset($_GET['rem_nom']) ? $_GET['rem_nom'] : 'ING. CÉSAR OTHÓN RODRÍGUEZ GÓMEZ';
$remitente_cargo = isset($_GET['rem_car']) ? $_GET['rem_car'] : 'SUBSECRETARIO DE INFRAESTRUCTURA CARRETERA';

$fecha_hoy = date('d') . ' de ' . strftime('%B') . ' de ' . date('Y');
// Corrección de strftime si no está configurado el locale
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fecha_formateada = date('d') . ' de ' . $meses[date('n') - 1] . ' de ' . date('Y');

$importe_letras = NumeroALetras::convertir($fua['importe']);
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

// --- LOGOS (Simulando el encabezado de la imagen) ---
$logo_secope = __DIR__ . '/../../../img/logo_secope.png';
if (file_exists($logo_secope)) {
    // Logo en la esquina superior o pie
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
$texto_cuerpo .= "POR UN IMPORTE DE \$" . number_format($fua['importe'], 2) . " (" . mb_strtoupper($importe_letras) . "), ";
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
