<?php
require_once '../../includes/functions.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$pdo = conectarDB();
$id = $_GET['id'] ?? null;

if (!$id) {
    redirectWithMessage('areas.php', 'danger', 'ID no proporcionado.');
}

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM area WHERE area_padre_id = ? AND activo = 1");
$stmt->execute([$id]);
$hijos = $stmt->fetch();

if ($hijos['count'] > 0) {
    redirectWithMessage('areas.php', 'warning', 'No se puede eliminar porque tiene dependencias hijas.');
}

$stmt = $pdo->prepare("SELECT nombre FROM area WHERE id = ?");
$stmt->execute([$id]);
$dependencia = $stmt->fetch();

if (!$dependencia) {
    redirectWithMessage('areas.php', 'danger', 'No encontrado.');
}

$stmt = $pdo->prepare("UPDATE area SET activo = 0, fecha_actualizacion = NOW() WHERE id = ?");
$stmt->execute([$id]);

logActivity('dependencia_eliminada', "Eliminado: {$dependencia['nombre']} (ID: $id)", $_SESSION['user_id']);
redirectWithMessage('areas.php', 'success', 'Eliminado exitosamente.');
?>
