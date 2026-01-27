<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = getConnection();
echo "<h2>Permissions for Module 31 (all users)</h2>";
$stmt = $pdo->query("
    SELECT ump.id_usuario, u.usuario, p.clave 
    FROM usuario_modulo_permisos ump 
    JOIN usuarios_sistema u ON ump.id_usuario = u.id
    JOIN permisos p ON ump.id_permiso = p.id
    WHERE ump.id_modulo = 31
");
echo "<table border=1><tr><th>User ID</th><th>User</th><th>Permission</th></tr>";
foreach ($stmt->fetchAll() as $row) {
    echo "<tr><td>{$row['id_usuario']}</td><td>{$row['usuario']}</td><td>{$row['clave']}</td></tr>";
}
echo "</table>";

echo "<h2>Users List</h2>";
$stmt = $pdo->query("SELECT id, usuario, tipo FROM usuarios_sistema");
print_r($stmt->fetchAll());
