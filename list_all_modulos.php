<?php
$conn = new mysqli('localhost', 'root', '', 'sistema_dependencias');
if ($conn->connect_error) die($conn->connect_error);

echo "LISTA COMPLETA DE MODULOS:\n";
$res = $conn->query("SELECT * FROM modulos");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
