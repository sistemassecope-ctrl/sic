<?php
/**
 * Configuración de Base de Datos
 * Maneja la conexión y funciones relacionadas con la base de datos
 */

// Configuración de la base de datos
// Configuración de la base de datos
//define('DB_HOST', 'localhost');
//define('DB_NAME', 'u394367385_secope');
//define('DB_USER', 'u394367385_secope');
//define('DB_PASS', 'Smettil@subito2s');

// Configuración de la base de datos


/**
 * Conectar a la base de datos
 * @return PDO Conexión a la base de datos
 */
function conectarDB()
{
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_spanish_ci"
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        return $pdo;
    } catch (PDOException $e) {
        // En producción, no mostrar el error detallado
        error_log("Error de conexión a BD: " . $e->getMessage());
        die("Error de conexión al sistema. Por favor intente más tarde.");
    }
}

// Inicializar conexión global
$pdo = conectarDB();

