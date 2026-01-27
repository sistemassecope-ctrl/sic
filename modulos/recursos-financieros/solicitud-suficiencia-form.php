<?php
/**
 * Módulo: Formulario de Solicitud de Suficiencia
 * Ubicación: /modulos/recursos-financieros/solicitud-suficiencia-form.php
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

$pdo = getConnection();
$user = getCurrentUser();

$id_fua = isset($_GET['id']) ? (int) $_GET['id'] : null;
$id_proyecto = isset($_GET['id_proyecto']) ? (int) $_GET['id_proyecto'] : null;
$fua = null;
$is_editing = false;

if ($id_fua) {
    if (!$puedeEditar) {
        setFlashMessage('error', 'No tienes permiso para editar solicitudes.');
        redirect('modulos/recursos-financieros/solicitudes-suficiencia.php');
    }
    $stmt = $pdo->prepare("SELECT * FROM solicitudes_suficiencia WHERE id_fua = ?");
    $stmt->execute([$id_fua]);
    $fua = $stmt->fetch();
    if ($fua) {
        $is_editing = true;
        $id_proyecto = $fua['id_proyecto'];
    }
} else {
    if (!$puedeCrear) {
        setFlashMessage('error', 'No tienes permiso para registrar nuevas solicitudes.');
        redirect('modulos/recursos-financieros/solicitudes-suficiencia.php');
    }
}

// Lógica de Bloqueo: Bloquear campos de captura si ya no está en estado "PENDIENTE" 
// o si ya ingresó a la bandeja de gestión administrativa.
$bloquearCaptura = $is_editing && ($fua['resultado_tramite'] !== 'PENDIENTE' || !empty($fua['fecha_ingreso_admvo']));
$bloquearFinal = $is_editing && !empty($fua['fecha_respuesta_sfa']); // Bloqueo total si ya terminó el proceso

$attrDisabledC = ($bloquearCaptura || $bloquearFinal) ? 'disabled' : '';
$attrReadonlyC = ($bloquearCaptura || $bloquearFinal) ? 'readonly' : '';

// Bloqueo progresivo del Timeline
$lockAdmvo = $is_editing && (!empty($fua['fecha_ingreso_cotrl_ptal']) || $bloquearFinal);
$lockCtrl  = $is_editing && (!empty($fua['fecha_titular']) || $bloquearFinal);
$lockFirma = $is_editing && (!empty($fua['fecha_acuse_antes_fa']) || $bloquearFinal);
$lockSFyA  = $bloquearFinal;

// Catálogos
$proyectos = $pdo->query("SELECT id_proyecto, nombre_proyecto, ejercicio FROM proyectos_obra ORDER BY ejercicio DESC, nombre_proyecto ASC")->fetchAll();

// --- Procesar Guardado ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id_proyecto' => !empty($_POST['id_proyecto']) ? (int) $_POST['id_proyecto'] : null,
        'nombre_proyecto_accion' => mb_strtoupper(trim($_POST['nombre_proyecto_accion'] ?? '')),
        'tipo_suficiencia' => $_POST['tipo_suficiencia'] ?? 'NUEVA',
        'estatus' => $_POST['estatus'] ?? 'ACTIVO',
        'resultado_tramite' => $_POST['resultado_tramite'] ?? 'PENDIENTE',
        'folio_fua' => trim($_POST['folio_fua'] ?? ''),
        'num_oficio_tramite' => trim($_POST['num_oficio_tramite'] ?? ''),
        'oficio_desf_ya' => trim($_POST['oficio_desf_ya'] ?? ''),
        'clave_presupuestal' => trim($_POST['clave_presupuestal'] ?? ''),
        'monto_obra' => (float) str_replace(',', '', $_POST['monto_obra'] ?? '0'),
        'monto_supervision' => (float) str_replace(',', '', $_POST['monto_supervision'] ?? '0'),
        'monto_total_solicitado' => (float) str_replace(',', '', $_POST['monto_total_solicitado'] ?? '0'),
        'autorizado_por' => trim($_POST['autorizado_por'] ?? ''),
        'elaborado_por' => trim($_POST['elaborado_por'] ?? ''),
        'fecha_elaboracion' => !empty($_POST['fecha_elaboracion']) ? $_POST['fecha_elaboracion'] : null,
        'fecha_autorizacion' => !empty($_POST['fecha_autorizacion']) ? $_POST['fecha_autorizacion'] : null,
        'fecha_ingreso_admvo' => !empty($_POST['fecha_ingreso_admvo']) ? $_POST['fecha_ingreso_admvo'] : null,
        'fecha_ingreso_cotrl_ptal' => !empty($_POST['fecha_ingreso_cotrl_ptal']) ? $_POST['fecha_ingreso_cotrl_ptal'] : null,
        'fecha_titular' => !empty($_POST['fecha_titular']) ? $_POST['fecha_titular'] : null,
        'fecha_firma_regreso' => !empty($_POST['fecha_firma_regreso']) ? $_POST['fecha_firma_regreso'] : null,
        'fecha_acuse_antes_fa' => !empty($_POST['fecha_acuse_antes_fa']) ? $_POST['fecha_acuse_antes_fa'] : null,
        'fecha_respuesta_sfa' => !empty($_POST['fecha_respuesta_sfa']) ? $_POST['fecha_respuesta_sfa'] : null,
        'observaciones' => trim($_POST['observaciones'] ?? '')
    ];

    try {
        if ($is_editing) {
            $sql = "UPDATE solicitudes_suficiencia SET id_proyecto=?, nombre_proyecto_accion=?, tipo_suficiencia=?, estatus=?, resultado_tramite=?, folio_fua=?, num_oficio_tramite=?, oficio_desf_ya=?, clave_presupuestal=?, monto_obra=?, monto_supervision=?, monto_total_solicitado=?, autorizado_por=?, elaborado_por=?, fecha_elaboracion=?, fecha_autorizacion=?, fecha_ingreso_admvo=?, fecha_ingreso_cotrl_ptal=?, fecha_titular=?, fecha_firma_regreso=?, fecha_acuse_antes_fa=?, fecha_respuesta_sfa=?, observaciones=? WHERE id_fua=?";
            $pdo->prepare($sql)->execute(array_merge(array_values($data), [$id_fua]));
            setFlashMessage('success', 'Solicitud actualizada correctamente');
        } else {
            $sql = "INSERT INTO solicitudes_suficiencia (id_proyecto, nombre_proyecto_accion, tipo_suficiencia, estatus, resultado_tramite, folio_fua, num_oficio_tramite, oficio_desf_ya, clave_presupuestal, monto_obra, monto_supervision, monto_total_solicitado, autorizado_por, elaborado_por, fecha_elaboracion, fecha_autorizacion, fecha_ingreso_admvo, fecha_ingreso_cotrl_ptal, fecha_titular, fecha_firma_regreso, fecha_acuse_antes_fa, fecha_respuesta_sfa, observaciones) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $pdo->prepare($sql)->execute(array_values($data));
            setFlashMessage('success', 'Solicitud registrada correctamente');
        }
        redirect('modulos/recursos-financieros/solicitudes-suficiencia.php' . ($data['id_proyecto'] ? "?id_proyecto=" . $data['id_proyecto'] : ""));
    } catch (Exception $e) {
        setFlashMessage('error', $e->getMessage());
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="solicitudes-suficiencia.php">Solicitudes</a></li>
                    <li class="breadcrumb-item active">
                        <?= $is_editing ? 'Editar' : 'Capturar' ?> Solicitud
                    </li>
                </ol>
            </nav>
            <h1 class="page-title"><i class="fas fa-file-invoice-dollar text-primary"></i>
                <?= $is_editing ? 'Editar' : 'Nueva' ?> Solicitud de Suficiencia
            </h1>
        </div>
    </div>

    <?= renderFlashMessage() ?>

    <form method="POST" onsubmit="preSubmit()">
        <div class="hoja-papel">
            <div class="sheet-header">
                <div>
                    <h3>SOLICITUD DE SUFICIENCIA</h3>
                    <p>RECURSOS FINANCIEROS</p>
                </div>
                <div class="text-end">
                    <label class="x-small d-block text-muted">FOLIO SISTEMA</label>
                    <span class="badge bg-secondary">#
                        <?= $is_editing ? $fua['id_fua'] : 'NUEVO' ?>
                    </span>
                </div>
            </div>

            <?php if (isAdmin()): ?>
                <!-- Seguimiento (Timeline) -->
                <div class="timeline-box mb-5">
                    <h6 class="text-center text-primary fw-bold mb-4">SEGUIMIENTO DEL TRÁMITE</h6>
                    <div class="timeline-steps">
                        <div class="step">
                            <div class="step-circle <?= !empty($fua['fecha_ingreso_admvo']) ? 'active' : '' ?>"><i
                                    class="fas fa-file-import"></i></div>
                            <label>Admvo.</label>
                            <input type="date" name="fecha_ingreso_admvo" class="form-control form-control-sm"
                                value="<?= $is_editing ? $fua['fecha_ingreso_admvo'] : '' ?>" <?= $lockAdmvo ? 'readonly' : '' ?>>
                        </div>
                        <div class="step">
                            <div class="step-circle <?= !empty($fua['fecha_ingreso_cotrl_ptal']) ? 'active' : '' ?>"><i
                                    class="fas fa-calculator"></i></div>
                            <label>Ctrl. Ptal.</label>
                            <input type="date" name="fecha_ingreso_cotrl_ptal" class="form-control form-control-sm"
                                value="<?= $is_editing ? $fua['fecha_ingreso_cotrl_ptal'] : '' ?>" <?= $lockCtrl ? 'readonly' : '' ?>>
                        </div>
                        <div class="step">
                            <div
                                class="step-circle <?= (!empty($fua['fecha_titular']) && !empty($fua['fecha_firma_regreso'])) ? 'active' : '' ?>">
                                <i class="fas fa-signature"></i>
                            </div>
                            <label>Firmas</label>
                            <div class="d-flex gap-1">
                                <input type="date" name="fecha_titular" title="Firma Titular"
                                    class="form-control form-control-sm"
                                    value="<?= $is_editing ? $fua['fecha_titular'] : '' ?>" <?= $lockFirma ? 'readonly' : '' ?>>
                                <input type="date" name="fecha_firma_regreso" title="Regreso de Firma"
                                    class="form-control form-control-sm"
                                    value="<?= $is_editing ? $fua['fecha_firma_regreso'] : '' ?>" <?= $lockFirma ? 'readonly' : '' ?>>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-circle <?= (!empty($fua['fecha_respuesta_sfa'])) ? 'active' : '' ?>"><i
                                    class="fas fa-university"></i></div>
                            <label>SFyA</label>
                            <div class="d-flex gap-1">
                                <input type="date" name="fecha_acuse_antes_fa" title="Acuse en SFyA"
                                    class="form-control form-control-sm"
                                    value="<?= $is_editing ? $fua['fecha_acuse_antes_fa'] : '' ?>" <?= $lockSFyA ? 'readonly' : '' ?>>
                                <input type="date" name="fecha_respuesta_sfa" title="Respuesta SFyA"
                                    class="form-control form-control-sm"
                                    value="<?= $is_editing ? $fua['fecha_respuesta_sfa'] : '' ?>" <?= $lockSFyA ? 'readonly' : '' ?>>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Hidden Admin Fields for non-admins when editing -->
                <?php if ($is_editing): ?>
                    <input type="hidden" name="fecha_ingreso_admvo" value="<?= $fua['fecha_ingreso_admvo'] ?>">
                    <input type="hidden" name="fecha_ingreso_cotrl_ptal" value="<?= $fua['fecha_ingreso_cotrl_ptal'] ?>">
                    <input type="hidden" name="fecha_titular" value="<?= $fua['fecha_titular'] ?>">
                    <input type="hidden" name="fecha_firma_regreso" value="<?= $fua['fecha_firma_regreso'] ?>">
                    <input type="hidden" name="fecha_acuse_antes_fa" value="<?= $fua['fecha_acuse_antes_fa'] ?>">
                    <input type="hidden" name="fecha_respuesta_sfa" value="<?= $fua['fecha_respuesta_sfa'] ?>">
                <?php endif; ?>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-12">
                    <label class="form-label x-small text-muted">VINCULAR PROYECTO (CATÁLOGO)</label>
                    <div style="position: relative;">
                        <?php if ($is_editing || $attrDisabledC): 
                            $nombre_proy_elegido = 'SIN PROYECTO VINCULADO';
                            foreach ($proyectos as $p) {
                                if ($p['id_proyecto'] == $id_proyecto) {
                                    $nombre_proy_elegido = "[" . $p['ejercicio'] . "] " . $p['nombre_proyecto'];
                                    break;
                                }
                            }
                        ?>
                            <input type="text" class="form-control" value="<?= e($nombre_proy_elegido) ?>" readonly 
                                style="background: rgba(255,255,255,0.05); color: #fff; font-weight: 600; cursor: not-allowed;">
                            <input type="hidden" name="id_proyecto" value="<?= $id_proyecto ?>">
                        <?php else: ?>
                            <select name="id_proyecto" id="id_proyecto" class="form-control select2" onchange="verificarSaldo()">
                                <option value="">-- SELECCIONE UN PROYECTO (OPCIONAL) --</option>
                                <?php foreach ($proyectos as $p): ?>
                                    <option value="<?= $p['id_proyecto'] ?>" <?= ($id_proyecto == $p['id_proyecto']) ? 'selected' : '' ?>>
                                        [<?= $p['ejercicio'] ?>] <?= e($p['nombre_proyecto']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label x-small text-muted">ACCIÓN / DESCRIPCIÓN ESPECÍFICA</label>
                    <textarea name="nombre_proyecto_accion" class="form-control text-uppercase" rows="2"
                        required <?= $attrReadonlyC ?>><?= $is_editing ? e($fua['nombre_proyecto_accion']) : '' ?></textarea>
                </div>

                <?php if (isAdmin()): ?>
                    <div class="col-md-4">
                        <label class="form-label x-small text-muted">TIPO MOVIMIENTO</label>
                        <?php if ($bloquearFinal): ?><input type="hidden" name="tipo_suficiencia" value="<?= $fua['tipo_suficiencia'] ?>"><?php endif; ?>
                        <select name="tipo_suficiencia" class="form-control" <?= $bloquearFinal ? 'disabled' : '' ?>>
                            <?php foreach (['NUEVA', 'REFRENDO', 'SALDO POR EJERCER', 'CONTROL'] as $o): ?>
                                <option value="<?= $o ?>" <?= ($is_editing && $fua['tipo_suficiencia'] == $o) ? 'selected' : '' ?>>
                                    <?= $o ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label x-small text-muted">ESTATUS INTERNO</label>
                        <?php if ($bloquearFinal): ?><input type="hidden" name="estatus" value="<?= $fua['estatus'] ?>"><?php endif; ?>
                        <select name="estatus" class="form-control" <?= $bloquearFinal ? 'disabled' : '' ?>>
                            <option value="ACTIVO" <?= ($is_editing && $fua['estatus'] == 'ACTIVO') ? 'selected' : '' ?>>ACTIVO
                            </option>
                            <option value="CANCELADO" <?= ($is_editing && $fua['estatus'] == 'CANCELADO') ? 'selected' : '' ?>>
                                CANCELADO</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label x-small text-muted">RESULTADO TRÁMITE</label>
                        <?php if ($bloquearFinal): ?><input type="hidden" name="resultado_tramite" value="<?= $fua['resultado_tramite'] ?>"><?php endif; ?>
                        <select name="resultado_tramite" class="form-control" <?= $bloquearFinal ? 'disabled' : '' ?>>
                            <option value="PENDIENTE" <?= ($is_editing && $fua['resultado_tramite'] == 'PENDIENTE') ? 'selected' : '' ?>>PENDIENTE</option>
                            <option value="AUTORIZADO" <?= ($is_editing && $fua['resultado_tramite'] == 'AUTORIZADO') ? 'selected' : '' ?>>AUTORIZADO</option>
                            <option value="NO AUTORIZADO" <?= ($is_editing && $fua['resultado_tramite'] == 'NO AUTORIZADO') ? 'selected' : '' ?>>NO AUTORIZADO</option>
                        </select>
                    </div>
                <?php else: ?>
                    <?php if ($is_editing): ?>
                        <input type="hidden" name="tipo_suficiencia" value="<?= $fua['tipo_suficiencia'] ?>">
                        <input type="hidden" name="estatus" value="<?= $fua['estatus'] ?>">
                        <input type="hidden" name="resultado_tramite" value="<?= $fua['resultado_tramite'] ?>">
                    <?php else: ?>
                        <input type="hidden" name="tipo_suficiencia" value="NUEVA">
                        <input type="hidden" name="estatus" value="ACTIVO">
                        <input type="hidden" name="resultado_tramite" value="PENDIENTE">
                    <?php endif; ?>
                <?php endif; ?>

                <div class="col-12">
                    <hr class="border-secondary">
                </div>

                <div class="col-md-4">
                    <label class="form-label x-small text-muted">IMPORTE OBRA</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" name="monto_obra" id="m_obra" class="form-control text-end monto-calc"
                            value="<?= $is_editing ? number_format($fua['monto_obra'], 2) : '' ?>"
                            oninput="formatCurrency(this)" <?= $attrReadonlyC ?>>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label x-small text-muted">IMPORTE SUPERVISIÓN</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" name="monto_supervision" id="m_sup" class="form-control text-end monto-calc"
                            value="<?= $is_editing ? number_format($fua['monto_supervision'], 2) : '' ?>"
                            oninput="formatCurrency(this)" <?= $attrReadonlyC ?>>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label x-small text-muted text-primary fw-bold">IMPORTE TOTAL SOLICITADO</label>
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white">$</span>
                        <input type="text" name="monto_total_solicitado" id="m_total"
                            class="form-control text-end fw-bold"
                            value="<?= $is_editing ? number_format($fua['monto_total_solicitado'], 2) : '' ?>" readonly>
                    </div>
                    <div id="saldo_info" class="text-muted x-small mt-1 fw-bold"></div>
                    <div id="saldo_error" class="text-danger x-small mt-1 fw-bold" style="display:none;"></div>
                </div>

                <div class="col-12">
                    <hr class="border-secondary">
                </div>

                <div class="col-md-4">
                    <label class="form-label x-small text-muted font-weight-bold text-primary">NÚM. DE OFICIO O
                        TRÁMITE</label>
                    <input type="text" name="num_oficio_tramite" class="form-control fw-bold border-primary"
                        value="<?= $is_editing ? e($fua['num_oficio_tramite']) : '' ?>" required <?= $attrReadonlyC ?>>
                </div>
                <?php if (isAdmin()): ?>
                    <div class="col-md-4">
                        <label class="form-label x-small text-muted">FOLIO SISTEMA / OTRO</label>
                        <input type="text" name="folio_fua" class="form-control"
                            value="<?= $is_editing ? e($fua['folio_fua']) : '' ?>" <?= $bloquearFinal ? 'readonly' : '' ?>>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label x-small text-muted">NO. OFICIO DESF. YA</label>
                        <input type="text" name="oficio_desf_ya" class="form-control"
                            value="<?= $is_editing ? e($fua['oficio_desf_ya']) : '' ?>" <?= $bloquearFinal ? 'readonly' : '' ?>>
                    </div>
                <?php else: ?>
                    <?php if ($is_editing): ?>
                        <input type="hidden" name="folio_fua" value="<?= $fua['folio_fua'] ?>">
                        <input type="hidden" name="oficio_desf_ya" value="<?= $fua['oficio_desf_ya'] ?>">
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (isAdmin()): ?>
                    <div class="col-md-12">
                        <label class="form-label x-small text-muted">CLAVE PRESUPUESTAL</label>
                        <input type="text" name="clave_presupuestal" class="form-control font-monospace"
                            placeholder="00-00000-0000..." value="<?= $is_editing ? e($fua['clave_presupuestal']) : '' ?>" <?= $bloquearFinal ? 'readonly' : '' ?>>
                    </div>
                <?php else: ?>
                    <?php if ($is_editing): ?>
                        <input type="hidden" name="clave_presupuestal" value="<?= $fua['clave_presupuestal'] ?>">
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Nuevos Campos Requeridos -->
                <div class="col-md-6">
                    <label class="form-label x-small text-muted">FIRMA DE QUIEN AUTORIZA</label>
                    <input type="text" name="autorizado_por" class="form-control text-uppercase"
                        placeholder="NOMBRE DE QUIEN AUTORIZA"
                        value="<?= $is_editing ? e($fua['autorizado_por']) : '' ?>" <?= $attrReadonlyC ?>>
                </div>
                <div class="col-md-6">
                    <label class="form-label x-small text-muted">REGISTRO DE QUIEN LO ELABORA</label>
                    <input type="text" name="elaborado_por" class="form-control text-uppercase"
                        placeholder="NOMBRE DE QUIEN ELABORA"
                        value="<?= $is_editing ? e($fua['elaborado_por'] ?? getNombreCompleto($user)) : getNombreCompleto($user) ?>" <?= $attrReadonlyC ?>>
                </div>
                <div class="col-md-6">
                    <label class="form-label x-small text-muted">FECHA DE ELABORACIÓN</label>
                    <input type="date" name="fecha_elaboracion" class="form-control"
                        value="<?= $is_editing ? $fua['fecha_elaboracion'] : date('Y-m-d') ?>" <?= $attrReadonlyC ?>>
                </div>
                <div class="col-md-6">
                    <label class="form-label x-small text-muted">FECHA DE AUTORIZACIÓN</label>
                    <input type="date" name="fecha_autorizacion" class="form-control"
                        value="<?= $is_editing ? $fua['fecha_autorizacion'] : '' ?>" <?= $attrReadonlyC ?>>
                </div>

                <?php if (isAdmin()): ?>
                    <div class="col-12">
                        <label class="form-label x-small text-muted">OBSERVACIONES</label>
                        <textarea name="observaciones" class="form-control"
                            rows="2"><?= $is_editing ? e($fua['observaciones']) : '' ?></textarea>
                    </div>
                <?php else: ?>
                    <?php if ($is_editing): ?>
                        <input type="hidden" name="observaciones" value="<?= $fua['observaciones'] ?>">
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="mt-5 pt-4 border-top text-end">
                <a href="solicitudes-suficiencia.php" class="btn btn-secondary px-4 me-2">CANCELAR</a>
                <button type="submit" id="btn_submit" class="btn btn-primary px-5 fw-bold">GUARDAR SOLICITUD</button>
            </div>
        </div>
    </form>
</main>

<style>
    .hoja-papel {
        background: var(--bg-card);
        border: 1px solid var(--border-primary);
        max-width: 1000px;
        margin: 0 auto;
        padding: 3rem;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
    }

    .sheet-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        border-bottom: 2px solid var(--accent-primary);
        padding-bottom: 1rem;
        margin-bottom: 2rem;
    }

    .timeline-box {
        background: rgba(255, 255, 255, 0.02);
        padding: 2rem;
        border-radius: var(--radius-md);
        border: 1px dashed var(--border-primary);
    }

    .timeline-steps {
        display: flex;
        justify-content: space-between;
        position: relative;
    }

    .step {
        flex: 1;
        text-align: center;
        position: relative;
        z-index: 1;
    }

    .step-circle {
        width: 40px;
        height: 40px;
        background: var(--bg-card);
        border: 2px solid var(--border-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
        color: var(--text-muted);
        transition: all 0.3s;
    }

    .step-circle.active {
        border-color: var(--accent-primary);
        color: var(--accent-primary);
        box-shadow: 0 0 10px rgba(var(--accent-primary-rgb), 0.3);
    }

    .step label {
        display: block;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 0.5rem;
    }

    .x-small {
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.5px;
    }
</style>

<script>
    let saldoDisponible = 999999999;

    function formatCurrency(input) {
        let value = input.value.replace(/[^0-9.]/g, '');
        const parts = value.split('.');
        if (parts.length > 2) value = parts[0] + '.' + parts.slice(1).join('');
        const numberParts = value.split('.');
        const integerPart = numberParts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        const decimalPart = numberParts.length > 1 ? '.' + numberParts[1].substring(0, 2) : '';
        input.value = integerPart + decimalPart;
        calcTotal();
    }

    function calcTotal() {
        const obra = parseFloat(document.getElementById('m_obra').value.replace(/,/g, '')) || 0;
        const sup = parseFloat(document.getElementById('m_sup').value.replace(/,/g, '')) || 0;
        const total = obra + sup;
        document.getElementById('m_total').value = total.toLocaleString('es-MX', { minimumFractionDigits: 2 });

        const saldoLabel = document.getElementById('saldo_info');
        const errorLabel = document.getElementById('saldo_error');

        if (saldoDisponible !== 999999999) {
            saldoLabel.innerHTML = 'Saldo disponible: $' + saldoDisponible.toLocaleString('es-MX', { minimumFractionDigits: 2 });
        } else {
            saldoLabel.innerHTML = '';
        }

        if (total > saldoDisponible) {
            errorLabel.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ¡El monto excede el saldo! Faltan: $' + (total - saldoDisponible).toLocaleString('es-MX', { minimumFractionDigits: 2 });
            errorLabel.style.display = 'block';
            document.getElementById('m_total').classList.add('text-danger');
        } else {
            errorLabel.style.display = 'none';
            document.getElementById('m_total').classList.remove('text-danger');
        }
    }

    async function verificarSaldo() {
        const idProy = document.getElementById('id_proyecto').value;
        if (!idProy) {
            saldoDisponible = 999999999;
            calcTotal();
            return;
        }
        const res = await fetch(`get-saldo-proyecto.php?id_proyecto=${idProy}&id_fua=<?= $is_editing ? $id_fua : 0 ?>`);
        const data = await res.json();
        if (!data.error) {
            saldoDisponible = data.saldo_disponible;
            calcTotal();
        }
    }

    function preSubmit() {
        document.querySelectorAll('.monto-calc, #m_total').forEach(i => i.value = i.value.replace(/,/g, ''));
    }

    document.addEventListener('DOMContentLoaded', () => {
        verificarSaldo();
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>