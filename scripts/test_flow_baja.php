<?php
// Script de prueba para el fujo de bajas
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- Iniciando Prueba de Flujo de Baja ---\n";

try {
    // 1. Crear Vehículo Dummy
    $sql = "INSERT INTO vehiculos (
        numero_economico, marca, modelo, tipo, color, numero_placas, area_id, activo
    ) VALUES (
        'TEST-001', 'MarcaTest', 'ModeloTest', 'Sedan', 'Blanco', 'TEST-PLA', NULL, 1
    )";
    $pdo->exec($sql);
    $vehiculoId = $pdo->lastInsertId();
    echo "[OK] Vehículo creado ID: $vehiculoId\n";

    // 2. Simular Solicitud 
    $userId = $pdo->query("SELECT id FROM usuarios_sistema LIMIT 1")->fetchColumn();
    if (!$userId) throw new Exception("No users found");
    
    $stmt = $pdo->prepare("INSERT INTO solicitudes_baja (vehiculo_id, solicitante_id, motivo, estado) VALUES (?, ?, 'Test Script', 'pendiente')");
    $stmt->execute([$vehiculoId, $userId]);
    $solicitudId = $pdo->lastInsertId();
    echo "[OK] Solicitud creada ID: $solicitudId (Estado: Pendiente) por Usuario $userId\n";

    // 3. Verificar estado pendiente
    $s = $pdo->query("SELECT * FROM solicitudes_baja WHERE id = $solicitudId")->fetch();
    if($s['estado'] !== 'pendiente') throw new Exception("Estado incorrecto, deberia ser pendiente");

    // 4. Intentar finalizar (Simular APROBACION)
    $pdo->prepare("UPDATE solicitudes_baja SET estado = 'autorizado', autorizador_id = ?, fecha_respuesta = NOW() WHERE id = ?")->execute([$userId, $solicitudId]);
    echo "[OK] Solicitud Aprobada (Simulado)\n";

    // 5. Simular Finalizacion (Logica Compleja)
    // Aquí si copiaremos la lógica de acciones_baja.php step 'finalizar_baja' para validar que funcione el query de traspaso
    $pdo->beginTransaction();
    
    // Get Vehiculo
    $v = $pdo->query("SELECT * FROM vehiculos WHERE id = $vehiculoId")->fetch();
    
    // Insert historico
    $sqlBaja = "INSERT INTO vehiculos_bajas (
        vehiculo_origen_id, numero_economico, numero_placas, numero_patrimonio, marca, modelo, 
        tipo, color, numero_serie, resguardo_nombre, 
        fecha_baja, motivo_baja, usuario_baja_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)";
    $stmtBaja = $pdo->prepare($sqlBaja);
    $stmtBaja->execute([
        $v['id'], $v['numero_economico'], $v['numero_placas'], $v['numero_patrimonio'], 
        $v['marca'], $v['modelo'], $v['tipo'], $v['color'], 
        $v['numero_serie'], $v['resguardo_nombre'],
        "TEST SCRIPT", $userId
    ]);

    // Update Vehiculo
    $pdo->prepare("UPDATE vehiculos SET activo = 0, en_proceso_baja = 0 WHERE id = $vehiculoId")->execute();
    
    // Update Solicitud
    $pdo->prepare("UPDATE solicitudes_baja SET estado = 'finalizado' WHERE id = $solicitudId")->execute();
    
    $pdo->commit();
    echo "[OK] Baja Finalizada\n";

    // 6. Verificar
    $vcheck = $pdo->query("SELECT activo FROM vehiculos WHERE id = $vehiculoId")->fetch();
    if ($vcheck['activo'] != 0) throw new Exception("El vehiculo sigue activo");

    $bcheck = $pdo->query("SELECT * FROM vehiculos_bajas WHERE vehiculo_origen_id = $vehiculoId")->fetch();
    if (!$bcheck) throw new Exception("No se creo registro en historico");

    echo "[SUCCESS] Pasa todas las validaciones.\n";

    // Clean up
    $pdo->exec("DELETE FROM vehiculos_bajas WHERE vehiculo_origen_id = $vehiculoId");
    $pdo->exec("DELETE FROM solicitudes_baja WHERE vehiculo_id = $vehiculoId");
    $pdo->exec("DELETE FROM vehiculos WHERE id = $vehiculoId");
    echo "[CLEANUP] Datos de prueba borrados.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "[ERROR] " . $e->getMessage() . "\n";
}
