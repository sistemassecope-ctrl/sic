<?php
/**
 * Cargar vista de notas asíncronamente para modal
 */
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

// Si no hay sesión, error 403 (o redirect, pero esto es AJAX)
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    die("Acceso denegado");
}

$vehiculo_id = isset($_GET['vehiculo_id']) ? (int)$_GET['vehiculo_id'] : 0;
$tipo_origen = isset($_GET['tipo']) ? $_GET['tipo'] : 'ACTIVO';
// URL a donde redirigir después de acciones POST (volver a la página principal)
$redirect_to = isset($_GET['from']) ? $_GET['from'] : '../index.php';

if ($vehiculo_id > 0) {
    $isModal = true; // Flag to indicate we are in a modal
    include 'list.php';
} else {
    echo '<div class="alert alert-danger">ID de vehículo inválido.</div>';
}
