<?php
require_once 'config/db.php';

echo "<h1>Actualizando contraseñas a Hash (Bcrypt)</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();

    // Obtener todos los usuarios
    $query = "SELECT id_usuario, usuario, password FROM usuarios";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $count = 0;

    echo "<ul>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['id_usuario'];
        $pass = $row['password'];

        // Verificar si ya parece un hash bcrypt (empieza con $2y$)
        if (substr($pass, 0, 4) !== '$2y$') {
            // No es hash, hay que hashear
            $new_hash = password_hash($pass, PASSWORD_DEFAULT);

            $updateQuery = "UPDATE usuarios SET password = :pass WHERE id_usuario = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':pass', $new_hash);
            $updateStmt->bindParam(':id', $id);

            if ($updateStmt->execute()) {
                echo "<li style='color:green'>Usuario <strong>{$row['usuario']}</strong> actualizado correctamente.</li>";
                $count++;
            } else {
                echo "<li style='color:red'>Error actualizando usuario <strong>{$row['usuario']}</strong>.</li>";
            }
        } else {
            echo "<li style='color:blue'>Usuario <strong>{$row['usuario']}</strong> ya tiene contraseña hasheada. Se omite.</li>";
        }
    }
    echo "</ul>";

    echo "<p>Total actualizados: $count</p>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>