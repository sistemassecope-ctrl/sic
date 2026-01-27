<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- VERIFICANDO ID 50 ---\n";
$stmt = $pdo->prepare("SELECT * FROM modulos WHERE id = 50");
$stmt->execute();
$modulo = $stmt->fetch();

if ($modulo) {
    echo "Módulo 50: {$modulo['nombre_modulo']}\n";
    
    if (stripos($modulo['nombre_modulo'], 'rea') !== false) {
        echo "✅ Es el módulo de Áreas. Desactivando...\n";
        $pdo->prepare("UPDATE modulos SET estado = 0 WHERE id = 50")->execute();
        echo "✅ Módulo desactivado.\n";
    } else {
        echo "⚠️ NO parece ser el módulo de Áreas. Buscando otro...\n";
        $stmtSearch = $pdo->query("SELECT * FROM modulos WHERE nombre_modulo LIKE '%Area%'");
        $others = $stmtSearch->fetchAll();
        foreach($others as $o) {
            echo "Encontrado: {$o['id']} - {$o['nombre_modulo']} (Padre: {$o['id_padre']})\n";
            if ($o['id_padre'] == 4 || $o['id_padre'] == 20) { // Assuming RH is 4, check parent logic
                 echo "-> Candidato a desactivar.\n";
            }
        }
    }
} else {
    echo "No existe ID 50.\n";
}
