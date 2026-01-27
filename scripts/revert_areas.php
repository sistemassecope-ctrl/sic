<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- REVIRTIENDO: REACTIVANDO ID 32 ---\n";
$stmt = $pdo->prepare("UPDATE modulos SET estado = 1 WHERE id = 32");
$stmt->execute();

echo "✅ Módulo 32 (Áreas) reactivado. El menú debería aparecer nuevamente.\n";
