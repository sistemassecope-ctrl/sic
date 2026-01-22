<?php
/**
 * Módulo: Gestión de Empleados (Directorio)
 * Ubicación: /modulos/recursos-humanos/empleados.php
 * Descripción: Listado avanzado de empleados con búsqueda y filtros de permisos
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getConnection();
$user = getCurrentUser();

// Permisos del Módulo de Empleados (ID 20 ó 2 segun se haya definido, usaremos 2 para RH general por ahora)
define('MODULO_ID', 2);
$permisos = getUserPermissions(MODULO_ID);

/*
 * Validación de Acceso:
 * 1. Admin Global
 * 2. Tiene permiso explícito de 'ver' o 'administrar' en el módulo
 * 3. Es Admin de Área (se verifica implícitamente al filtrar resultados)
 */
if (!isAdmin() && empty($permisos)) {
    // Si no es admin y no tiene permisos explícitos, verificar si tiene rol de sistema en su ficha de empleado
    if (($user['rol_sistema'] ?? 'usuario') === 'usuario') {
        setFlashMessage('error', 'No tienes permiso para acceder al directorio de empleados');
        redirect('/index.php');
    }
}

// Params de Búsqueda
$busqueda = sanitize($_GET['q'] ?? '');
$filtroArea = (int) ($_GET['area'] ?? 0);
$filtroEstatus = sanitize($_GET['estatus'] ?? 'ACTIVO');

// Construcción de Query
$where = ["1=1"];
$params = [];

// 1. Filtro de Seguridad por Área (Multi-tenancy lógico)
/*
 * Si NO es Admin Global, solo puede ver empleados de sus áreas asignadas.
 * La función getAreaFilterSQL maneja esto.
 */
if (!isAdmin()) {
    $where[] = getAreaFilterSQL('e.area_id');
}

// 2. Filtros de Usuario
if ($busqueda) {
    $where[] = "(e.nombres LIKE ? OR e.apellido_paterno LIKE ? OR e.apellido_materno LIKE ? OR e.numero_empleado LIKE ?)";
    $term = "%$busqueda%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($filtroArea > 0) {
    $where[] = "e.area_id = ?";
    $params[] = $filtroArea;
}

if ($filtroEstatus !== 'TODOS') {
    $where[] = "e.estatus = ?";
    $params[] = $filtroEstatus;
}

// Clausula Activo (Sistema)
$where[] = "e.activo = 1"; // Solo registros no eliminados lógicamente

$sql = "SELECT 
            e.id, 
            e.nombres, e.apellido_paterno, e.apellido_materno, 
            e.email, e.foto, e.numero_empleado, e.estatus,
            e.rol_sistema,
            a.nombre_area, 
            p.nombre as nombre_puesto 
        FROM empleados e 
        LEFT JOIN areas a ON e.area_id = a.id
        LEFT JOIN puestos_trabajo p ON e.puesto_trabajo_id = p.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY e.apellido_paterno ASC, e.nombres ASC
        LIMIT 100";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $empleados = $stmt->fetchAll();
} catch (PDOException $e) {
    $empleados = [];
    $errorBD = $e->getMessage();
}

// Para el select de áreas del filtro
$areasDisponibles = $pdo->query("SELECT id, nombre_area FROM areas WHERE estado = 1 AND " . getAreaFilterSQL('id') . " ORDER BY nombre_area")->fetchAll();

?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<style>
    .avatar-initials {
        width: 40px;
        height: 40px;
        background-color: var(--accent-primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.1rem;
        /* Actualización visual */
    }

    .role-badge {
        font-size: 0.7rem;
        padding: 0.2em 0.6em;
        border-radius: 10px;
        background: #e2e8f0;
        color: #475569;
        font-weight: 600;
    }

    .role-admin_global {
        background: #fee2e2;
        color: #991b1b;
    }

    /* Rojo suave */
    .role-admin_area {
        background: #fef3c7;
        color: #92400e;
    }

    /* Ambar */
    .role-SUPERADMIN {
        background: #e0e7ff;
        color: #3730a3;
    }

    /* Indigo suave */
    .estatus-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 5px;
    }

    .estatus-ACTIVO {
        background-color: #10b981;
    }

    .estatus-BAJA {
        background-color: #ef4444;
    }

    .estatus-LICENCIA {
        background-color: #f59e0b;
    }

    /* Filtros Premium */
    .search-filters-bar {
        display: flex;
        gap: 1rem;
        align-items: center;
        background: var(--bg-card);
        padding: 1.25rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-primary);
        margin-bottom: 2rem;
        box-shadow: var(--shadow-md);
    }

    .search-input-group {
        flex: 2;
        position: relative;
    }

    .search-input-group i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
    }

    .search-input-group input {
        padding-left: 2.75rem !important;
    }

    .filter-select-group {
        flex: 1;
    }

    .filter-select-group select {
        width: 100%;
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%238b949e' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 16px 12px;
        appearance: none;
    }

    .btn-clear {
        color: var(--text-muted);
        transition: color var(--transition-fast);
    }

    .btn-clear:hover {
        color: var(--accent-danger);
    }

    @media (max-width: 992px) {
        .search-filters-bar {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Directorio de Personal</h1>
            <p class="page-description">Gestión y control de expedientes digitales</p>
        </div>
        <div class="d-flex gap-2">
            <?php if (isAdmin()): ?>
                <button class="btn btn-warning" onclick="alert('Funcionalidad de Sincronización Remota Pendiente')">
                    <i class="fas fa-sync-alt"></i> Sincronizar
                </button>
            <?php endif; ?>
            <a href="empleado-form.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Nuevo Empleado
            </a>
        </div>
    </div>

    <?= renderFlashMessage() ?>

    <!-- Filtros y Búsqueda -->
    <form method="GET" class="search-filters-bar">
        <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input type="text" name="q" class="form-control" placeholder="Buscar por nombre o número..."
                value="<?= e($busqueda) ?>">
        </div>

        <div class="filter-select-group">
            <select name="area" class="form-control" onchange="this.form.submit()">
                <option value="0">Todas las Áreas</option>
                <?php foreach ($areasDisponibles as $area): ?>
                    <option value="<?= $area['id'] ?>" <?= $filtroArea == $area['id'] ? 'selected' : '' ?>>
                        <?= e($area['nombre_area']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-select-group">
            <select name="estatus" class="form-control" onchange="this.form.submit()">
                <option value="ACTIVO" <?= $filtroEstatus === 'ACTIVO' ? 'selected' : '' ?>>Solo Activos</option>
                <option value="BAJA" <?= $filtroEstatus === 'BAJA' ? 'selected' : '' ?>>Bajas</option>
                <option value="LICENCIA" <?= $filtroEstatus === 'LICENCIA' ? 'selected' : '' ?>>Licencias</option>
                <option value="TODOS" <?= $filtroEstatus === 'TODOS' ? 'selected' : '' ?>>Todos los estatus</option>
            </select>
        </div>

        <?php if ($busqueda || $filtroArea > 0 || $filtroEstatus !== 'ACTIVO'): ?>
            <a href="empleados.php" class="btn-clear" title="Limpiar Filtros">
                <i class="fas fa-times-circle fa-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <!-- Tabla de Resultados -->
    <div class="card" style="border:none; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 text-secondary text-uppercase"
                            style="font-size: 0.75rem; letter-spacing: 0.5px;">Empleado</th>
                        <th class="text-secondary text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            Adscripción</th>
                        <th class="text-secondary text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            Estatus / Rol</th>
                        <th class="text-end pe-4 text-secondary text-uppercase"
                            style="font-size: 0.75rem; letter-spacing: 0.5px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($empleados)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="text-muted mb-2"><i class="fas fa-users-slash fa-2x"></i></div>
                                <p class="mb-0">No se encontraron empleados con los filtros actuales.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($empleados as $emp):
                            $nombre = $emp['nombres'] ?? '';
                            $apellido = $emp['apellido_paterno'] ?? '';
                            $initials = strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($emp['foto'])): ?>
                                            <img src="<?= e($emp['foto']) ?>" class="rounded-circle me-3" width="40" height="40"
                                                style="object-fit:cover;">
                                        <?php else: ?>
                                            <div class="avatar-initials me-3 text-white bg-gradient"><?= $initials ?></div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold text-dark">
                                                <?= e($emp['apellido_paterno'] . ' ' . $emp['apellido_materno'] . ' ' . $emp['nombres']) ?>
                                            </div>
                                            <div class="small text-muted">
                                                <i class="fas fa-id-badge me-1"></i> <?= e($emp['numero_empleado'] ?? 'S/N') ?>
                                                <?php if ($emp['email']): ?>
                                                    <span class="mx-1">•</span> <?= e($emp['email']) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?= e($emp['nombre_puesto'] ?? 'Sin puesto asignado') ?>
                                    </div>
                                    <div class="small text-muted"><?= e($emp['nombre_area'] ?? 'Sin área asignada') ?></div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        <span class="badge rounded-pill bg-light text-dark border fw-normal">
                                            <span class="estatus-dot estatus-<?= $emp['estatus'] ?>"></span>
                                            <?= ucwords(strtolower($emp['estatus'])) ?>
                                        </span>

                                        <?php if (($emp['rol_sistema'] ?? 'usuario') !== 'usuario'): ?>
                                            <span class="role-badge role-<?= $emp['rol_sistema'] ?>">
                                                <?php
                                                // Traducción visual rápida
                                                echo $emp['rol_sistema'] === 'admin_global' ? 'Admin Global' :
                                                    ($emp['rol_sistema'] === 'SUPERADMIN' ? '🛡️ SUPERADMIN' :
                                                        ($emp['rol_sistema'] === 'admin_area' ? 'Admin Área' : $emp['rol_sistema']));
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="empleado-form.php?id=<?= $emp['id'] ?>"
                                            class="btn btn-outline-secondary btn-sm" title="Editar Expediente">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    // Inicializar colores aleatorios para avatares si se desea
    document.querySelectorAll('.avatar-initials').forEach(avatar => {
        const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
        const randomColor = colors[Math.floor(Math.random() * colors.length)];
        avatar.style.backgroundColor = randomColor;
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>