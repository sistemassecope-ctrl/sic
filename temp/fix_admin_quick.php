<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();
try {
    // Vincular admin al empleado 402 que ya existe
    $stmt = $pdo->prepare("UPDATE usuarios_sistema SET id_empleado = 402 WHERE usuario = 'admin'");
    $stmt->execute();
    echo "Usuario 'admin' vinculado al empleado 402 exitosamente.\n";
    
    // Verificar
    $stmt = $pdo->query("SELECT u.usuario, e.nombres FROM usuarios_sistema u JOIN empleados e ON u.id_empleado = e.id WHERE u.usuario = 'admin'");
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Ahora admin es: " . $res['nombres'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
