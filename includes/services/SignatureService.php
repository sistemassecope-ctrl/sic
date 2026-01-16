<?php
require_once __DIR__ . '/../../config/db.php';

class SignatureService
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Valida si el PIN proporcionado corresponde al usuario.
     */
    public function validatePin($idUser, $pin)
    {
        $stmt = $this->db->prepare("SELECT pin_firma FROM usuarios_config_firma WHERE id_usuario = ?");
        $stmt->execute([$idUser]);
        $hash = $stmt->fetchColumn();

        if (!$hash)
            return false; // Usuario no configurado
        return password_verify($pin, $hash);
    }

    /**
     * Realiza el proceso de firma de un documento.
     * 
     * @param int $idDocumento ID del documento en archivo_documentos
     * @param int $idUsuario ID del usuario firmante
     * @param string $pin PIN de 6 dígitos
     * @param string $rolFirmante Rol con el que firma (ej. 'TITULAR', 'REVISOR')
     * @return array ['success' => bool, 'message' => string]
     */
    public function signDocument($idDocumento, $idUsuario, $pin, $rolFirmante)
    {
        try {
            // 1. Validar PIN
            if (!$this->validatePin($idUsuario, $pin)) {
                throw new Exception("El PIN de seguridad es incorrecto.");
            }

            // 2. Verificar estado del documento
            $stmtDoc = $this->db->prepare("SELECT estado, hash_contenido FROM archivo_documentos WHERE id_documento = ?");
            $stmtDoc->execute([$idDocumento]);
            $doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);

            if (!$doc)
                throw new Exception("Documento no encontrado.");

            // Permitir firmar en BORRADOR (inicia el proceso) o EN_FIRMA
            if ($doc['estado'] === 'CANCELADO' || $doc['estado'] === 'FIRMADO') {
                throw new Exception("El documento ya no acepta firmas (Estado: " . $doc['estado'] . ").");
            }

            // 3. Verificar si ya firmó
            $stmtCheck = $this->db->prepare("SELECT id_firma FROM archivo_firmas WHERE id_documento = ? AND id_usuario = ?");
            $stmtCheck->execute([$idDocumento, $idUsuario]);
            if ($stmtCheck->rowCount() > 0) {
                throw new Exception("Ya has firmado este documento previamente.");
            }

            // 4. Generar Hash de la Firma
            // Formato robusto: Hash(HashDoc + ID_USER + TIMESTAMP + SALT + ROL)
            $timestamp = date('Y-m-d H:i:s');
            $salt = "SIC_SECURE_SALT_2026_" . $idDocumento; // Semilla vinculada al documento
            $dataToHash = $doc['hash_contenido'] . '|' . $idUsuario . '|' . $timestamp . '|' . $salt . '|' . $rolFirmante;
            $hashFirma = hash('sha256', $dataToHash);

            // 5. Registrar Firma
            $sql = "INSERT INTO archivo_firmas (id_documento, id_usuario, rol_firmante, fecha_firma, hash_firma, metadata_firma) 
                    VALUES (:id_doc, :id_usr, :rol, :fecha, :hash, :meta)";

            // Metadata (IP, Browser)
            $metadata = json_encode([
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'SYSTEM',
                'agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI'
            ]);

            $stmtInsert = $this->db->prepare($sql);
            $stmtInsert->execute([
                ':id_doc' => $idDocumento,
                ':id_usr' => $idUsuario,
                ':rol' => $rolFirmante,
                ':fecha' => $timestamp,
                ':hash' => $hashFirma,
                ':meta' => $metadata
            ]);

            // 6. Actualizar estado del documento a EN_FIRMA si estaba en BORRADOR
            if ($doc['estado'] === 'BORRADOR') {
                $this->db->prepare("UPDATE archivo_documentos SET estado = 'EN_FIRMA' WHERE id_documento = ?")
                    ->execute([$idDocumento]);
            }

            return ['success' => true, 'message' => 'Documento firmado correctamente.', 'hash_firma' => $hashFirma];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>