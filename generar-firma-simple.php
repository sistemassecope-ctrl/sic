<?php
/**
 * Generar Firma Simple Compatible con TCPDF
 * Ubicación: generar-firma-simple.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

try {
    $pdo = getConnection();

    echo "========================================\n";
    echo "GENERAR FIRMA SIMPLE (Compatible TCPDF)\n";
    echo "========================================\n\n";

    // Crear firma con GD
    $width = 300;
    $height = 100;

    $image = imagecreatetruecolor($width, $height);

    // Fondo blanco
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $white);

    // Color de texto - azul oscuro
    $textColor = imagecolorallocate($image, 0, 51, 102);

    // Texto cursivo simulado
    $nombre = "Administrador del Sistema";

    // Intentar usar fuente TTF si existe, sino usar built-in
    $fontFile = __DIR__ . '/includes/libs/tcpdf/fonts/times.php';

    // Usar imagestring (fuente built-in de GD)
    $fontSize = 5; // 1-5, siendo 5 el más grande

    // Calcular posición centrada
    $textWidth = imagefontwidth($fontSize) * strlen($nombre);
    $x = ($width - $textWidth) / 2;
    $y = ($height - imagefontheight($fontSize)) / 2;

    // Dibujar texto
    imagestring($image, $fontSize, $x, $y, $nombre, $textColor);

    // Línea decorativa debajo (simula firma manuscrita)
    $lineY = $y + imagefontheight($fontSize) + 5;
    imageline($image, $x, $lineY, $x + $textWidth, $lineY, $textColor);

    // Pequeña rúbrica al inicio
    imageline($image, $x - 20, $lineY - 10, $x - 10, $lineY, $textColor);
    imageline($image, $x - 10, $lineY, $x - 5, $lineY - 15, $textColor);

    // Guardar cómo imagen PNG
    ob_start();
    imagepng($image, null, 9); // máxima compresión
    $imageData = ob_get_clean();
    imagedestroy($image);

    // Convertir a base64
    $firmaBase64 = 'data:image/png;base64,' . base64_encode($imageData);

    echo "✓ Firma generada con GD\n";
    echo "  Dimensiones: {$width}x{$height}px\n";
    echo "  Tamaño: " . strlen($firmaBase64) . " caracteres\n\n";

    // Guardar en archivo para verificación
    file_put_contents(__DIR__ . '/img/firma_simple.png', $imageData);
    echo "✓ Guardada en: img/firma_simple.png\n\n";

    // Actualizar en BD
    $stmt = $pdo->prepare("SELECT id_empleado FROM usuarios_sistema WHERE id = 1");
    $stmt->execute();
    $empleadoId = $stmt->fetchColumn();

    if (!$empleadoId) {
        throw new Exception("No se encontró el empleado del administrador");
    }

    $stmt = $pdo->prepare("
        UPDATE empleado_firmas 
        SET firma_imagen = ?,
            updated_at = NOW()
        WHERE empleado_id = ?
    ");

    $stmt->execute([$firmaBase64, $empleadoId]);

    if ($stmt->rowCount() > 0) {
        echo "✓ Firma actualizada en la base de datos\n\n";

        // Registrar en log
        $stmtLog = $pdo->prepare("
            INSERT INTO firma_log (empleado_id, accion, ip_address, detalles)
            VALUES (?, 'FIRMA_CAPTURADA', ?, ?)
        ");
        $stmtLog->execute([
            $empleadoId,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            json_encode(['metodo' => 'firma_simple_gd', 'compatible' => 'tcpdf'])
        ]);

        echo "========================================\n";
        echo "FIRMA SIMPLE INSTALADA ✓\n";
        echo "========================================\n";
        echo "Esta firma es 100% compatible con TCPDF\n";
        echo "y debería aparecer en todos los PDFs.\n\n";

        echo "Prueba ahora:\n";
        echo "1. http://localhost/pao/test-firma-pdf.php\n";
        echo "2. http://localhost/pao/test-firma-db.html\n";
        echo "3. Generar un oficio desde bandeja-gestion.php\n\n";

    } else {
        echo "⚠️  No se actualizó ningún registro\n";
    }

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
