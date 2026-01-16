<?php
include("modulos/concursos/contratos/conexion.php");


function show_columns($table, $conn) {
    file_put_contents("columns_output.txt", "Columns for $table:\n", FILE_APPEND);
    $result = $conn->query("SHOW COLUMNS FROM $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            file_put_contents("columns_output.txt", $row['Field'] . "\n", FILE_APPEND);
        }
    } else {
        file_put_contents("columns_output.txt", "Error: " . $conn->error . "\n", FILE_APPEND);
    }
    file_put_contents("columns_output.txt", "-------------------\n", FILE_APPEND);
}

file_put_contents("columns_output.txt", ""); // Clear file
show_columns("persona_moral", $conexion);
show_columns("persona_fisica", $conexion);
?>
