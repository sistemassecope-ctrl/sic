<?php
/**
 * AJAX: Procesar Firma de Documento
 * Ubicación: modulos/recursos-financieros/procesar-firma.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/services/DocumentoService.php';
require_once __DIR__ . '/../../includes/services/SignatureFlowService.php';

requireAuth();

$pdo = getConnection();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido');
    exit;
}

$flujoId = (int) ($_POST['flujo_id'] ?? 0);
$tipoFirma = trim($_POST['tipo_firma'] ?? '');

if (!$flujoId) {
    jsonError('ID de flujo inválido');
    exit;
}

if (!in_array($tipoFirma, ['pin', 'fiel', 'autografa'])) {
    jsonError('Tipo de firma no válido');
    exit;
}

try {
    $flowService = new \SIC\Services\SignatureFlowService($pdo);

    $datosFirma = [];

    if ($tipoFirma === 'pin') {
        $pin = $_POST['pin'] ?? '';
        if (empty($pin)) {
            jsonError('Debe proporcionar su PIN');
            exit;
        }
        $datosFirma['pin'] = $pin;
    } elseif ($tipoFirma === 'fiel') {
        // Aquí iría la lógica de archivos .cer y .key
        // Por ahora simulamos con datos de sesión/BD
        $datosFirma['ruta_cer'] = ''; // Path al certificado
        $datosFirma['ruta_key'] = ''; // Path a la llave privada
        $datosFirma['password'] = $_POST['password_fiel'] ?? '';
    }
    // Para autografa no se necesitan datos adicionales

    $resultado = $flowService->procesarFirma($flujoId, $user['id'], $tipoFirma, $datosFirma);

    if ($resultado['success']) {
        jsonSuccess($resultado['message']);
    } else {
        jsonError($resultado['message']);
    }

} catch (Exception $e) {
    error_log("Error al procesar firma: " . $e->getMessage());
    jsonError('Error al procesar la firma: ' . $e->getMessage());
}
