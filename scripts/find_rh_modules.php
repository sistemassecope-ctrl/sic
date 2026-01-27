<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- BUSCANDO RECURSOS HUMANOS ---\n";

// 1. Find 'Recursos Humanos'
$stmt = $pdo->query("SELECT * FROM modulos WHERE nombre_modulo LIKE '%Humanos%'");
$rh = $stmt->fetch();

if (!$rh) {
    die("❌ No se encontró el módulo Recursos Humanos.\n");
}

echo "Padre: {$rh['nombre_modulo']} (ID: {$rh['id']})\n";

// 2. Find children
$stmtChildren = $pdo->prepare("SELECT * FROM modulos WHERE id_padre = ?");
$stmtChildren->execute([$rh['id']]);
$children = $stmtChildren->fetchAll();

echo "--- SUBMÓDULOS ---\n";
foreach ($children as $child) {
    echo "ID: {$child['id']} - Nombre: {$child['nombre_modulo']} - Estado: {$child['estado']}\n";
}
