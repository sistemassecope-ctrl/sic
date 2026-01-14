<?php
require 'config/db.php';
$db = (new Database())->getConnection();

echo "<h1>Diagnostico de Usuarios</h1>";

// 1. Check Table Structure for 'activo'
$stmt = $db->query("DESCRIBE usuarios");
echo "<h2>Estructura Tabla Usuarios</h2><pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

// 2. Count Users by Role and Active status
$stmt = $db->query("SELECT id_rol, activo, COUNT(*) as total FROM usuarios GROUP BY id_rol, activo");
echo "<h2>Conteo por Rol y Estado</h2><pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

// 3. List first 10 users
$stmt = $db->query("SELECT id_usuario, usuario, nombre_completo, id_rol, activo FROM usuarios LIMIT 10");
echo "<h2>Muestra de Usuarios</h2><pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>