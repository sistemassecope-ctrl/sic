<?php
include("modulos/concursos/contratos/conexion.php");

function show_columns($table, $conn) {
    echo "Columns for $table:\n";
    $result = $conn->query("SHOW COLUMNS FROM $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . "\n";
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
    echo "-------------------\n";
}

show_columns("area", $conexion);
?>
