<?php
$db = (new Database())->getConnection();

// --- Edit Mode Logic ---
$id_fua = isset($_GET['id']) ? (int) $_GET['id'] : null;
$pre_id_proyecto = isset($_GET['id_proyecto']) ? (int) $_GET['id_proyecto'] : null;
$fua = null;
$is_editing = false;

if ($id_fua) {
    $stmt = $db->prepare("SELECT * FROM fuas WHERE id_fua = ?");
    $stmt->execute([$id_fua]);
    $fua = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($fua) {
        $is_editing = true;
    }
}

// --- Fetch Catalogs ---
// 1. Proyectos
$stmtProyectos = $db->query("SELECT id_proyecto, nombre_proyecto, ejercicio FROM proyectos_obra ORDER BY ejercicio DESC, nombre_proyecto ASC");
$proyectos = $stmtProyectos->fetchAll(PDO::FETCH_ASSOC);

// 2. Tipos de Acción FUA
$stmtTipos = $db->query("SELECT * FROM cat_tipos_fua_accion ORDER BY nombre_tipo_accion ASC");
$tiposAccion = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

// 3. Fuentes de Financiamiento (CATALOGO)
$sqlFuentes = "SELECT * FROM cat_fuentes_financiamiento WHERE activo = 1";
if ($is_editing && !empty($fua['fuente_recursos'])) {
    $sqlFuentes .= " OR abreviatura = " . $db->quote($fua['fuente_recursos']);
}
$sqlFuentes .= " ORDER BY anio DESC, abreviatura ASC";
$stmtFuentes = $db->query($sqlFuentes);
$fuentes = $stmtFuentes->fetchAll(PDO::FETCH_ASSOC);

// Fetch Employees for Oficio "al vuelo"
$empleados = $db->query("
    SELECT e.*, p.nombre as puesto_nombre,
           CONCAT(COALESCE(e.nombres, ''), ' ', COALESCE(e.apellido_paterno, ''), ' ', COALESCE(e.apellido_materno, '')) as nombre_completo
    FROM empleados e 
    LEFT JOIN puestos_trabajo p ON e.puesto_trabajo_id = p.id 
    WHERE e.activo = 1
    ORDER BY e.apellido_paterno, e.nombres
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-8">
        <h2 class="text-primary fw-bold"><i class="bi bi-folder2-open"></i>
            <?php echo $is_editing ? 'Editar' : 'Captura de'; ?> SUFICIENCIA PRESUPUESTAL <small class="text-muted fs-5">(Vista Carpeta)</small></h2>
        <p class="text-muted">
            Registro de la Suficiencia Presupuestal / Afectación.
        </p>
    </div>
    <div class="col-4 text-end">
        <a href="/pao/index.php?route=recursos_financieros/fuas" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Regresar
        </a>
    </div>
</div>

<?php if (isset($_SESSION['error_fua'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-octagon-fill me-2"></i>
        <?php echo $_SESSION['error_fua']; unset($_SESSION['error_fua']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form action="/pao/index.php?route=recursos_financieros/fuas/guardar" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
    
    <?php if ($is_editing): ?>
            <input type="hidden" name="id_fua" value="<?php echo $fua['id_fua']; ?>">
    <?php endif; ?>

    <!-- TABS NAVIGATION -->
    <ul class="nav nav-tabs mb-4 px-3 border-bottom-0" id="fuaTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-uppercase" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                <i class="bi bi-info-circle me-2"></i>Datos Generales
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-uppercase" id="seguimiento-tab" data-bs-toggle="tab" data-bs-target="#seguimiento" type="button" role="tab" aria-controls="seguimiento" aria-selected="false">
                <i class="bi bi-calendar-week me-2"></i>Seguimiento
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-uppercase" id="adicionales-tab" data-bs-toggle="tab" data-bs-target="#adicionales" type="button" role="tab" aria-controls="adicionales" aria-selected="false">
                <i class="bi bi-paperclip me-2"></i>Documentos y Extras
            </button>
        </li>
    </ul>

    <!-- TABS CONTENT -->
    <div class="tab-content" id="fuaTabsContent">
        
        <!-- TAP 1: DATOS GENERALES -->
        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                     <div class="row g-3">
                        <!-- ESTATUS -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase text-secondary">Estatus</label>
                            <select name="estatus" class="form-select">
                                <?php
                                $opts = ['ACTIVO', 'CANCELADO'];
                                foreach ($opts as $o) {
                                    $sel = ($is_editing && $fua['estatus'] == $o) ? 'selected' : '';
                                    echo "<option value='$o' $sel>$o</option>";
                                }
                                ?>
                            </select>
                        </div>
        
                        <!-- RESULTADO TRAMITE -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase text-secondary">Resultado</label>
                            <select name="resultado_tramite" class="form-select">
                                <option value="PENDIENTE" <?php echo ($is_editing && $fua['resultado_tramite'] == 'PENDIENTE') ? 'selected' : ''; ?>>PENDIENTE</option>
                                <option value="AUTORIZADO" <?php echo ($is_editing && $fua['resultado_tramite'] == 'AUTORIZADO') ? 'selected' : ''; ?>>AUTORIZADO</option>
                                <option value="NO AUTORIZADO" <?php echo ($is_editing && $fua['resultado_tramite'] == 'NO AUTORIZADO') ? 'selected' : ''; ?>>RECHAZADO</option>
                            </select>
                        </div>
        
                        <!-- TIPO DE FUA -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase text-secondary">Tipo de Suficiencia</label>
                            <select name="tipo_suficiencia" class="form-select">
                                <?php
                                $opts = ['NUEVA', 'REFRENDO', 'SALDO POR EJERCER', 'CONTROL'];
                                foreach ($opts as $o) {
                                    $sel = ($is_editing && $fua['tipo_suficiencia'] == $o) ? 'selected' : '';
                                    echo "<option value='$o' $sel>$o</option>";
                                }
                                ?>
                            </select>
                        </div>
        
                        <!-- FOLIO -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase text-secondary">Folio de FUA</label>
                            <input type="text" name="folio_fua" class="form-control" value="<?php echo $is_editing ? $fua['folio_fua'] : ''; ?>">
                        </div>
        
                        <!-- NOMBRE DEL PROYECTO -->
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase text-secondary">Nombre del Proyecto o Acción</label>
                            <input type="text" name="nombre_proyecto_accion" class="form-control" 
                                   value="<?php echo $is_editing ? htmlspecialchars($fua['nombre_proyecto_accion']) : ''; ?>" required>
                        </div>
        
                        <!-- PROYECTO (CATALOGO) -->
                        <div class="col-md-12">
                             <label class="form-label fw-bold small text-uppercase text-secondary">Proyecto Vinculado (Catálogo)</label>
                             <select name="id_proyecto" class="form-select select2">
                                 <option value="">-- Seleccione un Proyecto (Opcional) --</option>
                                 <?php foreach ($proyectos as $p): ?>
                                         <option value="<?php echo $p['id_proyecto']; ?>" 
                                             <?php
                                             $selected = '';
                                             if ($is_editing && $fua['id_proyecto'] == $p['id_proyecto']) {
                                                 $selected = 'selected';
                                             } elseif (!$is_editing && $pre_id_proyecto == $p['id_proyecto']) {
                                                 $selected = 'selected';
                                             }
                                             echo $selected;
                                             ?>>
                                             <?php echo $p['ejercicio'] . ' - ' . htmlspecialchars($p['nombre_proyecto']); ?>
                                         </option>
                                 <?php endforeach; ?>
                             </select>
                        </div>
        
                        <!-- (CAMPOS ELIMINADOS: TIPO OBRA Y DIRECCION SOLICITANTE) -->
        
                        <div class="col-12 mt-4">
                            <h6 class="text-secondary border-bottom pb-2 mb-3 text-uppercase fw-bold" style="font-size: 0.85rem;">
                                <i class="bi bi-cash-stack me-2"></i>Información Financiera
                            </h6>
                        </div>

                        <!-- IMPORTES DETALLADOS -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase text-secondary">Importe de Obra</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" name="monto_obra" id="monto_obra" class="form-control text-end currency-input" 
                                    value="<?php echo $is_editing ? number_format($fua['monto_obra'], 2) : '0.00'; ?>" oninput="calcularTotal()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase text-secondary">Importe de Supervisión</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" name="monto_supervision" id="monto_supervision" class="form-control text-end currency-input" 
                                    value="<?php echo $is_editing ? number_format($fua['monto_supervision'], 2) : '0.00'; ?>" oninput="calcularTotal()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase text-secondary">Importe Solicitado</label>
                            <div class="input-group">
                                <span class="input-group-text bg-primary text-white">$</span>
                                <input type="text" name="importe" id="importe" class="form-control text-end fw-bold bg-white" 
                                    value="<?php echo $is_editing ? number_format($fua['importe'], 2) : '0.00'; ?>" readonly>
                            </div>
                        </div>

                        <!-- FUENTE DE RECURSOS -->
                        <div class="col-md-12 mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small text-uppercase text-secondary mb-0">Fuente de Recursos</label>
                                <a href="/pao/index.php?route=recursos_financieros/cat_fuentes" target="_blank" class="text-primary small text-decoration-none" title="Administrar Catálogo">
                                    <i class="bi bi-gear-fill"></i> Gestionar
                                </a>
                            </div>
                            <select name="fuente_recursos" class="form-select">
                                <option value="">-- Seleccione Fuente --</option>
                                <?php foreach ($fuentesFinanciamiento as $ff): ?>
                                        <option value="<?php echo $ff['abreviatura']; ?>" 
                                            <?php echo ($is_editing && $fua['fuente_recursos'] == $ff['abreviatura']) ? 'selected' : ''; ?>>
                                            <?php 
                                            $label = $ff['anio'] . ' - ' . $ff['abreviatura'] . ' (' . $ff['nombre_fuente'] . ')';
                                            if ($ff['activo'] == 0) $label .= ' (INACTIVO)';
                                            echo $label; 
                                            ?>
                                        </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: SEGUIMIENTO (TIMELINE Y OFICIOS) -->
        <div class="tab-pane fade" id="seguimiento" role="tabpanel" aria-labelledby="seguimiento-tab">
            
            <!-- TIMELINE VISUAL -->
            <div class="card shadow-sm mb-4 border-0 bg-light">
                <div class="card-body py-4">
                    <label class="form-label fw-bold small text-secondary mb-4 d-block text-center text-uppercase ls-1">Línea de Tiempo del Documento</label>
                    <div class="timeline-container d-flex justify-content-between position-relative px-4">
                        <div class="timeline-line position-absolute start-0 w-100 bg-secondary opacity-25" style="height: 4px; top: 18px !important; z-index: 0; left: 0; right: 0;"></div>

                        <!-- PASO 1: Ingreso Admvo -->
                        <div class="timeline-step text-center position-relative" style="z-index: 1; flex: 1;">
                            <div class="step-indicator bg-white border border-4 border-<?php echo (!empty($fua['fecha_ingreso_admvo'])) ? 'success' : 'secondary'; ?> rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 40px; height: 40px;">
                                <i class="bi bi-<?php echo (!empty($fua['fecha_ingreso_admvo'])) ? 'check-lg text-success' : 'card-list text-secondary'; ?> fw-bold"></i>
                            </div>
                            <h6 class="fw-bold text-<?php echo (!empty($fua['fecha_ingreso_admvo'])) ? 'dark' : 'muted'; ?> mb-2">Ingreso Admvo.</h6>
                            <div class="px-2">
                                <input type="date" name="fecha_ingreso_admvo" onchange="updateStepStatus(this)" class="form-control form-control-sm text-center border-<?php echo (!empty($fua['fecha_ingreso_admvo'])) ? 'success' : ''; ?>" value="<?php echo $is_editing ? $fua['fecha_ingreso_admvo'] : ''; ?>">
                            </div>
                        </div>

                        <!-- PASO 2: Ingreso Control Ptal -->
                        <div class="timeline-step text-center position-relative" style="z-index: 1; flex: 1;">
                            <div class="step-indicator bg-white border border-4 border-<?php echo (!empty($fua['fecha_ingreso_cotrl_ptal'])) ? 'success' : 'secondary'; ?> rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 40px; height: 40px;">
                                <i class="bi bi-<?php echo (!empty($fua['fecha_ingreso_cotrl_ptal'])) ? 'check-lg text-success' : 'currency-dollar text-secondary'; ?> fw-bold"></i>
                            </div>
                            <h6 class="fw-bold text-<?php echo (!empty($fua['fecha_ingreso_cotrl_ptal'])) ? 'dark' : 'muted'; ?> mb-2">Control Ptal.</h6>
                            <div class="px-2">
                                <input type="date" name="fecha_ingreso_cotrl_ptal" onchange="updateStepStatus(this)" class="form-control form-control-sm text-center border-<?php echo (!empty($fua['fecha_ingreso_cotrl_ptal'])) ? 'success' : ''; ?>" value="<?php echo $is_editing ? $fua['fecha_ingreso_cotrl_ptal'] : ''; ?>">
                            </div>
                        </div>

                        <!-- PASO 3: GRUPO FIRMAS -->
                        <div class="timeline-step text-center position-relative" style="z-index: 1; flex: 2;">
                            <?php 
                            $hasTitular = !empty($fua['fecha_titular']);
                            $hasRegreso = !empty($fua['fecha_firma_regreso']);
                            $stepColor = ($hasTitular && $hasRegreso) ? 'success' : (($hasTitular || $hasRegreso) ? 'warning' : 'secondary');
                            $stepIcon = ($hasTitular && $hasRegreso) ? 'check-all' : 'pen';
                            ?>
                            <div class="step-indicator bg-white border border-4 border-<?php echo $stepColor; ?> rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 40px; height: 40px;">
                                <i class="bi bi-<?php echo $stepIcon; ?> text-<?php echo $stepColor; ?> fw-bold"></i>
                            </div>
                            <h6 class="fw-bold text-<?php echo ($stepColor != 'secondary') ? 'dark' : 'muted'; ?> mb-2">Proceso de Firmas</h6>
                            <div class="d-flex justify-content-center gap-2 px-1">
                                <div class="text-center">
                                    <small class="d-block text-muted" style="font-size: 0.7rem;">Titular</small>
                                    <input type="date" name="fecha_titular" onchange="updateStepStatus(this)" class="form-control form-control-sm text-center px-1 border-<?php echo $hasTitular ? 'success' : ''; ?>" style="width: 110px;" value="<?php echo $is_editing ? $fua['fecha_titular'] : ''; ?>">
                                </div>
                                <div class="vr bg-secondary opacity-25"></div>
                                <div class="text-center">
                                    <small class="d-block text-muted" style="font-size: 0.7rem;">Regreso</small>
                                    <input type="date" name="fecha_firma_regreso" onchange="updateStepStatus(this)" class="form-control form-control-sm text-center px-1 border-<?php echo $hasRegreso ? 'success' : ''; ?>" style="width: 110px;" value="<?php echo $is_editing ? $fua['fecha_firma_regreso'] : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <!-- PASO 4: GRUPO SFA -->
                        <div class="timeline-step text-center position-relative" style="z-index: 1; flex: 2;">
                            <?php 
                            $hasAcuse = !empty($fua['fecha_acuse_antes_fa']);
                            $hasRespuesta = !empty($fua['fecha_respuesta_sfa']);
                            $stepColorSFA = ($hasAcuse && $hasRespuesta) ? 'primary' : (($hasAcuse || $hasRespuesta) ? 'warning' : 'secondary');
                            $stepIconSFA = ($hasAcuse && $hasRespuesta) ? 'building-check' : 'building';
                            ?>
                            <div class="step-indicator bg-white border border-4 border-<?php echo $stepColorSFA; ?> rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 40px; height: 40px;">
                                <i class="bi bi-<?php echo $stepIconSFA; ?> text-<?php echo $stepColorSFA; ?> fw-bold"></i>
                            </div>
                            <h6 class="fw-bold text-<?php echo ($stepColorSFA != 'secondary') ? 'dark' : 'muted'; ?> mb-2">Trámite SFyA</h6>
                            <div class="d-flex justify-content-center gap-2 px-1">
                                <div class="text-center">
                                    <small class="d-block text-muted" style="font-size: 0.7rem;">Acuse</small>
                                    <input type="date" name="fecha_acuse_antes_fa" onchange="updateStepStatus(this)" class="form-control form-control-sm text-center px-1 border-<?php echo $hasAcuse ? 'primary' : ''; ?>" style="width: 110px;" value="<?php echo $is_editing ? $fua['fecha_acuse_antes_fa'] : ''; ?>">
                                </div>
                                <div class="vr bg-secondary opacity-25"></div>
                                <div class="text-center">
                                    <small class="d-block text-muted" style="font-size: 0.7rem;">Respuesta</small>
                                    <input type="date" name="fecha_respuesta_sfa" onchange="updateStepStatus(this)" class="form-control form-control-sm text-center px-1 border-<?php echo $hasRespuesta ? 'primary' : ''; ?>" style="width: 110px;" value="<?php echo $is_editing ? $fua['fecha_respuesta_sfa'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- OFICIOS INFO -->
             <div class="card shadow-sm border-0 rounded-3">
                 <div class="card-body p-4">
                     <h5 class="mb-3 text-secondary">Documentación de Entrada</h5>
                     <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">No. Oficio Entrada</label>
                            <div class="input-group">
                                 <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                 <input type="text" name="no_oficio_entrada" class="form-control" value="<?php echo $is_editing ? $fua['no_oficio_entrada'] : ''; ?>">
                            </div>
                        </div>
                         <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Oficio DESF Y A</label>
                             <div class="input-group">
                                 <span class="input-group-text bg-light"><i class="bi bi-envelope-check"></i></span>
                                <input type="text" name="oficio_desf_ya" class="form-control" value="<?php echo $is_editing ? $fua['oficio_desf_ya'] : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Clave Presupuestal</label>
                             <div class="input-group">
                                 <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                <input type="text" name="clave_presupuestal" class="form-control" value="<?php echo $is_editing ? $fua['clave_presupuestal'] : ''; ?>">
                            </div>
                        </div>
                     </div>
                 </div>
            </div>
        </div>

        <!-- TAB 3: ADICIONALES -->
        <div class="tab-pane fade" id="adicionales" role="tabpanel" aria-labelledby="adicionales-tab">
            <div class="card shadow-sm border-0 rounded-3">
                 <div class="card-body p-4">
                     <div class="row g-3">
                         <div class="col-md-12">
                            <label class="form-label fw-bold small text-secondary">Tarea / Actividad Específica</label>
                            <input type="text" name="tarea" class="form-control" value="<?php echo $is_editing ? htmlspecialchars($fua['tarea']) : ''; ?>">
                         </div>
                          <div class="col-md-12">
                            <label class="form-label fw-bold small text-secondary">Observaciones Generales</label>
                            <textarea name="observaciones" class="form-control" rows="4"><?php echo $is_editing ? htmlspecialchars($fua['observaciones']) : ''; ?></textarea>
                         </div>
                         
                         <div class="col-md-12">
                            <label class="form-label fw-bold small text-secondary">Documentos Adjuntos</label>
                            <input type="file" name="documentos_adjuntos[]" class="form-control" multiple>
                            <?php if ($is_editing && !empty($fua['documentos_adjuntos'])): ?>
                                    <div class="alert alert-light border mt-3 d-flex align-items-center">
                                        <i class="bi bi-file-earmark-check display-6 me-3 text-secondary"></i>
                                        <div>
                                            <strong>Archivos actuales:</strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($fua['documentos_adjuntos']); ?></small>
                                        </div>
                                    </div>
                            <?php endif; ?>
                         </div>
                     </div>
                 </div>
            </div>
        </div>
    </div> <!-- End Tab Content -->

    <!-- FOOTER BOTONES -->
    <div class="fixed-bottom bg-white border-top py-3 shadow-lg" style="left: 250px;"> <!-- Adjust left margin for sidebar -->
        <div class="container-fluid px-5 text-end">
            <?php if ($is_editing): ?>
                <button type="button" class="btn btn-outline-primary px-4 me-2 border-2 fw-bold" onclick="prepararOficio(<?php echo $fua['id_fua']; ?>)">
                    <i class="bi bi-file-earmark-pdf me-2"></i>GENERAR OFICIO
                </button>
            <?php endif; ?>
            <a href="/pao/index.php?route=recursos_financieros/fuas" class="btn btn-secondary px-4 me-2">Cancelar</a>
            <button type="submit" class="btn btn-primary px-5 fw-bold"><i class="bi bi-save2"></i> Guardar FUA</button>
        </div>
    </div>
    <div style="height: 80px;"></div> <!-- Spacer -->

</form>

<!-- Modal Generar Oficio Al Vuelo -->
<div class="modal fade" id="modalOficio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Personalizar Oficio "Al Vuelo"</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formOficio" target="_blank" action="/pao/modulos/recursos_financieros/fuas/generar_oficio.php" method="GET">
                    <input type="hidden" name="id" id="modal_id_fua">
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold"><i class="bi bi-person-fill-down me-2"></i>DATOS DEL DESTINATARIO</h6>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase">Buscar Empleado</label>
                                <select class="form-select select2-empleados" onchange="autoFillOficio(this, 'dest')">
                                    <option value="">-- Seleccionar de la tabla --</option>
                                    <?php foreach($empleados as $emp): ?>
                                        <option value="<?php echo htmlspecialchars($emp['nombre_completo']); ?>" 
                                                data-cargo="<?php echo htmlspecialchars($emp['puesto_nombre'] ?: ''); ?>">
                                            <?php echo htmlspecialchars($emp['nombre_completo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase text-muted">Nombre y Título (Manual)</label>
                                <input type="text" name="dest_nom" id="oficio_dest_nom" class="form-control" value="C.P. MARLEN SÁNCHEZ GARCÍA">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase text-muted">Cargo</label>
                                <input type="text" name="dest_car" id="oficio_dest_car" class="form-control" value="DIRECTORA DE ADMINISTRACIÓN">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold"><i class="bi bi-person-fill-up me-2"></i>DATOS DEL REMITENTE</h6>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase">Buscar Empleado</label>
                                <select class="form-select select2-empleados" onchange="autoFillOficio(this, 'rem')">
                                    <option value="">-- Seleccionar de la tabla --</option>
                                    <?php foreach($empleados as $emp): ?>
                                        <option value="<?php echo htmlspecialchars($emp['nombre_completo']); ?>" 
                                                data-cargo="<?php echo htmlspecialchars($emp['puesto_nombre'] ?: ''); ?>">
                                            <?php echo htmlspecialchars($emp['nombre_completo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase text-muted">Nombre y Título (Manual)</label>
                                <input type="text" name="rem_nom" id="oficio_rem_nom" class="form-control" value="ING. CÉSAR OTHÓN RODRÍGUEZ GÓMEZ">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase text-muted">Cargo</label>
                                <input type="text" name="rem_car" id="oficio_rem_car" class="form-control" value="SUBSECRETARIO DE INFRAESTRUCTURA CARRETERA">
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3 d-flex align-items-center border-0" style="background-color: #f0f7ff; color: #055160;">
                        <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                        <small>Puedes seleccionar un empleado de la lista para autocompletar o escribir directamente en los campos inferiores si no se encuentra en el registro.</small>
                    </div>

                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-light px-4 me-2 border" data-bs-dismiss="modal">CERRAR</button>
                        <button type="submit" class="btn btn-dark px-5 shadow-sm" onclick="bootstrap.Modal.getInstance(document.getElementById('modalOficio')).hide();">
                            <i class="bi bi-file-pdf me-2"></i>GENERAR PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos Tabs Carpetas */
    .nav-tabs .nav-link {
        color: #6c757d;
        border: none;
        background-color: transparent;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
    }
    .nav-tabs .nav-link:hover {
        border-color: transparent;
        color: #0d6efd;
    }
    .nav-tabs .nav-link.active {
        color: #0d6efd;
        background-color: transparent;
        border-bottom: 3px solid #0d6efd;
    }
    
    /* Estilo para campos con información */
    .is-filled {
        background-color: #e8f5e9 !important;
        border-right: 3px solid #66bb6a !important;
    }
    /* Estilo para estatus CANCELADO */
    .is-cancelled {
        background-color: #ffebee !important;
        border-right: 3px solid #ef5350 !important;
        color: #c62828 !important;
    }
    .form-control, .form-select {
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }
</style>

<script>
    function PreparingModal() {
        // Init Select2 if available inside modal
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            $('.select2-empleados').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('#modalOficio')
            });
        }
    }

    function autoFillOficio(select, type) {
        const selectedOption = select.options[select.selectedIndex];
        if(!selectedOption.value) return;

        const nameInput = document.getElementById(`oficio_${type}_nom`);
        const cargoInput = document.getElementById(`oficio_${type}_car`);
        
        nameInput.value = selectedOption.value;
        cargoInput.value = selectedOption.getAttribute('data-cargo') || '';
        
        // Add subtle highlight effect
        [nameInput, cargoInput].forEach(inp => {
            inp.style.backgroundColor = '#fff9c4';
            setTimeout(() => inp.style.backgroundColor = 'white', 1000);
        });
    }

    function prepararOficio(id) {
        document.getElementById('modal_id_fua').value = id;
        var modalEl = document.getElementById('modalOficio');
        var myModal = new bootstrap.Modal(modalEl);
        PreparingModal();
        myModal.show();
    }

    function updateStepStatus(input) {
        // Reuse logic logic
        const stepContainer = input.closest('.timeline-step');
        if(!stepContainer) return;

        const indicator = stepContainer.querySelector('.step-indicator');
        const icon = indicator.querySelector('i');
        const title = stepContainer.querySelector('h6');
        const isFilled = input.value !== '';
        const colorClass = input.name === 'fecha_acuse_antes_fa' || input.name === 'fecha_respuesta_sfa' ? 'primary' : 'success';
        
        if (isFilled) {
            indicator.className = `step-indicator bg-white border border-4 border-${colorClass} rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm`;
            icon.className = `bi bi-check-lg text-${colorClass} fw-bold`;
            title.classList.remove('text-muted');
            title.classList.add('text-dark');
            input.classList.add(`border-${colorClass}`);
        } else {
            // Revert logic simplified for tab view
            indicator.className = 'step-indicator bg-white border border-4 border-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm';
            // Simple generic icon revert or keep generic
            icon.className = `bi bi-circle text-secondary fw-bold`; 
            title.classList.add('text-muted');
            title.classList.remove('text-dark');
            input.classList.remove(`border-${colorClass}`);
        }
    }

    function formatMoney(amount) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }

    function parseMoney(text) {
        return parseFloat(text.replace(/,/g, '')) || 0;
    }

    function calcularTotal() {
        const obraInput = document.getElementById('monto_obra');
        const supervisionInput = document.getElementById('monto_supervision');
        const totalInput = document.getElementById('importe');

        const obra = parseMoney(obraInput.value);
        const supervision = parseMoney(supervisionInput.value);
        const total = obra + supervision;

        totalInput.value = formatMoney(total);
        
        // Trigger validation if it exists in scope
        if(typeof validarMontoGlobal === 'function') {
            validarMontoGlobal();
        }
    }

    // Event delegation for formatting on blur
    document.addEventListener('blur', function(e) {
        if (e.target.classList.contains('currency-input')) {
            const val = parseMoney(e.target.value);
            e.target.value = formatMoney(val);
        }
    }, true);

    let validarMontoGlobal = null;

    document.addEventListener('DOMContentLoaded', function () {
        const importeInput = document.querySelector('input[name="importe"]');
        const proyectoSelectQuery = 'select[name="id_proyecto"]';
        const currentFuaId = "<?php echo $is_editing ? $fua['id_fua'] : 0; ?>";
        let saldoMaximo = 0;

        // --- Init Select2 ---
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
             $(proyectoSelectQuery).select2({
                 theme: 'bootstrap-5',
                 width: '100%',
                 dropdownParent: $('#general') // As it is inside a tab
             });
        }

        // --- Validación de Saldo de Proyecto ---
        function consultarSaldo() {
            const idProyecto = $(proyectoSelectQuery).val();
            if(!idProyecto) return;

            fetch('<?php echo BASE_URL; ?>/modulos/recursos_financieros/fuas/get_saldo_proyecto.php?id_proyecto=' + idProyecto + '&id_fua=' + currentFuaId)
                .then(response => response.json())
                .then(data => {
                    if(data.error) return;
                    saldoMaximo = parseFloat(data.saldo_disponible);
                    validarMonto(); 
                })
                .catch(err => console.error("Error al obtener saldo:", err));
        }

        function validarMonto() {
            if(!importeInput.value) return;
            const idProyecto = $(proyectoSelectQuery).val();
            if(!idProyecto) return;

            const monto = parseMoney(importeInput.value);
            
            // Limpiar alertas previas
            const parent = importeInput.parentElement;
            const existingAlert = parent.querySelector('.alert-saldo-proyecto');
            if(existingAlert) existingAlert.remove();
            
            importeInput.classList.remove('is-invalid');
            
            if (monto > saldoMaximo) {
                importeInput.classList.add('is-invalid');
                const div = document.createElement('div');
                div.className = 'invalid-feedback alert-saldo-proyecto d-block fw-bold mt-2';
                div.innerHTML = `
                    <i class="bi bi-exclamation-triangle-fill"></i> El importe ($${monto.toLocaleString('es-MX')}) excede el saldo disponible del proyecto.<br>
                    <span class="text-dark">Saldo Disponible: $${saldoMaximo.toLocaleString('es-MX', {minimumFractionDigits: 2})}</span>
                `;
                parent.appendChild(div);
            }
        }
        validarMontoGlobal = validarMonto;

        // Listeners
        if (typeof jQuery !== 'undefined') {
            $(proyectoSelectQuery).on('change', consultarSaldo);
        }
        importeInput.addEventListener('input', validarMonto);
        
        // Ejecutar al inicio si ya hay proyecto seleccionado
        if($(proyectoSelectQuery).val()) {
            consultarSaldo();
        }

        // --- Uppercase Logic (excluding currency fields) ---
        const textInputs = document.querySelectorAll('input[type="text"], textarea');
        textInputs.forEach(function (input) {
            if (!input.classList.contains('currency-input') && input.id !== 'importe') {
                input.classList.add('text-uppercase');
                input.addEventListener('input', function () {
                    this.value = this.value.toUpperCase();
                });
            }
        });

        // --- Highlight Logic ---
        const allInputs = document.querySelectorAll('.form-control, .form-select');
        function checkUsage(input) {
            if (input.name === 'estatus' && input.value === 'CANCELADO') {
                input.classList.remove('is-filled');
                input.classList.add('is-cancelled');
                return;
            } else if (input.name === 'estatus') {
                 input.classList.remove('is-cancelled');
            }

            if(input.value && input.value.trim() !== '') {
                input.classList.add('is-filled');
            } else {
                input.classList.remove('is-filled');
            }
        }

        allInputs.forEach(input => {
            checkUsage(input);
            input.addEventListener('input', () => checkUsage(input));
            input.addEventListener('change', () => checkUsage(input));
            input.addEventListener('blur', () => checkUsage(input));
        });
    });
</script>
