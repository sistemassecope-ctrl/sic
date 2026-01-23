<?php
/**
 * Módulo: Vehículos - Crear/Editar
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// 1. Identificación Módulo
$pdo = getConnection();
$stmtMod = $pdo->prepare("SELECT id FROM modulos WHERE nombre_modulo = ?");
$stmtMod->execute(['Vehículos']);
$modulo = $stmtMod->fetch();
$MODULO_ID = $modulo ? $modulo['id'] : 0;

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $id !== null;

// 2. Permisos
if ($isEdit) {
    requirePermission('editar', $MODULO_ID);
} else {
    requirePermission('crear', $MODULO_ID);
}

// 3. Obtener Datos (Si es Edición)
$v = [];
if ($isEdit) {
    // Seguridad Row-Level: Solo editar si es de mi área
    $filtroAreas = getAreaFilterSQL('area_id');
    $stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE id = ? AND $filtroAreas");
    $stmt->execute([$id]);
    $v = $stmt->fetch();

    if (!$v) {
        setFlashMessage('error', 'Vehículo no encontrado o no tienes permiso para esta área.');
        redirect('/modulos/vehiculos/');
    }
}

// Obtener Áreas Disponibles (Para asignar)
// Seguridad: El usuario solo puede asignar áreas a las que tiene acceso.
// Si es admin global, todas. Si es admin área, solo las suyas.
$filtroAreasSelect = getAreaFilterSQL('id');
$areas = $pdo->query("SELECT id, nombre_area FROM areas WHERE estado = 1 AND $filtroAreasSelect ORDER BY nombre_area")->fetchAll();

?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fas fa-car" style="color: var(--accent-blue);"></i>
                <?= $isEdit ? 'Editar Vehículo' : 'Nuevo Vehículo' ?>
            </h1>
        </div>
        <div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Regresar
            </a>
        </div>
    </div>

    <?= renderFlashMessage() ?>

    <?php
    $bajaInfo = null;
    if ($isEdit && ($v['activo'] == 0 || $v['en_proceso_baja'] == 'SI')) {
        $stmtBaja = $pdo->prepare("SELECT * FROM vehiculos_bajas WHERE vehiculo_origen_id = ? ORDER BY fecha_baja DESC LIMIT 1");
        $stmtBaja->execute([$v['id']]);
        $bajaInfo = $stmtBaja->fetch();
    }
    ?>
    
    <?php if ($bajaInfo): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert" style="background-color: #fde8e8; border-color: #fbd5d5; color: #9b1c1c;">
        <div class="row w-100">
            <div class="col-md-4">
                <strong>Fecha de Baja</strong><br>
                <div class="bg-white p-2 border rounded mt-1">
                    <?= date('d/m/Y', strtotime($bajaInfo['fecha_baja'])) ?>
                </div>
            </div>
            <div class="col-md-4">
                <strong>Año de Baja</strong><br>
                <div class="bg-white p-2 border rounded mt-1">
                    <?= date('Y', strtotime($bajaInfo['fecha_baja'])) ?>
                </div>
            </div>
            <div class="col-md-4">
                <strong>Motivo</strong><br>
                <div class="bg-white p-2 border rounded mt-1">
                    <?= e($bajaInfo['motivo_baja']) ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="save.php" method="POST" class="row g-3">
                <input type="hidden" name="id" value="<?= $v['id'] ?>">
                <?php $isEdit = true; include 'form_content.php'; ?>
            </form>
        </div>
    </div>

    <!-- Integración de Bitácora de Notas (Solo Edición) -->
    <?php if ($isEdit): ?>
    <div class="card mt-4">
        <div class="card-body">
            <?php 
                $vehiculo_id = $v['id'];
                $redirect_to = '/modulos/vehiculos/edit.php?id=' . $v['id'];
                // tipo_origen por defecto es 'ACTIVO', correcto para vehiculos.
                include 'notas/list.php'; 
            ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php if ($isEdit) include 'notas/modal_edit.php'; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
