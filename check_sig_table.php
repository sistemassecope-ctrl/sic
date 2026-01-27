<?php
require_once 'config/database.php';
$pdo = getConnection();
$cols = $pdo->query("DESCRIBE usuarios_config_firma")->fetchAll();
foreach ($cols as $col)
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
