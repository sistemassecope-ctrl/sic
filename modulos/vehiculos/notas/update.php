<?php
/**
 * Actualizar nota
 */
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php');
}

$nota_id = (int)$_POST['nota_id'];
$vehiculo_id = (int)$_POST['vehiculo_id'];
$nota_text = trim($_POST['nota']);
$redirect_to = $_POST['redirect_to'] ?? '../index.php';

if (empty($nota_text)) {
    setFlashMessage('error', 'La nota no puede estar vacía.');
    redirect($redirect_to . "#vehiculo-$vehiculo_id");
}

try {
    $pdo = getConnection();

    // Manejo de imagen
    $new_imagen_path = null;
    $mantener_imagen = false;

    // Verificar si existe la nota y obtener imagen actual
    $stmt = $pdo->prepare("SELECT imagen_path FROM vehiculos_notas WHERE id = ?");
    $stmt->execute([$nota_id]);
    $currentNota = $stmt->fetch();

    if (!$currentNota) {
        setFlashMessage('error', 'Nota no encontrada.');
        redirect($redirect_to);
    }

    // 1. Si se subió nueva imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        // ... (Validación similar a create.php) ...
        $fileTmpPath = $_FILES['imagen']['tmp_name'];
        $fileName = $_FILES['imagen']['name'];
        $fileSize = $_FILES['imagen']['size'];
        $fileType = $_FILES['imagen']['type'];
        
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (in_array($fileType, $allowedMimeTypes) && $fileSize <= 5 * 1024 * 1024) {
             $extension = pathinfo($fileName, PATHINFO_EXTENSION);
             $newFileName = 'nota_' . $vehiculo_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
             $dest_path = __DIR__ . '/uploads/' . $newFileName;
             
             if(move_uploaded_file($fileTmpPath, $dest_path)) {
                 $new_imagen_path = $newFileName;
                 
                 // Borrar imagen anterior si existía
                 if (!empty($currentNota['imagen_path'])) {
                     $oldPath = __DIR__ . '/uploads/' . $currentNota['imagen_path'];
                     if (file_exists($oldPath)) unlink($oldPath);
                 }
             }
        }
    } else {
        // No se subió imagen nueva, ¿se eliminó la existente?
        if (isset($_POST['eliminar_imagen']) && $_POST['eliminar_imagen'] == '1') {
             if (!empty($currentNota['imagen_path'])) {
                 $oldPath = __DIR__ . '/uploads/' . $currentNota['imagen_path'];
                 if (file_exists($oldPath)) unlink($oldPath);
             }
             $new_imagen_path = null;
        } else {
            // Mantener la existente
            $new_imagen_path = $currentNota['imagen_path'];
        }
    }

    // Actualizar registro
    $stmtUpdate = $pdo->prepare("UPDATE vehiculos_notas SET nota = ?, imagen_path = ?, updated_at = NOW() WHERE id = ?");
    $stmtUpdate->execute([$nota_text, $new_imagen_path, $nota_id]);

    setFlashMessage('success', 'Nota actualizada correctamente.');
    redirect($redirect_to . "#vehiculo-$vehiculo_id");

} catch (PDOException $e) {
    setFlashMessage('error', 'Error al actualizar: ' . $e->getMessage());
    redirect($redirect_to . "#vehiculo-$vehiculo_id");
}
