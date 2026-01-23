<?php
/**
 * Módulo de Recursos Humanos - Listado de Empleados
 * Demuestra el filtrado por áreas y verificación de permisos
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAuth();

// ID del módulo de Recursos Humanos
define('MODULO_ID', 2);

// Obtener permisos del usuario para este módulo
$permisos = getUserPermissions(MODULO_ID);
$puedeVer = in_array('ver', $permisos);
$puedeCrear = in_array('crear', $permisos);
$puedeEditar = in_array('editar', $permisos);
$puedeEliminar = in_array('eliminar', $permisos);
$puedeExportar = in_array('exportar', $permisos);

// Si no puede ver, denegar acceso
if (!$puedeVer) {
    setFlashMessage('error', 'No tienes permiso para acceder a este módulo');
    redirect('/index.php');
}

$pdo = getConnection();
$user = getCurrentUser();
$areasUsuario = getUserAreas();

// Procesar eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar']) && $puedeEliminar) {
    $empleadoId = (int) $_POST['empleado_id'];

    // Verificar que el empleado pertenece a un área accesible
    $stmt = $pdo->prepare("SELECT area_id FROM empleados WHERE id = ?");
    $stmt->execute([$empleadoId]);
    $empleado = $stmt->fetch();

    if ($empleado && in_array($empleado['area_id'], $areasUsuario)) {
        $stmt = $pdo->prepare("UPDATE empleados SET estatus = 'Inactivo', activo = 0 WHERE id = ?");
        $stmt->execute([$empleadoId]);
        setFlashMessage('success', 'Empleado desactivado correctamente');
    } else {
        setFlashMessage('error', 'No tienes permiso para modificar este empleado');
    }
    redirect('/recursos-humanos/empleados.php');
}

// Filtros
$filtroArea = isset($_GET['area']) ? (int) $_GET['area'] : null;
$filtroBusqueda = isset($_GET['q']) ? sanitize($_GET['q']) : '';

// Construir consulta con filtrado por áreas del usuario
$sql = "
    SELECT 
        e.id, e.nombres as nombre, e.apellido_paterno, e.apellido_materno,
        e.email, e.telefono, e.estatus, e.activo,
        a.id as area_id, a.nombre_area,
        p.nombre as nombre_puesto
    FROM empleados e
    INNER JOIN areas a ON e.area_id = a.id
    INNER JOIN puestos_trabajo p ON e.puesto_trabajo_id = p.id
    WHERE e.activo = 1 AND " . getAreaFilterSQL('e.area_id');

$params = [];

// Aplicar filtro de área adicional (si el usuario seleccionó una específica)
if ($filtroArea && in_array($filtroArea, $areasUsuario)) {
    $sql .= " AND e.area_id = ?";
    $params[] = $filtroArea;
}

// Aplicar búsqueda
if ($filtroBusqueda) {
    $sql .= " AND (e.nombres LIKE ? OR e.apellido_paterno LIKE ? OR e.email LIKE ?)";
    $params[] = "%$filtroBusqueda%";
    $params[] = "%$filtroBusqueda%";
    $params[] = "%$filtroBusqueda%";
}

$sql .= " ORDER BY e.nombres";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$empleados = $stmt->fetchAll();

// Obtener áreas para el filtro (solo las que el usuario puede ver)
$areasParaFiltro = $pdo->query("
    SELECT * FROM areas 
    WHERE estado = 1 AND " . getAreaFilterSQL('id') . "
    ORDER BY nombre_area
")->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fas fa-users" style="color: var(--accent-primary);"></i>
                Gestión de Empleados
            </h1>
            <p class="page-description">
                Administra los empleados de tu organización
                <?php if (count($areasUsuario) < 5): ?>
                    <span class="badge badge-warning" style="margin-left: 0.5rem;">
                        <i class="fas fa-filter"></i>
                        Mostrando solo: <?= implode(', ', array_column($areasParaFiltro, 'nombre_area')) ?>
                    </span>
                <?php endif; ?>
            </p>
        </div>

        <?php if ($puedeCrear): ?>
            <a href="<?= url('/recursos-humanos/empleado-form.php') ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Empleado
            </a>
        <?php endif; ?>
    </div>

    <?= renderFlashMessage() ?>

    <!-- Información de permisos del usuario actual -->
    <div class="card"
        style="margin-bottom: 1.5rem; background: linear-gradient(135deg, rgba(88, 166, 255, 0.1) 0%, rgba(163, 113, 247, 0.1) 100%);">
        <div class="card-body" style="padding: 1rem 1.5rem;">
            <div
                style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <strong style="color: var(--text-primary);">
                        <i class="fas fa-user-shield"></i> Tus permisos en este módulo:
                    </strong>
                    <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <span class="badge <?= $puedeVer ? 'badge-success' : 'badge-danger' ?>">
                            <i class="fas fa-<?= $puedeVer ? 'check' : 'times' ?>"></i> Ver
                        </span>
                        <span class="badge <?= $puedeCrear ? 'badge-success' : 'badge-danger' ?>">
                            <i class="fas fa-<?= $puedeCrear ? 'check' : 'times' ?>"></i> Crear
                        </span>
                        <span class="badge <?= $puedeEditar ? 'badge-success' : 'badge-danger' ?>">
                            <i class="fas fa-<?= $puedeEditar ? 'check' : 'times' ?>"></i> Editar
                        </span>
                        <span class="badge <?= $puedeEliminar ? 'badge-success' : 'badge-danger' ?>">
                            <i class="fas fa-<?= $puedeEliminar ? 'check' : 'times' ?>"></i> Eliminar
                        </span>
                        <span class="badge <?= $puedeExportar ? 'badge-success' : 'badge-danger' ?>">
                            <i class="fas fa-<?= $puedeExportar ? 'check' : 'times' ?>"></i> Exportar
                        </span>
                    </div>
                </div>
                <div style="text-align: right;">
                    <strong style="color: var(--text-primary);">Áreas visibles:</strong>
                    <div style="color: var(--accent-secondary); font-size: 1.25rem; font-weight: 700;">
                        <?= count($areasUsuario) ?> de 5
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-body">
            <form method="GET" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="margin: 0; flex: 1; min-width: 200px;">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="q" class="form-control" placeholder="Nombre, apellido o email..."
                        value="<?= e($filtroBusqueda) ?>">
                </div>

                <?php if (count($areasParaFiltro) > 1): ?>
                    <div class="form-group" style="margin: 0; min-width: 200px;">
                        <label class="form-label">Área</label>
                        <select name="area" class="form-control">
                            <option value="">Todas mis áreas</option>
                            <?php foreach ($areasParaFiltro as $area): ?>
                                <option value="<?= $area['id'] ?>" <?= $filtroArea == $area['id'] ? 'selected' : '' ?>>
                                    <?= e($area['nombre_area']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Buscar
                </button>

                <?php if ($puedeExportar): ?>
                    <a href="<?= url('/recursos-humanos/exportar.php') ?>?<?= http_build_query($_GET) ?>"
                        class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Exportar
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Tabla de empleados -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Empleados encontrados: <strong><?= count($empleados) ?></strong>
            </h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Área</th>
                            <th>Puesto</th>
                            <th>Contacto</th>
                            <th>Estado</th>
                            <?php if ($puedeEditar || $puedeEliminar): ?>
                                <th style="width: 120px;">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($empleados)): ?>
                            <tr>
                                <td colspan="<?= ($puedeEditar || $puedeEliminar) ? 6 : 5 ?>"
                                    style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                    <i class="fas fa-users-slash"
                                        style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                    No se encontraron empleados en las áreas que tienes asignadas
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($empleados as $emp): ?>
                                <tr data-id="<?= $emp['id'] ?>">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <span
                                                style="width: 40px; height: 40px; background: var(--gradient-accent); 
                                                     border-radius: 50%; display: flex; align-items: center; 
                                                     justify-content: center; font-size: 0.85rem; color: white; flex-shrink: 0;">
                                                <?= strtoupper(substr($emp['nombre'], 0, 1) . substr($emp['apellido_paterno'], 0, 1)) ?>
                                            </span>
                                            <div>
                                                <div style="font-weight: 600;">
                                                    <?= e($emp['nombre'] . ' ' . $emp['apellido_paterno'] . ' ' . $emp['apellido_materno']) ?>
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--text-muted);">
                                                    ID: <?= $emp['id'] ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?= e($emp['nombre_area']) ?></span>
                                    </td>
                                    <td><?= e($emp['nombre_puesto']) ?></td>
                                    <td>
                                        <div style="font-size: 0.85rem;">
                                            <?php if ($emp['email']): ?>
                                                <div><i class="fas fa-envelope" style="width: 16px; color: var(--text-muted);"></i>
                                                    <?= e($emp['email']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($emp['telefono']): ?>
                                                <div><i class="fas fa-phone" style="width: 16px; color: var(--text-muted);"></i>
                                                    <?= e($emp['telefono']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge <?= $emp['estatus'] == 'Activo' ? 'badge-success' : 'badge-danger' ?>">
                                            <?= e($emp['estatus']) ?>
                                        </span>
                                    </td>
                                    <?php if ($puedeEditar || $puedeEliminar): ?>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <?php if ($puedeEditar): ?>
                                                    <a href="<?= url('/recursos-humanos/empleado-form.php?id=' . $emp['id']) ?>"
                                                        class="btn btn-sm btn-secondary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($puedeEliminar && $emp['estado'] == 1): ?>
                                                    <form method="POST" style="display: inline;"
                                                        onsubmit="return confirm('¿Desactivar este empleado?')">
                                                        <input type="hidden" name="empleado_id" value="<?= $emp['id'] ?>">
                                                        <button type="submit" name="eliminar" class="btn btn-sm btn-danger"
                                                            title="Desactivar">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>