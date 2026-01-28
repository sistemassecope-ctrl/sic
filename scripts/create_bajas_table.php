<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

try {
    $sql = "CREATE TABLE IF NOT EXISTS solicitudes_baja (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehiculo_id INT NOT NULL,
        solicitante_id INT NOT NULL,
        area_solicitante_id INT,
        fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
        motivo TEXT,
        estado ENUM('pendiente', 'autorizado', 'rechazado', 'finalizado') DEFAULT 'pendiente',
        autorizador_id INT DEFAULT NULL,
        fecha_respuesta DATETIME DEFAULT NULL,
        comentarios_respuesta TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id),
        FOREIGN KEY (solicitante_id) REFERENCES usuarios_sistema(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Table 'solicitudes_baja' created successfully or already exists.\n";

} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage() . "\n");
}
