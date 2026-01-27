<?php
/**
 * Módulo: Proyectos de Obra (POA)
 * Ubicación: /modulos/recursos-financieros/proyectos.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// ID del módulo de Proyectos de Obra
define('MODULO_ID', 53);

// Obtener permisos del usuario para este módulo
$permisos_user = getUserPermissions(MODULO_ID);
$puedeVer = in_array('ver', $permisos_user);
$puedeCrear = in_array('crear', $permisos_user);
$puedeEditar = in_array('editar', $permisos_user);
$puedeEliminar = in_array('eliminar', $permisos_user);

// Si no puede ver, denegar acceso
if (!$puedeVer) {
    setFlashMessage('error', 'No tienes permiso para acceder a este módulo');
    redirect('modulos/recursos-financieros/poas.php');
}

$pdo = getConnection();
$user = getCurrentUser();

$id_programa = isset($_GET['id_programa']) ? (int) $_GET['id_programa'] : 0;

if ($id_programa === 0) {
    setFlashMessage('error', 'Programa no especificado.');
    redirect('modulos/recursos-financieros/poas.php');
}

// Información del Programa
$stmt_prog = $pdo->prepare("SELECT * FROM programas_anuales WHERE id_programa = ?");
$stmt_prog->execute([$id_programa]);
$programa = $stmt_prog->fetch();

if (!$programa) {
    setFlashMessage('error', 'El programa solicitado no existe.');
    redirect('modulos/recursos-financieros/poas.php');
}

// Filtro de Áreas
$areaFilter = getAreaFilterSQL('p.id_unidad_responsable');

// Listado de Proyectos
$stmt_proy = $pdo->prepare("
    SELECT p.*, m.nombre_municipio,
           (SELECT COUNT(*) FROM solicitudes_suficiencia f WHERE f.id_proyecto = p.id_proyecto) as num_fuas,
           (SELECT SUM(monto_total_solicitado) FROM solicitudes_suficiencia f WHERE f.id_proyecto = p.id_proyecto AND f.estatus != 'CANCELADO') as total_fua
    FROM proyectos_obra p
    LEFT JOIN cat_municipios m ON p.id_municipio = m.id_municipio
    WHERE p.id_programa = ? AND $areaFilter
    ORDER BY p.id_proyecto DESC
");
$stmt_proy->execute([$id_programa]);
$proyectos = $stmt_proy->fetchAll();

// Totales
$total_inversion = 0;
foreach ($proyectos as $p) {
    $total_inversion += (float) $p['monto_total'];
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="poas.php">Programas Operativos</a></li>
                    <li class="breadcrumb-item active">
                        <?= e($programa['nombre']) ?>
                    </li>
                </ol>
            </nav>
            <h1 class="page-title"><i class="fas fa-project-diagram text-primary"></i> Proyectos de Obra</h1>
            <p class="page-description">Listado de acciones programadas para el ejercicio
                <?= $programa['ejercicio'] ?>
            </p>
        </div>
        <?php if ($puedeCrear): ?>
            <div class="page-actions">
                <a href="proyecto-form.php?id_programa=<?= $id_programa ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Proyecto
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?= renderFlashMessage() ?>

    <div class="row stats-grid">
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">$
                    <?= number_format($total_inversion, 2) ?>
                </div>
                <div class="stat-label">Inversión Total Programada</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">
                    <?= count($proyectos) ?>
                </div>
                <div class="stat-label">Proyectos Registrados</div>
            </div>
        </div>
    </div>

    <div class="card mb-4"
        style="background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-primary);">
        <div class="card-body">
            <div class="input-group" style="max-width: 400px; position: relative;">
                <span
                    style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 5;">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="searchInput" class="form-control"
                    placeholder="Filtrar por nombre, municipio o ID..." style="padding-left: 2.75rem;">
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <!-- Listado con Diseño de Renglones Estilizados -->
            <div class="management-grid" id="proyectosGrid">
                <?php if (empty($proyectos)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                        <h3>No hay proyectos registrados</h3>
                        <p>Aún no se han dado de alta obras en este programa.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($proyectos as $p): ?>
                        <?php
                        $monto_total = (float) $p['monto_total'];
                        $total_fua = (float) ($p['total_fua'] ?? 0);
                        $saldo = $monto_total - $total_fua;

                        $semaf_class = 'text-muted';
                        if ($total_fua > 0) {
                            if ($saldo > 0)
                                $semaf_class = 'text-warning';
                            elseif ($saldo == 0)
                                $semaf_class = 'text-success';
                            else
                                $semaf_class = 'text-danger';
                        }
                        ?>
                        <div class="management-row"
                            ondblclick="window.location.href='proyecto-form.php?id=<?= $p['id_proyecto'] ?>'">
                            <!-- Paso 1: Folio e Identificación -->
                            <div class="row-step step-id">
                                <div class="status-indicator bg-primary"></div>
                                <span class="solicitud-folio">ID #<?= $p['id_proyecto'] ?></span>
                                <div class="x-small text-muted mt-1"><i
                                        class="fas fa-map-marker-alt me-1"></i><?= e($p['nombre_municipio'] ?? 'N/A') ?></div>
                            </div>

                            <!-- Paso 2: Detalles del Proyecto -->
                            <div class="row-step step-details">
                                <h4 class="proyecto-name"><?= e($p['nombre_proyecto']) ?></h4>
                                <div class="area-badge"><i class="fas fa-info-circle me-1"></i><?= e($p['breve_descripcion']) ?>
                                </div>
                            </div>

                            <!-- Paso 3: Semáforo de Salud Presupuestal -->
                            <div class="row-step step-health" style="flex: 0 0 250px;">
                                <div class="health-container" style="width: 100%;">
                                    <?php
                                    $porcentaje = ($monto_total > 0) ? ($total_fua / $monto_total) * 100 : 0;
                                    $status_color = 'success';
                                    $status_label = 'Saludable';

                                    if ($total_fua == 0) {
                                        $status_color = 'info';
                                        $status_label = 'Sin Ejercer';
                                    } elseif ($porcentaje >= 100) {
                                        $status_color = 'danger';
                                        $status_label = 'Presupuesto Agotado';
                                    } elseif ($porcentaje >= 80) {
                                        $status_color = 'warning';
                                        $status_label = 'Atención (80%+)';
                                    }
                                    ?>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge border border-<?= $status_color ?> text-<?= $status_color ?>"
                                            style="font-size: 0.65rem; padding: 2px 8px; background: none;">
                                            <i class="fas fa-circle me-1"
                                                style="font-size: 0.5rem; vertical-align: middle;"></i> <?= $status_label ?>
                                        </span>
                                        <span class="percentage-value"
                                            style="font-size: 0.75rem; font-weight: 700; color: var(--text-primary);">
                                            <?= round($porcentaje, 1) ?>%
                                        </span>
                                    </div>
                                    <div class="progress"
                                        style="height: 6px; background: rgba(255,255,255,0.08); border-radius: 10px;">
                                        <div class="progress-bar bg-<?= $status_color ?> shadow-sm"
                                            style="width: <?= min($porcentaje, 100) ?>%"></div>
                                    </div>
                                    <div class="mt-2 d-flex justify-content-between x-small" style="font-size: 0.65rem;">
                                        <span class="text-muted">Disp.</span>
                                        <span class="fw-bold <?= $saldo < 0 ? 'text-danger' : 'text-success' ?>">
                                            $<?= number_format($saldo, 2) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Paso 4: Acciones -->
                            <div class="row-step step-actions">
                                <div class="d-flex gap-2">
                                    <a href="solicitudes-suficiencia.php?id_proyecto=<?= $p['id_proyecto'] ?>"
                                        class="btn-action-row secondary" title="Ver Solicitudes">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                    <?php if ($puedeEditar): ?>
                                        <a href="proyecto-form.php?id=<?= $p['id_proyecto'] ?>" class="btn-action-row primary"
                                            title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($puedeEliminar): ?>
                                        <button type="button" class="btn-action-row danger"
                                            onclick="confirmDelete(<?= $p['id_proyecto'] ?>)" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<style>
    /* Management Row Staircase Flow */
    .management-grid {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .management-row {
        background: var(--bg-card);
        border: 1px solid var(--border-primary);
        border-radius: 12px;
        display: flex;
        align-items: stretch;
        overflow: hidden;
        transition: all 0.3s;
        min-height: 90px;
        cursor: pointer;
    }

    .management-row:hover {
        transform: translateX(10px);
        border-color: var(--accent-primary);
        box-shadow: -5px 5px 15px rgba(0, 0, 0, 0.2);
    }

    .row-step {
        padding: 1rem 1.25rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: var(--bg-tertiary);
        position: relative;
        clip-path: polygon(calc(100% - 15px) 0%, 100% 50%, calc(100% - 15px) 100%, 0% 100%, 15px 50%, 0% 0%);
        margin-left: -12px;
    }

    .row-step:first-child {
        margin-left: 0;
        clip-path: polygon(calc(100% - 15px) 0%, 100% 50%, calc(100% - 15px) 100%, 0% 100%, 0% 50%, 0% 0%);
        padding-left: 1.5rem;
        flex: 0 0 160px;
    }

    .row-step.step-details {
        flex: 1;
        background: rgba(255, 255, 255, 0.02);
    }

    .row-step.step-finance {
        background: rgba(255, 255, 255, 0.01);
    }

    .row-step.step-actions {
        flex: 0 0 160px;
        clip-path: polygon(100% 0%, 100% 50%, 100% 100%, 0% 100%, 15px 50%, 0% 0%);
        background: var(--bg-secondary);
        align-items: center;
    }

    .status-indicator {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
    }

    .solicitud-folio {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        color: var(--accent-primary);
        font-size: 0.9rem;
    }

    .proyecto-name {
        font-size: 1rem;
        font-weight: 700;
        margin: 0;
        color: var(--text-primary);
        line-height: 1.2;
    }

    .area-badge {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .monto-box .label {
        font-size: 0.6rem;
        text-transform: uppercase;
        color: var(--text-muted);
        display: block;
    }

    .monto-box .value {
        font-size: 1.1rem;
        font-weight: 800;
        color: #fff;
    }

    .btn-action-row {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        transition: all 0.2s;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }

    .btn-action-row.primary {
        background: rgba(88, 166, 255, 0.1);
        color: var(--accent-primary);
    }

    .btn-action-row.secondary {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-secondary);
    }

    .btn-action-row.danger {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }

    .btn-action-row:hover {
        transform: scale(1.1);
        filter: brightness(1.2);
    }

    .empty-state {
        text-align: center;
        padding: 5rem;
        background: var(--bg-card);
        border: 2px dashed var(--border-primary);
        border-radius: 20px;
    }
</style>

<script>
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const term = this.value.toLowerCase();
        const rows = document.querySelectorAll('.management-row');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? 'flex' : 'none';
        });
    });

    function confirmDelete(id) {
        if (confirm('¿Está seguro de eliminar este proyecto y toda su información relacionada?')) {
            window.location.href = 'proyecto-delete.php?id=' + id + '&id_programa=<?= $id_programa ?>';
        }
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>