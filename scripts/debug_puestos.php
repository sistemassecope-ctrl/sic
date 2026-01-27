<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- BUSCANDO PUESTOS ---\n";
$stmt = $pdo->query("SELECT * FROM modulos WHERE nombre_modulo LIKE '%Puestos%'");
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($modules as $m) {
    echo "ID: {$m['id']} | Nombre: {$m['nombre_modulo']} | Ruta: {$m['ruta']} | Padre: {$m['id_padre']} | Estado: {$m['estado']}\n";
    
    // Check if file exists
    $path = __DIR__ . '/../' . $m['ruta'];
    if (file_exists($path)) {
        echo "✅ Archivo existe: $path\n";
    } else {
        echo "❌ Archivo NO existe: $path\n";
    }
}
