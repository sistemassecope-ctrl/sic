<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'No autenticado',
        'debug' => [
            'session_status' => session_status(),
            'session_id' => session_id(),
            'has_session' => isset($_SESSION)
        ]
    ]);
    exit;
}

// Asegurar que $pdo esté disponible
global $pdo;
if (!isset($pdo)) {
    $pdo = conectarDB();
}

// Verificar permisos para ver empleados
// Si es superadmin (nivel 1), permitir automáticamente
$user_nivel = $_SESSION['user_nivel'] ?? null;
$tiene_permisos = false;

if ($user_nivel == 1) {
    // Superadmin tiene acceso total
    $tiene_permisos = true;
} else {
    // Para otros niveles, verificar permisos en la base de datos
    $tiene_permisos = canAccessModule('empleados.php', 'leer');
}

if (!$tiene_permisos) {
    $user = getCurrentUser();
    
    // Debug adicional: verificar qué módulos existen
    try {
        if (isset($pdo)) {
            $stmt = $pdo->prepare("SELECT id, nombre, url, ruta FROM modulos WHERE (url = ? OR ruta = ?) AND activo = TRUE");
            $stmt->execute(['empleados.php', 'empleados.php']);
            $modulo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar permisos directamente
            $stmt2 = $pdo->prepare("SELECT pm.* FROM permisos_modulos pm WHERE pm.modulo_id = ? AND pm.nivel_usuario_id = ? AND pm.activo = TRUE");
            if ($modulo) {
                $stmt2->execute([$modulo['id'], $_SESSION['user_nivel']]);
                $permisos_directos = $stmt2->fetch(PDO::FETCH_ASSOC);
            } else {
                $permisos_directos = null;
            }
        } else {
            $modulo = null;
            $permisos_directos = null;
        }
    } catch (Exception $e) {
        $modulo = null;
        $permisos_directos = null;
    }
    
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'No tienes permisos para generar enlaces',
        'debug' => [
            'user_id' => $user['id'] ?? 'N/A',
            'user_nivel' => $_SESSION['user_nivel'] ?? 'N/A',
            'is_authenticated' => isAuthenticated(),
            'modulo_encontrado' => $modulo ? true : false,
            'modulo_id' => $modulo['id'] ?? 'N/A',
            'modulo_url' => $modulo['url'] ?? 'N/A',
            'modulo_ruta' => $modulo['ruta'] ?? 'N/A',
            'permisos_directos' => $permisos_directos ? 'Existen' : 'No existen',
            'columnas_permisos' => $permisos_directos ? array_keys($permisos_directos) : []
        ]
    ]);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);
$empleado_id = isset($input['empleado_id']) ? intval($input['empleado_id']) : 0;

if (!$empleado_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de empleado no válido']);
    exit;
}

// Obtener usuario actual
$user = getCurrentUser();
if (!$user || !$user['id']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener información del usuario']);
    exit;
}

// Generar token seguro
function generarTokenFicha($empleado_id, $usuario_id) {
    // Crear token con: empleado_id + usuario_id + timestamp + secret
    $secret = defined('SITE_URL') ? SITE_URL : 'secope_secret_key_2024';
    $timestamp = time();
    $data = $empleado_id . '|' . $usuario_id . '|' . $timestamp;
    $token = hash_hmac('sha256', $data, $secret);
    
    // Retornar token + timestamp (base64 para URL-safe)
    $token_data = base64_encode($token . '|' . $timestamp);
    return rtrim(strtr($token_data, '+/', '-_'), '='); // URL-safe base64
}

$token = generarTokenFicha($empleado_id, $user['id']);

echo json_encode([
    'success' => true,
    'token' => $token,
    'expires_in' => 7 * 24 * 60 * 60 // 7 días en segundos
]);

