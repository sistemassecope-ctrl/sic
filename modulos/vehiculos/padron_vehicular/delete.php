<?php
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: " . BASE_URL . "/index.php?route=vehiculos/padron_vehicular");
    exit;
}

$db = (new Database())->getConnection();

try {
    $stmt = $db->prepare("DELETE FROM vehiculos WHERE id = ?");
    $stmt->execute([$id]);
    
    echo "<script>
        window.location.href = '" . BASE_URL . "/index.php?route=vehiculos/padron_vehicular';
        alert('Vehículo eliminado correctamente.');
    </script>";
} catch (PDOException $e) {
    echo "<div class='alert alert-danger m-3'>Error al eliminar: " . $e->getMessage() . "</div>";
    echo "<a href='" . BASE_URL . "/index.php?route=vehiculos/padron_vehicular' class='btn btn-secondary m-3'>Regresar</a>";
}
?>
