<?php
/**
 * Configuración de Base de Datos
 * Sistema PAO v2 - Permisos Atómicos para Organizaciones
 */

// Ruta base del proyecto (cambiar si el proyecto está en otra ubicación)
define('BASE_PATH', '/pao_v2');

// Configuración de conexión
define('DB_HOST', '192.168.100.114');
define('DB_NAME', 'pao_v2');
define('DB_USER', 'u394367385_secope');
define('DB_PASS', 'Smettil@subito2');
define('DB_CHARSET', 'utf8mb4');

/**
 * Obtener conexión PDO a la base de datos
 * @return PDO
 */
function getConnection(): PDO
{
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Error de conexión a BD: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }
    }
    
    return $pdo;
}

/**
 * Alias para compatibilidad
 * @return PDO
 */
function conectarDB(): PDO
{
    return getConnection();
}
