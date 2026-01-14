<?php
require 'config/db.php';
$db = (new Database())->getConnection();

try {
    $stmt = $db->query("UPDATE usuarios SET activo = 1");
    echo "Se han activado " . $stmt->rowCount() . " usuarios. Ahora deberían aparecer en las listas.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>