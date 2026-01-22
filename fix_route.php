<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    
    // Check current path
    $stmt = $pdo->prepare("SELECT id, nombre_modulo, ruta FROM modulos WHERE nombre_modulo = ?");
    $stmt->execute(['Vehículos']);
    $mod = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Current Config for Vehículos:\n";
    print_r($mod);
    
    // Fix it
    if ($mod) {
        $sql = "UPDATE modulos SET ruta = ? WHERE id = ?";
        $pdo->prepare($sql)->execute(['/modulos/vehiculos/index.php', $mod['id']]);
        echo "Updated ruta to: /modulos/vehiculos/index.php\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
