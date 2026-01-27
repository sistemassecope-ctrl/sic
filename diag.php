<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

echo "<h1>Diagnostic</h1>";
echo "Session data:<pre>";
print_r($_SESSION);
echo "</pre>";
echo "Current User:<pre>";
print_r(getCurrentUser());
echo "</pre>";
echo "Is Admin: " . (isAdmin() ? 'YES' : 'NO') . "<br>";

$pdo = getConnection();
echo "<h2>Modules</h2>";
$stmt = $pdo->query("SELECT id, nombre_modulo, clave, ruta FROM modulos");
echo "<table border=1><tr><th>ID</th><th>Nombre</th><th>Clave</th><th>Ruta</th></tr>";
foreach ($stmt->fetchAll() as $row) {
    echo "<tr><td>{$row['id']}</td><td>{$row['nombre_modulo']}</td><td>{$row['clave']}</td><td>{$row['ruta']}</td></tr>";
}
echo "</table>";

echo "<h2>Permissions Table Sample</h2>";
$stmt = $pdo->query("SELECT * FROM permisos LIMIT 10");
print_r($stmt->fetchAll());
