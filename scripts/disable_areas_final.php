<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- DESACTIVANDO ÁREAS (ID 32) ---\n";
$stmt = $pdo->prepare("UPDATE modulos SET estado = 0 WHERE id = 32");
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo "✅ Módulo 32 (Áreas) desactivado correctamente.\n";
} else {
    echo "⚠️ No se realizaron cambios (tal vez ya estaba desactivado).\n";
}
