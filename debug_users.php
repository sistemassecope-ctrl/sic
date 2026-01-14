<?php
require 'config/db.php';
$db = (new Database())->getConnection();

// Estado actual
$stmt = $db->query("SELECT id_usuario, usuario, nombre_completo, id_rol, activo FROM usuarios");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total Usuarios: " . count($users) . "\n";
echo str_pad("ID", 5) . str_pad("User", 15) . str_pad("Rol", 5) . str_pad("Activo", 8) . "Nombre\n";
echo str_repeat("-", 60) . "\n";

foreach ($users as $u) {
    echo str_pad($u['id_usuario'], 5) .
        str_pad($u['usuario'], 15) .
        str_pad($u['id_rol'], 5) .
        str_pad($u['activo'], 8) .
        $u['nombre_completo'] . "\n";
}
?>