<?php
// conexion.php
require_once __DIR__ . '/../../../config/database.php';

// Definir BASE_URL para compatibilidad con módulos legacy
if (!defined('BASE_URL')) {
    define('BASE_URL', defined('BASE_PATH') ? BASE_PATH : '/pao');
}

$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conexion->connect_error) {
    die('Error de conexión a la base de datos: ' . $conexion->connect_error);
}

$conexion->set_charset(DB_CHARSET);
?>