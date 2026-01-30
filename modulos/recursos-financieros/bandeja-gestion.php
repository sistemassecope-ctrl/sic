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

// TAB 1: Solicitudes Nuevas (Fase 1 y 2)
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

                -- DOCUMENTO A: SOLICITUD (Tipo 1)
                ds.id as doc_suf_id,
                ds.estatus as doc_suf_status,
                (SELECT df.id FROM documento_flujo_firmas df WHERE df.documento_id = ds.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_suf_id,
                (SELECT df.tipo_firma FROM documento_flujo_firmas df WHERE df.documento_id = ds.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_suf_type,
                (SELECT df.rol_oficio FROM documento_flujo_firmas df WHERE df.documento_id = ds.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_suf_role,

                -- DOCUMENTO B: VALIDACIÓN (Tipo 7 - VAL_SUF)
                dv.id as doc_val_id,
                dv.estatus as doc_val_status,
                (SELECT df.id FROM documento_flujo_firmas df WHERE df.documento_id = dv.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_val_id,
                (SELECT df.tipo_firma FROM documento_flujo_firmas df WHERE df.documento_id = dv.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_val_type,
                (SELECT df.rol_oficio FROM documento_flujo_firmas df WHERE df.documento_id = dv.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_val_role

            FROM proyectos_obra po
            LEFT JOIN areas a ON po.id_unidad_responsable = a.id
            LEFT JOIN solicitudes_suficiencia f ON po.id_proyecto = f.id_proyecto AND f.estatus = 'ACTIVO'
            LEFT JOIN cat_momentos_suficiencia m ON COALESCE(f.id_momento_gestion, 1) = m.id
            
            -- Joins a Documento A: Solicitud (Tipo 1) - ÚLTIMO CREADO
            LEFT JOIN documentos ds ON ds.id = (
                SELECT MAX(d.id) FROM documentos d 
                WHERE d.tipo_documento_id = 1 
                AND JSON_UNQUOTE(JSON_EXTRACT(d.contenido_json, '$.id_fua')) = CAST(f.id_fua AS CHAR)
            )
            -- Joins a Documento B: Validación (Tipo 7) - ÚLTIMO CREADO
            LEFT JOIN documentos dv ON dv.id = (
                SELECT MAX(d.id) FROM documentos d 
                WHERE d.tipo_documento_id = 7 
                AND JSON_UNQUOTE(JSON_EXTRACT(d.contenido_json, '$.id_fua')) = CAST(f.id_fua AS CHAR)
            )

            WHERE ($areaFilter)
              AND (f.id_momento_gestion IS NULL OR f.id_momento_gestion = 1)
              AND po.id_proyecto NOT IN (
                  SELECT id_proyecto FROM solicitudes_suficiencia WHERE id_momento_gestion > 1 AND estatus = 'ACTIVO'
              )
            ORDER BY po.id_proyecto DESC
        ";
} else {
    // TABS 2-6: Seguimiento
    $sql = "
            SELECT 
                f.id_fua, f.num_oficio_tramite, f.nombre_proyecto_accion, f.monto_total_solicitado, f.created_at, f.id_momento_gestion,
                po.nombre_proyecto, 
                a.nombre_area, 
                m.nombre as momento_nombre, m.color as momento_color,

                -- DOCUMENTO A: SOLICITUD (Tipo 1)
                ds.id as doc_suf_id,
                ds.estatus as doc_suf_status,
                (SELECT df.id FROM documento_flujo_firmas df WHERE df.documento_id = ds.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_suf_id,
                (SELECT df.tipo_firma FROM documento_flujo_firmas df WHERE df.documento_id = ds.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_suf_type,
                (SELECT df.rol_oficio FROM documento_flujo_firmas df WHERE df.documento_id = ds.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_suf_role,

                -- DOCUMENTO B: VALIDACIÓN (Tipo 7)
                dv.id as doc_val_id,
                dv.estatus as doc_val_status,
                (SELECT df.id FROM documento_flujo_firmas df WHERE df.documento_id = dv.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_val_id,
                (SELECT df.tipo_firma FROM documento_flujo_firmas df WHERE df.documento_id = dv.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_val_type,
                (SELECT df.rol_oficio FROM documento_flujo_firmas df WHERE df.documento_id = dv.id AND df.firmante_id = {$user['id']} AND df.estatus = 'pendiente' LIMIT 1) as pending_val_role

            FROM solicitudes_suficiencia f
            LEFT JOIN proyectos_obra po ON f.id_proyecto = po.id_proyecto
            LEFT JOIN areas a ON po.id_unidad_responsable = a.id
            LEFT JOIN cat_momentos_suficiencia m ON f.id_momento_gestion = m.id
            
            LEFT JOIN documentos ds ON ds.id = (
                SELECT MAX(d.id) FROM documentos d 
                WHERE d.tipo_documento_id = 1 
                AND JSON_UNQUOTE(JSON_EXTRACT(d.contenido_json, '$.id_fua')) = CAST(f.id_fua AS CHAR)
            )
            LEFT JOIN documentos dv ON dv.id = (
                SELECT MAX(d.id) FROM documentos d 
                WHERE d.tipo_documento_id = 7 
                AND JSON_UNQUOTE(JSON_EXTRACT(d.contenido_json, '$.id_fua')) = CAST(f.id_fua AS CHAR)
            )

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
                <?php
                // --- SINGLE ACTION LOGIC DETERMINATION ---
                $actionButton = '';

                // 1. Prioridad Absoluta: Firmar Oficio Externo (Validación)
                if (!empty($s['pending_val_id'])) {
                    $isAttendance = in_array($s['pending_val_role'], ['COPIA', 'ATENCION']);
                    $btnText = $isAttendance ? 'CONFIRMAR DE RECIBIDO' : 'FIRMAR OFICIO';
                    $btnIcon = $isAttendance ? 'fa-check-double' : 'fa-pen-fancy';
                    $btnClass = $isAttendance ? 'btn-success' : 'btn-warning'; // Verde para confirmar, Amarillo para firmar
        
                    $actionButton = '
                        <button type="button" class="btn ' . $btnClass . ' w-100 fw-bold pulse-btn shadow-sm mb-2" 
                                onclick="openSignatureModal(' . $s['pending_val_id'] . ', \'' . e($s['num_oficio_tramite']) . '\', \'' . e($s['pending_val_type']) . '\', ' . $s['doc_val_id'] . ')">
                            <i class="fas ' . $btnIcon . ' me-2"></i> ' . $btnText . '
                        </button>';
                }
                // 2. Prioridad: Firmar Solicitud Interna
                else if (!empty($s['pending_suf_id'])) {
                    $isAttendance = in_array($s['pending_suf_role'], ['COPIA', 'ATENCION']);
                    $btnText = $isAttendance ? 'CONFIRMAR DE RECIBIDO' : 'FIRMAR SOLICITUD';
                    $btnIcon = $isAttendance ? 'fa-check-double' : 'fa-file-signature';
                    $btnClass = $isAttendance ? 'btn-success' : 'btn-warning';

                    $actionButton = '
                        <button type="button" class="btn ' . $btnClass . ' w-100 fw-bold pulse-btn shadow-sm mb-2" 
                                onclick="openSignatureModal(' . $s['pending_suf_id'] . ', \'' . e($s['num_oficio_tramite']) . '\', \'' . e($s['pending_suf_type']) . '\', ' . $s['doc_suf_id'] . ')">
                            <i class="fas ' . $btnIcon . ' me-2"></i> ' . $btnText . '
                        </button>';
                }
                // 3. Generación de Oficio Administrativo (Solo en Fase 3 y si no existe el doc)
                else if ($s['id_momento_gestion'] == 3 && empty($s['doc_val_id']) && $puedeEditar) {
                    $actionButton = '
                        <button type="button" class="btn btn-primary w-100 fw-bold shadow-sm mb-2" 
                                onclick="generarOficioValidacion(' . $s['id_fua'] . ')">
                            <i class="fas fa-magic me-2"></i> GENERAR OFICIO EXTERNO
                        </button>';
                }
                // 4. Edición Inicial (Si no hay FUA o está en captura)
                else if ((empty($s['id_fua']) || $s['id_momento_gestion'] <= 1) && $puedeEditar) {
                    $actionButton = '
                        <a href="' . $editLink . '" class="btn btn-primary w-100 fw-bold shadow-sm mb-2">
                             <i class="fas ' . ($s['id_fua'] ? 'fa-edit' : 'fa-plus') . ' me-2"></i> ' . ($s['id_fua'] ? 'EDITAR SOLICITUD' : 'INICIAR TRÁMITE') . '
                        </a>';
                }
                // 5. Default: Ver/Descargar
                else if ($s['id_fua']) {
                    $actionButton = '
                        <a href="generar-oficio.php?id=' . $s['id_fua'] . '" target="_blank" class="btn btn-outline-secondary w-100 mb-2">
                             <i class="fas fa-eye me-2"></i> VER SOLICITUD
                        </a>';
                }
                ?>

                <div class="management-row border rounded-3 p-3 mb-3 bg-white shadow-sm position-relative">
                    <div class="row align-items-center">

                        <!-- COL 1: DETALLES DEL PROYECTO -->
                        <div class="col-md-5 border-end">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-light text-dark border">
                                    Folio: <?= $s['num_oficio_tramite'] ?: 'S/N' ?>
                                </span>
                                <span class="badge"
                                    style="background: <?= $s['momento_color'] ?>1a; color: <?= $s['momento_color'] ?>; border: 1px solid <?= $s['momento_color'] ?>;">
                                    <?= e($s['momento_nombre']) ?>
                                </span>
                            </div>
                            <h5 class="fw-bold text-dark mb-1" style="font-size: 1rem;">
                                <?= e($s['nombre_proyecto_accion'] ?: $s['nombre_proyecto']) ?>
                            </h5>
                            <div class="small text-muted mb-2">
                                <i class="fas fa-building me-1"></i> <?= e($s['nombre_area'] ?: 'Área no asignada') ?>
                            </div>
                            <div class="fw-bold text-primary fs-5">
                                $<?= number_format($s['monto_total_solicitado'], 2) ?>
                            </div>
                        </div>

                        <!-- COL 2: ESTADO DOCUMENTAL (SEMAFORO) -->
                        <div class="col-md-4 border-end">
                            <div class="vstack gap-2 px-3">
                                <!-- Documento A: Solicitud -->
                                <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light">
                                    <div class="text-truncate">
                                        <i class="fas fa-file-alt text-secondary me-2"></i> Solicitud Interna
                                    </div>
                                    <?php if ($s['doc_suf_status'] == 'firmado'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check"></i> OK</span>
                                    <?php elseif ($s['doc_suf_id']): ?>
                                        <span class="badge bg-warning text-dark">FIRMANDO...</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">PENDIENTE</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Documento B: Oficio Validación -->
                                <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light">
                                    <div class="text-truncate">
                                        <i class="fas fa-file-contract text-secondary me-2"></i> Oficio Externo
                                    </div>
                                    <?php if ($s['doc_val_status'] == 'firmado'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check"></i> OK</span>
                                    <?php elseif ($s['doc_val_id']): ?>
                                        <span class="badge bg-warning text-dark">FIRMANDO...</span>
                                    <?php elseif ($s['id_momento_gestion'] >= 3): ?>
                                        <span class="badge bg-danger">NO GENERADO</span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- COL 3: ACCIÓN ÚNICA -->
                        <div class="col-md-3 text-center">
                            <?= $actionButton ?>

                            <!-- Links Secundarios Discretos -->
                            <div class="d-flex justify-content-center gap-2 mt-2">
                                <?php if ($s['doc_suf_id']): ?>
                                    <a href="generar-oficio.php?id=<?= $s['id_fua'] ?>" target="_blank" class="text-secondary"
                                        title="Ver Solicitud PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($s['doc_suf_id']): ?>
                                    <a href="#" onclick="showTimeline(<?= $s['doc_suf_id'] ?>)" class="text-secondary"
                                        title="Ver Historial">
                                        <i class="fas fa-history"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
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

<!-- Modal Firma -->
<?php include __DIR__ . '/../../includes/modals/firma-electronica.php'; ?>
<!-- Fin Modal Firma -->

<script>
    function generarOficioValidacion(idFua) {
        if (!confirm('¿Confirma que desea generar el Oficio de Validación Administrativa?\n\nEsta acción:\n1. Creará un nuevo documento oficial.\n2. Lo enviará automáticamente a firma del Titular.\n3. Avanzará el trámite a la siguiente fase.')) {
            return;
        }

        const formData = new FormData();
        formData.append('id_fua', idFua);

        fetch('generar-oficio-validacion.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('❌ Error de conexión al generar el oficio.');
            });
    }

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

    function openSignatureModal(flujoId, folio, tipoFirma, docId) {
        document.getElementById('valFlujoId').value = flujoId;
        document.getElementById('valFlujoIdAuto').value = flujoId;
        document.getElementById('lblFolioFirma').textContent = folio;

        // Setup Download Button
        const btnDownload = document.getElementById('btnDescargarDoc');
        if (btnDownload) {
            btnDownload.onclick = function () {
                window.open('generar-oficio.php?doc_id=' + docId, '_blank');
            };
        }

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

});
</script>

<?php include __DIR__ . '/../../includes/modals/expediente-suficiencia.php'; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>