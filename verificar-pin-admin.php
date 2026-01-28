<?php
/**
 * Script: Verificar PIN del Administrador
 * Ubicación: verificar-pin-admin.php
 * Uso: Verifica que el PIN está correctamente registrado
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

try {
    $pdo = getConnection();

    echo "========================================\n";
    echo "VERIFICACIÓN DE FIRMA DIGITAL\n";
    echo "========================================\n\n";

    // Usuario admin
    $userId = 1;

    // Obtener datos del admin
    $stmt = $pdo->prepare("
        SELECT u.id, u.usuario, u.id_empleado, e.nombres, e.apellido_paterno,
               ef.id as firma_id, ef.pin_hash, ef.estado, ef.intentos_fallidos,
               ef.fecha_captura, ef.ultima_modificacion_pin
        FROM usuarios_sistema u
        LEFT JOIN empleados e ON u.id_empleado = e.id
        LEFT JOIN empleado_firmas ef ON e.id = ef.empleado_id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $admin = $stmt->fetch();

    if (!$admin) {
        throw new Exception("Usuario no encontrado");
    }

    // Mostrar información
    echo "👤 Usuario: {$admin['usuario']}\n";
    echo "📛 Nombre: {$admin['nombres']} {$admin['apellido_paterno']}\n";
    echo "🆔 Empleado ID: {$admin['id_empleado']}\n";
    echo "\n";

    if ($admin['firma_id']) {
        echo "✅ FIRMA REGISTRADA\n";
        echo "   ID Firma: {$admin['firma_id']}\n";
        echo "   Estado: " . ($admin['estado'] ? 'ACTIVO ✓' : 'INACTIVO ✗') . "\n";
        echo "   Hash PIN: " . substr($admin['pin_hash'], 0, 30) . "...\n";
        echo "   Intentos Fallidos: {$admin['intentos_fallidos']}\n";
        echo "   Capturada: {$admin['fecha_captura']}\n";
        echo "   Última modificación PIN: " . ($admin['ultima_modificacion_pin'] ?? 'N/A') . "\n";

        // Probar el PIN
        echo "\n🔐 PRUEBA DE PIN\n";
        $testPin = '1234';

        if (password_verify($testPin, $admin['pin_hash'])) {
            echo "   ✅ PIN '$testPin' es CORRECTO\n";
        } else {
            echo "   ❌ PIN '$testPin' es INCORRECTO\n";
        }

        // Ver logs recientes
        $stmtLog = $pdo->prepare("
            SELECT accion, created_at, ip_address 
            FROM firma_log 
            WHERE empleado_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmtLog->execute([$admin['id_empleado']]);
        $logs = $stmtLog->fetchAll();

        if ($logs) {
            echo "\n📋 ÚLTIMAS ACCIONES\n";
            foreach ($logs as $log) {
                echo "   • {$log['accion']} - {$log['created_at']} - IP: {$log['ip_address']}\n";
            }
        }

    } else {
        echo "❌ NO HAY FIRMA REGISTRADA\n";
        echo "   Ejecute: php generar-pin-admin.php\n";
    }

    echo "\n========================================\n";
    echo "Verificación completada.\n";
    echo "========================================\n\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
