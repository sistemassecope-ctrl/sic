<?php
// Incluir configuración de base de datos
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/rbac.php'; // Incluir lógica RBAC

// Configuración de sesión (debe ir antes de session_start())
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS

session_start();

// Inicializar conexión a la base de datos
$pdo = conectarDB();

/**
 * Autenticar usuario por identificador (email o nombre de usuario) y contraseña
 */
function authenticate($identificador, $password) {
    global $pdo;
    
    try {
        $identificador = trim($identificador);
        // Verificar si el usuario existe y está activo
        $stmt = $pdo->prepare(
            "SELECT u.*, n.nombre as nivel_nombre, e.nombres as empleado_nombre, e.foto as empleado_foto, e.email_institucional ".
            "FROM usuarios_sistema u " .
            "LEFT JOIN niveles_usuario n ON u.nivel_usuario_id = n.id " .
            "LEFT JOIN empleados e ON u.empleado_id = e.id " .
            "WHERE (u.username = ? OR e.numero_empleado = ?) AND u.activo = TRUE"
        );
        $stmt->execute([$identificador, $identificador]);
        $user = $stmt->fetch();
        
        if (!$user) {
            logFailedLogin($identificador, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            return false;
        }
        
        // Verificar si la cuenta está bloqueada
        if ($user['bloqueado_hasta'] && strtotime($user['bloqueado_hasta']) > time()) {
            return ['error' => 'Cuenta bloqueada temporalmente. Intenta más tarde.'];
        }
        
        // Verificar contraseña
        if (password_verify($password, $user['password_hash'])) {
            // Login exitoso - resetear intentos fallidos
            $stmt = $pdo->prepare("UPDATE usuarios_sistema SET intentos_fallidos = 0, ultimo_acceso = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Crear sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_username'] = $user['username'] ?? null;
            $_SESSION['user_nivel'] = $user['nivel_usuario_id'];
            $_SESSION['user_nivel_nombre'] = $user['nivel_nombre'];
            $_SESSION['user_empleado_id'] = $user['empleado_id'];
            $_SESSION['user_empleado_nombre'] = $user['empleado_nombre'];
            $_SESSION['user_empleado_foto'] = $user['empleado_foto'] ?? null;
            $_SESSION['user_email_institucional'] = $user['email_institucional'] ?? null;
            $_SESSION['login_time'] = time();
            $_SESSION['user_requiere_cambio_password'] = (int) ($user['requiere_cambio_password'] ?? 0);
            
            // Log de actividad
            logActivity('login', 'Login exitoso', $user['id']);
            
            return true;
        } else {
            // Login fallido - incrementar intentos
            $intentos = $user['intentos_fallidos'] + 1;
            $bloqueado_hasta = null;
            
            // Bloquear después de 5 intentos fallidos por 30 minutos
            if ($intentos >= 5) {
                $bloqueado_hasta = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            }
            
            $stmt = $pdo->prepare("UPDATE usuarios_sistema SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id = ?");
            $stmt->execute([$intentos, $bloqueado_hasta, $user['id']]);
            
            logFailedLogin($identificador, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            return ['error' => 'Credenciales incorrectas.'];
        }
    } catch (PDOException $e) {
        error_log("Error en autenticación: " . $e->getMessage());
        return ['error' => 'Error del sistema. Intenta más tarde.'];
    }
}

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && 
           !empty($_SESSION['user_id']) && 
           isset($_SESSION['user_nivel']) && 
           !empty($_SESSION['user_nivel']);
}

/**
 * Obtener información del usuario actual
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'username' => $_SESSION['user_username'] ?? null,
        'nivel_id' => $_SESSION['user_nivel'] ?? null,
        'nivel_nombre' => $_SESSION['user_nivel_nombre'] ?? null,
        'empleado_id' => $_SESSION['user_empleado_id'] ?? null,
        'empleado_nombre' => $_SESSION['user_empleado_nombre'] ?? null,
        'empleado_foto' => $_SESSION['user_empleado_foto'] ?? null,
        'email_institucional' => $_SESSION['user_email_institucional'] ?? null,
        'login_time' => $_SESSION['login_time'] ?? null,
        'requiere_cambio_password' => $_SESSION['user_requiere_cambio_password'] ?? 0,
    ];
}

/**
 * Verificar si el usuario tiene un nivel específico o superior
 */
function hasNivel($nivel_requerido) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $niveles_prioridad = [
        'Superadmin' => 1,
        'Información y Trámites Personales' => 2,
        'Apoyo de Áreas Técnicas' => 3,
        'Apoyo de RH' => 4,
        'Apoyo de Sistemas' => 5
    ];
    
    $nivel_actual = $_SESSION['user_nivel_nombre'] ?? null;
    if (!$nivel_actual) {
        return false;
    }
    $prioridad_actual = $niveles_prioridad[$nivel_actual] ?? 999;
    $prioridad_requerida = $niveles_prioridad[$nivel_requerido] ?? 999;
    
    return $prioridad_actual <= $prioridad_requerida;
}

/**
 * Verificar si el usuario puede acceder a un módulo específico
 */
function canAccessModule($modulo_url, $permiso = 'leer') {
    if (!isAuthenticated()) {
        return false;
    }
    
    global $pdo;
    
    try {
        $user_nivel = $_SESSION['user_nivel'] ?? null;
        if (!$user_nivel) {
            return false;
        }
        
        // Superadmin (nivel 1) tiene acceso total automáticamente
        if ($user_nivel == 1) {
            return true;
        }
        
        // Intentar primero con la estructura moderna (puede_ver, puede_crear, etc.)
        try {
            $stmt = $pdo->prepare("
                SELECT pm.puede_ver, pm.puede_crear, pm.puede_editar, pm.puede_eliminar
                FROM permisos_modulos pm
                JOIN modulos m ON pm.modulo_id = m.id
                WHERE (m.ruta = ? OR m.url = ?) AND pm.nivel_usuario_id = ? AND pm.activo = TRUE
            ");
            $stmt->execute([$modulo_url, $modulo_url, $user_nivel]);
            $permisos = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($permisos && isset($permisos['puede_ver'])) {
                // Estructura moderna: puede_ver, puede_crear, puede_editar, puede_eliminar
                switch ($permiso) {
                    case 'leer':
                    case 'ver':
                        return !empty($permisos['puede_ver']);
                    case 'escribir':
                    case 'crear':
                        return !empty($permisos['puede_crear']);
                    case 'editar':
                        return !empty($permisos['puede_editar']);
                    case 'eliminar':
                        return !empty($permisos['puede_eliminar']);
                    default:
                        return !empty($permisos['puede_ver']);
                }
            }
        } catch (PDOException $e) {
            // Si falla, intentar con la estructura antigua
        }
        
        // Intentar con la estructura antigua (puede_leer, puede_escribir, etc.)
        try {
            $stmt = $pdo->prepare("
                SELECT pm.puede_leer, pm.puede_escribir, pm.puede_eliminar, pm.puede_administrar
                FROM permisos_modulos pm
                JOIN modulos m ON pm.modulo_id = m.id
                WHERE (m.ruta = ? OR m.url = ?) AND pm.nivel_usuario_id = ? AND pm.activo = TRUE
            ");
            $stmt->execute([$modulo_url, $modulo_url, $user_nivel]);
            $permisos = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($permisos && isset($permisos['puede_leer'])) {
                // Estructura antigua: puede_leer, puede_escribir, puede_eliminar, puede_administrar
                switch ($permiso) {
                    case 'leer':
                    case 'ver':
                        return !empty($permisos['puede_leer']);
                    case 'escribir':
                    case 'crear':
                    case 'editar':
                        return !empty($permisos['puede_escribir']);
                    case 'eliminar':
                        return !empty($permisos['puede_eliminar']);
                    default:
                        return !empty($permisos['puede_leer']);
                }
            }
        } catch (PDOException $e) {
            // Si también falla, intentar obtener todas las columnas disponibles
        }
        
        // Último intento: obtener todas las columnas y verificar
        try {
            $stmt = $pdo->prepare("
                SELECT pm.*
                FROM permisos_modulos pm
                JOIN modulos m ON pm.modulo_id = m.id
                WHERE (m.ruta = ? OR m.url = ?) AND pm.nivel_usuario_id = ? AND pm.activo = TRUE
            ");
            $stmt->execute([$modulo_url, $modulo_url, $user_nivel]);
            $permisos = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$permisos) {
                return false;
            }
            
            // Determinar qué estructura tiene
            if (isset($permisos['puede_ver'])) {
                switch ($permiso) {
                    case 'leer':
                    case 'ver':
                        return !empty($permisos['puede_ver']);
                    case 'escribir':
                    case 'crear':
                        return !empty($permisos['puede_crear']);
                    case 'editar':
                        return !empty($permisos['puede_editar']);
                    case 'eliminar':
                        return !empty($permisos['puede_eliminar']);
                    default:
                        return !empty($permisos['puede_ver']);
                }
            } elseif (isset($permisos['puede_leer'])) {
                switch ($permiso) {
                    case 'leer':
                    case 'ver':
                        return !empty($permisos['puede_leer']);
                    case 'escribir':
                    case 'crear':
                    case 'editar':
                        return !empty($permisos['puede_escribir']);
                    case 'eliminar':
                        return !empty($permisos['puede_eliminar']);
                    default:
                        return !empty($permisos['puede_leer']);
                }
            }
        } catch (PDOException $e) {
            error_log("Error verificando permisos (último intento): " . $e->getMessage());
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Error verificando permisos: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener módulos disponibles para el usuario actual
 */
function getModulosDisponibles() {
    if (!isAuthenticated()) {
        return [];
    }
    
    global $pdo;
    
    try {
        $user_nivel = $_SESSION['user_nivel'] ?? null;
        if (!$user_nivel) {
            return [];
        }
        
        // Intento 1: consulta con columnas activo (entornos con campos 'activo')
        $sql1 = "
            SELECT m.*, pm.puede_ver, pm.puede_crear, pm.puede_editar, pm.puede_eliminar
            FROM modulos m
            JOIN permisos_modulos pm ON m.id = pm.modulo_id
            WHERE pm.nivel_usuario_id = ? AND m.activo = TRUE AND pm.activo = TRUE
            ORDER BY m.id
        ";
        try {
            $stmt = $pdo->prepare($sql1);
            $stmt->execute([$user_nivel]);
            $modulos = $stmt->fetchAll();
        } catch (PDOException $e1) {
            // Intento 2: fallback sin columnas 'activo' (producción sin esos campos)
            $sql2 = "
                SELECT m.*, pm.puede_ver, pm.puede_crear, pm.puede_editar, pm.puede_eliminar
                FROM modulos m
                JOIN permisos_modulos pm ON m.id = pm.modulo_id
                WHERE pm.nivel_usuario_id = ?
                ORDER BY m.id
            ";
            $stmt = $pdo->prepare($sql2);
            $stmt->execute([$user_nivel]);
            $modulos = $stmt->fetchAll();
        }
        
        return $modulos;
    } catch (PDOException $e) {
        error_log("Error obteniendo módulos (fallback): " . $e->getMessage());
        return [];
    }
}

/**
 * Cerrar sesión
 */
function logout() {
    if (isAuthenticated()) {
        logActivity('logout', 'Logout exitoso', $_SESSION['user_id']);
    }
    session_destroy();
    session_start();
}

/**
 * Requerir autenticación
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Requerir nivel específico
 */
function requireNivel($nivel) {
    requireAuth();
    if (!hasNivel($nivel)) {
        header('Location: ' . BASE_URL . 'modulos/rh/mi_perfil.php?error=unauthorized');
        exit;
    }
}

/**
 * Requerir acceso a módulo
 */
function requireModuleAccess($modulo_url, $permiso = 'leer') {
    requireAuth();
    if (!canAccessModule($modulo_url, $permiso)) {
        header('Location: ' . BASE_URL . 'modulos/rh/mi_perfil.php?error=unauthorized');
        exit;
    }
}

/**
 * Verificar si la sesión ha expirado (8 horas)
 */
function checkSessionExpiry() {
    if (isAuthenticated()) {
        $login_time = $_SESSION['login_time'];
        $current_time = time();
        $session_duration = 8 * 60 * 60; // 8 horas en segundos
        
        if (($current_time - $login_time) > $session_duration) {
            logout();
            return false;
        }
        
        // Renovar el tiempo de sesión
        $_SESSION['login_time'] = $current_time;
        return true;
    }
    
    return false;
}

/**
 * Generar token de recuperación de contraseña
 */
function generatePasswordResetToken($email) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM usuarios_sistema WHERE email = ? AND activo = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        $token = bin2hex(random_bytes(32));
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $pdo->prepare("UPDATE usuarios_sistema SET token_recuperacion = ?, token_expiracion = ? WHERE id = ?");
        $stmt->execute([$token, $expiracion, $user['id']]);
        
        return $token;
    } catch (PDOException $e) {
        error_log("Error generando token: " . $e->getMessage());
        return false;
    }
}

/**
 * Verificar token de recuperación
 */
function verifyPasswordResetToken($token) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, email 
            FROM usuarios_sistema 
            WHERE token_recuperacion = ? AND token_expiracion > NOW() AND activo = TRUE
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error verificando token: " . $e->getMessage());
        return false;
    }
}

/**
 * Cambiar contraseña con token
 */
function resetPasswordWithToken($token, $new_password) {
    global $pdo;
    
    try {
        $user = verifyPasswordResetToken($token);
        if (!$user) {
            return false;
        }
        
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            UPDATE usuarios_sistema 
            SET password_hash = ?, token_recuperacion = NULL, token_expiracion = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$password_hash, $user['id']]);
        
        logActivity('password_reset', 'Contraseña restablecida', $user['id']);
        return true;
    } catch (PDOException $e) {
        error_log("Error cambiando contraseña: " . $e->getMessage());
        return false;
    }
}

/**
 * Generar token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Limpiar datos de entrada
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Registrar intento de login fallido
 */
function logFailedLogin($identificador, $ip) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO log_actividades (usuario_id, accion, tabla_afectada, datos_anteriores, ip_address, user_agent) 
            VALUES (NULL, 'login_failed', 'auth', ?, ?, ?)
        ");
        $stmt->execute([json_encode(['identificador' => $identificador]), $ip, $_SERVER['HTTP_USER_AGENT'] ?? 'unknown']);
    } catch (PDOException $e) {
        error_log("Error loggeando intento fallido: " . $e->getMessage());
    }
}

/**
 * Log de actividad
 */
function logActivity($action, $details = '', $usuario_id = null) {
    global $pdo;
    
    if (!$usuario_id && isAuthenticated()) {
        $usuario_id = $_SESSION['user_id'];
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO log_actividades (usuario_id, accion, tabla_afectada, datos_anteriores, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $usuario_id,
            $action,
            $_SERVER['PHP_SELF'] ?? 'unknown',
            json_encode(['details' => $details]),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Error loggeando actividad: " . $e->getMessage());
    }
}

/**
 * Verificar si hay demasiados intentos fallidos
 */
function checkBruteForce($email, $ip) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as intentos 
            FROM log_actividades 
            WHERE accion = 'login_failed' AND datos_anteriores LIKE ? 
            AND fecha_creacion > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute(['%' . $email . '%']);
        $result = $stmt->fetch();
        
        return $result['intentos'] >= 10; // Bloquear después de 10 intentos en 1 hora
    } catch (PDOException $e) {
        error_log("Error verificando brute force: " . $e->getMessage());
        return false;
    }
}

