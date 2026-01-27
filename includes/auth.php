<?php
/**
 * Sistema de Autenticación
 * Manejo de sesiones, login, logout y verificación de permisos
 */

session_start();

require_once __DIR__ . '/../config/database.php';

/**
 * Verificar si el usuario está autenticado
 * @return bool
 */
function isAuthenticated(): bool
{
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Obtener el ID del usuario actual
 * @return int|null
 */
function getCurrentUserId(): ?int
{
    return $_SESSION['usuario_id'] ?? null;
}

/**
 * Obtener información completa del usuario actual
 * @return array|null
 */
function getCurrentUser(): ?array
{
    if (!isAuthenticated()) {
        return null;
    }

    static $user = null;

    if ($user === null) {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                u.id, u.usuario, u.tipo, u.estado,
                e.id as empleado_id, e.nombres as nombre, e.apellido_paterno, e.apellido_materno,
                e.email, e.rol_sistema, e.permisos_extra,
                a.id as area_id, a.nombre_area,
                p.id as puesto_id, p.nombre as nombre_puesto
            FROM usuarios_sistema u
            LEFT JOIN empleados e ON u.id_empleado = e.id
            LEFT JOIN areas a ON e.area_id = a.id
            LEFT JOIN puestos_trabajo p ON e.puesto_trabajo_id = p.id
            WHERE u.id = ?
        ");
        $stmt->execute([getCurrentUserId()]);
        $row = $stmt->fetch();
        $user = $row ?: null;
    }

    return $user;
}

/**
 * Realizar login
 * @param string $usuario
 * @param string $password
 * @return array ['success' => bool, 'message' => string]
 */
function login(string $usuario, string $password): array
{
    $pdo = getConnection();

    // Buscar usuario y su rol de empleado
    $stmt = $pdo->prepare("
        SELECT u.id, u.contrasena, u.tipo, u.estado, u.intentos_fallidos, u.cambiar_password, e.rol_sistema
        FROM usuarios_sistema u
        LEFT JOIN empleados e ON u.id_empleado = e.id
        WHERE u.usuario = ?
    ");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'Usuario no encontrado'];
    }

    if ($user['estado'] != 1) {
        return ['success' => false, 'message' => 'Usuario inactivo'];
    }

    if ($user['intentos_fallidos'] >= 5) {
        return ['success' => false, 'message' => 'Usuario bloqueado por múltiples intentos fallidos'];
    }

    // Verificar contraseña
    if (!password_verify($password, $user['contrasena'])) {
        // Incrementar intentos fallidos
        $stmt = $pdo->prepare("UPDATE usuarios_sistema SET intentos_fallidos = intentos_fallidos + 1 WHERE id = ?");
        $stmt->execute([$user['id']]);
        return ['success' => false, 'message' => 'Contraseña incorrecta'];
    }

    // Login exitoso
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['usuario_tipo'] = $user['tipo'];
    $_SESSION['rol_sistema'] = $user['rol_sistema'] ?? 'usuario';
    $_SESSION['cambiar_password'] = $user['cambiar_password'] == 1;

    // Resetear intentos fallidos y actualizar último acceso
    $stmt = $pdo->prepare("UPDATE usuarios_sistema SET intentos_fallidos = 0, ultimo_acceso = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    return ['success' => true, 'message' => 'Login exitoso'];
}

/**
 * Cerrar sesión
 */
function logout(): void
{
    session_unset();
    session_destroy();
}

/**
 * Verificar si el usuario es administrador
 * @return bool
 */
function isAdmin(): bool
{
    if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] == 1) {
        return true;
    }
    if (isset($_SESSION['rol_sistema']) && in_array($_SESSION['rol_sistema'], ['admin_global', 'SUPERADMIN'])) {
        return true;
    }
    return false;
}

/**
 * Obtener los permisos del usuario actual para un módulo específico
 * @param int $moduloId
 * @return array Lista de claves de permisos
 */
function getUserPermissions(int $moduloId): array
{
    if (!isAuthenticated()) {
        return [];
    }

    // Los administradores tienen todos los permisos
    if (isAdmin()) {
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT clave FROM permisos");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT p.clave
        FROM usuario_modulo_permisos ump
        INNER JOIN permisos p ON ump.id_permiso = p.id
        WHERE ump.id_usuario = ? AND ump.id_modulo = ?
    ");
    $stmt->execute([getCurrentUserId(), $moduloId]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Verificar si el usuario tiene un permiso específico en un módulo
 * @param string $permisoClave
 * @param int $moduloId
 * @return bool
 */
function hasPermission(string $permisoClave, int $moduloId): bool
{
    $permisos = getUserPermissions($moduloId);
    return in_array($permisoClave, $permisos);
}

/**
 * Obtener las áreas accesibles para el usuario actual
 * @return array Lista de IDs de áreas
 */
function getUserAreas(): array
{
    if (!isAuthenticated()) {
        return [];
    }

    // Los administradores ven todas las áreas
    if (isAdmin()) {
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT id FROM areas WHERE estado = 1");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT id_area
        FROM usuario_areas
        WHERE id_usuario = ?
    ");
    $stmt->execute([getCurrentUserId()]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Verificar si el usuario tiene acceso a un área específica
 * @param int $areaId
 * @return bool
 */
function hasAccessToArea(int $areaId): bool
{
    $areas = getUserAreas();
    return in_array($areaId, $areas);
}

/**
 * Generar cláusula SQL para filtrar por áreas del usuario
 * @param string $columnName Nombre de la columna de área (ej: 'id_area', 'e.id_area')
 * @return string
 */
function getAreaFilterSQL(string $columnName = 'id_area'): string
{
    // Si es administrador, tiene acceso total a todas las áreas, incluyendo registros sin área (NULL)
    if (isAdmin()) {
        return "1=1";
    }

    $areas = getUserAreas();

    if (empty($areas)) {
        return "$columnName IN (0)"; // No tiene acceso a ninguna área
    }

    return "$columnName IN (" . implode(',', array_map('intval', $areas)) . ")";
}

/**
 * Requerir autenticación - redirige si no está autenticado
 */
function requireAuth(): void
{
    if (!isAuthenticated()) {
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';
        header('Location: ' . $basePath . '/login.php');
        exit;
    }

    // Verificar si se requiere cambio de contraseña
    if (isset($_SESSION['cambiar_password']) && $_SESSION['cambiar_password'] === true) {
        $currentScript = basename($_SERVER['PHP_SELF']);
        // Permitir solo la página de cambio de contraseña, logout y endpoints de API/AJAX si fuera necesario que no rompan la sesión
        if ($currentScript !== 'cambiar_password.php' && $currentScript !== 'logout.php') {
            $basePath = defined('BASE_PATH') ? BASE_PATH : '';
            // Ajustar ruta dependiendo de donde esté cambiar_password.php vs root
            // Asumiendo que requireAuth se usa en contextos donde la ruta relativa funciona o usando BASE_PATH
            header('Location: ' . $basePath . '/modulos/usuarios/cambiar_password.php');
            exit;
        }
    }
}

/**
 * Requerir permiso específico - lanza excepción si no tiene permiso
 * @param string $permisoClave
 * @param int $moduloId
 */
function requirePermission(string $permisoClave, int $moduloId): void
{
    requireAuth();

    if (!hasPermission($permisoClave, $moduloId)) {
        http_response_code(403);
        die('No tienes permiso para realizar esta acción');
    }
}

/**
 * Registrar actividad del usuario
 * @param string $accion
 * @param string $detalles
 */
function logActivity(string $accion, string $detalles = ''): void
{
    if (!isAuthenticated()) {
        return;
    }

    // Por ahora solo logueamos a archivo, después se puede guardar en BD
    $logEntry = sprintf(
        "[%s] Usuario ID %d: %s - %s\n",
        date('Y-m-d H:i:s'),
        getCurrentUserId(),
        $accion,
        $detalles
    );

    error_log($logEntry, 3, __DIR__ . '/../logs/activity.log');
}
