<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = conectarDB();
    echo "--- ELIMINANDO FOREIGN KEY PROBLEMÁTICA ---\n";

    // Check if FK exists before dropping (MySQL specific)
    $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'documento_bitacora' AND CONSTRAINT_NAME = 'documento_bitacora_ibfk_2' AND TABLE_SCHEMA = DATABASE()");
    if ($stmt->rowCount() > 0) {
        $pdo->exec("ALTER TABLE documento_bitacora DROP FOREIGN KEY documento_bitacora_ibfk_2");
        echo "✅ Foreign Key eliminada.\n";
    } else {
        echo "ℹ️ Foreign Key no encontrada o ya eliminada.\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
