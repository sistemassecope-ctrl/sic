<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

// Get all modules
$stmt = $pdo->query("SELECT id, nombre_modulo, id_padre, estado FROM modulos ORDER BY orden");
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "ID   | NOMBRE                         | PADRE | ESTADO\n";
echo "-----|--------------------------------|-------|--------\n";
foreach ($modules as $m) {
    if ($m['id_padre'] === null) {
        $id = str_pad($m['id'], 4);
        $nome = str_pad($m['nombre_modulo'], 30);
        $estado = $m['estado'] ? 'ON' : 'OFF';
        echo "$id | $nome | NULL  | $estado\n";
        
        // Print children immediately
        foreach ($modules as $c) {
            if ($c['id_padre'] == $m['id']) {
                $cid = str_pad($c['id'], 4);
                $cnome = str_pad("  -> " . $c['nombre_modulo'], 30);
                $cestado = $c['estado'] ? 'ON' : 'OFF';
                echo "$cid | $cnome | {$m['id']}     | $cestado\n";
            }
        }
    }
}
