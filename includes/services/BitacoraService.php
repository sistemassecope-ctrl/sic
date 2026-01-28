<?php
/**
 * Servicio de Bitácora Inmutable para Documentos.
 * Archivo: includes/services/BitacoraService.php
 */

namespace SIC\Services;

use PDO;

class BitacoraService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Registra un evento en la bitácora inmutable.
     */
    public function registrar(array $log): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO documento_bitacora (
                documento_id, fase_anterior, fase_nueva, estatus_anterior, estatus_nuevo,
                accion, descripcion, observaciones, usuario_id, ip_address, user_agent,
                firma_tipo, firma_hash, timestamp_evento
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(6))
        ");

        $stmt->execute([
            $log['documento_id'],
            $log['fase_anterior'] ?? null,
            $log['fase_nueva'] ?? null,
            $log['estatus_anterior'] ?? null,
            $log['estatus_nuevo'] ?? null,
            $log['accion'],
            $log['descripcion'],
            $log['observaciones'] ?? null,
            $log['usuario_id'],
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System',
            $log['firma_tipo'] ?? 'ninguna',
            $log['firma_hash'] ?? null
        ]);
    }

    /**
     * Obtiene el historial completo de un documento.
     */
    public function obtenerHistorial(int $documentoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT b.*, u.usuario as username
            FROM documento_bitacora b
            JOIN usuarios_sistema u ON b.usuario_id = u.id
            WHERE b.documento_id = ?
            ORDER BY b.timestamp_evento DESC
        ");
        $stmt->execute([$documentoId]);
        return $stmt->fetchAll();
    }
}
