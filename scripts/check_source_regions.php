<?php
require_once __DIR__ . '/../config/database.php';

// Configuración de Origen (SIC)
define('SIC_HOST', '192.168.100.14');
define('SIC_DB', 'sic');
define('SIC_USER', 'sic_test');
define('SIC_PASS', 'sic_test.2025');

$result = [];

try {
    $dsnSic = "mysql:host=" . SIC_HOST . ";dbname=" . SIC_DB . ";charset=utf8mb4";
    $pdoSrc = new PDO($dsnSic, SIC_USER, SIC_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Check distinct regions
    $regions = $pdoSrc->query("SELECT region, COUNT(*) as count FROM vehiculos GROUP BY region")->fetchAll();
    $result['regions'] = $regions;
    
    // Check columns
    $cols = $pdoSrc->query("DESCRIBE vehiculos")->fetchAll(PDO::FETCH_COLUMN);
    $result['columns'] = $cols;
    
    // Check headers 10 rows to see if region is populated implicitly or via another field?
    $rows = $pdoSrc->query("SELECT id, numero_economico, region, secretaria_subsecretaria, direccion_departamento FROM vehiculos LIMIT 10")->fetchAll();
    $result['sample_data'] = $rows;

} catch (Exception $e) {
    $result['error'] = $e->getMessage();
}

file_put_contents('regions_dump.json', json_encode($result, JSON_PRETTY_PRINT));
echo "Dump created.";
