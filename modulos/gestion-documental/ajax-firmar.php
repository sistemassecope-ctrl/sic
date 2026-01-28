<?php
/**
 * AJAX: Procesar firma de documento (PIN/FIEL)
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/services/DocumentoService.php';
require_once __DIR__ . '/../../includes/services/SignatureFlowService.php';

requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$pdo = getConnection();
$user = getCurrentUser();
$flowService = new \SIC\Services\SignatureFlowService($pdo);

$flujoId = (int) ($_POST['flujo_id'] ?? 0);
$tipoFirma = $_POST['tipo_firma'] ?? 'pin';

try {
    if ($tipoFirma === 'pin') {
        $resultado = $flowService->procesarFirma($flujoId, $user['id'], 'pin', ['pin' => $_POST['pin'] ?? '']);
    } else {
        // Para FIEL en este demo/pilot básico asumimos que los archivos vienen en el POST o ya están en el servidor
        // En una implementación real se subirían archivos temporales aquí.
        throw new Exception("Firma FIEL requiere carga de certificados (en desarrollo). Por ahora use Firma por PIN.");
    }

    echo json_encode($resultado);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
