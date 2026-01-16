<?php
include("modulos/concursos/padron/conexion.php");

echo "LISTA DE MODULOS:\n";
$res = $conexion->query("SELECT id, nombre, url FROM modulos");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Nombre: {$row['nombre']} | URL: {$row['url']}\n";
}

echo "\nBUSQUEDA 'Concursos':\n";
$res = $conexion->query("SELECT * FROM modulos WHERE nombre LIKE '%Concursos%'");
if ($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No se encontró ningún módulo con 'Concursos' en el nombre.\n";
}
?>
