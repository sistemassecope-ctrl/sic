<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();
$sql = "CREATE TABLE IF NOT EXISTS vehiculos_bajas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_origen_id INT,
    numero_economico VARCHAR(50),
    numero_placas VARCHAR(50),
    marca VARCHAR(50),
    modelo VARCHAR(50),
    area_id INT,
    region VARCHAR(50),
    fecha_baja DATETIME,
    motivo_baja VARCHAR(100),
    usuario_baja_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$pdo->exec($sql);
echo "Table vehiculos_bajas ensured.\n";
