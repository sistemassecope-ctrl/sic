<?php
$conn = new mysqli('localhost', 'root', '', 'sistema_dependencias');
if ($conn->connect_error) die($conn->connect_error);

$res = $conn->query("SELECT * FROM modulos");
$f = fopen("modulos_list_v2.txt", "w");
while($row = $res->fetch_assoc()) {
    foreach($row as $k => $v) fwrite($f, "[$k]: $v\n");
    fwrite($f, "--------------------\n");
}
fclose($f);
echo "OK";
?>
