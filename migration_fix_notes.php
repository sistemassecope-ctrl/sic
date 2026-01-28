<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = conectarDB();
    echo "--- APLICANDO PARCHE DE BASE DE DATOS ---\n";

    // Verificar si la columna existe
    $stmt = $pdo->query("SHOW COLUMNS FROM usuario_bandeja_documentos LIKE 'notas_internas'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE usuario_bandeja_documentos ADD COLUMN notas_internas TEXT NULL AFTER tipo_accion_requerida");
        echo "✅ Columna 'notas_internas' agregada exitosamente.\n";
    } else {
        echo "ℹ️ La columna 'notas_internas' ya existe.\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
