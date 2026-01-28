<?php
/**
 * Módulo: Vehículos - Acciones de Baja
 * Descripción: Endpoint para gestionar solicitudes, aprobaciones y finalización de bajas.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$action = $_POST['action'] ?? '';
$pdo = getConnection();
$userId = getCurrentUserId();
$userArea = getUserAreas()[0] ?? null; // Asumimos área principal para el solicitante

try {
    switch ($action) {
        case 'solicitar_baja':
            $vehiculoId = (int)$_POST['vehiculo_id'];
            $motivo = sanitize($_POST['motivo']);

            if (empty($motivo)) {
                throw new Exception("El motivo es obligatorio.");
            }

            // Verificar si ya existe solicitud pendiente
            $stmt = $pdo->prepare("SELECT id FROM solicitudes_baja WHERE vehiculo_id = ? AND estado = 'pendiente'");
            $stmt->execute([$vehiculoId]);
            if ($stmt->fetch()) {
                throw new Exception("Este vehículo ya tiene una solicitud de baja pendiente.");
            }

            $stmt = $pdo->prepare("
                INSERT INTO solicitudes_baja (vehiculo_id, solicitante_id, area_solicitante_id, motivo, estado) 
                VALUES (?, ?, ?, ?, 'pendiente')
            ");
            $stmt->execute([$vehiculoId, $userId, $userArea, $motivo]);
            
            // Marcar vehículo en proceso? (Opcional, pero útil visualmente)
            $pdo->prepare("UPDATE vehiculos SET en_proceso_baja = 1 WHERE id = ?")->execute([$vehiculoId]);

            echo json_encode(['success' => true, 'message' => 'Solicitud de baja enviada correctamente.']);
            break;

        case 'responder_solicitud':
            // Verificar permisos de Director o Admin
            if (!isAdmin() && !hasPermission('autorizar_bajas', 54)) { 
                 if (!isAdmin()) {
                     throw new Exception("No tienes permisos para autorizar bajas.");
                 }
            }

            $solicitudId = (int)$_POST['solicitud_id'];
            $decision = $_POST['decision']; // 'aprobada' o 'rechazada'
            $comentarios = sanitize($_POST['comentarios']);

            if (!in_array($decision, ['aprobada', 'rechazada'])) {
                throw new Exception("Decisión no válida.");
            }

            if ($decision === 'rechazada' && empty($comentarios)) {
                throw new Exception("Debe proporcionar un motivo para el rechazo.");
            }

            // Obtener datos de la solicitud original para historial y notas
            $stmtSol = $pdo->prepare("SELECT * FROM solicitudes_baja WHERE id = ?");
            $stmtSol->execute([$solicitudId]);
            $solicitud = $stmtSol->fetch();

            if (!$solicitud) {
                throw new Exception("Solicitud no encontrada.");
            }

            $pdo->beginTransaction();

            if ($decision === 'aprobada') {
                // AUTOMATIC FINALIZATION
                
                // 1. Obtener vehículo
                $stmtV = $pdo->prepare("SELECT * FROM vehiculos WHERE id = ?");
                $stmtV->execute([$solicitud['vehiculo_id']]);
                $vehiculo = $stmtV->fetch();

                if (!$vehiculo) {
                    $pdo->rollBack();
                    throw new Exception("Vehículo asociado no encontrado.");
                }

                // 2. Insertar Notas en la Bitácora del Vehículo (vehiculos_notas)
                
                // Nota 1: La Solicitud Original (incluir info de solicitante en el texto)
                $notaSolicitud = "SOLICITUD DE BAJA (Usuario ID: " . $solicitud['solicitante_id'] . "): " . $solicitud['motivo'];
                $stmtNota1 = $pdo->prepare("INSERT INTO vehiculos_notas (vehiculo_id, tipo_origen, nota, created_at) VALUES (?, 'SOLICITUD_BAJA', ?, ?)");
                $stmtNota1->execute([$vehiculo['id'], $notaSolicitud, $solicitud['created_at']]);

                // Nota 2: La Autorización (incluir info de autorizador en el texto)
                $notaAuth = "BAJA AUTORIZADA (Usuario ID: " . $userId . "): " . $comentarios;
                $stmtNota2 = $pdo->prepare("INSERT INTO vehiculos_notas (vehiculo_id, tipo_origen, nota, created_at) VALUES (?, 'AUTORIZACION_BAJA', ?, NOW())");
                $stmtNota2->execute([$vehiculo['id'], $notaAuth]);

                // 3. Insertar en histórico
                // Mantenemos el motivo original en la tabla de bajas para referencia rápida
                $sqlBaja = "INSERT INTO vehiculos_bajas (
                    vehiculo_origen_id, numero_economico, numero_placas, numero_patrimonio, marca, modelo, 
                    tipo, color, numero_serie, resguardo_nombre, 
                    fecha_baja, motivo_baja, usuario_baja_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)";
                
                $stmtBaja = $pdo->prepare($sqlBaja);
                $stmtBaja->execute([
                    $vehiculo['id'], $vehiculo['numero_economico'], $vehiculo['numero_placas'], $vehiculo['numero_patrimonio'], 
                    $vehiculo['marca'], $vehiculo['modelo'], $vehiculo['tipo'], $vehiculo['color'], 
                    $vehiculo['numero_serie'], $vehiculo['resguardo_nombre'],
                    "AUTORIZADO: " . $solicitud['motivo'], $userId
                ]);

                // 4. Actualizar vehículo a inactivo (sin modificar observaciones)
                $stmtUpdate = $pdo->prepare("UPDATE vehiculos SET activo = 0, en_proceso_baja = 0 WHERE id = ?");
                $stmtUpdate->execute([$vehiculo['id']]);

                // 5. Actualizar solicitud a finalizado
                $stmtFinal = $pdo->prepare("
                    UPDATE solicitudes_baja 
                    SET estado = 'finalizado', autorizador_id = ?, fecha_respuesta = NOW(), comentarios_respuesta = ?, visto = 0
                    WHERE id = ?
                ");
                $stmtFinal->execute([$userId, $comentarios, $solicitudId]);

                $message = "Solicitud aprobada y vehículo dado de baja automáticamente.";

            } else {
                // RECHAZADA
                $stmtRechazo = $pdo->prepare("
                    UPDATE solicitudes_baja 
                    SET estado = 'rechazado', autorizador_id = ?, fecha_respuesta = NOW(), comentarios_respuesta = ?, visto = 0
                    WHERE id = ?
                ");
                $stmtRechazo->execute([$userId, $comentarios, $solicitudId]);

                // Liberar vehículo
                $pdo->prepare("UPDATE vehiculos SET en_proceso_baja = 0 WHERE id = ?")->execute([$solicitud['vehiculo_id']]);
                
                $message = "Solicitud rechazada correctamente.";
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => $message]);
            break;

        case 'finalizar_baja':
             // Deprecated functionality
             throw new Exception("Esta acción ya no es necesaria. La baja se procesa automáticamente al aprobar.");
             break;

        default:
            throw new Exception("Acción no válida.");
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
