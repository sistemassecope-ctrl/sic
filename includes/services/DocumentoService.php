<?php
/**
 * Servicio principal para la gestión de documentos y bitácora inmutable.
 * Archivo: includes/services/DocumentoService.php
 */

namespace SIC\Services;

use PDO;
use RuntimeException;

require_once __DIR__ . '/BitacoraService.php';
require_once __DIR__ . '/FolioService.php';

class DocumentoService
{
    private PDO $pdo;
    private BitacoraService $bitacora;
    private FolioService $folios;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->bitacora = new BitacoraService($pdo);
        $this->folios = new FolioService($pdo);
    }

    /**
     * Crea un nuevo documento en el sistema.
     */
    public function crear(array $datos): int
    {
        $startedTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }

        try {
            // 1. Obtener tipo de documento
            $tipoId = $this->obtenerTipoId($datos['codigo_tipo']);

            // 2. Generar Folio usando FolioService
            $folio = $this->folios->generar($tipoId);

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

            // 4. Registrar en Bitácora usando BitacoraService
            $this->bitacora->registrar([
                'documento_id' => $documentoId,
                'fase_nueva' => 'generacion',
                'estatus_nuevo' => 'borrador',
                'accion' => 'CREAR',
                'descripcion' => "Creación inicial del documento con folio $folio",
                'usuario_id' => $datos['usuario_id']
            ]);

            if ($startedTransaction) {
                $this->pdo->commit();
            }
            return $documentoId;
        } catch (\Exception $e) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new RuntimeException("Error al crear documento: " . $e->getMessage());
        }
    }

    /**
     * Actualiza la fase y el estatus de un documento, registrando el cambio en la bitácora.
     */
    public function actualizarFase(int $documentoId, string $faseNueva, string $estatusNuevo, int $usuarioId, string $accion, string $descripcion, ?string $observaciones = null): void
    {
        $startedTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }

        try {
            // 1. Obtener estado anterior
            // Use FOR UPDATE only if in a transaction, which we are.
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

            // 3. Registrar en Bitácora usando BitacoraService
            $this->bitacora->registrar([
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

            if ($startedTransaction) {
                $this->pdo->commit();
            }
        } catch (\Exception $e) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
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

        $this->bitacora->registrar([
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
     * Pasarela para BitacoraService (compatibilidad)
     */
    public function registrarBitacora(array $log): void
    {
        $this->bitacora->registrar($log);
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
}
