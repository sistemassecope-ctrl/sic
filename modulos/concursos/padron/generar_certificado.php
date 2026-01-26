<?php
// generar_certificado.php - Genera el certificado PDF del Padrón de Contratistas

// Limpiar cualquier salida previa
ob_clean();

include("proteger.php");
include("conexion.php");

// Incluir TCPDF (necesitarás descargar la librería)
require_once('tcpdf/tcpdf.php');

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
    // No tiene certificado válido
    header("Location: dashboard.php?error=certificado_no_disponible");
    exit();
}

$certificado = $resultado->fetch_assoc();

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

// Domicilio
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 5, 'DOMICILIO:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$domicilio = $certificado['calle'] . ' ' . $certificado['colonia'] . ', ' . $certificado['municipio'] . ', ' . $certificado['estado'] . '. CODIGO POSTAL: ' . $certificado['cp'];
if (!empty($certificado['telefono'])) {
    $domicilio .= ' TELEFONO: ' . $certificado['telefono'];
}
$pdf->MultiCell(0, 5, $domicilio, 0, 'L');

// Datos fiscales
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

// Capital contable y fecha de inicio
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 5, 'CAPITAL CONTABLE:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(30, 5, '$' . number_format($certificado['capital_contable'], 2), 0, 0);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 5, 'FECHA DE INICIO DE OPERACIONES:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, $certificado['fecha_inicio_operaciones'] ? date('d/m/Y', strtotime($certificado['fecha_inicio_operaciones'])) : '', 0, 1);

// Número de registro y fechas
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

$pdf->Ln(5);

// ===== TABLA DE ESPECIALIDADES =====
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 6, 'ESPECIALIDAD', 0, 1, 'C');

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(25, 4, 'OBRA CIVIL', 1, 0, 'C');
$pdf->Cell(35, 4, 'INDUSTRIAL Y ELECTROMECANICA', 1, 0, 'C');
$pdf->Cell(25, 4, 'INSTALACIONES', 1, 0, 'C');
$pdf->Cell(35, 4, 'PERFORACIONES PARA POZOS DE AGUA', 1, 0, 'C');
$pdf->Cell(25, 4, 'SERVICIOS PROFESIONALES', 1, 1, 'C');

$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell(25, 4, 'CONCEPTO', 1, 0, 'C');
$pdf->Cell(15, 4, 'CLAVE', 1, 0, 'C');
$pdf->Cell(20, 4, 'CONCEPTO', 1, 0, 'C');
$pdf->Cell(15, 4, 'CLAVE', 1, 0, 'C');
$pdf->Cell(25, 4, 'CONCEPTO', 1, 0, 'C');
$pdf->Cell(15, 4, 'CLAVE', 1, 0, 'C');
$pdf->Cell(35, 4, 'CONCEPTO', 1, 0, 'C');
$pdf->Cell(15, 4, 'CLAVE', 1, 0, 'C');
$pdf->Cell(25, 4, 'CONCEPTO', 1, 0, 'C');
$pdf->Cell(15, 4, 'CLAVE', 1, 1, 'C');

// Aquí puedes agregar las especialidades específicas del certificado
// Por ahora, dejamos algunas filas de ejemplo
$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(25, 4, 'ESTRUCTURAS METALICAS', 1, 0, 'L');
$pdf->Cell(15, 4, '1.2', 1, 0, 'C');
$pdf->Cell(20, 4, '', 1, 0, 'C');
$pdf->Cell(15, 4, '', 1, 0, 'C');
$pdf->Cell(25, 4, '', 1, 0, 'C');
$pdf->Cell(15, 4, '', 1, 0, 'C');
$pdf->Cell(35, 4, 'CIMENTACION', 1, 0, 'L');
$pdf->Cell(15, 4, '3.4.4', 1, 0, 'C');
$pdf->Cell(25, 4, '', 1, 0, 'C');
$pdf->Cell(15, 4, '', 1, 1, 'C');

$pdf->Cell(25, 4, 'SANITARIAS', 1, 0, 'L');
$pdf->Cell(15, 4, '3.1.1', 1, 0, 'C');
$pdf->Cell(20, 4, '', 1, 0, 'C');
$pdf->Cell(15, 4, '', 1, 0, 'C');
$pdf->Cell(25, 4, '', 1, 0, 'C');
$pdf->Cell(15, 4, '', 1, 0, 'C');
$pdf->Cell(35, 4, 'ACARREOS', 1, 0, 'L');
$pdf->Cell(15, 4, '3.3.17', 1, 0, 'C');
$pdf->Cell(25, 4, '', 1, 0, 'C');
$pdf->Cell(15, 4, '', 1, 1, 'C');

$pdf->Cell(25, 4, 'EDIFICACION', 1, 0, 'L');
$pdf->Cell(15, 4, '1.3', 1, 0, 'C');
$pdf->Cell(20, 4, '', 1, 0, 'C');
$pdf->Cell(15, 4, '', 1, 0, 'C');
$pdf->Cell(25, 4, '', 1, 0, 'C');
$pdf->Cell(15, 4, '', 1, 0, 'C');
$pdf->Cell(35, 4, '', 1, 0, 'C');
$pdf->Cell(15, 4, '', 1, 0, 'C');
$pdf->Cell(25, 4, '', 1, 0, 'C');
$pdf->Cell(15, 4, '', 1, 1, 'C');

$pdf->Ln(10);

// ===== FIRMA Y QR =====
$pdf->SetFont('helvetica', '', 10);

// Firma (lado izquierdo)
$pdf->Cell(90, 30, '', 1, 0, 'C');
$pdf->SetXY($pdf->GetX() - 90, $pdf->GetY() + 25);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(90, 5, 'ARQ. ANA ROSA HERNANDEZ RENTERIA', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(90, 5, 'SECRETARIA DE COMUNICACIONES Y OBRAS PUBLICAS', 0, 1, 'C');

// QR (lado derecho)
$pdf->SetXY($pdf->GetX() + 90, $pdf->GetY() - 30);
$pdf->Cell(90, 30, '', 1, 0, 'C');

// Generar URL para validación (configurada para producción)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$url_validacion = $protocol . '://padron.gusati.net/validar_certificado.php?hash=' . $certificado['hash_validacion'];

// Crear código QR (posicionado mejor para orientación horizontal)
$pdf->write2DBarcode($url_validacion, 'QRCODE,L', $pdf->GetX() - 80, $pdf->GetY() + 5, 60, 60, array(
    'border' => true,
    'padding' => 2
));

$pdf->Ln(35);

// ===== PIE DE HOJA =====
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'LUGAR Y FECHA DE EXPEDICION ' . $certificado['lugar_expedicion'] . ' a ' . date('d', strtotime($certificado['fecha_expedicion'])) . ' de ' . date('F', strtotime($certificado['fecha_expedicion'])) . ' de ' . date('Y', strtotime($certificado['fecha_expedicion'])), 0, 1, 'C');

$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, 'ESTE DOCUMENTO NO SERA VALIDO SI PRESENTA TACHADURAS, ENMENDADURAS O RASPADURAS', 0, 1, 'C');

// Limpiar cualquier salida adicional y generar PDF
ob_clean();
$pdf->Output('certificado_' . $certificado['numero_certificado'] . '.pdf', 'I');
exit();
?>