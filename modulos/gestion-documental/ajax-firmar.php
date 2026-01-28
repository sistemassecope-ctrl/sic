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
    } else if ($tipoFirma === 'fiel') {
        // Manejo de carga de archivos temporales para FIEL
        if (!isset($_FILES['fiel_cer']) || !isset($_FILES['fiel_key'])) {
            throw new Exception("Se requieren los archivos .cer y .key para la firma FIEL.");
        }

        $datosFirma = [
            'ruta_cer' => $_FILES['fiel_cer']['tmp_name'],
            'ruta_key' => $_FILES['fiel_key']['tmp_name'],
            'password' => $_POST['fiel_pass'] ?? ''
        ];

        $resultado = $flowService->procesarFirma($flujoId, $user['id'], 'fiel', $datosFirma);
    } else if ($tipoFirma === 'autografa') {
        $resultado = $flowService->procesarFirma($flujoId, $user['id'], 'autografa', []);
    } else {
        throw new Exception("Tipo de firma no reconocido: " . $tipoFirma);
    }

    echo json_encode($resultado);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
