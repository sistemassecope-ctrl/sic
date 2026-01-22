<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = getConnection();
$stmt = $pdo->query("SELECT u.usuario, u.tipo, e.rol_sistema FROM usuarios_sistema u LEFT JOIN empleados e ON u.id_empleado = e.id");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "User: {$row['usuario']} | Tipo: {$row['tipo']} | Rol: {$row['rol_sistema']}\n";
}
