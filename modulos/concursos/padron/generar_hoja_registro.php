<?php
// generar_hoja_registro.php - Genera la Hoja de Registro en PDF (Diseño Oficial)
ob_clean();

include("proteger.php");
include("conexion.php");
require_once('tcpdf/tcpdf.php');

$rfc = $_SESSION['rfc'];
$tipo_persona = (strlen($rfc) === 12) ? 'moral' : 'fisica';
$tabla = ($tipo_persona === 'moral') ? 'persona_moral' : 'persona_fisica';

// Obtener datos
$stmt = $conexion->prepare("SELECT * FROM $tabla WHERE rfc = ?");
$stmt->bind_param("s", $rfc);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("Error: No se encontraron datos. Por favor, complete su perfil primero.");
}

$persona = $resultado->fetch_assoc();

// Buscar correo
$stmt_correo = $conexion->prepare("SELECT correo FROM usuarios_padron WHERE rfc = ?");
$stmt_correo->bind_param("s", $rfc);
$stmt_correo->execute();
$res_correo = $stmt_correo->get_result();
$correo = ($res_correo->num_rows > 0) ? $res_correo->fetch_assoc()['correo'] : '';

// --- CONFIGURACIÓN PDF ---
$pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
$pdf->SetCreator('SECOPE');
$pdf->SetAuthor('SECOPE');
$pdf->SetTitle('Hoja de Registro - ' . $rfc);
$pdf->SetMargins(15, 10, 15); // Márgenes un poco más pequeños arriba
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// --- PATH DEL LOGO ---
$logo_path = __DIR__ . '/../../../img/logo_secope.png';

// --- ENCABEZADO ---
if (file_exists($logo_path)) {
    $pdf->Image($logo_path, 15, 8, 45, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
}

// Títulos centrados
$pdf->SetY(20);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 5, 'SECRETARÍA DE COMUNICACIONES Y OBRAS PÚBLICAS', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 6, 'PADRÓN DE CONTRATISTAS', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'HOJA DE REGISTRO - ' . ($tipo_persona === 'moral' ? 'PERSONA MORAL' : 'PERSONA FÍSICA'), 0, 1, 'C');
$pdf->Ln(10);

// --- ESTILOS ---
$h_cell = 6;
$font_size = 9;
$font_size_lbl = 9;

function seccion($pdf, $titulo) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(0, 6, $titulo, 1, 1, 'L', 1);
    $pdf->Ln(1);
}

function campo($pdf, $label, $valor, $w = 0, $ln = 1, $border = 'B') {
    global $h_cell, $font_size, $font_size_lbl;
    $pdf->SetFont('helvetica', 'B', $font_size_lbl);
    
    if ($w == 0) {
        $pdf->Cell(45, $h_cell, $label . ':', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', $font_size);
        $pdf->Cell(0, $h_cell, $valor, $border, $ln, 'L');
    } else {
        $pdf->Cell($w * 0.4, $h_cell, $label . ':', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', $font_size);
        $pdf->Cell($w * 0.6, $h_cell, $valor, $border, $ln, 'L');
    }
}

// --- 1. DATOS GENERALES ---
seccion($pdf, '1. DATOS GENERALES');
campo($pdf, 'RAZÓN SOCIAL', ($tipo_persona === 'moral' ? $persona['nombre_empresa'] : $persona['nombre']));
campo($pdf, 'RFC', $persona['rfc']);

// Especialidad
$pdf->SetFont('helvetica', 'B', $font_size_lbl);
$pdf->Cell(45, $h_cell, 'ESPECIALIDAD:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', $font_size);
$pdf->MultiCell(0, $h_cell, $persona['especialidad'], 'B', 'L');

// --- 2. DOMICILIO FISCAL Y CONTACTO ---
$pdf->Ln(2);
seccion($pdf, '2. DOMICILIO FISCAL Y CONTACTO');
campo($pdf, 'CALLE Y NÚMERO', $persona['calle']);

$w_col = 90;
campo($pdf, 'COLONIA', $persona['colonia'], $w_col, 0);
campo($pdf, 'C.P.', $persona['cp'], $w_col, 1);
campo($pdf, 'MUNICIPIO', $persona['municipio'], $w_col, 0);
campo($pdf, 'ESTADO', $persona['estado'], $w_col, 1);
campo($pdf, 'TELÉFONO', $persona['telefono'], $w_col, 0);
campo($pdf, 'CELULAR', $persona['celular'], $w_col, 1);
campo($pdf, 'CORREO ELECTRÓNICO', $correo);

// --- 3. INFORMACIÓN LEGAL ---
$pdf->Ln(2);
seccion($pdf, '3. INFORMACIÓN LEGAL Y ADMINISTRATIVA');

if ($tipo_persona === 'fisica') {
    $doc = $persona['documento'] . (!empty($persona['numero_documento']) ? ' - ' . $persona['numero_documento'] : '');
    campo($pdf, 'IDENTIFICACIÓN', $doc);
    campo($pdf, 'REGISTRO IMSS', $persona['imss'], $w_col, 0);
    campo($pdf, 'REGISTRO INFONAVIT', $persona['infonavit'], $w_col, 1);
} else {
    // Datos Acta Constitutiva
    campo($pdf, 'ESCRITURA NO.', $persona['acta_escritura'], $w_col, 0);
    campo($pdf, 'FECHA ACTA', $persona['acta_fecha'], $w_col, 1);
    campo($pdf, 'NOTARIO ACTA', $persona['acta_notario']);
    
    if (!empty($persona['reformas_escritura'])) {
        campo($pdf, 'REFORMAS NO.', $persona['reformas_escritura'], $w_col, 0);
        campo($pdf, 'FECHA REFORMA', $persona['reformas_fecha'], $w_col, 1);
    }
    
    // Representante Legal
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 5, 'DATOS DEL REPRESENTANTE LEGAL', 0, 1, 'L');
    campo($pdf, 'NOMBRE REP.', $persona['rep_nombre']);
    campo($pdf, 'ESCRITURA PODER', $persona['rep_escritura'], $w_col, 0);
    campo($pdf, 'FECHA PODER', $persona['rep_fecha'], $w_col, 1);
    campo($pdf, 'NOTARIO PODER', $persona['rep_notario']);
}

// CMIC y Capital
campo($pdf, 'REGISTRO CMIC', ($tipo_persona === 'moral' ? $persona['reg_cmic'] : $persona['regCmic']), $w_col, 0);
campo($pdf, 'CAPITAL CONTABLE', '$ ' . number_format((float)$persona['capital'], 2), $w_col, 1);

// --- 4. OBJETO SOCIAL / DESCRIPCIÓN ---
$pdf->Ln(2);
seccion($pdf, ($tipo_persona === 'moral' ? '4. DESCRIPCIÓN DEL OBJETO SOCIAL' : '4. DESCRIPCIÓN DE ACTIVIDADES'));
$pdf->SetFont('helvetica', '', $font_size);
$pdf->MultiCell(0, 5, $persona['descripcion'], 'B', 'L');

// --- DECLARACIÓN ---
$pdf->Ln(6);
$pdf->SetFont('helvetica', '', 8);
$txt_declaracion = "MANIFIESTO BAJO PROTESTA DE DECIR VERDAD QUE LOS DATOS ASENTADOS EN LA PRESENTE SOLICITUD SON VERÍDICOS Y ACTUALIZADOS, ACEPTANDO QUE CUALQUIER FALSEDAD EN LOS MISMOS SERÁ CAUSA DE LA CANCELACIÓN DEL TRÁMITE.";
$pdf->MultiCell(0, 4, $txt_declaracion, 0, 'C');

// --- FIRMAS ---
$y_firmas = $pdf->GetY() + 10;
if ($y_firmas > 245) $pdf->AddPage();
$pdf->SetY($y_firmas);

$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
$fecha_actual = "Victoria de Durango, Dgo., a " . date('d') . " de " . $meses[date('n')-1] . " de " . date('Y');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, $fecha_actual, 0, 1, 'R');
$pdf->Ln(12);

$pdf->SetFont('helvetica', '', 9);
$nombre_firma = ($tipo_persona === 'moral' ? $persona['rep_nombre'] : $persona['nombre']);
$pdf->Cell(0, 5, $nombre_firma, 0, 1, 'C');
$pdf->Line(65, $pdf->GetY(), 150, $pdf->GetY());
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, 'NOMBRE Y FIRMA DEL SOLICITANTE', 0, 1, 'C');

// Generar
ob_end_clean();
$pdf->Output('Hoja_Registro_' . $rfc . '.pdf', 'D');
exit;
?>
