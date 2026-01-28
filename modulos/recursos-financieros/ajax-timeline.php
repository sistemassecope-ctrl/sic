<?php
/**
 * Endpoint AJAX: Obtener Timeline del Documento
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/documento-timeline.php';

requireAuth();

$documentoId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($documentoId <= 0) {
    echo "<div class='alert alert-danger'>ID de documento inválido.</div>";
    exit;
}

$pdo = getConnection();

try {
    $timeline = new \SIC\Components\DocumentoTimeline($pdo);
    $timeline->render($documentoId);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error al cargar el timeline: ' . e($e->getMessage()) . '</div>';
    error_log("Error en ajax-timeline.php: " . $e->getMessage());
}
