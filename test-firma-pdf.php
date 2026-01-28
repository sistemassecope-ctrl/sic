<?php
/**
 * Script de Debugging: Test de Firma en PDF
 * Ubicación: test-firma-pdf.php
 */

ob_start();

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/libs/tcpdf/tcpdf.php';

requireAuth();

$pdo = getConnection();
$user = getCurrentUser();

echo "=== DEBUG: Test de Firma en PDF ===\n\n";

// 1. Obtener firma de la BD
echo "1. Obteniendo firma de la BD...\n";
$stmt_firma = $pdo->prepare("
    SELECT ef.firma_imagen 
    FROM empleado_firmas ef
    JOIN usuarios_sistema u ON u.id_empleado = ef.empleado_id
    WHERE u.id = ?
");
$stmt_firma->execute([$user['id']]);
$firma_base64 = $stmt_firma->fetchColumn();

if ($firma_base64) {
    echo "   ✓ Firma encontrada\n";
    echo "   Tamaño: " . strlen($firma_base64) . " caracteres\n";
    echo "   Primeros 50: " . substr($firma_base64, 0, 50) . "...\n";
    $tiene_firma = true;
} else {
    echo "   ✗ NO se encontró firma\n";
    $tiene_firma = false;
}

echo "\n2. Creando PDF de prueba...\n";

// Crear PDF
$pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
$pdf->SetCreator('Test Firma');
$pdf->SetAuthor('Sistema PAO');
$pdf->SetTitle('Test de Firma Digital');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(25, 30, 25);
$pdf->AddPage();

// Texto de prueba
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'DOCUMENTO DE PRUEBA - FIRMA DIGITAL', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(0, 7, 'Este es un documento de prueba para verificar que la firma digital se renderiza correctamente en el PDF.', 0, 'J');
$pdf->Ln(20);

// Información de debugging
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, 'INFORMACIÓN DE DEBUG:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Usuario: ' . $user['usuario'], 0, 1, 'L');
$pdf->Cell(0, 5, 'ID: ' . $user['id'], 0, 1, 'L');
$pdf->Cell(0, 5, 'Tiene firma: ' . ($tiene_firma ? 'SÍ' : 'NO'), 0, 1, 'L');
if ($tiene_firma) {
    $pdf->Cell(0, 5, 'Tamaño firma: ' . strlen($firma_base64) . ' caracteres', 0, 1, 'L');
}
$pdf->Ln(20);

// Sección de firma
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 5, 'A T E N T A M E N T E', 0, 1, 'C');
$pdf->Ln(5);

echo "3. Intentando renderizar firma...\n";

if ($tiene_firma) {
    echo "   Método 1: Usando base64 directo\n";

    try {
        // Método 1: Base64 directo (com vino de la BD)
        $y_pos = $pdf->GetY();
        $pdf->Image($firma_base64, 80, $y_pos, 50, 0, '', '', '', false, 300, '', false, false, 0, false, false, false);
        echo "   ✓ Método 1 ejecutado (posición Y: $y_pos)\n";

        $pdf->Ln(30);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Método 1: Base64 directo', 0, 1, 'C');
        $pdf->Ln(10);

    } catch (Exception $e) {
        echo "   ✗ Error en Método 1: " . $e->getMessage() . "\n";
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Cell(0, 5, 'ERROR: ' . $e->getMessage(), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(10);
    }

    // Método 2: Guardar temporal y usar ruta
    echo "   Método 2: Usando archivo temporal\n";

    try {
        $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $firma_base64);
        $imageData = base64_decode($base64Data);
        $tempFile = sys_get_temp_dir() . '/firma_test_' . time() . '.png';
        file_put_contents($tempFile, $imageData);

        $y_pos = $pdf->GetY();
        $pdf->Image($tempFile, 80, $y_pos, 50, 0, 'PNG', '', '', false, 300, '', false, false, 0);
        echo "   ✓ Método 2 ejecutado (posición Y: $y_pos)\n";

        unlink($tempFile); // Limpiar

        $pdf->Ln(30);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Método 2: Archivo temporal', 0, 1, 'C');
        $pdf->Ln(10);

    } catch (Exception $e) {
        echo "   ✗ Error en Método 2: " . $e->getMessage() . "\n";
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Cell(0, 5, 'ERROR: ' . $e->getMessage(), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(10);
    }

    // Método 3: Usando @data
    echo "   Método 3: Usando @data binario\n";

    try {
        $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $firma_base64);
        $imageData = base64_decode($base64Data);

        $y_pos = $pdf->GetY();
        $pdf->Image('@' . $imageData, 80, $y_pos, 50, 0, 'PNG', '', '', false, 300, '', false, false, 0);
        echo "   ✓ Método 3 ejecutado (posición Y: $y_pos)\n";

        $pdf->Ln(30);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Método 3: @data binario', 0, 1, 'C');

    } catch (Exception $e) {
        echo "   ✗ Error en Método 3: " . $e->getMessage() . "\n";
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Cell(0, 5, 'ERROR: ' . $e->getMessage(), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }

} else {
    echo "   ✗ No hay firma para renderizar\n";
    $pdf->SetTextColor(255, 0, 0);
    $pdf->Cell(0, 5, 'NO HAY FIRMA REGISTRADA', 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
}

echo "\n4. Generando PDF...\n";

// Generar PDF
ob_end_clean();
$pdf->Output('Test_Firma_' . time() . '.pdf', 'I');
echo "   ✓ PDF generado\n";

exit;
