<?php
/**
 * Servicio de alto nivel para manejar el flujo de documentos.
 */

namespace SIC\Services;

use PDO;
use RuntimeException;

class FlujoDocumentosService
{
    private PDO $pdo;
    private NotificadorService $notificador;
    private ?PdfDocumentoService $pdfService;

    public function __construct(PDO $pdo, NotificadorService $notificador, ?PdfDocumentoService $pdfService = null)
    {
        $this->pdo = $pdo;
        $this->notificador = $notificador;
        $this->pdfService = $pdfService;
    }

    /**
     * Crea un documento y clona la plantilla de flujo seleccionada.
     */
    public function crearDocumento(array $datosDocumento, int $plantillaId = 0, array $actores = []): int
    {
        if ($plantillaId <= 0 && empty($actores)) {
            throw new RuntimeException('Debes definir una plantilla o una lista de actores.');
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('INSERT INTO documentos (tipo_documento, folio, titulo, estado_actual, creado_por, fecha_creacion) VALUES (?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $datosDocumento['tipo_documento'],
                $datosDocumento['folio'],
                $datosDocumento['titulo'],
                'pendiente',
                $datosDocumento['creado_por'],
            ]);

            $documentoId = (int) $this->pdo->lastInsertId();

            if ($plantillaId > 0) {
                $this->clonarPlantilla($documentoId, $plantillaId);
            } else {
                $this->insertarFlujoPorActores($documentoId, $actores);
            }

            $this->asignarPrimerActor($documentoId);

            $this->pdo->commit();
            return $documentoId;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw new RuntimeException('No se pudo crear el documento: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Registra una acción de aprobación/rechazo del actor.
     */
    public function procesarAccion(int $documentoFlujoId, int $actorId, string $accion, ?string $comentarios = null): void
    {
        $accion = strtolower($accion);
        if (!in_array($accion, ['aprobar', 'rechazar'], true)) {
            throw new RuntimeException('Acción inválida.');
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT df.*, d.estado_actual FROM documento_flujos df JOIN documentos d ON df.documento_id = d.id WHERE df.id = ? FOR UPDATE');
            $stmt->execute([$documentoFlujoId]);
            $flujo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$flujo) {
                throw new RuntimeException('El paso de flujo no existe.');
            }

            if ((int) $flujo['actor_id'] !== $actorId) {
                throw new RuntimeException('Este actor no está autorizado para resolver el paso actual.');
            }

            if ($flujo['estatus'] !== 'pendiente') {
                throw new RuntimeException('El paso ya fue atendido.');
            }

            $nuevoEstatus = $accion === 'aprobar' ? 'aprobado' : 'rechazado';
            $stmt = $this->pdo->prepare('UPDATE documento_flujos SET estatus = ?, fecha_resolucion = NOW(), comentarios = ? WHERE id = ?');
            $stmt->execute([$nuevoEstatus, $comentarios, $documentoFlujoId]);

            $this->registrarHistorial($flujo['documento_id'], $actorId, $accion, $comentarios);

            if ($accion === 'aprobar') {
                $this->asignarSiguienteActor($flujo['documento_id'], (int) $flujo['orden'], $actorId);
            } else {
                $this->actualizarEstadoDocumento($flujo['documento_id'], 'rechazado');
                $this->notificarRechazo($flujo['documento_id'], $actorId, $comentarios);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw new RuntimeException('No se pudo procesar la acción: ' . $e->getMessage(), 0, $e);
        }
    }

    private function clonarPlantilla(int $documentoId, int $plantillaId): void
    {
        $stmt = $this->pdo->prepare('SELECT orden, actor_id, tiempo_maximo_horas FROM flujo_plantilla_detalle WHERE plantilla_id = ? ORDER BY orden');
        $stmt->execute([$plantillaId]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($detalles)) {
            throw new RuntimeException('La plantilla seleccionada no tiene pasos definidos.');
        }

        $insert = $this->pdo->prepare('INSERT INTO documento_flujos (documento_id, orden, actor_id, estatus, fecha_asignacion) VALUES (?, ?, ?, "pendiente", NULL)');
        foreach ($detalles as $detalle) {
            $insert->execute([$documentoId, $detalle['orden'], $detalle['actor_id']]);
        }
    }

    private function insertarFlujoPorActores(int $documentoId, array $actores): void
    {
        if (empty($actores)) {
            throw new RuntimeException('La lista de actores está vacía.');
        }

        $orden = 1;
        $insert = $this->pdo->prepare('INSERT INTO documento_flujos (documento_id, orden, actor_id, estatus, fecha_asignacion) VALUES (?, ?, ?, "pendiente", NULL)');
        foreach ($actores as $actorId) {
            $insert->execute([$documentoId, $orden++, (int) $actorId]);
        }
    }

    private function asignarPrimerActor(int $documentoId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM documento_flujos WHERE documento_id = ? ORDER BY orden ASC LIMIT 1');
        $stmt->execute([$documentoId]);
        $primerPaso = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($primerPaso) {
            $stmt = $this->pdo->prepare('UPDATE documento_flujos SET fecha_asignacion = NOW() WHERE id = ?');
            $stmt->execute([$primerPaso['id']]);
        }
    }

    private function asignarSiguienteActor(int $documentoId, int $ordenActual, int $actorId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM documento_flujos WHERE documento_id = ? AND orden > ? AND estatus = "pendiente" ORDER BY orden ASC LIMIT 1');
        $stmt->execute([$documentoId, $ordenActual]);
        $siguiente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($siguiente) {
            $stmt = $this->pdo->prepare('UPDATE documento_flujos SET fecha_asignacion = NOW() WHERE id = ?');
            $stmt->execute([$siguiente['id']]);
            $this->actualizarEstadoDocumento($documentoId, 'pendiente');
            $this->notificarSiguienteActor((int) $siguiente['id']);
        } else {
            $this->actualizarEstadoDocumento($documentoId, 'aprobado');
            $this->generarPdfFinal($documentoId, $actorId);
        }
    }

    private function actualizarEstadoDocumento(int $documentoId, string $estado): void
    {
        $stmt = $this->pdo->prepare('UPDATE documentos SET estado_actual = ?, fecha_actualizacion = NOW() WHERE id = ?');
        $stmt->execute([$estado, $documentoId]);
    }

    private function registrarHistorial(int $documentoId, int $actorId, string $accion, ?string $comentarios): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO documento_historial (documento_id, actor_id, accion, comentarios, fecha) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$documentoId, $actorId, $accion, $comentarios]);
    }

    private function notificarSiguienteActor(int $documentoFlujoId): void
    {
        $this->notificador->notificarAsignacion($documentoFlujoId);
    }

    private function notificarRechazo(int $documentoId, int $actorId, ?string $comentarios): void
    {
        $stmt = $this->pdo->prepare('SELECT df.actor_id, us.email FROM documento_flujos df LEFT JOIN usuarios_sistema us ON df.actor_id = us.id WHERE df.documento_id = ? AND df.estatus = "aprobado"');
        $stmt->execute([$documentoId]);
        $aprobadoresPrevios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mensaje = 'Documento rechazado por el actor ' . $actorId . '. Comentarios: ' . ($comentarios ?? '');
        $this->notificador->notificarRechazo($documentoId, $aprobadoresPrevios, $mensaje);
    }

    private function generarPdfFinal(int $documentoId, int $actorId): void
    {
        if (!$this->pdfService) {
            return;
        }

        try {
            $this->pdfService->generar($documentoId, $actorId);
        } catch (\Throwable $e) {
            error_log('No se pudo generar el PDF del documento ' . $documentoId . ': ' . $e->getMessage());
        }
    }
}
