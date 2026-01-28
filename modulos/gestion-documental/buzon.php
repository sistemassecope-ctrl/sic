<?php
/**
 * Módulo: Buzón Universal de Documentos
 * Ubicación: /modulos/gestion-documental/buzon.php
 * Descripción: Centro de notificaciones y acciones pendientes para el usuario.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// Módulo de Gestión Documental (asumimos un ID o lo definimos)
if (!defined('MODULO_ID'))
    define('MODULO_ID', 60);

$pdo = getConnection();
$user = getCurrentUser();

// --- Lógica de Filtros ---
$tab = $_GET['tab'] ?? 'pendientes'; // pendientes, procesados, informativos

// --- Consultas ---
// 1. Pendientes de Acción (Aprobar, Firmar, Revisar)
$sqlPendientes = "
    SELECT ub.*, d.folio_sistema, d.titulo, d.fase_actual, d.estatus as doc_estatus, d.prioridad,
           ctd.nombre as tipo_nombre, ctd.codigo as tipo_codigo,
           df.id as flujo_id, df.tipo_firma
    FROM usuario_bandeja_documentos ub
    JOIN documentos d ON ub.documento_id = d.id
    JOIN cat_tipos_documento ctd ON d.tipo_documento_id = ctd.id
    LEFT JOIN documento_flujo_firmas df ON d.id = df.documento_id 
         AND df.firmante_id = ub.usuario_id 
         AND df.estatus = 'pendiente'
    WHERE ub.usuario_id = ? AND ub.procesado = 0
    ORDER BY ub.prioridad DESC, ub.fecha_asignacion DESC
";
$pendientes = $pdo->prepare($sqlPendientes);
$pendientes->execute([$user['id']]);
$listaPendientes = $pendientes->fetchAll();

// 2. Procesados recientemente
$sqlProcesados = "
    SELECT ub.*, d.folio_sistema, d.titulo, d.fase_actual, d.estatus as doc_estatus,
           d.archivo_pdf, ctd.nombre as tipo_nombre
    FROM usuario_bandeja_documentos ub
    JOIN documentos d ON ub.documento_id = d.id
    JOIN cat_tipos_documento ctd ON d.tipo_documento_id = ctd.id
    WHERE ub.usuario_id = ? AND ub.procesado = 1
    ORDER BY ub.fecha_proceso DESC
    LIMIT 10
";
$procesados = $pdo->prepare($sqlProcesados);
$procesados->execute([$user['id']]);
$listaProcesados = $procesados->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-mailbox text-primary me-2"></i>Mi Buzón de Documentos</h1>
            <p class="text-muted">Gestiona tus aprobaciones, firmas y notificaciones de trámites.</p>
        </div>
    </div>

    <!-- Resumen de Actividad -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="kpi-card glass-vibrant-bg">
                <div class="kpi-icon pending"><i class="fas fa-clock"></i></div>
                <div class="kpi-data">
                    <span class="kpi-value">
                        <?= count($listaPendientes) ?>
                    </span>
                    <span class="kpi-label">Pendientes de Acción</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card glass-vibrant-bg">
                <div class="kpi-icon success"><i class="fas fa-check-circle"></i></div>
                <div class="kpi-data">
                    <span class="kpi-value">
                        <?= count($listaProcesados) ?>
                    </span>
                    <span class="kpi-label">Procesados (Reciente)</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card glass-vibrant-bg">
                <div class="kpi-icon info"><i class="fas fa-bell"></i></div>
                <div class="kpi-data">
                    <span class="kpi-value">0</span>
                    <span class="kpi-label">Avisos Informativos</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs del Buzón -->
    <ul class="nav nav-tabs custom-nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $tab == 'pendientes' ? 'active' : '' ?>" href="?tab=pendientes">
                <i class="fas fa-tasks me-2"></i>Acciones Requeridas
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab == 'procesados' ? 'active' : '' ?>" href="?tab=procesados">
                <i class="fas fa-history me-2"></i>Historial de mi actividad
            </a>
        </li>
    </ul>

    <div class="document-list">
        <?php if ($tab == 'pendientes'): ?>
            <?php if (empty($listaPendientes)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-double fa-3x mb-3 text-success"></i>
                    <h3>¡Todo al día!</h3>
                    <p>No tienes documentos pendientes de aprobación o firma.</p>
                </div>
            <?php else: ?>
                <?php foreach ($listaPendientes as $doc): ?>
                    <div class="doc-item-row priority-<?= $doc['prioridad'] ?>">
                        <div class="doc-type-icon">
                            <?php
                            $icon = 'fa-file-alt';
                            if ($doc['tipo_codigo'] == 'SUFPRE')
                                $icon = 'fa-file-invoice-dollar';
                            ?>
                            <i class="fas <?= $icon ?>"></i>
                        </div>
                        <div class="doc-info">
                            <div class="doc-header">
                                <span class="badge bg-primary-soft text-primary mb-1">
                                    <?= e($doc['tipo_nombre']) ?>
                                </span>
                                <span class="doc-folio">
                                    <?= e($doc['folio_sistema']) ?>
                                </span>
                            </div>
                            <h4 class="doc-title">
                                <?= e($doc['titulo']) ?>
                            </h4>
                            <div class="doc-meta">
                                <span><i class="fas fa-user-clock me-1"></i> Asignado el
                                    <?= date('d/m/Y H:i', strtotime($doc['fecha_asignacion'])) ?>
                                </span>
                                <?php if ($doc['fecha_limite']): ?>
                                    <span class="text-danger ms-3"><i class="fas fa-hourglass-end me-1"></i> Límite:
                                        <?= date('d/m/Y', strtotime($doc['fecha_limite'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="doc-action-status">
                            <span class="action-tag action-<?= $doc['tipo_accion_requerida'] ?>">
                                <i class="fas fa-exclamation-circle me-1"></i> Requiere:
                                <?= ucfirst($doc['tipo_accion_requerida']) ?>
                            </span>
                        </div>
                        <div class="doc-actions">
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3"
                                onclick="abrirModalFirma(<?= $doc['flujo_id'] ?? 0 ?>, '<?= e($doc['folio_sistema']) ?>', '<?= e($doc['tipo_accion_requerida']) ?>', '<?= e($doc['tipo_firma'] ?? 'pin') ?>')">
                                <i class="fas fa-signature me-1"></i> Atender
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php else: ?>
            <!-- Vista de Procesados -->
            <?php if (empty($listaProcesados)): ?>
                <div class="empty-state text-muted">
                    <p>Aún no has procesado documentos en el nuevo sistema.</p>
                </div>
            <?php else: ?>
                <?php foreach ($listaProcesados as $doc): ?>
                    <div class="doc-item-row processed">
                        <div class="doc-type-icon text-success"><i class="fas fa-check-circle"></i></div>
                        <div class="doc-info">
                            <h4 class="doc-title">
                                <?= e($doc['titulo']) ?> <small class="text-muted">(
                                    <?= e($doc['folio_sistema']) ?>)
                                </small>
                            </h4>
                            <div class="doc-meta">
                                <span><i class="fas fa-check me-1"></i> Procesado el
                                    <?= date('d/m/Y H:i', strtotime($doc['fecha_proceso'])) ?>
                                </span>
                            </div>
                        </div>
                        <div class="doc-actions">
                            <?php if ($doc['archivo_pdf']): ?>
                                <a href="<?= url('/' . $doc['archivo_pdf']) ?>" target="_blank"
                                    class="btn btn-success btn-sm rounded-pill px-3">
                                    <i class="fas fa-file-pdf me-1"></i> Ver Final
                                </a>
                            <?php else: ?>
                                <span class="badge bg-secondary">En Proceso</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Modal de Firma -->
<div class="modal fade" id="modalFirma" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-vibrant-bg border-primary">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-shield-alt text-primary me-2"></i>Seguridad de Firma
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="formFirma">
                <input type="hidden" id="f_flujo_id" name="flujo_id">
                <input type="hidden" id="f_tipo_firma" name="tipo_firma">
                <div class="modal-body py-4">
                    <div class="text-center mb-4">
                        <div class="doc-preview-icon mb-2">
                            <i class="fas fa-file-signature fa-2x text-accent"></i>
                        </div>
                        <h6 id="f_folio" class="text-accent fw-bold mb-1">FOLIO</h6>
                        <p id="f_instrucciones" class="text-muted small">Instrucciones de firma.</p>
                    </div>

                    <div id="firma_pin_container" class="d-none">
                        <div class="form-group mb-3">
                            <label class="form-label small text-muted text-uppercase fw-bold">PIN de Seguridad (4
                                Dígitos)</label>
                            <div class="pin-input-container">
                                <input type="password" name="pin" id="f_pin"
                                    class="form-control form-control-lg text-center letter-spacing-lg" maxlength="4"
                                    placeholder="••••">
                            </div>
                        </div>
                    </div>

                    <div id="firma_fiel_container" class="d-none">
                        <div class="alert alert-warning small">
                            <i class="fas fa-exclamation-triangle me-2"></i>Requiere Certificados e.Firma (SAT)
                        </div>
                        <div class="mb-2">
                            <label class="form-label small text-muted">Archivo .cer</label>
                            <input type="file" name="fiel_cer" class="form-control form-control-sm">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Archivo .key</label>
                            <input type="file" name="fiel_key" class="form-control form-control-sm">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Contraseña de la llave privada</label>
                            <input type="password" name="fiel_pass" class="form-control" placeholder="Contraseña">
                        </div>
                    </div>

                    <div id="firma_autografa_container" class="d-none">
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-2"></i> <strong>Firma Autógrafa:</strong> Al confirmar,
                            declaras que imprimirás el documento para recabar la firma física. El sistema esperará la
                            carga del archivo escaneado para continuar.
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirm_autografa" required>
                            <label class="form-check-label small" for="confirm_autografa">
                                Entiendo y confirmo que procederé con la firma física.
                            </label>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold">
                            <i class="fas fa-check-circle me-2"></i>Confirmar Acción
                        </button>
                    </div>

                    <div class="text-center mt-3" id="pin_forgot_container">
                        <a href="#" class="text-muted small text-decoration-none">¿Olvidaste tu PIN?</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function abrirModalFirma(id, folio, accion, tipoFirma) {
        document.getElementById('f_flujo_id').value = id;
        document.getElementById('f_folio').innerText = folio;
        document.getElementById('f_tipo_firma').value = tipoFirma;

        // Ocultar todos los contenedores
        document.getElementById('firma_pin_container').classList.add('d-none');
        document.getElementById('firma_fiel_container').classList.add('d-none');
        document.getElementById('firma_autografa_container').classList.add('d-none');
        document.getElementById('pin_forgot_container').classList.add('d-none');

        const instrucciones = document.getElementById('f_instrucciones');
        const pinInput = document.getElementById('f_pin');

        if (tipoFirma === 'pin') {
            document.getElementById('firma_pin_container').classList.remove('d-none');
            document.getElementById('pin_forgot_container').classList.remove('d-none');
            instrucciones.innerText = "Ingresa tu PIN de seguridad de 4 dígitos para firmar.";
            pinInput.required = true;
        } else if (tipoFirma === 'fiel') {
            document.getElementById('firma_fiel_container').classList.remove('d-none');
            instrucciones.innerText = "Carga tus archivos de e.Firma (SAT) para validar legalmente.";
            pinInput.required = false;
        } else if (tipoFirma === 'autografa') {
            document.getElementById('firma_autografa_container').classList.remove('d-none');
            instrucciones.innerText = "Confirma el inicio del proceso de firma física.";
            pinInput.required = false;
        }

        const modal = new bootstrap.Modal(document.getElementById('modalFirma'));
        modal.show();
    }

    document.getElementById('formFirma').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';

        try {
            const formData = new FormData(this);
            const res = await fetch('ajax-firmar.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Documento Firmado!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire('Error', data.message, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (err) {
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
</script>

<style>
    .letter-spacing-lg {
        letter-spacing: 0.5rem;
        font-size: 1.5rem;
    }

    .doc-preview-icon {
        width: 60px;
        height: 60px;
        background: rgba(var(--accent-primary-rgb), 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }

    .text-accent {
        color: var(--accent-primary);
    }
</style>

<style>
    .kpi-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .kpi-icon {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
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

    .kpi-value {
        display: block;
        font-size: 1.5rem;
        font-weight: 800;
        color: #fff;
        line-height: 1;
    }

    .kpi-label {
        font-size: 0.75rem;
        color: var(--text-secondary);
        text-transform: uppercase;
    }

    .custom-nav-tabs .nav-link {
        color: var(--text-secondary);
        border: none;
        padding: 1rem 1.5rem;
        font-weight: 600;
        border-bottom: 2px solid transparent;
        transition: all 0.3s;
    }

    .custom-nav-tabs .nav-link:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.05);
    }

    .custom-nav-tabs .nav-link.active {
        color: var(--accent-primary);
        background: transparent;
        border-bottom: 2px solid var(--accent-primary);
    }

    .doc-item-row {
        background: var(--bg-card);
        border: 1px solid var(--border-primary);
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        transition: all 0.3s;
    }

    .doc-item-row:hover {
        transform: translateX(5px);
        border-color: var(--accent-primary);
        box-shadow: -5px 5px 15px rgba(0, 0, 0, 0.2);
    }

    .doc-type-icon {
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: var(--text-secondary);
    }

    .doc-info {
        flex: 1;
    }

    .doc-folio {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        color: var(--accent-primary);
        margin-left: 10px;
    }

    .doc-title {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: #fff;
    }

    .doc-meta {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 5px;
    }

    .action-tag {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 20px;
        text-transform: uppercase;
    }

    .action-aprobar {
        background: rgba(163, 113, 247, 0.1);
        color: #a371f7;
    }

    .action-firmar {
        background: rgba(210, 153, 34, 0.1);
        color: #d29922;
    }

    .action-revisar {
        background: rgba(88, 166, 255, 0.1);
        color: #58a6ff;
    }

    .bg-primary-soft {
        background: rgba(88, 166, 255, 0.1);
    }

    .empty-state {
        text-align: center;
        padding: 4rem;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 20px;
        border: 2px dashed var(--border-primary);
    }
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>