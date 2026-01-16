<?php
// Intentar con la conexión local (si existe) o ver todas las bases de datos
$host = 'localhost';
$user = 'root';
$pass = ''; // Por defecto en WAMP

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Error local: " . $conn->connect_error);
}

echo "BASES DE DATOS LOCALES:\n";
$res = $conn->query("SHOW DATABASES");
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}

// Buscar tabla 'modulos' en todas las DBs disponibles
echo "\nBUSCANDO 'modulos' EN TODAS LAS DBs:\n";
$resDB = $conn->query("SHOW DATABASES");
while($dbRow = $resDB->fetch_array()) {
    $db = $dbRow[0];
    if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys'])) continue;
    
    $conn->select_db($db);
    $resTab = $conn->query("SHOW TABLES LIKE 'modulos'");
    if ($resTab && $resTab->num_rows > 0) {
        echo "[!] ENCONTRADA en base de datos: $db\n";
    }
}
?>
