<?php
/**
 * Procesar creación de nota
 */
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php');
}

// Validar datos básicos
if (!isset($_POST['vehiculo_id']) || !isset($_POST['nota'])) {
    setFlashMessage('error', 'Datos incompletos.');
    redirect('../index.php');
}

$vehiculo_id = (int)$_POST['vehiculo_id'];
$nota = trim($_POST['nota']);
$redirect_to = $_POST['redirect_to'] ?? '../index.php'; // Permitir volver a index o bajas

if (empty($nota)) {
    setFlashMessage('error', 'La nota no puede estar vacía.');
    redirect($redirect_to . "#vehiculo-$vehiculo_id");
}

$imagen_path = null;

// Procesar subida de imagen
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['imagen']['tmp_name'];
    $fileName = $_FILES['imagen']['name'];
    $fileSize = $_FILES['imagen']['size'];
    $fileType = $_FILES['imagen']['type'];
    
    // Validar tipo
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!in_array($fileType, $allowedMimeTypes)) {
        setFlashMessage('error', 'Tipo de archivo no permitido. Solo JPG, PNG, GIF o PDF.');
        redirect($redirect_to . "#vehiculo-$vehiculo_id");
    }
    
    // Validar tamaño (5MB)
    if ($fileSize > 5 * 1024 * 1024) {
        setFlashMessage('error', 'El archivo es demasiado grande (Máx 5MB).');
        redirect($redirect_to . "#vehiculo-$vehiculo_id");
    }
    
    // Generar nombre único
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = 'nota_' . $vehiculo_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $uploadFileDir = __DIR__ . '/uploads/';
    $dest_path = $uploadFileDir . $newFileName;
    
    if(move_uploaded_file($fileTmpPath, $dest_path)) {
        $imagen_path = $newFileName;
    } else {
        setFlashMessage('error', 'Error al subir el archivo.');
        redirect($redirect_to . "#vehiculo-$vehiculo_id");
    }
}

try {
    $pdo = getConnection();
    
    // Insertar nota
    $tipo_origen = $_POST['tipo_origen'] ?? 'ACTIVO';
    $stmt = $pdo->prepare("INSERT INTO vehiculos_notas (vehiculo_id, tipo_origen, nota, imagen_path, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$vehiculo_id, $tipo_origen, $nota, $imagen_path]);
    
    setFlashMessage('success', 'Nota agregada correctamente.');
    redirect($redirect_to . "#vehiculo-$vehiculo_id");

} catch (PDOException $e) {
    setFlashMessage('error', 'Error en base de datos: ' . $e->getMessage());
    redirect($redirect_to . "#vehiculo-$vehiculo_id");
}
