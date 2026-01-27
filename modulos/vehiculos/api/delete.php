<?php
// 1. Configuracion de errores critica (Primeras lineas)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// 2. Definir tipo de contenido tempranamente
header('Content-Type: application/json; charset=utf-8');

// 3. Buffer de salida para capturar cualquier texto "basura" o advertencias previas
ob_start();

// 4. Manejador de apagado (Shutdown Handler) para capturar Errores Fatales que escapan al try-catch
register_shutdown_function(function() {
    $error = error_get_last();
    // Si hubo un error fatal (E_ERROR, E_PARSE, etc.)
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        // Limpiar buffer
        if (ob_get_length()) ob_clean();
        
        // Devolver JSON de error
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Fatal Error: ' . $error['message'] . ' in ' . basename($error['file']) . ':' . $error['line']
        ]);
    }
});

try {
    // 5. Inclusiones dentro del try para capturar errores de sintaxis o falta de archivos en includes
    require_once __DIR__ . '/../../../includes/auth.php';
    require_once __DIR__ . '/../../../config/database.php';
    
    // 6. Verificar Autenticación
    // Nota: requireAuth podría redirigir. Para API, idealmente debería devolver 401, pero manejamos la redirección si ocurre.
    if (!function_exists('requireAuth')) {
        throw new Exception("Función requireAuth no encontrada. Verifique includes/auth.php");
    }
    requireAuth();

    // 7. Verificar Método (Soportamos POST y DELETE)
    $method = $_SERVER['REQUEST_METHOD'];
    // No bloqueamos estrictamente para evitar problemas con clientes que envian POST, pero idealmente es DELETE/POST

    // 8. DB Connection
    $pdo = getConnection();

    // 9. Verificar Permisos
    // Usamos el ID de módulo 'Vehículos'
    $stmtMod = $pdo->prepare("SELECT id FROM modulos WHERE nombre_modulo = ?");
    $stmtMod->execute(['Vehículos']);
    $modulo = $stmtMod->fetch();
    $MODULO_ID = $modulo ? $modulo['id'] : 0;
    
    requirePermission('editar', $MODULO_ID);

    // 10. Obtener Datos
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON inválido recibido: " . substr($input, 0, 50));
    }

    $id = $data['id'] ?? 0;

    if (!$id) {
        throw new Exception("ID de vehículo no proporcionado o inválido");
    }

    // 11. Ejecutar Eliminación
    // Borrar notas
    $stmtNotasDel = $pdo->prepare("DELETE FROM vehiculos_notas WHERE vehiculo_id = ?");
    $stmtNotasDel->execute([$id]);

    // Borrar vehículo
    $stmt = $pdo->prepare("DELETE FROM vehiculos WHERE id = ?");
    $stmt->execute([$id]);
    
    // 12. Respuesta Exitosa
    if (ob_get_length()) ob_clean(); // Limpiar cualquier warning previo
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    // 13. Captura de Excepciones y Errores (PHP 7+)
    if (ob_get_length()) ob_clean();
    
    // Loguear error real en el servidor
    error_log("API Delete Error: " . $e->getMessage());
    
    // Devolver JSON al cliente
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
