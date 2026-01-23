<?php
/**
 * Módulo: Vehículos - Guardar
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

// 1. Identificación Módulo
$pdo = getConnection();
$stmtMod = $pdo->prepare("SELECT id FROM modulos WHERE nombre_modulo = ?");
$stmtMod->execute(['Vehículos']);
$modulo = $stmtMod->fetch();
$MODULO_ID = $modulo ? $modulo['id'] : 0;

// Recoger datos
$id = isset($_POST['id']) ? (int)$_POST['id'] : null;
$numero_economico = sanitize($_POST['numero_economico']);
$numero_patrimonio = sanitize($_POST['numero_patrimonio']);
$numero_placas = sanitize($_POST['numero_placas']);
$poliza = sanitize($_POST['poliza']);
$marca = sanitize($_POST['marca']);
$tipo = sanitize($_POST['tipo']);
$modelo = sanitize($_POST['modelo']);
$color = sanitize($_POST['color']);
$numero_serie = sanitize($_POST['numero_serie']);
$telefono = sanitize($_POST['telefono']);
$region = sanitize($_POST['region']);
$con_logotipos = sanitize($_POST['con_logotipos']);
$en_proceso_baja = sanitize($_POST['en_proceso_baja']);
$kilometraje = sanitize($_POST['kilometraje']);

$area_id = isset($_POST['area_id']) && !empty($_POST['area_id']) ? (int)$_POST['area_id'] : null;
$factura_nombre = sanitize($_POST['factura_nombre']);
$resguardo_nombre = sanitize($_POST['resguardo_nombre']);
$observacion_1 = sanitize($_POST['observacion_1']);
$observacion_2 = sanitize($_POST['observacion_2']);
// Si es nuevo, activo por defecto. Si es edit, depende del check.
// $activo = isset($_POST['activo']) ? 1 : ($id ? 0 : 1); 
// REGLA: Si está en esta tabla, ES ACTIVO. No usamos activo=0.
$activo = 1; 

// 2. Permisos y Validaciones
if ($id) {
    requirePermission('editar', $MODULO_ID);
    
    // Validar propiedad del área (Row-Level Security)
    // No basta con tener permiso de editar, el registro debe ser de un área permitida (antes de cambiarla)
    $filtroAreas = getAreaFilterSQL('area_id');
    $stmtCheck = $pdo->prepare("SELECT id FROM vehiculos WHERE id = ? AND $filtroAreas");
    $stmtCheck->execute([$id]);
    if (!$stmtCheck->fetch()) {
        die("Acceso denegado: No puedes editar vehículos de esta área.");
    }

} else {
    requirePermission('crear', $MODULO_ID);
}

// Validar que el área destino sea permitida para el usuario (solo si se especifica un área)
if ($area_id !== null) {
    $allowedAreas = $pdo->query("SELECT id FROM areas WHERE " . getAreaFilterSQL('id'))->fetchAll(PDO::FETCH_COLUMN);
    if (!isAdmin() && !in_array($area_id, $allowedAreas)) {
        setFlashMessage('error', 'No puedes asignar vehículos a un área que no administras.');
        redirect($id ? "edit.php?id=$id" : "create.php");
    }
}

try {
    if ($id) {
    $sql = "UPDATE vehiculos SET 
            numero_economico = ?, numero_placas = ?, numero_patrimonio = ?, marca = ?, modelo = ?, 
            tipo = ?, color = ?, numero_serie = ?, poliza = ?, telefono = ?,
            region = ?, con_logotipos = ?, en_proceso_baja = ?, kilometraje = ?,
            area_id = ?, resguardo_nombre = ?, factura_nombre = ?, observacion_1 = ?, observacion_2 = ?, activo = ?
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $numero_economico, $numero_placas, $numero_patrimonio, $marca, $modelo,
        $tipo, $color, $numero_serie, $poliza, $telefono,
        $region, $con_logotipos, $en_proceso_baja, $kilometraje,
        $area_id, $resguardo_nombre, $factura_nombre, $observacion_1, $observacion_2, $activo,
        $id
    ]);
    setFlashMessage('success', 'Vehículo actualizado correctamente.');
} else {
    $sql = "INSERT INTO vehiculos (
            numero_economico, numero_placas, numero_patrimonio, marca, modelo, 
            tipo, color, numero_serie, poliza, telefono,
            region, con_logotipos, en_proceso_baja, kilometraje,
            area_id, resguardo_nombre, factura_nombre, observacion_1, observacion_2, activo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $numero_economico, $numero_placas, $numero_patrimonio, $marca, $modelo,
        $tipo, $color, $numero_serie, $poliza, $telefono,
        $region, $con_logotipos, $en_proceso_baja, $kilometraje,
        $area_id, $resguardo_nombre, $factura_nombre, $observacion_1, $observacion_2
    ]);
    setFlashMessage('success', 'Vehículo registrado correctamente.');
}
    redirect('modulos/vehiculos/index.php');

} catch (PDOException $e) {
    setFlashMessage('error', 'Error en base de datos: ' . $e->getMessage());
    redirect($id ? "edit.php?id=$id" : "create.php");
}
