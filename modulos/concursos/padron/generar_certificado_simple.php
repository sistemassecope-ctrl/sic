<?php
// generar_certificado_simple.php - Versión simplificada para probar
ob_start(); // Iniciar buffer de salida

include("proteger.php");
include("conexion.php");

$rfc = $_SESSION['rfc'];

// Verificar si el usuario tiene un certificado válido
$stmt = $conexion->prepare("
    SELECT c.*, p.nombre, p.especialidad, p.calle, p.colonia, p.municipio, p.estado, p.cp 
    FROM certificados c 
    INNER JOIN persona_fisica p ON c.rfc = p.rfc 
    WHERE c.rfc = ? AND c.vigente = 1 AND c.papeleria_correcta = 1 AND c.fecha_vigencia >= CURDATE()
    ORDER BY c.fecha_emision DESC 
    LIMIT 1
");

$stmt->bind_param("s", $rfc);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    header("Location: dashboard.php?error=certificado_no_disponible");
    exit();
}

$certificado = $resultado->fetch_assoc();

// Limpiar buffer y configurar headers
ob_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="certificado_' . $certificado['numero_certificado'] . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Verificar si TCPDF está disponible
if (!file_exists('tcpdf/tcpdf.php')) {
    // Si no está TCPDF, mostrar mensaje de error
    echo '<html><body>';
    echo '<h1>Error: TCPDF no encontrado</h1>';
    echo '<p>Por favor, instala TCPDF siguiendo las instrucciones en instalar_tcpdf.md</p>';
    echo '<p>O temporalmente puedes usar el archivo de ejemplo:</p>';
    echo '<a href="certificado_ejemplo.pdf" target="_blank">Ver Certificado de Ejemplo</a>';
    echo '</body></html>';
    exit();
}

require_once('tcpdf/tcpdf.php');

// Crear PDF en orientación horizontal (landscape)
$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurar información del documento
$pdf->SetCreator('SECOPE - Sistema de Certificados');
$pdf->SetAuthor('SECRETARIA DE COMUNICACIONES Y OBRAS PUBLICAS');
$pdf->SetTitle('Certificado del Padrón de Contratistas');
$pdf->SetSubject('Certificado de Registro');

// Configurar márgenes para orientación horizontal
$pdf->SetMargins(20, 25, 20);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Configurar fuente
$pdf->SetFont('helvetica', '', 10);

// Agregar página
$pdf->AddPage();

// ===== ENCABEZADO =====
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'PADRON DE CONTRATISTAS DEL ESTADO DE DURANGO', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'EMITIDO POR LA SECRETARIA DE COMUNICACIONES Y OBRAS PUBLICAS', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(0, 4, 'SE OTORGA EL PRESENTE REGISTRO EN CUMPLIMIENTO A LO ESTABLECIDO POR LOS ARTICULOS 89 DE LA LEY DE OBRA PUBLICA Y SERVICIOS RELACIONADOS CON LA MISMA PARA EL ESTADO DE DURANGO Y SUS MUNICIPIOS Y 46 DE SU REGLAMENTO', 0, 'C');

$pdf->Ln(5);

// ===== DATOS DE LA EMPRESA =====
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 6, 'DATOS DE LA EMPRESA O PERSONA FISICA', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);

// Nombre o razón social
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 5, 'NOMBRE O RAZON SOCIAL:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, $certificado['nombre_razon_social'], 0, 1);

// Representante
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 5, 'REPRESENTANTE O APODERADO LEGAL:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, $certificado['representante_apoderado'], 0, 1);

// RFC
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(15, 5, 'R.F.C.:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(25, 5, $certificado['rfc'], 0, 0);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(20, 5, 'I.M.S.S.:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(25, 5, $certificado['imss'] ?? '', 0, 0);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(25, 5, 'INFONAVIT:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(25, 5, $certificado['infonavit'] ?? '', 0, 0);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(20, 5, 'CAMARA:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, $certificado['camara'] ?? '', 0, 1);

// Número de registro
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(35, 5, 'No. DE REGISTRO:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(20, 5, $certificado['numero_registro'], 0, 0);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(25, 5, 'INSCRIPCION:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(20, 5, $certificado['inscripcion'] ?? '', 0, 0);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(20, 5, 'REFRENDO:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(15, 5, $certificado['refrendo'] ? date('d/m/Y', strtotime($certificado['refrendo'])) : '', 0, 0);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(15, 5, 'VIGENCIA:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, date('d/m/Y', strtotime($certificado['fecha_vigencia'])), 0, 1);

$pdf->Ln(10);

// ===== QR =====
$pdf->SetFont('helvetica', '', 10);

// Generar URL para validación (configurada para producción)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$url_validacion = $protocol . '://padron.gusati.net/validar_certificado.php?hash=' . $certificado['hash_validacion'];

// Crear código QR (posicionado mejor para orientación horizontal)
$pdf->write2DBarcode($url_validacion, 'QRCODE,L', 200, $pdf->GetY(), 60, 60, array(
    'border' => true,
    'padding' => 2
));

$pdf->Ln(60);

// ===== PIE DE HOJA =====
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'LUGAR Y FECHA DE EXPEDICION ' . $certificado['lugar_expedicion'] . ' a ' . date('d', strtotime($certificado['fecha_expedicion'])) . ' de ' . date('F', strtotime($certificado['fecha_expedicion'])) . ' de ' . date('Y', strtotime($certificado['fecha_expedicion'])), 0, 1, 'C');

$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, 'ESTE DOCUMENTO NO SERA VALIDO SI PRESENTA TACHADURAS, ENMENDADURAS O RASPADURAS', 0, 1, 'C');

// Salida del PDF
$pdf->Output('certificado_' . $certificado['numero_certificado'] . '.pdf', 'I');
exit();
?>
