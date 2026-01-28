<?php
/**
 * Módulo: Bandeja de Gestión de Suficiencias
 * Ubicación: /modulos/recursos-financieros/bandeja-gestion.php
 * Descripción: Dashboard para el área gestora con estados y prioridades.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// ID del módulo (Usamos el mismo que Solicitudes o uno nuevo si prefieres, 54 es Solicitudes)
define('MODULO_ID', 54);

// Obtener permisos del usuario para este módulo
$permisos_user = getUserPermissions(MODULO_ID);
$puedeVer = in_array('ver', $permisos_user);

if (!$puedeVer) {
    setFlashMessage('error', 'No tienes permiso para acceder a la bandeja de gestión.');
    redirect('/index.php');
}

$pdo = getConnection();
$user = getCurrentUser();

// Filtro de Áreas para proyectos vinculados
$areaFilter = getAreaFilterSQL('po.id_unidad_responsable');

// --- Lógica de Filtros por Tab ---
$tab = isset($_GET['tab']) ? (int) $_GET['tab'] : 1; // Por defecto: Autorizado en POA

// Obtener catálogo de momentos para los contadores
$momentos = $pdo->query("SELECT * FROM cat_momentos_suficiencia WHERE activo = 1 ORDER BY orden ASC")->fetchAll();

// Contadores por momento de gestión
// Tab 1: Proyectos en POA sin solicitud o en etapa 1 (que no tengan solicitud en etapas > 1)
$countTab1 = $pdo->query("
    SELECT COUNT(*) 
    FROM proyectos_obra po
    WHERE ($areaFilter)
      AND po.id_proyecto NOT IN (
          SELECT id_proyecto FROM solicitudes_suficiencia WHERE id_momento_gestion > 1 AND estatus = 'ACTIVO'
      )
")->fetchColumn();

// Tabs 2-6: Basados únicamente en solicitudes_suficiencia
$statsRequests = $pdo->query("
    SELECT f.id_momento_gestion, COUNT(*) as total 
    FROM solicitudes_suficiencia f
    LEFT JOIN proyectos_obra po ON f.id_proyecto = po.id_proyecto
    WHERE f.estatus = 'ACTIVO' AND ($areaFilter OR f.id_proyecto IS NULL)
    GROUP BY f.id_momento_gestion
")->fetchAll(PDO::FETCH_KEY_PAIR);

$statsByMomento = $statsRequests;
$statsByMomento[1] = $countTab1;

// Contadores para las tarjetas superiores (KPIs generales)
$stats = $pdo->query("
    SELECT 
        SUM(CASE WHEN f.id_momento_gestion < 6 THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN f.id_momento_gestion = 6 THEN 1 ELSE 0 END) as committed,
        SUM(CASE WHEN f.id_momento_gestion = 1 THEN 1 ELSE 0 END) as new_entries,
        SUM(CASE WHEN f.estatus = 'ACTIVO' THEN f.monto_total_solicitado ELSE 0 END) as total_monto
    FROM solicitudes_suficiencia f
    LEFT JOIN proyectos_obra po ON f.id_proyecto = po.id_proyecto
    WHERE f.estatus = 'ACTIVO' AND ($areaFilter OR f.id_proyecto IS NULL)
")->fetch(PDO::FETCH_ASSOC);

// Consulta principal según el TAB (Momento de Gestión)
if ($tab === 1) {
    $sql = "
        SELECT 
            po.id_proyecto, 
            po.nombre_proyecto, 
            f.nombre_proyecto_accion,
            po.monto_total as monto_total_solicitado,
            a.nombre_area,
            f.id_fua,
            f.num_oficio_tramite,
            f.created_at,
            f.id_momento_gestion,
            m.nombre as momento_nombre,
            m.color as momento_color,
            d.id as documento_id,
            d.fase_actual as doc_fase,
            d.estatus as doc_estatus,
            (SELECT df.id FROM documento_flujo_firmas df WHERE df.documento_id = d.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_flow_id,
            (SELECT df.tipo_firma FROM documento_flujo_firmas df WHERE df.documento_id = d.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_flow_type
        FROM proyectos_obra po
        LEFT JOIN areas a ON po.id_unidad_responsable = a.id
        LEFT JOIN solicitudes_suficiencia f ON po.id_proyecto = f.id_proyecto AND f.estatus = 'ACTIVO'
        LEFT JOIN documentos d ON d.tipo_documento_id = 1 AND JSON_UNQUOTE(JSON_EXTRACT(d.contenido_json, '$.id_fua')) = f.id_fua
        LEFT JOIN cat_momentos_suficiencia m ON COALESCE(f.id_momento_gestion, 1) = m.id
        WHERE ($areaFilter)
          AND (f.id_momento_gestion IS NULL OR f.id_momento_gestion = 1)
          AND po.id_proyecto NOT IN (
              SELECT id_proyecto FROM solicitudes_suficiencia WHERE id_momento_gestion > 1 AND estatus = 'ACTIVO'
          )
        ORDER BY po.id_proyecto DESC
    ";
} else {
    $sql = "
        SELECT f.*, po.nombre_proyecto, a.nombre_area, m.nombre as momento_nombre, m.color as momento_color,
               d.id as documento_id, d.fase_actual as doc_fase, d.estatus as doc_estatus,
                (SELECT df.id FROM documento_flujo_firmas df WHERE df.documento_id = d.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_flow_id,
                (SELECT df.tipo_firma FROM documento_flujo_firmas df WHERE df.documento_id = d.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_flow_type
        FROM solicitudes_suficiencia f
        LEFT JOIN proyectos_obra po ON f.id_proyecto = po.id_proyecto
        LEFT JOIN areas a ON po.id_unidad_responsable = a.id
        LEFT JOIN documentos d ON d.tipo_documento_id = 1 AND JSON_UNQUOTE(JSON_EXTRACT(d.contenido_json, '$.id_fua')) = f.id_fua
        LEFT JOIN cat_momentos_suficiencia m ON f.id_momento_gestion = m.id
        WHERE f.estatus = 'ACTIVO' AND f.id_momento_gestion = $tab AND ($areaFilter OR f.id_proyecto IS NULL)
        ORDER BY f.created_at ASC
    ";
}

$solicitudes = $pdo->query($sql)->fetchAll();

// Roles y permisos específicos para la vista
$puedeEditar = in_array('editar', $permisos_user);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-inbox text-primary me-2"></i>Bandeja de Gestión de Suficiencias</h1>
            <p class="text-muted">Centro de control para el seguimiento y trámite de recursos financieros.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="solicitudes-suficiencia.php" class="btn btn-outline-secondary">
                <i class="fas fa-list me-1"></i> Ver todas
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="kpi-card glass-vibrant-bg">
                <div class="kpi-icon pending"><i class="fas fa-file-signature"></i></div>
                <div class="kpi-data">
                    <span class="kpi-value">
                        <?= number_format($stats['new_entries'] ?? 0) ?>
                    </span>
                    <span class="kpi-label">Nuevas en POA</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card glass-vibrant-bg">
                <div class="kpi-icon info"><i class="fas fa-sync"></i></div>
                <div class="kpi-data">
                    <span class="kpi-value">
                        <?= number_format($stats['in_progress'] ?? 0) ?>
                    </span>
                    <span class="kpi-label">En Proceso de Gestión</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card glass-vibrant-bg">
                <div class="kpi-icon success"><i class="fas fa-check-double"></i></div>
                <div class="kpi-data">
                    <span class="kpi-value">
                        <?= number_format($stats['committed'] ?? 0) ?>
                    </span>
                    <span class="kpi-label">Con Suficiencia (Finalized)</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card glass-vibrant-bg">
                <div class="kpi-icon warning"><i class="fas fa-dollar-sign"></i></div>
                <div class="kpi-data">
                    <span class="kpi-value">$
                        <?= number_format(($stats['total_monto'] ?? 0) / 1000000, 1) ?>M
                    </span>
                    <span class="kpi-label">Monto Total en Trámite</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs de Navegación -->
    <div class="custom-tabs mb-4" style="overflow-x: auto; white-space: nowrap;">
        <?php foreach ($momentos as $m): ?>
            <a href="?tab=<?= $m['id'] ?>" class="tab-item <?= $tab == $m['id'] ? 'active' : '' ?>"
                title="<?= e($m['descripcion']) ?>">
                <span class="tab-number"><?= $m['orden'] ?>.</span> <?= e($m['nombre']) ?>
                <?php if (isset($statsByMomento[$m['id']]) && $statsByMomento[$m['id']] > 0): ?>
                    <span class="badge bg-danger ms-2" style="font-size: 0.6rem;">
                        <?= $statsByMomento[$m['id']] ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Listado de Solicitudes -->
    <div class="management-grid">
        <?php if (empty($solicitudes)): ?>
            <div class="empty-state">
                <i class="fas fa-coffee fa-3x mb-3 text-muted"></i>
                <h3>¡Todo al día!</h3>
                <p>No hay solicitudes pendientes en esta sección.</p>
            </div>
        <?php else: ?>
            <?php foreach ($solicitudes as $s):
                $now = new DateTime();
                $created = !empty($s['created_at']) ? new DateTime($s['created_at']) : $now;
                $diff = $now->diff($created);
                $days = $diff->days;

                $priorityClass = 'priority-low';
                if ($days > 2)
                    $priorityClass = 'priority-medium';
                if ($days > 5)
                    $priorityClass = 'priority-high';

                $editLink = $s['id_fua']
                    ? "solicitud-suficiencia-form.php?id=" . $s['id_fua']
                    : "solicitud-suficiencia-form.php?id_proyecto=" . $s['id_proyecto'];
                ?>
                <div class="management-row <?= $priorityClass ?>">
                    <!-- Paso 1: Folio e Identificación -->
                    <div class="row-step step-id">
                        <div class="status-indicator"></div>
                        <span class="solicitud-folio">#<?= e($s['num_oficio_tramite'] ?: ($s['id_fua'] ?: 'S/F')) ?></span>
                        <span class="time-elapsed"><i
                                class="far fa-clock me-1"></i><?= $s['id_fua'] ? "Hace $days días" : 'Pendiente' ?></span>
                    </div>

                    <!-- Paso 2: Información del Proyecto -->
                    <div class="row-step step-details">
                        <div class="d-flex justify-content-between">
                            <h4 class="proyecto-name"><?= e($s['nombre_proyecto_accion'] ?: $s['nombre_proyecto']) ?></h4>
                            <span class="badge"
                                style="background: <?= $s['momento_color'] ?>1a; color: <?= $s['momento_color'] ?>; border: 1px solid <?= $s['momento_color'] ?>33; font-size: 0.65rem; height: fit-content; margin-top: 5px;">
                                <?= e($s['momento_nombre']) ?>
                            </span>
                        </div>
                        <div class="area-badge"><i
                                class="fas fa-building me-1"></i><?= e($s['nombre_area'] ?: 'Área no asignada') ?></div>
                    </div>

                    <!-- Paso 3: Importe -->
                    <div class="row-step step-finance">
                        <div class="monto-box">
                            <span class="label">Importe Solicitado</span>
                            <span class="value">$<?= number_format($s['monto_total_solicitado'], 2) ?></span>
                        </div>
                    </div>

                    <!-- Paso 4: Acciones -->
                    <div class="row-step step-actions">
                        <div class="d-flex gap-2">
                            <?php if ($puedeEditar): ?>
                                <a href="<?= $editLink ?>" class="btn-action-row primary"
                                    title="<?= $s['id_fua'] ? 'Gestionar' : 'Iniciar Trámite' ?>">
                                    <i class="fas <?= $s['id_fua'] ? 'fa-edit' : 'fa-plus' ?>"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($s['id_fua']): ?>
                                <?php if (!empty($s['pending_flow_id'])): ?>
                                    <button type="button" class="btn-action-row warning pulse-btn"
                                        onclick="openSignatureModal(<?= $s['pending_flow_id'] ?>, '<?= e($s['num_oficio_tramite']) ?>', '<?= e($s['pending_flow_type']) ?>')"
                                        title="Firmar Documento Pendiente">
                                        <?php if ($s['pending_flow_type'] == 'autografa'): ?>
                                            <i class="fas fa-file-pen"></i>
                                        <?php else: ?>
                                            <i class="fas fa-pen-fancy"></i>
                                        <?php endif; ?>
                                    </button>
                                <?php endif; ?>

                                <?php if ($s['documento_id']): ?>
                                    <button type="button" class="btn-action-row info" onclick="showTimeline(<?= $s['documento_id'] ?>)"
                                        title="Ver Trazabilidad Documental">
                                        <i class="fas fa-history"></i>
                                    </button>
                                <?php endif; ?>
                                <a href="generar-oficio.php?id=<?= $s['id_fua'] ?>" target="_blank" class="btn-action-row secondary"
                                    title="Oficio">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<style>
    /* Premium Look & Feel Styles */
    :root {
        --priority-high: #ef4444;
        --priority-medium: #f59e0b;
        --priority-low: #10b981;
    }

    .kpi-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .kpi-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    .kpi-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .kpi-icon.pending {
        background: rgba(88, 166, 255, 0.1);
        color: #58a6ff;
    }

    .kpi-icon.success {
        background: rgba(46, 160, 67, 0.1);
        color: #2ea043;
    }

    .kpi-icon.info {
        background: rgba(163, 113, 247, 0.1);
        color: #a371f7;
    }

    .kpi-icon.warning {
        background: rgba(210, 153, 34, 0.1);
        color: #d29922;
    }

    .kpi-icon.info {
        background: rgba(88, 166, 255, 0.1);
        color: #58a6ff;
    }

    .btn-action-row.info {
        background: rgba(163, 113, 247, 0.1);
        color: #a371f7;
        border: 1px solid rgba(163, 113, 247, 0.2);
    }

    .btn-action-row.info:hover {
        background: #a371f7;
        color: #fff;
        transform: scale(1.1);
    }

    .kpi-value {
        display: block;
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1;
    }

    .kpi-label {
        font-size: 0.8rem;
        color: var(--text-secondary);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Stepped Chevron Tabs */
    .custom-tabs {
        display: flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.02);
        padding: 10px;
        border-radius: 12px;
        gap: 0;
        border: 1px solid var(--border-primary);
        overflow: hidden;
    }

    .tab-item {
        flex: 1;
        padding: 1rem 1.5rem 1rem 2.5rem;
        color: var(--text-secondary);
        text-decoration: none;
        font-weight: 700;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        position: relative;
        background: var(--bg-tertiary);
        clip-path: polygon(calc(100% - 20px) 0%, 100% 50%, calc(100% - 20px) 100%, 0% 100%, 20px 50%, 0% 0%);
        margin-left: -15px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
    }

    .tab-item:first-child {
        margin-left: 0;
        clip-path: polygon(calc(100% - 20px) 0%, 100% 50%, calc(100% - 20px) 100%, 0% 100%, 0% 50%, 0% 0%);
        padding-left: 1.5rem;
        border-radius: 8px 0 0 8px;
    }

    .tab-item:last-child {
        clip-path: polygon(100% 0%, 100% 50%, 100% 100%, 0% 100%, 20px 50%, 0% 0%);
        border-radius: 0 8px 8px 0;
    }

    .tab-item:hover {
        background: rgba(255, 255, 255, 0.08);
        color: var(--text-primary);
        z-index: 2;
    }

    .tab-item.active {
        background: var(--accent-primary);
        color: #fff;
        z-index: 3;
        box-shadow: 5px 0 15px rgba(0, 0, 0, 0.3);
    }

    .tab-item.active .badge {
        background: #fff !important;
        color: var(--accent-primary) !important;
    }

    .tab-number {
        font-size: 0.7rem;
        opacity: 0.7;
        margin-right: 5px;
    }


    /* Management Row Staircase Effect */
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
        padding: 1.25rem;
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
        flex: 0 0 220px;
        background: rgba(255, 255, 255, 0.01);
    }

    .row-step.step-actions {
        flex: 0 0 140px;
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

    .priority-high .status-indicator {
        background: var(--priority-high);
        box-shadow: 2px 0 10px rgba(239, 68, 68, 0.3);
    }

    .priority-medium .status-indicator {
        background: var(--priority-medium);
    }

    .priority-low .status-indicator {
        background: var(--priority-low);
    }

    .solicitud-folio {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        color: var(--accent-primary);
        font-size: 0.9rem;
        margin-bottom: 5px;
    }

    .time-elapsed {
        font-size: 0.7rem;
        color: var(--text-muted);
    }

    .proyecto-name {
        font-size: 1rem;
        font-weight: 700;
        margin: 0;
        color: var(--text-primary);
    }

    .area-badge {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 4px;
    }

    .monto-box .label {
        font-size: 0.6rem;
        text-transform: uppercase;
        color: var(--text-muted);
        display: block;
    }

    .monto-box .value {
        font-size: 1.15rem;
        font-weight: 800;
        color: #fff;
    }

    .btn-action-row {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        transition: all 0.2s;
        text-decoration: none;
    }

    .btn-action-row.primary {
        background: rgba(88, 166, 255, 0.1);
        color: var(--accent-primary);
        border: 1px solid rgba(88, 166, 255, 0.2);
    }

    .btn-action-row.primary:hover {
        background: var(--accent-primary);
        color: #fff;
        transform: scale(1.1);
    }

    .btn-action-row.warning {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .btn-action-row.warning:hover {
        background: #f59e0b;
        color: #fff;
        transform: scale(1.1);
    }

    .pulse-btn {
        animation: pulse-animation 2s infinite;
    }

    @keyframes pulse-animation {
        0% {
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(245, 158, 11, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
        }
    }

    .btn-action-row.secondary {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-secondary);
        border: 1px solid var(--border-primary);
    }

    .btn-action-row.secondary:hover {
        background: #ef4444;
        color: #fff;
        border-color: #ef4444;
        transform: scale(1.1);
    }

    .empty-state {
        text-align: center;
        padding: 5rem;
        background: var(--bg-card);
        border: 2px dashed var(--border-primary);
        border-radius: 20px;
    }
</style>

<!-- Modal para Timeline -->
<div class="modal fade" id="modalTimeline" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content glass-vibrant-bg border-primary shadow-lg"
            style="background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(20px);">
            <div class="modal-header border-bottom border-primary">
                <h5 class="modal-title fw-bold text-white"><i class="fas fa-stream me-2 text-primary"></i>Trazabilidad
                    Documental</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body" id="timelineContent" style="min-height: 400px;">
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</script>

<!-- Modal de Firma -->
<div class="modal fade" id="modalFirma" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-vibrant-bg border-warning shadow-lg"
            style="background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(20px);">
            <div class="modal-header border-bottom border-warning">
                <h5 class="modal-title fw-bold text-white">
                    <i class="fas fa-pen-fancy me-2 text-warning"></i>Firmar Documento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body text-white">
                <p class="text-muted small mb-3">Estás a punto de firmar el documento <strong id="lblFolioFirma"
                        class="text-warning"></strong>. Esta acción es legalmente vinculante.</p>

                <li class="nav-item" role="presentation">
                    <button class="nav-link active btn-sm" id="pills-pin-tab" data-bs-toggle="pill"
                        data-bs-target="#pills-pin" type="button" role="tab"><i
                            class="fas fa-key me-1"></i>Digital</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link btn-sm" id="pills-fiel-tab" data-bs-toggle="pill"
                        data-bs-target="#pills-fiel" type="button" role="tab"><i
                            class="fas fa-file-signature me-1"></i>FIEL</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link btn-sm" id="pills-auto-tab" data-bs-toggle="pill"
                        data-bs-target="#pills-auto" type="button" role="tab"><i
                            class="fas fa-pen-nib me-1"></i>Autógrafa</button>
                </li>

                <div class="tab-content" id="pills-tabContent">
                    <!-- PIN FORM -->
                    <div class="tab-pane fade show active" id="pills-pin" role="tabpanel">
                        <form id="formFirmaPin">
                            <input type="hidden" name="flujo_id" id="valFlujoId">
                            <input type="hidden" name="tipo_firma" value="pin">

                            <div class="mb-3">
                                <label class="form-label x-small text-muted">Ingresa tu PIN de 4-6 dígitos</label>
                                <input type="password"
                                    class="form-control bg-dark border-secondary text-white text-center fs-4 letter-spacing-lg"
                                    name="pin" maxlength="6" required placeholder="• • • • • •">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-warning fw-bold">
                                    <i class="fas fa-check-circle me-2"></i>AUTORIZAR CON PIN
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- FIEL FORM (Simulado para Demo) -->
                    <div class="tab-pane fade" id="pills-fiel" role="tabpanel">
                        <div class="text-center p-4 border border-dashed border-secondary rounded mb-3">
                            <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                            <p class="small text-muted mb-0">Arrastra tus archivos .cer y .key aquí</p>
                            <button type="button" class="btn btn-outline-light btn-sm mt-2">Seleccionar
                                Archivos</button>
                        </div>
                        <div class="alert alert-info x-small">
                            <i class="fas fa-info-circle me-1"></i> El módulo de FIEL se encuentra en modo de pruebas.
                            Use Digital por el momento.
                        </div>
                    </div>

                    <!-- AUTOGRAFA FORM -->
                    <div class="tab-pane fade" id="pills-auto" role="tabpanel">
                        <form id="formFirmaAuto">
                            <input type="hidden" name="flujo_id" id="valFlujoIdAuto">
                            <input type="hidden" name="tipo_firma" value="autografa">

                            <div class="text-center mb-4">
                                <i class="fas fa-print fa-3x text-secondary mb-3"></i>
                                <h6 class="text-white">Proceso de Firma Autógrafa</h6>
                                <p class="small text-muted">
                                    1. Descargue el documento.<br>
                                    2. Imprímalo y fírmelo con bolígrafo.<br>
                                    3. Confirme aquí para cambiar el estado a "Firmado".
                                </p>
                                <button type="button" class="btn btn-outline-info btn-sm mb-3">
                                    <i class="fas fa-download me-1"></i> Descargar PDF
                                </button>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success fw-bold">
                                    <i class="fas fa-check me-2"></i>CONFIRMAR FIRMA
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showTimeline(documentoId) {
        const modalElement = document.getElementById('modalTimeline');
        const modal = new bootstrap.Modal(modalElement);
        const content = document.getElementById('timelineContent');

        content.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Cargando bitácora...</p></div>';
        modal.show();

        fetch(`ajax-timeline.php?id=${documentoId}`)
            .then(response => response.text())
            .then(html => {
                content.innerHTML = html;
            })
            .catch(err => {
                content.innerHTML = '<div class="alert alert-danger mx-3 my-3">Error al cargar el historial. Revise su conexión o contacte al administrador.</div>';
            });
    }

    function openSignatureModal(flujoId, folio, tipoFirma) {
        document.getElementById('valFlujoId').value = flujoId;
        document.getElementById('valFlujoIdAuto').value = flujoId;
        document.getElementById('lblFolioFirma').textContent = folio;

        // Reset tabs
        const triggerElPin = document.querySelector('#pills-pin-tab')
        const triggerElFiel = document.querySelector('#pills-fiel-tab')
        const triggerElAuto = document.querySelector('#pills-auto-tab')

        // Hide all first? Or just show the one we need?
        // Let's activate the correct tab based on type
        if (tipoFirma === 'autografa') {
            bootstrap.Tab.getOrCreateInstance(triggerElAuto).show();
        } else if (tipoFirma === 'fiel') {
            bootstrap.Tab.getOrCreateInstance(triggerElFiel).show();
        } else {
            bootstrap.Tab.getOrCreateInstance(triggerElPin).show();
        }

        new bootstrap.Modal(document.getElementById('modalFirma')).show();
    }

    document.getElementById('formFirmaAuto').addEventListener('submit', function (e) {
        e.preventDefault();
        // Same logic as PIN form
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';

        const formData = new FormData(this);
        fetch('procesar-firma.php', {
            method: 'POST', body: formData
        }).then(res => res.json()).then(data => {
            if (data.success) { alert('✅ Firma registrada.'); location.reload(); }
            else { alert('❌ Error: ' + data.message); btn.disabled = false; btn.innerHTML = originalText; }
        }).catch(err => {
            alert('Error de conexión.'); btn.disabled = false; btn.innerHTML = originalText;
        });
    });

    document.getElementById('formFirmaPin').addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verificando...';

        const formData = new FormData(this);

        // Adjust path to ajax-firmar.php based on your structure
        fetch('procesar-firma.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Success popup using sweetalert if available or standard alert
                    alert('✅ Documento firmado correctamente.');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error de conexión.');
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>