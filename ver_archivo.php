<?php
// ver_archivo.php: Serve files from Digital Archive
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/services/DigitalArchiveService.php';

$uuid = $_GET['uuid'] ?? null;
$id = $_GET['id'] ?? null;

if (!$uuid && !$id) {
    http_response_code(400);
    die("Falta identificador (UUID o ID).");
}

$db = (new Database())->getConnection();

try {
    if ($uuid) {
        $stmt = $db->prepare("SELECT * FROM archivo_documentos WHERE uuid = ?");
        $stmt->execute([$uuid]);
    } else {
        $stmt = $db->prepare("SELECT * FROM archivo_documentos WHERE id_documento = ?");
        $stmt->execute([$id]);
    }

    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        http_response_code(404);
        die("Documento no encontrado.");
    }

    $basePath = __DIR__ . '/uploads/archivo_digital/';
    $filePath = $basePath . $doc['ruta_almacenamiento'];

    if (!file_exists($filePath)) {
        http_response_code(404);
        die("El archivo físico no existe en el servidor.");
    }

    // Headers
    header("Content-Type: " . $doc['mime_type']);
    header("Content-Length: " . $doc['tamano_bytes']);

    // Si queremos forzar descarga con nombre original:
    // header("Content-Disposition: attachment; filename=\"" . $doc['nombre_archivo_original'] . "\"");

    // Si preferimos ver en navegador (inline) para PDFs:
    header("Content-Disposition: inline; filename=\"" . $doc['nombre_archivo_original'] . "\"");

    readfile($filePath);

} catch (Exception $e) {
    http_response_code(500);
    echo "Error interno: " . $e->getMessage();
}
?>