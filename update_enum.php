<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = conectarDB();
    echo "--- ACTUALIZANDO ENUM TIPO FIRMA ---\n";

    // Check if column exists and update
    // We cannot easily check the current enum values with a simple query that works across all mysql versions without parsing info schema logic which might be complex,
    // so we just run the alter. It's idempotent-ish (if it's already there, it might just warn or do nothing, or re-apply).

    $sql = "ALTER TABLE cat_tipos_documento MODIFY COLUMN tipo_firma_default ENUM('pin', 'fiel', 'ambas', 'autografa') DEFAULT 'pin'";
    $pdo->exec($sql);
    echo "✅ Enum actualizado correctamente a: ('pin', 'fiel', 'ambas', 'autografa')\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
