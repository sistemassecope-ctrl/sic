<?php
$conn = new mysqli('localhost', 'root', '', 'sistema_dependencias');
if ($conn->connect_error) die($conn->connect_error);

echo "STRUCTURE:\n";
$res = $conn->query("SHOW COLUMNS FROM modulos");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}

echo "\nDATA:\n";
$res = $conn->query("SELECT * FROM modulos");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Nombre: {$row['nombre']} | Parent: " . ($row['parent_id'] ?? 'N/A') . " | URL: {$row['url']}\n";
}
?>
