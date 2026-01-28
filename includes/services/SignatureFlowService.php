<?php
/**
 * Servicio unificado para gestionar el flujo de firmas (Dual: PIN y FIEL)
 * Archivo: includes/services/SignatureFlowService.php
 */

namespace SIC\Services;

use PDO;
use RuntimeException;
use Exception;

require_once __DIR__ . '/FirmaService.php';
require_once __DIR__ . '/ExpedienteDigitalService.php';
require_once __DIR__ . '/NotificadorService.php';

class SignatureFlowService
{
    private PDO $pdo;
    private DocumentoService $docService;
    private ExpedienteDigitalService $expedienteService;
    private NotificadorService $notificador;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->docService = new DocumentoService($pdo);
        $this->expedienteService = new ExpedienteDigitalService($pdo);
        $this->notificador = new NotificadorService($pdo);
    }

    /**
     * Inicia el flujo de firmas para un documento basado en su plantilla configurada.
     */
    public function iniciarFlujo(int $documentoId): void
    {
        $this->pdo->beginTransaction();
        try {
            // 1. Obtener la plantilla del tipo de documento
            $stmt = $this->pdo->prepare("
                SELECT ctd.plantilla_flujo_id
                FROM documentos d
                JOIN cat_tipos_documento ctd ON d.tipo_documento_id = ctd.id
                WHERE d.id = ?
            ");
            $stmt->execute([$documentoId]);
            $plantillaId = $stmt->fetchColumn();

            if (!$plantillaId) {
                throw new RuntimeException("El documento no tiene una plantilla de flujo asociada.");
            }

            // 2. Copiar pasos de la plantilla al flujo del documento
            $stmtPasos = $this->pdo->prepare("
                SELECT * FROM flujo_plantilla_detalle 
                WHERE plantilla_id = ? 
                ORDER BY orden ASC
            ");
            $stmtPasos->execute([$plantillaId]);
            $detalles = $stmtPasos->fetchAll();

            if (empty($detalles)) {
                throw new RuntimeException("La plantilla no tiene pasos definidos.");
            }

            $insertPaso = $this->pdo->prepare("
                INSERT INTO documento_flujo_firmas (
                    documento_id, orden, firmante_id, rol_firmante, tipo_firma, fecha_asignacion, fecha_limite
                ) VALUES (?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? HOUR))
            ");

            foreach ($detalles as $d) {
                $insertPaso->execute([
                    $documentoId,
                    $d['orden'],
                    $d['actor_usuario_id'],
                    $d['actor_rol'],
                    $d['tipo_firma'],
                    $d['tiempo_maximo_horas']
                ]);
            }

            // 3. Activar el primer paso en la bandeja del usuario
            $primerPaso = $detalles[0];
            $stmtBandeja = $this->pdo->prepare("
                INSERT INTO usuario_bandeja_documentos (
                    usuario_id, documento_id, tipo_accion_requerida, prioridad, fecha_asignacion
                ) VALUES (?, ?, 'firmar', 2, NOW())
            ");
            $stmtBandeja->execute([$primerPaso['actor_usuario_id'], $documentoId]);
            $stepId = (int) $this->pdo->lastInsertId(); // This is wrong, lastInsertId of bandeja not flujo.

            // Wait, we need the ID of the step in documento_flujo_firmas to notify.
            $stmtLastFlow = $this->pdo->prepare("SELECT id FROM documento_flujo_firmas WHERE documento_id = ? AND orden = 1");
            $stmtLastFlow->execute([$documentoId]);
            $flowStepId = $stmtLastFlow->fetchColumn();

            // NOTIFICACIÓN
            $this->notificador->notificarAsignacion($flowStepId);

            // 4. Actualizar estado del documento
            $this->docService->actualizarFase(
                $documentoId,
                'aprobacion',
                'pendiente',
                0, // Sistema
                'INICIAR',
                "Iniciando flujo de firmas basado en plantilla estándar."
            );

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new RuntimeException("Error al iniciar flujo: " . $e->getMessage());
        }
    }

    /**
     * Procesa la firma de un paso del flujo.
     * Soporta firma interna (PIN) y firma legal (FIEL).
     */
    public function procesarFirma(int $flujoId, int $usuarioId, string $tipoFirma, array $datosFirma): array
    {
        $this->pdo->beginTransaction();
        try {
            // 1. Obtener datos del paso de flujo
            $stmt = $this->pdo->prepare("
                SELECT df.*, d.folio_sistema, d.hash_integridad, d.id as documento_id
                FROM documento_flujo_firmas df
                JOIN documentos d ON df.documento_id = d.id
                WHERE df.id = ? AND df.firmante_id = ? AND df.estatus = 'pendiente'
                FOR UPDATE
            ");
            $stmt->execute([$flujoId, $usuarioId]);
            $paso = $stmt->fetch();

            if (!$paso) {
                throw new RuntimeException("El paso de firma no existe, ya fue procesado o no tienes permisos.");
            }

            $resultado = [];
            if ($tipoFirma === 'pin') {
                $resultado = $this->validarFirmaPin($usuarioId, $datosFirma['pin']);
            } else if ($tipoFirma === 'fiel') {
                $resultado = $this->validarFirmaFiel($paso['hash_integridad'], $datosFirma);
            } else {
                throw new RuntimeException("Tipo de firma no soportado.");
            }

            // 2. Registrar firma en la tabla de flujo
            $stmtUpdate = $this->pdo->prepare("
                UPDATE documento_flujo_firmas SET 
                    estatus = 'firmado',
                    fecha_firma = NOW(),
                    tipo_firma = ?,
                    firma_pin_hash = ?,
                    firma_fiel_hash = ?,
                    certificado_serial = ?,
                    sello_tiempo = NOW(),
                    tipo_respuesta = 'aprobado'
                WHERE id = ?
            ");

            $stmtUpdate->execute([
                $tipoFirma,
                $tipoFirma === 'pin' ? $resultado['hash'] : null,
                $tipoFirma === 'fiel' ? $resultado['firma_base64'] : null,
                $tipoFirma === 'fiel' ? $resultado['numero_certificado'] : null,
                $flujoId
            ]);

            // 3. Registrar en bitácora inmutable
            $this->docService->registrarBitacora([
                'documento_id' => $paso['documento_id'],
                'accion' => 'FIRMAR',
                'descripcion' => "Documento firmado electrónicamente ($tipoFirma) por usuario ID $usuarioId",
                'usuario_id' => $usuarioId,
                'firma_tipo' => $tipoFirma,
                'firma_hash' => $tipoFirma === 'pin' ? $resultado['hash'] : $resultado['firma_base64']
            ]);

            // 4. Marcar en bandeja como procesado
            $stmtBandeja = $this->pdo->prepare("
                UPDATE usuario_bandeja_documentos 
                SET procesado = 1, fecha_proceso = NOW() 
                WHERE usuario_id = ? AND documento_id = ?
            ");
            $stmtBandeja->execute([$usuarioId, $paso['documento_id']]);

            // 5. Verificar si hay más firmas pendientes
            $this->verificarSiguientePaso($paso['documento_id']);

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Firma procesada correctamente.'];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function validarFirmaPin(int $usuarioId, string $pin): array
    {
        $stmt = $this->pdo->prepare("SELECT pin_hash FROM empleado_firmas ef JOIN usuarios_sistema us ON ef.empleado_id = us.id_empleado WHERE us.id = ?");
        $stmt->execute([$usuarioId]);
        $hash = $stmt->fetchColumn();

        if (!$hash || !password_verify($pin, $hash)) {
            throw new RuntimeException("PIN de firma incorrecto.");
        }

        return ['hash' => $hash];
    }

    private function validarFirmaFiel(string $cadena, array $datos): array
    {
        // Usamos el FirmaService existente para la lógica criptográfica pesada
        return FirmaService::firmarCadena(
            $cadena,
            $datos['ruta_cer'],
            $datos['ruta_key'],
            $datos['password']
        );
    }

    private function verificarSiguientePaso(int $documentoId): void
    {
        // 1. Verificar si hay un siguiente paso pendiente en el orden
        $stmt = $this->pdo->prepare("
            SELECT * FROM documento_flujo_firmas 
            WHERE documento_id = ? AND estatus = 'pendiente' 
            ORDER BY orden ASC LIMIT 1
        ");
        $stmt->execute([$documentoId]);
        $siguientePaso = $stmt->fetch();

        if ($siguientePaso) {
            // 2. Asignar a la bandeja del siguiente firmante
            $stmtBandeja = $this->pdo->prepare("
                INSERT INTO usuario_bandeja_documentos (
                    usuario_id, documento_id, tipo_accion_requerida, prioridad, fecha_asignacion
                ) VALUES (?, ?, 'firmar', 2, NOW())
                ON DUPLICATE KEY UPDATE procesado = 0, fecha_asignacion = NOW()
            ");
            $stmtBandeja->execute([$siguientePaso['firmante_id'], $documentoId]);

            // 3. Notificar al siguiente firmante
            $this->notificador->notificarAsignacion($siguientePaso['id']);
        } else {
            // 4. Si no hay más pasos, cerrar el documento
            $this->docService->actualizarFase(
                $documentoId,
                'resuelto',
                'firmado',
                0, // Sistema
                'FINALIZAR',
                "El documento ha completado su cadena de firmas exitosamente."
            );

            // 5. Generar PDF final y archivar
            try {
                $this->expedienteService->generarPdfFinal($documentoId);
            } catch (Exception $pdfEx) {
                error_log("Error al generar PDF final: " . $pdfEx->getMessage());
            }
        }
    }
}
