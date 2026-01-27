<?php
require_once __DIR__ . '/config/database.php';
$pdo = getConnection();

try {
    $pdo->beginTransaction();

    // 1. Crear tabla de catálogo
    $pdo->exec("CREATE TABLE IF NOT EXISTS cat_momentos_suficiencia (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT,
        orden INT DEFAULT 0,
        color VARCHAR(20) DEFAULT '#4f46e5',
        activo TINYINT(1) DEFAULT 1
    )");

    // 2. Insertar los 6 momentos solicitados
    $momentos = [
        [1, 'Autorizado en POA', 'El proyecto ya forma parte del Plan Anual de Obra.', 1, '#10b981'],
        [2, 'En Gestión de Suficiencia', 'Se ha iniciado la solicitud interna de recursos.', 2, '#3b82f6'],
        [3, 'Validación Técnica/Admon.', 'Cuando el expediente pasa a la instancia interna que genera el oficio de salida.', 3, '#8b5cf6'],
        [4, 'En Firma de Titular', 'El oficio está en el despacho de la titular para validación final.', 4, '#f59e0b'],
        [5, 'Turnado a Instancia Externa', 'El trámite ya salió de la dependencia hacia (por ejemplo) Finanzas o Secope.', 5, '#ef4444'],
        [6, 'Con Suficiencia Presupuestal', 'La dependencia externa confirma que el recurso está disponible y etiquetado.', 6, '#059669']
    ];

    $stmt = $pdo->prepare("INSERT INTO cat_momentos_suficiencia (id, nombre, descripcion, orden, color) 
                           VALUES (?, ?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), descripcion=VALUES(descripcion), color=VALUES(color)");

    foreach ($momentos as $m) {
        $stmt->execute($m);
    }

    // 3. Agregar columna a solicitudes_suficiencia
    $stmt = $pdo->query("SHOW COLUMNS FROM solicitudes_suficiencia LIKE 'id_momento_gestion'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE solicitudes_suficiencia ADD COLUMN id_momento_gestion INT DEFAULT 1 AFTER estatus");
        $pdo->exec("ALTER TABLE solicitudes_suficiencia ADD CONSTRAINT fk_momento_gestion FOREIGN KEY (id_momento_gestion) REFERENCES cat_momentos_suficiencia(id)");
    }

    $pdo->commit();
    echo "Base de datos actualizada con éxito.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
