<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/services/FirmaService.php';

use SIC\Services\FirmaService;

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

$documentoFlujoId = $_POST['documento_flujo_id'] ?? null;
$hashDocumento = $_POST['hash_documento'] ?? null;
$cadenaOriginal = $_POST['cadena_original'] ?? null;

if (!$documentoFlujoId || !$hashDocumento || !$cadenaOriginal) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos para firmar.']);
    exit;
}

if (!isset($_FILES['archivo_cer']) || !isset($_FILES['archivo_key'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Debes adjuntar los archivos .cer y .key.']);
    exit;
}

$password = $_POST['password'] ?? '';
if ($password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Debes proporcionar la contraseña de la llave privada.']);
    exit;
}

$archivoCer = $_FILES['archivo_cer'];
$archivoKey = $_FILES['archivo_key'];

$tmpCer = $archivoCer['tmp_name'];
$tmpKey = $archivoKey['tmp_name'];

try {
    $firma = FirmaService::firmarCadena($cadenaOriginal, $tmpCer, $tmpKey, $password);

    $stmt = $pdo->prepare('SELECT documento_id, actor_id FROM documento_flujos WHERE id = ?');
    $stmt->execute([$documentoFlujoId]);
    $flujo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$flujo) {
        throw new RuntimeException('No se encontró el paso del flujo.');
    }

    if ((int) $flujo['actor_id'] !== (int) $_SESSION['user_id']) {
        throw new RuntimeException('No tienes permiso para firmar este documento.');
    }

    $stmt = $pdo->prepare('INSERT INTO documento_firmas (documento_id, documento_flujo_id, actor_id, cadena_original, firma_base64, hash_documento, numero_certificado, vigencia_certificado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $flujo['documento_id'],
        $documentoFlujoId,
        $_SESSION['user_id'],
        $cadenaOriginal,
        $firma['firma_base64'],
        $hashDocumento,
        $firma['numero_certificado'],
        $firma['vigencia_certificado'],
    ]);

    echo json_encode(['success' => true, 'firma' => $firma['firma_base64']]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
