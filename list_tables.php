<?php
include("modulos/concursos/contratos/conexion.php");
$result = $conexion->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_array()) {
        echo $row[0] . "\n";
    }
} else {
    echo "Error: " . $conexion->error;
}
?>
