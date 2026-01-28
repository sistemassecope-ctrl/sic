<?php
/**
 * Servicio principal para la gestión de documentos y bitácora inmutable.
 * Archivo: includes/services/DocumentoService.php
 */

namespace SIC\Services;

use PDO;
use RuntimeException;

class DocumentoService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crea un nuevo documento en el sistema.
     */
    public function crear(array $datos): int
    {
        $this->pdo->beginTransaction();
        try {
            // 1. Obtener tipo de documento
            $tipoId = $this->obtenerTipoId($datos['codigo_tipo']);

            // 2. Generar Folio
            $folio = $this->generarFolio($tipoId);

            // 3. Insertar Documento
            $stmt = $this->pdo->prepare("
                INSERT INTO documentos (
                    tipo_documento_id, folio_sistema, titulo, contenido_json,
                    usuario_generador_id, prioridad, fase_actual, estatus, fecha_generacion
                ) VALUES (?, ?, ?, ?, ?, ?, 'generacion', 'borrador', NOW())
            ");

            $stmt->execute([
                $tipoId,
                $folio,
                $datos['titulo'],
                json_encode($datos['contenido'] ?? []),
                $datos['usuario_id'],
                $datos['prioridad'] ?? 'normal'
            ]);

            $documentoId = (int) $this->pdo->lastInsertId();

            // 4. Registrar en Bitácora
            $this->registrarBitacora([
                'documento_id' => $documentoId,
                'fase_nueva' => 'generacion',
                'estatus_nuevo' => 'borrador',
                'accion' => 'CREAR',
                'descripcion' => "Creación inicial del documento con folio $folio",
                'usuario_id' => $datos['usuario_id']
            ]);

            $this->pdo->commit();
            return $documentoId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new RuntimeException("Error al crear documento: " . $e->getMessage());
        }
    }

    /**
     * Actualiza la fase y el estatus de un documento, registrando el cambio en la bitácora.
     */
    public function actualizarFase(int $documentoId, string $faseNueva, string $estatusNuevo, int $usuarioId, string $accion, string $descripcion, ?string $observaciones = null): void
    {
        $this->pdo->beginTransaction();
        try {
            // 1. Obtener estado anterior
            $stmt = $this->pdo->prepare("SELECT fase_actual, estatus FROM documentos WHERE id = ? FOR UPDATE");
            $stmt->execute([$documentoId]);
            $actual = $stmt->fetch();

            if (!$actual)
                throw new RuntimeException("Documento #$documentoId no encontrado.");

            // 2. Actualizar documento
            $update = $this->pdo->prepare("
                UPDATE documentos SET 
                    fase_actual = ?, 
                    estatus = ?, 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $update->execute([$faseNueva, $estatusNuevo, $documentoId]);

            // 3. Registrar en Bitácora
            $this->registrarBitacora([
                'documento_id' => $documentoId,
                'fase_anterior' => $actual['fase_actual'],
                'fase_nueva' => $faseNueva,
                'estatus_anterior' => $actual['estatus'],
                'estatus_nuevo' => $estatusNuevo,
                'accion' => $accion,
                'descripcion' => $descripcion,
                'observaciones' => $observaciones,
                'usuario_id' => $usuarioId
            ]);

            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new RuntimeException("Error al actualizar fase: " . $e->getMessage());
        }
    }

    /**
     * Registra un hito o acción sin cambiar la fase del documento.
     */
    public function registrarHito(int $documentoId, int $usuarioId, string $accion, string $descripcion, ?string $observaciones = null): void
    {
        $stmt = $this->pdo->prepare("SELECT fase_actual, estatus FROM documentos WHERE id = ?");
        $stmt->execute([$documentoId]);
        $actual = $stmt->fetch();

        $this->registrarBitacora([
            'documento_id' => $documentoId,
            'fase_anterior' => $actual['fase_actual'] ?? null,
            'fase_nueva' => $actual['fase_actual'] ?? null,
            'estatus_anterior' => $actual['estatus'] ?? null,
            'estatus_nuevo' => $actual['estatus'] ?? null,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'observaciones' => $observaciones,
            'usuario_id' => $usuarioId
        ]);
    }

    /**
     * Busca un documento vinculado a un recurso externo (ej: id_fua).
     */
    public function buscarPorReferenciaExterno(string $codigoTipo, int $idExterno): ?int
    {
        $tipoId = $this->obtenerTipoId($codigoTipo);
        $stmt = $this->pdo->prepare("
            SELECT id FROM documentos 
            WHERE tipo_documento_id = ? 
              AND JSON_EXTRACT(contenido_json, '$.id_fua') = ?
            LIMIT 1
        ");
        $stmt->execute([$tipoId, $idExterno]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }

    /**
     * Registra un evento en la bitácora inmutable.
     */
    public function registrarBitacora(array $log): void
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

    private function obtenerTipoId(string $codigo): int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM cat_tipos_documento WHERE codigo = ?");
        $stmt->execute([$codigo]);
        $id = $stmt->fetchColumn();
        if (!$id)
            throw new RuntimeException("Tipo de documento '$codigo' no encontrado.");
        return (int) $id;
    }

    private function generarFolio(int $tipoId): string
    {
        $stmt = $this->pdo->prepare("SELECT prefijo_folio, ultimo_folio FROM cat_tipos_documento WHERE id = ? FOR UPDATE");
        $stmt->execute([$tipoId]);
        $tipo = $stmt->fetch();

        $nuevoFolio = (int) $tipo['ultimo_folio'] + 1;
        $folioFormateado = $tipo['prefijo_folio'] . "-" . date('Y') . "-" . str_pad($nuevoFolio, 5, '0', STR_PAD_LEFT);

        $update = $this->pdo->prepare("UPDATE cat_tipos_documento SET ultimo_folio = ? WHERE id = ?");
        $update->execute([$nuevoFolio, $tipoId]);

        return $folioFormateado;
    }
}
