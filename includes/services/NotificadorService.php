<?php
/**
 * Servicio de notificaciones (correo y panel interno) adaptado al nuevo sistema documental.
 * Archivo: includes/services/NotificadorService.php
 */

namespace SIC\Services;

use PDO;

class NotificadorService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Notifica a un usuario que tiene un documento pendiente de firmar.
     */
    public function notificarAsignacion(int $documentoFlujoId): void
    {
        // 1. Obtener información del paso y del documento
        $stmt = $this->pdo->prepare("
            SELECT df.documento_id, df.firmante_id, u.usuario as email, u.usuario as username, d.titulo, d.folio_sistema
            FROM documento_flujo_firmas df
            JOIN documentos d ON df.documento_id = d.id
            JOIN usuarios_sistema u ON df.firmante_id = u.id
            WHERE df.id = ?
        ");
        $stmt->execute([$documentoFlujoId]);
        $destinatario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$destinatario) {
            return;
        }

        $mensaje = "Se le ha asignado el documento {$destinatario['folio_sistema']}: \"{$destinatario['titulo']}\" para su firma.";

        // 2. Registrar notificación en el panel (usando la tabla de la bandeja universal o una dedicada)
        $this->registrarNotificacionPanel($destinatario['documento_id'], (int) $destinatario['firmante_id'], $mensaje);

        // 3. Enviar correo electrónico
        $this->enviarCorreoAsignacion($destinatario, $mensaje);
    }

    /**
     * Notifica a los intervinientes previos que un documento fue rechazado.
     */
    public function notificarRechazo(int $documentoId, array $actoresPrevios, string $mensaje): void
    {
        foreach ($actoresPrevios as $actor) {
            $destinatarioId = $actor['firmante_id'] ?? $actor['id'] ?? null;
            if ($destinatarioId) {
                $this->registrarNotificacionPanel($documentoId, (int) $destinatarioId, "RECHAZO: " . $mensaje);
            }
        }
    }

    private function registrarNotificacionPanel(int $documentoId, int $destinatarioId, string $mensaje): void
    {
        // Verificar si ya existe en la bandeja universal para evitar duplicados
        $stmt = $this->pdo->prepare("
            INSERT INTO usuario_bandeja_documentos (usuario_id, documento_id, tipo_accion_requerida, notas_internas, fecha_asignacion)
            VALUES (?, ?, 'revisar', ?, NOW())
            ON DUPLICATE KEY UPDATE notas_internas = ?, updated_at = NOW()
        ");
        $stmt->execute([$destinatarioId, $documentoId, $mensaje, $mensaje]);
    }

    private function enviarCorreoAsignacion(array $destinatario, string $mensaje): void
    {
        // Placeholder: Aquí se integraría PHPMailer
        // error_log("Simulación envío correo a {$destinatario['email']}: $mensaje");
    }
}
