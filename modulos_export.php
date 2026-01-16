<?php
$conn = new mysqli('localhost', 'root', '', 'sistema_dependencias');
if ($conn->connect_error) die($conn->connect_error);

$res = $conn->query("SELECT * FROM modulos");
while($row = $res->fetch_assoc()) {
    foreach($row as $k => $v) echo "[$k]: $v\n";
    echo "--------------------\n";
}
?>
