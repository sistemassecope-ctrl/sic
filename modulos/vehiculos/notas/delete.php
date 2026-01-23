<?php
/**
 * Eliminar nota
 */
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php');
}

$nota_id = (int)$_POST['nota_id'];
$vehiculo_id = (int)$_POST['vehiculo_id'];
$redirect_to = $_POST['redirect_to'] ?? '../index.php';

try {
    $pdo = getConnection();

    // 1. Obtener info de la nota para borrar imagen si existe
    $stmt = $pdo->prepare("SELECT imagen_path FROM vehiculos_notas WHERE id = ?");
    $stmt->execute([$nota_id]);
    $nota = $stmt->fetch();

    if ($nota) {
        // Borrar archivo físico
        if (!empty($nota['imagen_path'])) {
            $filePath = __DIR__ . '/uploads/' . $nota['imagen_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // 2. Borrar registro
        $stmtDelete = $pdo->prepare("DELETE FROM vehiculos_notas WHERE id = ?");
        $stmtDelete->execute([$nota_id]);

        setFlashMessage('success', 'Nota eliminada correctamente.');
    } else {
        setFlashMessage('error', 'Nota no encontrada.');
    }

    redirect($redirect_to . "#vehiculo-$vehiculo_id");

} catch (PDOException $e) {
    setFlashMessage('error', 'Error al eliminar: ' . $e->getMessage());
    redirect($redirect_to . "#vehiculo-$vehiculo_id");
}
