<?php
/**
 * Script: Generar PIN de Firma Digital para Administrador
 * Ubicación: generar-pin-admin.php
 * Uso: Ejecutar una vez para crear el PIN del administrador
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

try {
    $pdo = getConnection();

    // =====================================
    // CONFIGURACIÓN
    // =====================================
    $PIN_ADMIN = '1234'; // PIN por defecto (cambiar después del primer uso)
    $ADMIN_USER_ID = 1;  // ID del usuario administrador (ajustar si es diferente)

    // =====================================
    // OBTENER EMPLEADO DEL ADMINISTRADOR
    // =====================================
    $stmt = $pdo->prepare("
        SELECT u.id as usuario_id, u.usuario, u.id_empleado, e.nombres, e.apellido_paterno 
        FROM usuarios_sistema u 
        LEFT JOIN empleados e ON u.id_empleado = e.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$ADMIN_USER_ID]);
    $admin = $stmt->fetch();

    if (!$admin) {
        throw new Exception("No se encontró el usuario administrador con ID $ADMIN_USER_ID");
    }

    if (!$admin['id_empleado']) {
        throw new Exception("El usuario administrador no tiene un empleado vinculado. Primero debe existir en la tabla empleados.");
    }

    echo "========================================\n";
    echo "GENERADOR DE PIN DIGITAL\n";
    echo "========================================\n";
    echo "Usuario: {$admin['usuario']}\n";
    echo "Empleado: {$admin['nombres']} {$admin['apellido_paterno']}\n";
    echo "ID Empleado: {$admin['id_empleado']}\n";
    echo "========================================\n\n";

    // =====================================
    // VERIFICAR SI YA TIENE PIN
    // =====================================
    $stmtCheck = $pdo->prepare("SELECT id, estado FROM empleado_firmas WHERE empleado_id = ?");
    $stmtCheck->execute([$admin['id_empleado']]);
    $existingFirma = $stmtCheck->fetch();

    if ($existingFirma) {
        echo "⚠️  ADVERTENCIA: El empleado ya tiene un PIN registrado.\n";
        echo "Estado actual: " . ($existingFirma['estado'] ? 'ACTIVO' : 'INACTIVO') . "\n\n";
        echo "¿Desea actualizar el PIN? (Se generará un nuevo hash)\n";
        echo "Presione ENTER para continuar o Ctrl+C para cancelar...\n";

        if (php_sapi_name() === 'cli') {
            readline();
        }

        // Actualizar PIN existente
        $pinHash = password_hash($PIN_ADMIN, PASSWORD_BCRYPT);

        $stmtUpdate = $pdo->prepare("
            UPDATE empleado_firmas 
            SET pin_hash = ?, 
                ultima_modificacion_pin = NOW(),
                intentos_fallidos = 0,
                bloqueado_hasta = NULL,
                estado = 1,
                updated_at = NOW()
            WHERE empleado_id = ?
        ");
        $stmtUpdate->execute([$pinHash, $admin['id_empleado']]);

        // Registrar en log
        $stmtLog = $pdo->prepare("
            INSERT INTO firma_log (empleado_id, accion, ip_address, detalles)
            VALUES (?, 'PIN_CAMBIADO', ?, ?)
        ");
        $stmtLog->execute([
            $admin['id_empleado'],
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            json_encode(['modificado_via' => 'script_generar_pin', 'usuario_script' => $admin['usuario']])
        ]);

        echo "\n✅ PIN actualizado exitosamente!\n";

    } else {
        // Crear nuevo registro de firma
        echo "📝 Creando nuevo registro de firma digital...\n\n";

        // Generar hash del PIN
        $pinHash = password_hash($PIN_ADMIN, PASSWORD_BCRYPT);

        // Crear una imagen de firma placeholder (un rectángulo con el nombre)
        // En producción, esto debería ser capturado con canvas
        $firmaPlaceholder = generatePlaceholderSignature($admin['nombres'], $admin['apellido_paterno']);

        // Insertar registro
        $stmtInsert = $pdo->prepare("
            INSERT INTO empleado_firmas (
                empleado_id, 
                firma_imagen, 
                pin_hash, 
                fecha_captura, 
                capturado_por,
                ultima_modificacion_pin,
                estado
            ) VALUES (?, ?, ?, NOW(), ?, NOW(), 1)
        ");

        $stmtInsert->execute([
            $admin['id_empleado'],
            $firmaPlaceholder,
            $pinHash,
            $ADMIN_USER_ID // El admin se auto-registra
        ]);

        // Registrar en log
        $stmtLog = $pdo->prepare("
            INSERT INTO firma_log (empleado_id, accion, ip_address, detalles)
            VALUES (?, 'FIRMA_CAPTURADA', ?, ?)
        ");
        $stmtLog->execute([
            $admin['id_empleado'],
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            json_encode(['creado_via' => 'script_generar_pin', 'usuario_script' => $admin['usuario']])
        ]);

        echo "✅ Registro de firma digital creado exitosamente!\n";
    }

    // =====================================
    // RESUMEN FINAL
    // =====================================
    echo "\n========================================\n";
    echo "RESUMEN DE CONFIGURACIÓN\n";
    echo "========================================\n";
    echo "✓ Usuario: {$admin['usuario']}\n";
    echo "✓ Empleado ID: {$admin['id_empleado']}\n";
    echo "✓ PIN configurado: $PIN_ADMIN\n";
    echo "✓ Hash generado: " . substr($pinHash, 0, 20) . "...\n";
    echo "✓ Estado: ACTIVO\n";
    echo "========================================\n\n";

    echo "⚠️  IMPORTANTE:\n";
    echo "1. El PIN generado es: $PIN_ADMIN\n";
    echo "2. Guarde este PIN en un lugar seguro\n";
    echo "3. Puede cambiar el PIN desde el módulo de empleados\n";
    echo "4. La firma es un placeholder temporal\n";
    echo "5. Registre una firma real desde el sistema\n\n";

    echo "🎉 ¡Proceso completado!\n\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Detalles: " . $e->getTraceAsString() . "\n\n";
    exit(1);
}

/**
 * Genera una imagen placeholder de firma en base64
 */
function generatePlaceholderSignature($nombre, $apellido)
{
    // Crear una imagen simple con GD
    $width = 300;
    $height = 100;

    $image = imagecreate($width, $height);

    // Colores
    $bgColor = imagecolorallocate($image, 255, 255, 255); // Blanco
    $textColor = imagecolorallocate($image, 0, 51, 102);  // Azul oscuro
    $lineColor = imagecolorallocate($image, 200, 200, 200); // Gris claro

    // Texto
    $text = strtoupper(substr($nombre, 0, 1) . ". " . $apellido);

    // Línea decorativa
    imageline($image, 20, 70, 280, 70, $lineColor);

    // Texto centrado (aproximado)
    imagestring($image, 5, 50, 30, $text, $textColor);
    imagestring($image, 2, 50, 75, 'FIRMA DIGITAL', $lineColor);

    // Capturar la imagen en buffer
    ob_start();
    imagepng($image);
    $imageData = ob_get_clean();
    imagedestroy($image);

    // Convertir a base64
    return 'data:image/png;base64,' . base64_encode($imageData);
}
