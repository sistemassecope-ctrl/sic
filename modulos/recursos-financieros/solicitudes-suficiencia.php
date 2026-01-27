<?php
/**
 * Módulo: Solicitudes de Suficiencia
 * Ubicación: /modulos/recursos-financieros/solicitudes-suficiencia.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// ID del módulo de Solicitudes de Suficiencia
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

// Fetch Solicitudes
$sql = "
    SELECT f.*, po.nombre_proyecto as proyecto_origen,
           (COALESCE(po.monto_federal,0) + COALESCE(po.monto_estatal,0) + COALESCE(po.monto_municipal,0) + COALESCE(po.monto_otros,0)) as total_proyecto,
           (SELECT SUM(monto_total_solicitado) FROM solicitudes_suficiencia f2 WHERE f2.id_proyecto = f.id_proyecto AND f2.estatus != 'CANCELADO') as total_comprometido_proy
    FROM solicitudes_suficiencia f
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
                $total_fua += (float) $f['monto_total_solicitado'];
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
                    <li class="breadcrumb-item active">Solicitudes de Suficiencia</li>
                </ol>
            </nav>
            <h1 class="page-title"><i class="fas fa-file-invoice-dollar text-primary"></i> Solicitud de Suficiencia</h1>
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
                    <a href="solicitud-suficiencia-form.php<?= $id_proyecto ? "?id_proyecto=$id_proyecto" : "" ?>"
                        class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Solicitud
                    </a>
                <?php endif; ?>
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

    <!-- Listado con Diseño de Renglones Estilizados -->
    <div class="management-grid" id="fuaTable">
        <?php if (empty($fuas)): ?>
            <div class="empty-state">
                <i class="fas fa-file-invoice-dollar fa-3x mb-3 text-muted"></i>
                <h3>No se encontraron suficiencias</h3>
                <p>Aún no se han registrado solicitudes en este proyecto o criterio.</p>
            </div>
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

                $semaf = 'text-muted';
                if ($f['total_proyecto'] > 0) {
                    $saldo_proy = $f['total_proyecto'] - $f['total_comprometido_proy'];
                    if ($f['total_comprometido_proy'] == 0)
                        $semaf = 'text-muted';
                    elseif ($saldo_proy > 0)
                        $semaf = 'text-warning';
                    elseif ($saldo_proy == 0)
                        $semaf = 'text-success';
                    else
                        $semaf = 'text-danger';
                }
                ?>
                <div class="management-row">
                    <!-- Paso 1: Folio e Identificación -->
                    <div class="row-step step-id">
                        <div class="status-indicator <?= $f['estatus'] == 'ACTIVO' ? 'bg-success' : 'bg-danger' ?>"></div>
                        <span class="solicitud-folio">#<?= $f['id_fua'] ?></span>
                        <div class="fw-bold x-small"><?= e($f['num_oficio_tramite'] ?? 'S/O') ?></div>
                        <div class="text-muted x-small"><?= e($f['folio_fua'] ?? 'S/F') ?></div>
                    </div>

                    <!-- Paso 2: Detalles del Proyecto -->
                    <div class="row-step step-details">
                        <h4 class="proyecto-name">
                            <?= e($f['nombre_proyecto_accion'] ?? $f['proyecto_origen'] ?? 'Sin nombre') ?></h4>
                        <?php if (isAdmin()): ?>
                            <div class="progress-container mt-2">
                                <div class="d-flex justify-content-between x-small mb-1">
                                    <span class="text-muted"><?= $progressLabel ?></span>
                                    <span class="fw-bold text-<?= $progressColor ?>"><?= $progress ?>%</span>
                                </div>
                                <div class="progress" style="height: 4px; background: rgba(255,255,255,0.05);">
                                    <div class="progress-bar bg-<?= $progressColor ?>" style="width: <?= $progress ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Paso 3: Importe y Financiero -->
                    <div class="row-step step-finance">
                        <div class="monto-box">
                            <span class="label">Total Solicitado</span>
                            <span class="value">$<?= number_format($f['monto_total_solicitado'], 2) ?></span>
                        </div>
                        <?php if (isAdmin()): ?>
                            <div class="x-small mt-1 text-muted">
                                <i class="fas fa-circle <?= $semaf ?> me-1"></i> Estado Financiero
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Paso 4: Acciones -->
                    <div class="row-step step-actions">
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <button type="button" class="btn-action-row secondary" onclick="prepararOficio(<?= $f['id_fua'] ?>)"
                                title="Generar Oficio">
                                <i class="fas fa-file-pdf"></i>
                            </button>
                            <?php if ($puedeEditar): ?>
                                <a href="solicitud-suficiencia-form.php?id=<?= $f['id_fua'] ?>" class="btn-action-row primary"
                                    title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($puedeEliminar): ?>
                                <button type="button" class="btn-action-row danger" onclick="confirmDelete(<?= $f['id_fua'] ?>)"
                                    title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
        flex: 0 0 240px;
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

    .monto-box .label {
        font-size: 0.6rem;
        text-transform: uppercase;
        color: var(--text-muted);
        display: block;
    }

    .monto-box .value {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--accent-primary);
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
    document.getElementById('searchFUA').addEventListener('keyup', function () {
        const term = this.value.toLowerCase();
        const rows = document.querySelectorAll('.management-row');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? 'flex' : 'none';
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
            window.location.href = 'solicitud-suficiencia-delete.php?id=' + id;
        }
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>