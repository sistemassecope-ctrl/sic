<?php
/**
 * Script: Actualizar Firma Digital del Administrador
 * Ubicación: actualizar-firma-admin.php
 * Uso: Convierte la imagen generada a base64 y la guarda en la BD
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

try {
    $pdo = getConnection();

    echo "========================================\n";
    echo "ACTUALIZACIÓN DE FIRMA DIGITAL\n";
    echo "========================================\n\n";

    // Ruta a la imagen generada (ajustar según tu sistema)
    $imagePath = __DIR__ . '/img/firma_admin_digital.png';

    // Verificar si existe la imagen en la carpeta del proyecto
    if (!file_exists($imagePath)) {
        echo "⚠️  La imagen no existe en: $imagePath\n";
        echo "Buscando en rutas alternativas...\n\n";

        // Buscar en carpeta de usuario
        $userHome = getenv('USERPROFILE') ?: getenv('HOME');
        $alternativePaths = [
            $userHome . '/.gemini/antigravity/brain/*/firma_admin_digital*.png',
            __DIR__ . '/assets/img/firma_admin_digital.png',
            __DIR__ . '/uploads/firma_admin_digital.png'
        ];

        foreach ($alternativePaths as $pattern) {
            $files = glob($pattern);
            if ($files && count($files) > 0) {
                $imagePath = $files[0];
                echo "✓ Encontrada en: $imagePath\n\n";
                break;
            }
        }

        if (!file_exists($imagePath)) {
            echo "❌ No se encontró la imagen de firma.\n";
            echo "Por favor, copia la imagen generada a: " . __DIR__ . "/img/firma_admin_digital.png\n";
            echo "O proporciona la ruta correcta en el script.\n\n";

            // Generar firma alternativa con GD
            echo "Generando firma alternativa con GD...\n";
            $firmaBase64 = generarFirmaConGD();
        } else {
            // Leer y convertir a base64
            $imageData = file_get_contents($imagePath);
            $base64 = base64_encode($imageData);
            $firmaBase64 = 'data:image/png;base64,' . $base64;
        }
    } else {
        // Leer y convertir a base64
        $imageData = file_get_contents($imagePath);
        $base64 = base64_encode($imageData);
        $firmaBase64 = 'data:image/png;base64,' . $base64;
    }

    // Obtener empleado del admin
    $stmt = $pdo->prepare("SELECT id_empleado FROM usuarios_sistema WHERE id = 1");
    $stmt->execute();
    $empleadoId = $stmt->fetchColumn();

    if (!$empleadoId) {
        throw new Exception("No se encontró el empleado del administrador");
    }

    // Actualizar firma en la base de datos
    $stmt = $pdo->prepare("
        UPDATE empleado_firmas 
        SET firma_imagen = ?,
            updated_at = NOW()
        WHERE empleado_id = ?
    ");

    $stmt->execute([$firmaBase64, $empleadoId]);

    if ($stmt->rowCount() > 0) {
        echo "✅ Firma actualizada exitosamente!\n\n";

        // Registrar en log
        $stmtLog = $pdo->prepare("
            INSERT INTO firma_log (empleado_id, accion, ip_address, detalles)
            VALUES (?, 'FIRMA_CAPTURADA', ?, ?)
        ");
        $stmtLog->execute([
            $empleadoId,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            json_encode(['metodo' => 'actualizacion_automatica', 'tipo' => 'firma_generada'])
        ]);

        echo "========================================\n";
        echo "RESUMEN\n";
        echo "========================================\n";
        echo "✓ Usuario: admin\n";
        echo "✓ Empleado ID: $empleadoId\n";
        echo "✓ Firma actualizada\n";
        echo "✓ Tamaño: " . number_format(strlen($firmaBase64)) . " caracteres\n";
        echo "✓ Formato: PNG en base64\n";
        echo "========================================\n\n";

        echo "🎉 ¡Firma digital instalada correctamente!\n";
        echo "La firma aparecerá en todos los oficios PDF generados.\n\n";

    } else {
        echo "⚠️  No se actualizó ningún registro.\n";
        echo "Verifica que exista un registro en empleado_firmas para el empleado ID: $empleadoId\n";
    }

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}

/**
 * Genera una firma con GD como alternativa
 */
function generarFirmaConGD()
{
    $width = 400;
    $height = 150;

    $image = imagecreatetruecolor($width, $height);

    // Fondo transparente
    imagesavealpha($image, true);
    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $transparent);

    // Color de texto
    $textColor = imagecolorallocate($image, 0, 0, 0);

    // Texto de la firma (simulando cursiva)
    $text = "Administrador del Sistema";

    // Intentar usar una fuente TTF si existe
    $fontPath = __DIR__ . '/assets/fonts/signature.ttf';

    if (file_exists($fontPath)) {
        imagettftext($image, 24, -5, 50, 90, $textColor, $fontPath, $text);
    } else {
        // Usar fuente por defecto
        imagestring($image, 5, 50, 60, $text, $textColor);

        // Línea decorativa debajo
        imageline($image, 40, 100, 360, 95, $textColor);
    }

    // Capturar la imagen en buffer
    ob_start();
    imagepng($image);
    $imageData = ob_get_clean();
    imagedestroy($image);

    // Convertir a base64
    return 'data:image/png;base64,' . base64_encode($imageData);
}
