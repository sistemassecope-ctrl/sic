<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- REORDENANDO MÓDULO ADMINISTRACIÓN ---\n";

// 1. Buscar módulo 'Administración'
$stmt = $pdo->query("SELECT id, nombre_modulo, orden FROM modulos WHERE nombre_modulo LIKE '%Admin%' OR nombre_modulo LIKE '%Usuarios%' LIMIT 1");
$admin = $stmt->fetch();

if (!$admin) {
    die("❌ No se encontró el módulo de Administración.\n");
}

echo "Módulo encontrado: {$admin['nombre_modulo']} (ID: {$admin['id']}) - Orden Actual: {$admin['orden']}\n";

// 2. Obtener el orden máximo actual
$stmtMax = $pdo->query("SELECT MAX(orden) FROM modulos");
$maxOrden = $stmtMax->fetchColumn();

echo "Orden máximo actual en el sistema: $maxOrden\n";

// 3. Asignar nuevo orden (Max + 10)
$nuevoOrden = $maxOrden + 10;

// Si ya es el último, no hacer nada
if ($admin['orden'] >= $maxOrden && $admin['orden'] == $maxOrden) {
    echo "✅ El módulo ya está al final.\n";
} else {
    $pdo->prepare("UPDATE modulos SET orden = ? WHERE id = ?")->execute([$nuevoOrden, $admin['id']]);
    echo "✅ Orden actualizado a: $nuevoOrden. Ahora debería aparecer al final.\n";
}
