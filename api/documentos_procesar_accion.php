<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/services/NotificadorService.php';
require_once __DIR__ . '/../includes/services/PdfDocumentoService.php';
require_once __DIR__ . '/../includes/services/FlujoDocumentosService.php';

use SIC\Services\NotificadorService;
use SIC\Services\PdfDocumentoService;
use SIC\Services\FlujoDocumentosService;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión expirada']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$documentoFlujoId = $input['documento_flujo_id'] ?? null;
$accion = $input['accion'] ?? null;
$comentarios = $input['comentarios'] ?? null;

if (!$documentoFlujoId || !$accion) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

try {
    $notificador = new NotificadorService($pdo);
    $pdfService = new PdfDocumentoService($pdo);
    $servicio = new FlujoDocumentosService($pdo, $notificador, $pdfService);
    $servicio->procesarAccion((int) $documentoFlujoId, (int) $_SESSION['user_id'], $accion, $comentarios);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
