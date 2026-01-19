<?php
require_once __DIR__ . '/../../config/db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $sql = "CREATE TABLE IF NOT EXISTS solicitudes_combustible (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fecha DATE NULL,
        folio VARCHAR(50) NULL,
        obra_id INT NULL,
        no_solicitud VARCHAR(50) NULL,
        beneficiario VARCHAR(255) NULL,
        departamento_id INT NULL,
        usuario VARCHAR(255) NULL,
        vehiculo_id INT NULL,
        direccion VARCHAR(255) NULL,
        estatus VARCHAR(50) DEFAULT 'Pendiente',
        estatus_cedula VARCHAR(50) NULL,
        surtir_laguna TINYINT(1) DEFAULT 0,
        litros_premium DECIMAL(10,2) DEFAULT 0.00,
        litros_magna DECIMAL(10,2) DEFAULT 0.00,
        litros_diesel DECIMAL(10,2) DEFAULT 0.00,
        numero_vale VARCHAR(100) NULL,
        km_carretera DECIMAL(10,2) DEFAULT 0.00,
        km_terraceria DECIMAL(10,2) DEFAULT 0.00,
        km_brecha DECIMAL(10,2) DEFAULT 0.00,
        anio INT NULL,
        semana INT NULL,
        importe DECIMAL(12,2) DEFAULT 0.00,
        objetivo TEXT NULL,
        observaciones TEXT NULL,
        recibe VARCHAR(255) NULL,
        vobo VARCHAR(255) NULL,
        autoriza VARCHAR(255) NULL,
        solicita VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $conn->exec($sql);
    echo "Table 'solicitudes_combustible' created successfully or already exists.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
