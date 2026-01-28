<?php
/**
 * Script: Exportar y Verificar Firma del Admin
 * Ubicación: exportar-firma-admin.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

try {
    $pdo = getConnection();

    // Obtener firma de la BD
    $stmt = $pdo->prepare("
        SELECT ef.firma_imagen, u.usuario, e.nombres, e.apellido_paterno
        FROM empleado_firmas ef
        JOIN usuarios_sistema u ON u.id_empleado = ef.empleado_id
        JOIN empleados e ON e.id = ef.empleado_id
        WHERE u.id = 1
    ");
    $stmt->execute();
    $data = $stmt->fetch();

    if (!$data) {
        die("No se encontró la firma del administrador en la base de datos.");
    }

    echo "Usuario: " . $data['usuario'] . "\n";
    echo "Nombre: " . $data['nombres'] . " " . $data['apellido_paterno'] . "\n";
    echo "Tamaño de firma: " . strlen($data['firma_imagen']) . " caracteres\n";

    // Verificar formato
    if (strpos($data['firma_imagen'], 'data:image') === 0) {
        echo "✓ Formato correcto: Base64\n";

        // Extraer solo el base64
        $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $data['firma_imagen']);
        $imageData = base64_decode($base64Data);

        // Guardar en archivo temporal para verificar
        $outputPath = __DIR__ . '/img/firma_admin_export.png';
        file_put_contents($outputPath, $imageData);

        echo "✓ Firma exportada a: $outputPath\n";
        echo "✓ Tamaño del archivo: " . filesize($outputPath) . " bytes\n";

        // Crear HTML de prueba
        $htmlOutput = __DIR__ . '/test-firma-db.html';
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Firma desde BD</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; }
        .signature { border: 2px solid #ddd; padding: 20px; text-align: center; background: #f9f9f9; }
        img { max-width: 100%; background: white; padding: 10px; }
        .info { background: #e7f3ff; padding: 15px; margin: 20px 0; border-radius: 8px; }
    </style>
</head>
<body>
    <h1>🧪 Test de Firma desde Base de Datos</h1>
    
    <div class="info">
        <strong>Usuario:</strong> ' . $data['usuario'] . '<br>
        <strong>Nombre:</strong> ' . $data['nombres'] . ' ' . $data['apellido_paterno'] . '<br>
        <strong>Tamaño:</strong> ' . strlen($data['firma_imagen']) . ' caracteres
    </div>

    <h2>Método 1: Desde archivo exportado</h2>
    <div class="signature">
        <img src="img/firma_admin_export.png" alt="Firma exportada">
    </div>

    <h2>Método 2: Desde base64 directo</h2>
    <div class="signature">
        <img src="' . htmlspecialchars($data['firma_imagen']) . '" alt="Firma base64">
    </div>

    <h2>Método 3: Como está en la BD (raw)</h2>
    <div class="signature">
        <p>Primera parte del base64:</p>
        <pre style="font-size: 10px; overflow: scroll;">' . htmlspecialchars(substr($data['firma_imagen'], 0, 200)) . '...</pre>
    </div>
</body>
</html>';

        file_put_contents($htmlOutput, $html);
        echo "✓ HTML de prueba creado: $htmlOutput\n";
        echo "\nAbre en navegador: http://localhost/pao/test-firma-db.html\n";

    } else {
        echo "✗ Formato incorrecto. Debería empezar con 'data:image...'\n";
        echo "Primeros 100 caracteres:\n";
        echo substr($data['firma_imagen'], 0, 100) . "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
