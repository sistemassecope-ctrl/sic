<?php
// Configuración para módulo RH Standalone
// Ajustar estos valores según tu entorno

// Configuración de Base de Datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'spm_rh');
define('DB_CHARSET', 'utf8mb4');

// Definir BASE_URL dinámicamente
if (!defined('BASE_URL')) {
    $root_fs = str_replace('\\', '/', __DIR__);
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $web_path = str_replace($doc_root, '', $root_fs);
    $web_path = rtrim($web_path, '/');
    define('BASE_URL', $web_path . '/');
}

// Función de conexión a base de datos
function conectarDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

// Funciones de autenticación básicas (ajustar según tu sistema)
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
