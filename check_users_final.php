<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = getConnection();
$stmt = $pdo->query("SELECT u.usuario, e.rol_sistema, u.tipo FROM usuarios_sistema u LEFT JOIN empleados e ON u.id_empleado = e.id");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "User: {$row['usuario']} | Rol: {$row['rol_sistema']} | Tipo: {$row['tipo']}\n";
}
