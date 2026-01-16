<?php
include("modulos/concursos/padron/conexion.php");

echo "<h3>Estructura de la tabla 'modulos'</h3>";
$res = $conexion->query("DESCRIBE modulos");
if ($res) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    while($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conexion->error;
}

echo "<h3>Contenido de la tabla 'modulos'</h3>";
$res = $conexion->query("SELECT * FROM modulos");
if ($res) {
    echo "<table border='1'><tr>";
    $fields = $res->fetch_fields();
    foreach ($fields as $field) echo "<th>{$field->name}</th>";
    echo "</tr>";
    while($row = $res->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $val) echo "<td>$val</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conexion->error;
}
?>
