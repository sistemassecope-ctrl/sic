<?php
// generar_certificado_simple.php - Versión para administración (COPIADO A PADRON)
ob_start(); // Iniciar buffer de salida

include("conexion.php");

// Lógica híbrida de autenticación (Admin vs Contratista)
if (session_status() === PHP_SESSION_NONE) session_start();

$is_admin = isset($_SESSION['user_id']) && in_array($_SESSION['user_nivel'] ?? 0, [1, 3]);
$is_contractor = isset($_SESSION['rfc']);

if (!$is_admin && !$is_contractor) {
    header("Location: index.php"); 
    exit("Error: Acceso no autorizado.");
}

$certificado = null;

// Determinar modo basado en parámetros y sesión
if ($is_admin && isset($_GET['id']) && !empty($_GET['id'])) {
    // MODO ADMIN: Tiene permisos y proporcionó un ID específicamente
    $cert_id = intval($_GET['id']);
    $stmt = $conexion->prepare("SELECT * FROM certificados WHERE id = ?");
    $stmt->bind_param("i", $cert_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        $certificado = $resultado->fetch_assoc();
    }
} elseif ($is_contractor) {
    // MODO CONTRATISTA: No hay ID (o es irrelevante si soy contratista), uso mi RFC
    $rfc = $_SESSION['rfc'];
    $stmt = $conexion->prepare("
        SELECT * FROM certificados 
        WHERE rfc = ? AND vigente = 1 AND fecha_vigencia >= CURDATE()
        ORDER BY fecha_emision DESC 
        LIMIT 1
    ");
    $stmt->bind_param("s", $rfc);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        $certificado = $resultado->fetch_assoc();
    } else {
        die("Error: No se encontró un certificado vigente para este usuario.");
    }
} else {
    // Fallback: Es admin pero NO mandó ID, y NO es contratista
    die("Error: ID de certificado no proporcionado (Modo Admin).");
}

if (!$certificado) {
    die("Error: Certificado no encontrado.");
}


// Determinar tipo de persona para otros propósitos si es necesario
$rfc_titular = $certificado['rfc'];
$tipo_persona = (strlen($rfc_titular) === 12) ? 'moral' : 'fisica';

// Limpiar buffer y configurar headers
ob_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="certificado_' . $certificado['numero_certificado'] . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Verificar si TCPDF está disponible (Ruta local en padron)
$tcpdf_path = 'tcpdf/tcpdf.php'; 
if (!file_exists($tcpdf_path)) {
    echo '<html><body>';
    echo '<h1>Error: TCPDF no encontrado</h1>';
    echo '<p>El motor de PDF no se encuentra en la ruta: ' . htmlspecialchars($tcpdf_path) . '</p>';
    echo '</body></html>';
    exit();
}

require_once($tcpdf_path);

// Crear PDF en orientación horizontal (landscape)
$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurar información del documento
$pdf->SetCreator('SECOPE - Sistema de Certificados');
$pdf->SetAuthor('SECRETARIA DE COMUNICACIONES Y OBRAS PUBLICAS');
$pdf->SetTitle('Certificado del Padrón de Contratistas - ' . $certificado['numero_certificado']);
$pdf->SetSubject('Certificado de Registro');

// Eliminar cabeceras y pies de página predeterminados
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Configurar márgenes
$pdf->SetMargins(20, 20, 20);
$pdf->SetAutoPageBreak(TRUE, 15);

// Agregar página
$pdf->AddPage();

// Logo SECOPE (Arriba Izquierda)
// Ajustar ruta relativa: estamos en modulos/concursos/padron/, la imagen está en ../../../img/
$logo_path = '../../../img/logo_secope.png';
if (file_exists($logo_path)) {
    $pdf->Image($logo_path, 20, 10, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
}


// Dibujar un borde elegante si se desea, o simplemente el contenido
// ===== ENCABEZADO =====
// Mover el cursor un poco abajo para asegurar que no se encime con logo si es muy alto, 
// o mantener margen 20.
// 'Cell(0,...)' centra en el ancho de página (menos margenes).
$pdf->SetY(20); 

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'SECRETARÍA DE COMUNICACIONES Y OBRAS PÚBLICAS', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'PADRÓN DE CONTRATISTAS DEL ESTADO DE DURANGO', 0, 1, 'C');

$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(0, 4, 'SE OTORGA EL PRESENTE REGISTRO EN CUMPLIMIENTO A LO ESTABLECIDO POR LOS ARTÍCULOS 89 DE LA LEY DE OBRA PÚBLICA Y SERVICIOS RELACIONADOS CON LA MISMA PARA EL ESTADO DE DURANGO Y SUS MUNICIPIOS Y 46 DE SU REGLAMENTO', 0, 'C');

$pdf->Ln(8);

// ===== SECCIÓN DE DATOS =====
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'CERTIFICADO DE REGISTRO', 1, 1, 'C', true);

$pdf->Ln(5);

// Nombre o razón social - DISEÑO APILADO PARA EVITAR DESBORDES
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'NOMBRE O RAZÓN SOCIAL:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(0, 6, strtoupper($certificado['nombre_razon_social']), 0, 'L');

$pdf->Ln(3);

// Representante
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'REPRESENTANTE LEGAL:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(0, 6, strtoupper($certificado['representante_apoderado']), 0, 'L');

$pdf->Ln(6);

// Fila 1: RFC, IMSS, INFONAVIT - MEJOR DISTRIBUCIÓN
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(15, 6, 'R.F.C.:', 0, 0);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(50, 6, $certificado['rfc'], 0, 0);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(20, 6, 'I.M.S.S.:', 0, 0);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(50, 6, $certificado['imss'] ?? 'N/A', 0, 0);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(25, 6, 'INFONAVIT:', 0, 0);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 6, $certificado['infonavit'] ?? 'N/A', 0, 1);

$pdf->Ln(2);

// Fila 2: No. Registro, Cámara, Capital
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(35, 6, 'No. DE REGISTRO:', 0, 0);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(30, 6, $certificado['numero_registro'], 0, 0);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(25, 6, 'CÁMARA:', 0, 0);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(45, 6, $certificado['camara'] ?? 'S/REG', 0, 0);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 6, 'CAPITAL CONTABLE:', 0, 0);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 6, '$' . number_format($certificado['capital_contable'], 2), 0, 1);

$pdf->Ln(8);

// Fechas y Vigencia
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 6, 'FECHA DE EMISIÓN:', 0, 0);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(45, 6, date('d/m/Y', strtotime($certificado['fecha_emision'])), 0, 0);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 6, 'VIGENCIA HASTA:', 0, 0);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(200, 0, 0);
$pdf->Cell(0, 6, date('d/m/Y', strtotime($certificado['fecha_vigencia'])), 0, 1);
$pdf->SetTextColor(0, 0, 0);

$pdf->Ln(12);

// ===== QR Y VALIDACIÓN =====
// Generar URL para validación
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$url_validacion = $protocol . '://' . $host . '/pao/validar_certificado.php?hash=' . $certificado['hash_validacion'];

// Código QR
$style = array(
    'border' => 1,
    'vpadding' => 'auto',
    'hpadding' => 'auto',
    'fgcolor' => array(0,0,0),
    'bgcolor' => false,
    'module_width' => 1,
    'module_height' => 1
);
$pdf->write2DBarcode($url_validacion, 'QRCODE,L', 220, $pdf->GetY() - 25, 50, 50, $style, 'N');

$pdf->Ln(15);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->MultiCell(150, 4, 'Para validar la autenticidad de este documento, escanee el código QR o visite: ' . $url_validacion, 0, 'L');

// ===== PIE DE PÁGINA / FIRMA =====
// Posicionar texto de fecha encima
$pdf->SetY(-40); // 40mm desde el fondo
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'DURANGO, DGO., A ' . date('d', strtotime($certificado['fecha_expedicion'])) . ' DE ' . strtoupper(date('F', strtotime($certificado['fecha_expedicion']))) . ' DE ' . date('Y', strtotime($certificado['fecha_expedicion'])), 0, 1, 'C');

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, 'ESTE DOCUMENTO NO SERÁ VÁLIDO SI PRESENTA TACHADURAS, ENMENDADURAS O RASPADURAS', 0, 1, 'C');

// Salida del PDF
$pdf->Output('Certificado_' . $certificado['rfc'] . '.pdf', 'I');
ob_end_flush();
exit();
?>
