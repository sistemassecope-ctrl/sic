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

    <div class="card">
        <div class="card-body">
            <form action="save.php" method="POST" class="row g-3">
                <?php $isEdit = false; include 'form_content.php'; ?>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
