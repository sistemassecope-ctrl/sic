<?php
// conexion.php
require_once __DIR__ . '/../../../config/db.php';

$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conexion->connect_error) {
    die('Error de conexión a la base de datos: ' . $conexion->connect_error);
}

$conexion->set_charset(DB_CHARSET);
?>
