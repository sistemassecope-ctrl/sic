<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();
$rows = $pdo->query("SELECT id, numero_economico, vehiculo_origen_id FROM vehiculos_bajas LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
