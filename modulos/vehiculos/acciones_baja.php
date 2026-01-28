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
                // Nota: ID 54 asumido del plan, pero idealmente verificar permisos reales. 
                // Por ahora, isAdmin() es seguro. Si es Director específico, agregar lógica aquí.
                // Como Marlen es Directora Administrativa, asumimos que tiene rol admin o permiso especial.
                // Para simplificar según requerimiento: Admin o Rol Específico
                
                // Si NO es admin, verificamos si es el usuario Marlen (si tuvieramos su ID fijo) o validamos rol.
                // Implementación genérica: Solo Admins por ahora o quien tenga permiso explícito.
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

            $nuevoEstado = $decision === 'aprobada' ? 'autorizado' : 'rechazado';

            $stmt = $pdo->prepare("
                UPDATE solicitudes_baja 
                SET estado = ?, autorizador_id = ?, fecha_respuesta = NOW(), comentarios_respuesta = ?
                WHERE id = ?
            ");
            $stmt->execute([$nuevoEstado, $userId, $comentarios, $solicitudId]);

            // Si se rechaza, quitamos la marca de "en proceso" del vehículo
            if ($nuevoEstado === 'rechazado') {
                $stmtV = $pdo->prepare("SELECT vehiculo_id FROM solicitudes_baja WHERE id = ?");
                $stmtV->execute([$solicitudId]);
                $vid = $stmtV->fetchColumn();
                if ($vid) {
                    $pdo->prepare("UPDATE vehiculos SET en_proceso_baja = 0 WHERE id = ?")->execute([$vid]);
                }
            }

            echo json_encode(['success' => true, 'message' => "Solicitud $decision correctamente."]);
            break;

        case 'finalizar_baja':
            $solicitudId = (int)$_POST['solicitud_id'];

            // Obtener solicitud
            $stmt = $pdo->prepare("SELECT * FROM solicitudes_baja WHERE id = ?");
            $stmt->execute([$solicitudId]);
            $solicitud = $stmt->fetch();

            if (!$solicitud) {
                throw new Exception("Solicitud no encontrada.");
            }

            if ($solicitud['estado'] !== 'autorizado') {
                throw new Exception("La solicitud no está autorizada para finalizar.");
            }

            // Validar que quien finaliza sea el solicitante o un admin
            if ($solicitud['solicitante_id'] != $userId && !isAdmin()) {
                throw new Exception("Solo el solicitante original o un administrador pueden finalizar la baja.");
            }

            // Ejecutar la BAJA REAL
            $pdo->beginTransaction();

            // 1. Mover a histórico (insert en vehiculos_bajas)
            // Obtenemos datos del vehículo
            $stmtV = $pdo->prepare("SELECT * FROM vehiculos WHERE id = ?");
            $stmtV->execute([$solicitud['vehiculo_id']]);
            $vehiculo = $stmtV->fetch();

            if (!$vehiculo) {
                $pdo->rollBack();
                throw new Exception("Vehículo no encontrado.");
            }

            // Insertar en historico
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

            // 2. Desactivar vehículo (activo = 0)
            $stmtUpdate = $pdo->prepare("UPDATE vehiculos SET activo = 0, en_proceso_baja = 0, observaciones_baja = ? WHERE id = ?");
            $stmtUpdate->execute(["Baja Autorizada el " . date('Y-m-d'), $vehiculo['id']]);

            // 3. Actualizar estado solicitud
            $stmtSol = $pdo->prepare("UPDATE solicitudes_baja SET estado = 'finalizado' WHERE id = ?");
            $stmtSol->execute([$solicitudId]);

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Baja finalizada correctamente. El vehículo ha pasado al histórico.']);
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
