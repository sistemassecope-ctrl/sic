<?php
/**
 * Script de prueba: Verificar endpoint de firma
 * Ubicación: test-endpoint-firma.php
 */

echo "========================================\n";
echo "PRUEBA DE ENDPOINT DE FIRMA\n";
echo "========================================\n\n";

// Probar que los archivos existen
$files = [
    'includes/auth.php',
    'includes/helpers.php',
    'includes/services/SignatureFlowService.php',
    'includes/services/DocumentoService.php',
    'modulos/recursos-financieros/procesar-firma.php'
];

echo "1. Verificando archivos...\n";
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "   ✓ $file\n";
    } else {
        echo "   ✗ $file (NO EXISTE)\n";
    }
}

echo "\n2. Probando sintaxis PHP...\n";
$output = [];
$return = 0;
exec('php -l modulos/recursos-financieros/procesar-firma.php 2>&1', $output, $return);
if ($return === 0) {
    echo "   ✓ Sintaxis correcta\n";
} else {
    echo "   ✗ Error de sintaxis:\n";
    foreach ($output as $line) {
        echo "      $line\n";
    }
}

echo "\n3. Probando carga del archivo...\n";
try {
    ob_start();
    $_SERVER['REQUEST_METHOD'] = 'GET'; // Simular GET para evitar procesamiento
    include __DIR__ . '/modulos/recursos-financieros/procesar-firma.php';
    $content = ob_get_clean();
    echo "   ✓ Archivo cargado sin errores fatales\n";
    if ($content) {
        echo "   Respuesta: " . substr($content, 0, 100) . "\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "   ✗ Error al cargar: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "Prueba completada.\n";
echo "========================================\n\n";
