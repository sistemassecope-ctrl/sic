<?php
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$pdo = conectarDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: areas.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM area WHERE id = ? AND activo = 1");
    $stmt->execute([$id]);
    $original = $stmt->fetch();

    if (!$original) {
        redirectWithMessage('areas.php', 'danger', 'No encontrado');
    }

    $nuevo_nombre = rtrim($original['nombre']) . ' (copia)';
    $tipo = $original['tipo'];
    $padre_id = $original['area_padre_id'] ?: null;
    $descripcion = $original['descripcion'];
    $nivel = $original['nivel'];

    $sql = "INSERT INTO area (nombre, tipo, area_padre_id, descripcion, nivel, activo, fecha_creacion) 
            VALUES (?, ?, ?, ?, ?, 1, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nuevo_nombre, $tipo, $padre_id, $descripcion, $nivel]);

    logActivity('dependencia_duplicada', "Duplicado ID {$id} a {$nuevo_nombre}", $_SESSION['user_id']);
    redirectWithMessage('areas.php', 'success', 'Duplicado exitosamente.');

} catch (PDOException $e) {
    redirectWithMessage('areas.php', 'danger', 'Error: ' . $e->getMessage());
}
?>
