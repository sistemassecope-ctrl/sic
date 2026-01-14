<?php
// conexion.php
// Configura aquí tus datos de conexión
$host = 'localhost';
$usuario = 'u394367385_padron_secope';
$contrasena = 'Smettilasubito2p';
$base_de_datos = 'u394367385_padron';

$conexion = new mysqli($host, $usuario, $contrasena, $base_de_datos);
if ($conexion->connect_error) {
    die('Error de conexión a la base de datos: ' . $conexion->connect_error);
}
?> 