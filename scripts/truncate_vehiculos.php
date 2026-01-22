<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();
$pdo->exec("TRUNCATE TABLE vehiculos");
echo "Tabla vehiculos truncada.\n";
