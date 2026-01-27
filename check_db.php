<?php
require_once __DIR__ . '/includes/auth.php';
$db = getConnection();
$stmt = $db->query("DESCRIBE empleado_firmas");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
