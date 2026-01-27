<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = getConnection();
echo "<h2>Retana Info</h2>";
$stmt = $pdo->query("
    SELECT e.id as emp_id, e.nombres, e.apellido_paterno, e.rol_sistema, u.id as user_id, u.usuario, u.tipo 
    FROM empleados e 
    JOIN usuarios_sistema u ON e.id = u.id_empleado 
    WHERE u.id = 10
");
print_r($stmt->fetch());

echo "<h2>Permissions for Module 31 (Retana)</h2>";
$stmt = $pdo->query("
    SELECT p.clave 
    FROM usuario_modulo_permisos ump 
    JOIN permisos p ON ump.id_permiso = p.id
    WHERE ump.id_usuario = 10 AND ump.id_modulo = 31
");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
