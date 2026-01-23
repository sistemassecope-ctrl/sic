<?php
/**
 * Módulo: Suficiencias Presupuestales (FUAs)
 * Ubicación: /modulos/recursos-financieros/fuas.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// ID del módulo de Suficiencias (FUAs)
define('MODULO_ID', 54);

// Obtener permisos del usuario para este módulo
$permisos_user = getUserPermissions(MODULO_ID);
$puedeCrear = in_array('crear', $permisos_user);
$puedeEditar = in_array('editar', $permisos_user);
$puedeEliminar = in_array('eliminar', $permisos_user);

$pdo = getConnection();
$user = getCurrentUser();

$id_proyecto = isset($_GET['id_proyecto']) ? (int) $_GET['id_proyecto'] : null;

// Filtro de Áreas para proyectos vinculados
$areaFilter = getAreaFilterSQL('po.id_unidad_responsable');

// Fetch FUAs
$sql = "
    SELECT f.*, po.nombre_proyecto as proyecto_origen,
           (COALESCE(po.monto_federal,0) + COALESCE(po.monto_estatal,0) + COALESCE(po.monto_municipal,0) + COALESCE(po.monto_otros,0)) as total_proyecto,
           (SELECT SUM(importe) FROM fuas f2 WHERE f2.id_proyecto = f.id_proyecto AND f2.estatus != 'CANCELADO') as total_comprometido_proy
    FROM fuas f
    LEFT JOIN proyectos_obra po ON f.id_proyecto = po.id_proyecto
    WHERE ($areaFilter OR f.id_proyecto IS NULL)
";

$params = [];
if ($id_proyecto) {
    $sql .= " AND f.id_proyecto = :id_proyecto";
    $params['id_proyecto'] = $id_proyecto;
}

$sql .= " ORDER BY f.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fuas = $stmt->fetchAll();

// Info de Presupuesto si hay filtro
$budget_info = null;
if ($id_proyecto) {
    $stmtB = $pdo->prepare("
        SELECT 
            (COALESCE(monto_federal,0) + COALESCE(monto_estatal,0) + COALESCE(monto_municipal,0) + COALESCE(monto_otros,0)) as total_proyecto,
            nombre_proyecto
        FROM proyectos_obra 
        WHERE id_proyecto = ?
    ");
    $stmtB->execute([$id_proyecto]);
    $budget_info = $stmtB->fetch();

    if ($budget_info) {
        $total_fua = 0;
        foreach ($fuas as $f) {
            if ($f['estatus'] !== 'CANCELADO') {
                $total_fua += (float) $f['importe'];
            }
        }
        $budget_info['total_comprometido'] = $total_fua;
        $budget_info['saldo_disponible'] = $budget_info['total_proyecto'] - $total_fua;
    }
}

// Empleados para Oficio
$empleados = $pdo->query("
    SELECT e.*, p.nombre as puesto_nombre,
           CONCAT(COALESCE(e.nombres, ''), ' ', COALESCE(e.apellido_paterno, ''), ' ', COALESCE(e.apellido_materno, '')) as nombre_completo
    FROM empleados e 
    LEFT JOIN puestos_trabajo p ON e.puesto_trabajo_id = p.id 
    WHERE e.activo = 1
    ORDER BY e.apellido_paterno, e.nombres
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#">Recursos Financieros</a></li>
                    <li class="breadcrumb-item active">Suficiencias Presupuestales</li>
                </ol>
            </nav>
            <h1 class="page-title"><i class="fas fa-file-invoice-dollar text-primary"></i> Suficiencias (FUAs)</h1>
            <?php if ($budget_info): ?>
                <p class="page-description">Proyecto: <span class="text-primary fw-bold">
                        <?= e($budget_info['nombre_proyecto']) ?>
                    </span></p>
            <?php else: ?>
                <p class="page-description">Control de trámites administrativos y financieros</p>
            <?php endif; ?>
        </div>
        <div class="page-actions">
            <div class="btn-group">
                <?php if ($puedeCrear): ?>
                    <a href="fua-form.php<?= $id_proyecto ? "?id_proyecto=$id_proyecto" : "" ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Suficiencia
                    </a>
                <?php endif; ?>
                <a href="fua-carpeta.php<?= $id_proyecto ? "?id_proyecto=$id_proyecto" : "" ?>" class="btn btn-primary
                    border-start">
                    <i class="fas fa-folder-open"></i>
                </a>
            </div>
        </div>
    </div>

    <?= renderFlashMessage() ?>

    <?php if ($budget_info): ?>
        <div class="row stats-grid mb-4">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="fas fa-vault"></i></div>
                <div class="stat-content">
                    <div class="stat-value">$
                        <?= number_format($budget_info['total_proyecto'], 2) ?>
                    </div>
                    <div class="stat-label">Presupuesto del Proyecto</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info"><i class="fas fa-file-signature"></i></div>
                <div class="stat-content">
                    <div class="stat-value text-info">$
                        <?= number_format($budget_info['total_comprometido'], 2) ?>
                    </div>
                    <div class="stat-label">Total Comprometido</div>
                </div>
            </div>
            <div class="stat-card">
                <?php $saldoClass = $budget_info['saldo_disponible'] < 0 ? 'text-danger' : 'text-success'; ?>
                <div class="stat-icon <?= $budget_info['saldo_disponible'] < 0 ? 'danger' : 'success' ?>"><i
                        class="fas fa-coins"></i></div>
                <div class="stat-content">
                    <div class="stat-value <?= $saldoClass ?>">$
                        <?= number_format($budget_info['saldo_disponible'], 2) ?>
                    </div>
                    <div class="stat-label">Saldo Disponible</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <div class="input-group" style="max-width: 400px; position: relative;">
                <span
                    style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 5;">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="searchFUA" class="form-control" placeholder="Buscar por folio o proyecto..."
                    style="padding-left: 2.75rem;">
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover" id="fuaTable">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Folio / Tipo</th>
                            <th>Proyecto</th>
                            <th>Estatus</th>
                            <th>Etapa Administrativa</th>
                            <th class="text-end">Importe</th>
                            <th class="text-center">Financiero</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fuas)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No se encontraron suficiencias.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fuas as $f): ?>
                                <?php
                                $progress = 0;
                                $progressLabel = 'Inicial';
                                $progressColor = 'secondary';
                                if (!empty($f['fecha_respuesta_sfa'])) {
                                    $progress = 100;
                                    $progressLabel = 'Finalizado';
                                    $progressColor = 'success';
                                } elseif (!empty($f['fecha_acuse_antes_fa'])) {
                                    $progress = 85;
                                    $progressLabel = 'Tramite SFyA';
                                    $progressColor = 'primary';
                                } elseif (!empty($f['fecha_firma_regreso'])) {
                                    $progress = 70;
                                    $progressLabel = 'Firmas Listas';
                                    $progressColor = 'info';
                                } elseif (!empty($f['fecha_titular'])) {
                                    $progress = 50;
                                    $progressLabel = 'Firma Titular';
                                    $progressColor = 'warning';
                                } elseif (!empty($f['fecha_ingreso_cotrl_ptal'])) {
                                    $progress = 30;
                                    $progressLabel = 'Control Ptal.';
                                    $progressColor = 'warning';
                                } elseif (!empty($f['fecha_ingreso_admvo'])) {
                                    $progress = 15;
                                    $progressLabel = 'Admvo.';
                                    $progressColor = 'danger';
                                }

                                $monto_proy = (float) $f['total_proyecto'];
                                $comprom_proy = (float) $f['total_comprometido_proy'];
                                $saldo_proy = $monto_proy - $comprom_proy;
                                if ($monto_proy == 0)
                                    $semaf = 'text-muted';
                                elseif ($comprom_proy == 0)
                                    $semaf = 'text-muted';
                                elseif ($saldo_proy > 0)
                                    $semaf = 'text-warning';
                                elseif ($saldo_proy == 0)
                                    $semaf = 'text-success';
                                else
                                    $semaf = 'text-danger';
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold">#
                                        <?= $f['id_fua'] ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold">
                                            <?= e($f['folio_fua'] ?? 'S/F') ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= e($f['tipo_suficiencia']) ?>
                                        </small>
                                    </td>
                                    <td style="max-width: 250px;">
                                        <div class="text-truncate fw-medium"
                                            title="<?= e($f['nombre_proyecto_accion'] ?? $f['proyecto_origen']) ?>">
                                            <?= e($f['nombre_proyecto_accion'] ?? $f['proyecto_origen'] ?? 'Sin nombre') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $f['estatus'] == 'ACTIVO' ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $f['estatus'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="min-width: 120px;">
                                            <div class="d-flex justify-content-between x-small mb-1">
                                                <span class="text-muted">
                                                    <?= $progressLabel ?>
                                                </span>
                                                <span class="fw-bold text-<?= $progressColor ?>">
                                                    <?= $progress ?>%
                                                </span>
                                            </div>
                                            <div class="progress" style="height: 4px; background: rgba(255,255,255,0.05);">
                                                <div class="progress-bar bg-<?= $progressColor ?>"
                                                    style="width: <?= $progress ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-primary">$
                                        <?= number_format($f['importe'], 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <i class="fas fa-circle <?= $semaf ?>"></i>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-secondary"
                                                onclick="prepararOficio(<?= $f['id_fua'] ?>)" title="Generar Oficio">
                                                <i class="fas fa-file-pdf"></i>
                                            </button>
                                            <?php if ($puedeEditar): ?>
                                                <a href="fua-form.php?id=<?= $f['id_fua'] ?>" class="btn btn-sm btn-secondary"
                                                    title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($puedeEliminar): ?>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="confirmDelete(<?= $f['id_fua'] ?>)" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal Oficio -->
<div id="modalOficio" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3><i class="fas fa-file-signature text-primary"></i> Generar Oficio "Al Vuelo"</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="formOficio" target="_blank" action="generar-oficio.php" method="GET">
            <input type="hidden" name="id" id="modal_id_fua">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">DESTINATARIO</h6>
                        <div class="form-group">
                            <label class="form-label">Nombre y Título</label>
                            <input type="text" name="dest_nom" class="form-control" value="C.P. MARLEN SÁNCHEZ GARCÍA">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cargo</label>
                            <input type="text" name="dest_car" class="form-control" value="DIRECTORA DE ADMINISTRACIÓN">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">REMITENTE</h6>
                        <div class="form-group">
                            <label class="form-label">Nombre y Título</label>
                            <input type="text" name="rem_nom" class="form-control"
                                value="ING. CÉSAR OTHÓN RODRÍGUEZ GÓMEZ">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cargo</label>
                            <input type="text" name="rem_car" class="form-control"
                                value="SUBSECRETARIO DE INFRAESTRUCTURA CARRETERA">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cerrar</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Generar PDF</button>
            </div>
        </form>
    </div>
</div>

<style>
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        backdrop-filter: blur(4px);
    }

    .modal-content {
        background: var(--bg-card);
        border: 1px solid var(--border-primary);
        border-radius: var(--radius-lg);
        width: 100%;
        box-shadow: var(--shadow-lg);
    }

    .modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-primary);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--text-muted);
        cursor: pointer;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--border-primary);
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .x-small {
        font-size: 0.75rem;
    }
</style>

<script>
    document.getElementById('searchFUA').addEventListener('keyup', function () {
        const term = this.value.toLowerCase();
        const rows = document.querySelectorAll('#fuaTable tbody tr');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    });

    function prepararOficio(id) {
        document.getElementById('modal_id_fua').value = id;
        document.getElementById('modalOficio').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('modalOficio').style.display = 'none';
    }

    function confirmDelete(id) {
        if (confirm('¿Está seguro de eliminar este registro?')) {
            window.location.href = 'fua-delete.php?id=' + id;
        }
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>