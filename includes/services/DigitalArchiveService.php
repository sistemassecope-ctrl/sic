<?php
require_once __DIR__ . '/../../config/db.php';

class DigitalArchiveService
{
    private $db;
    private $uploadRoot;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
        // Definir la ruta raíz de uploads. Puede moverse a config.
        $this->uploadRoot = __DIR__ . '/../../uploads/archivo_digital';

        if (!file_exists($this->uploadRoot)) {
            mkdir($this->uploadRoot, 0777, true);
        }
    }

    /**
     * Guarda un nuevo documento en el sistema.
     * 
     * @param array $fileInfo Array $_FILES['input_name']
     * @param array $metadata [modulo_origen, referencia_id, tipo_documento, id_usuario]
     * @return int|false ID del documento creado o false si falló.
     */
    public function saveDocument($fileInfo, $metadata)
    {
        try {
            // Transaction managed by caller

            // 1. Validaciones básicas
            if ($fileInfo['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Error en la subida del archivo: " . $fileInfo['error']);
            }

            // 2. Calcular Hash SHA-256
            $tempPath = $fileInfo['tmp_name'];
            $hash = hash_file('sha256', $tempPath);
            $mimeType = mime_content_type($tempPath);
            $size = filesize($tempPath);

            // 3. Generar UUID
            $uuid = $this->generateUUID();

            // 4. Determinar ruta de almacenamiento (Año/Mes)
            $year = date('Y');
            $month = date('m');
            $relativeDir = "$year/$month";
            $absoluteDir = $this->uploadRoot . "/$relativeDir";

            if (!file_exists($absoluteDir)) {
                mkdir($absoluteDir, 0777, true);
            }

            // Nombre físico del archivo: UUID.extensión
            $ext = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
            $physicalName = $uuid . '.' . $ext;
            $relativePath = $relativeDir . '/' . $physicalName;
            $destination = $absoluteDir . '/' . $physicalName;

            // 5. Mover archivo
            if (!move_uploaded_file($tempPath, $destination)) {
                throw new Exception("No se pudo mover el archivo al almacenamiento final.");
            }

            // 6. Insertar en BD
            $stmt = $this->db->prepare("INSERT INTO archivo_documentos 
                (uuid, modulo_origen, referencia_id, tipo_documento, nombre_archivo_original, ruta_almacenamiento, hash_contenido, tamano_bytes, mime_type, id_usuario_creador)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $uuid,
                $metadata['modulo_origen'],
                $metadata['referencia_id'],
                $metadata['tipo_documento'],
                $fileInfo['name'],
                $relativePath,
                $hash,
                $size,
                $mimeType,
                $metadata['id_usuario']
            ]);

            $idDocumento = $this->db->lastInsertId();

            // Transaction managed by caller
            return $idDocumento;

        } catch (Exception $e) {
            // Re-throw to caller (guardar.php) can handle rollback and display error
            throw new Exception("Error en DigitalArchiveService: " . $e->getMessage());
        }
    }

    /**
     * Genera un UUID v4
     */
    private function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Obtiene la ruta absoluta de un documento por su ID
     */
    public function getDocumentPath($idDocumento)
    {
        $stmt = $this->db->prepare("SELECT ruta_almacenamiento FROM archivo_documentos WHERE id_documento = ?");
        $stmt->execute([$idDocumento]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $this->uploadRoot . '/' . $row['ruta_almacenamiento'];
        }
        return null;
    }
}
?>