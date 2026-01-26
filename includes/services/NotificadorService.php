<?php
/**
 * Servicio simplificado de notificaciones (correo y panel interno).
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

    public function notificarAsignacion(int $documentoFlujoId): void
    {
        $stmt = $this->pdo->prepare('SELECT df.documento_id, df.actor_id, u.email, u.username FROM documento_flujos df JOIN usuarios_sistema u ON df.actor_id = u.id WHERE df.id = ?');
        $stmt->execute([$documentoFlujoId]);
        $destinatario = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$destinatario) {
            return;
        }

        $this->registrarNotificacionPanel($documentoFlujoId, (int) $destinatario['actor_id'], 'Se te asignó un documento para revisión.');
        $this->enviarCorreoAsignacion($destinatario);
    }

    public function notificarRechazo(int $documentoId, array $actores, string $mensaje): void
    {
        foreach ($actores as $actor) {
            $destinatario = $actor['actor_id'] ?? $actor['id'] ?? null;
            if ($destinatario) {
                $this->registrarNotificacionPanel(null, (int) $destinatario, $mensaje, $documentoId);
            }
        }
    }

    private function registrarNotificacionPanel(?int $documentoFlujoId, int $destinatarioId, string $mensaje, ?int $documentoId = null): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO documento_notificaciones (documento_flujo_id, destinatario_id, tipo, enviado_en) VALUES (?, ?, "panel", NOW())');
        $stmt->execute([$documentoFlujoId, $destinatarioId]);
        // Aquí podríamos guardar el mensaje en una tabla de mensajes/alertas adicional.
    }

    private function enviarCorreoAsignacion(array $destinatario): void
    {
        // Placeholder: integrar PHPMailer u otra librería para envío real.
    }
}
