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
$timeline = new \SIC\Components\DocumentoTimeline($pdo);

echo $timeline->render($documentoId);
