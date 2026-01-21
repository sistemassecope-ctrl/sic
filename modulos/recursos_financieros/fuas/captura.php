<?php
$db = (new Database())->getConnection();

// --- Edit Mode Logic ---
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

// 2. Tipos de Acción FUA (DEPRECATED/REMOVED)
// $stmtTipos = $db->query("SELECT * FROM cat_tipos_fua_accion ORDER BY nombre_tipo_accion ASC");
// $tiposAccion = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

// 3. Fuentes de Financiamiento (CATALOGO)
$stmtFuentes = $db->query("SELECT * FROM cat_fuentes_financiamiento WHERE activo = 1 ORDER BY anio DESC, abreviatura ASC");
$fuentesFinanciamiento = $stmtFuentes->fetchAll(PDO::FETCH_ASSOC);

// 4. Cargar Documentos Adjuntos (Archivo Digital)
$documentosExistentes = [];
if ($is_editing && !empty($fua['documentos_adjuntos'])) {
    $ids = json_decode($fua['documentos_adjuntos'], true);
    if (is_array($ids) && count($ids) > 0) {
        $idsStr = implode(',', array_map('intval', $ids));
        $stmtDocs = $db->query("SELECT * FROM archivo_documentos WHERE id_documento IN ($idsStr)");
        $documentosExistentes = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>


<style>
    body {
        background-color: #e9ecef; /* Fondo escritorio */
    }
    .hoja-papel {
        background: white;
        max-width: 1000px; /* Ancho carta/oficio */
        margin: 30px auto;
        padding: 50px 60px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08); /* Sombra suave */
        border: 1px solid #d1d5db;
        position: relative;
    }
    .sheet-header {
        border-bottom: 2px solid #212529;
        margin-bottom: 30px;
        padding-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }
    .form-label {
        color: #495057;
        font-weight: 600 !important;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .form-control, .form-select {
        border-radius: 0; /* Bordes cuadrados */
        border: 1px solid #ced4da;
        padding: 8px 12px;
        font-size: 0.95rem;
        background-color: #fcfcfc;
    }
    .form-control:focus, .form-select:focus {
        border-color: #495057;
        box-shadow: none;
        background-color: #fff;
    }
    /* Timeline adjustments inside sheet */
    .timeline-container {
        padding: 0 20px;
    }
    .card.bg-light {
        background-color: #f8f9fa !important;
        border: 1px dashed #ced4da !important;
    }
    .input-group-text {
        border-radius: 0;
        background: #e9ecef;
        border: 1px solid #ced4da;
    }
</style>

<div class="container-fluid">
    <!-- Action Bar (Sticky or floating outside sheet usually, but sticking to top here) -->
    <div class="d-flex justify-content-between align-items-center mb-4 container" style="max-width: 1000px;">
        <div>
            <!-- Breadcrumb or Title outside -->
        </div>
        <a href="/pao/index.php?route=recursos_financieros/fuas" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> REGRESAR AL LISTADO
        </a>
    </div>

    <form action="/pao/index.php?route=recursos_financieros/fuas/guardar" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
        
        <?php if ($is_editing): ?>
            <input type="hidden" name="id_fua" value="<?php echo $fua['id_fua']; ?>">
        <?php endif; ?>

        <!-- Contenedor Hoja -->
        <div class="hoja-papel">
            
            <!-- Encabezado Tipo Documento -->
            <div class="sheet-header">
                <div>
                    <h4 class="text-uppercase fw-bold mb-0">Formato Único de Afectación (FUA)</h4>
                    <span class="text-muted small">Control Financiero y Presupuestal</span>
                </div>
                <div class="text-end">
                    <div class="badge bg-light text-dark border p-2">
                        FOLIO: <span class="fw-bold text-primary"><?php echo $is_editing ? ($fua['folio_fua'] ?? 'S/N') : 'NUEVO'; ?></span>
                    </div>
                </div>
            </div>

            <!-- TIMELINE VISUAL DE FECHAS -->
            <div class="card shadow-none mb-5 bg-light">
                <div class="card-body py-4">
                        <label class="form-label text-center d-block mb-4 text-primary">Seguimiento del Trámite</label>
                        
                        <div class="timeline-container d-flex justify-content-between position-relative px-2">
                            <!-- Linea conectora de fondo -->
                            <div class="timeline-line position-absolute start-0 w-100 bg-secondary opacity-25" style="height: 2px; top: 18px !important; z-index: 0; left: 0; right: 0;"></div>

                            <!-- PASO 1: Ingreso Admvo -->
                            <div class="timeline-step text-center position-relative" style="z-index: 1; flex: 1;">
                                <div class="step-indicator bg-white border border-2 border-<?php echo (!empty($fua['fecha_ingreso_admvo'])) ? 'success' : 'secondary'; ?> rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 36px; height: 36px;">
                                    <i class="bi bi-<?php echo (!empty($fua['fecha_ingreso_admvo'])) ? 'check-lg text-success' : 'card-list text-secondary'; ?> fw-bold small"></i>
                                </div>
                                <h6 class="fw-bold text-<?php echo (!empty($fua['fecha_ingreso_admvo'])) ? 'dark' : 'muted'; ?> mb-1" style="font-size: 0.75rem;">Ingreso Admvo</h6>
                                <div class="px-1">
                                    <input type="date" name="fecha_ingreso_admvo" onchange="updateStepStatus(this)" class="form-control form-control-sm text-center border-0 bg-transparent p-0" style="font-size: 0.7rem;" value="<?php echo $is_editing ? $fua['fecha_ingreso_admvo'] : ''; ?>">
                                </div>
                            </div>

                            <!-- PASO 2: Control Ptal -->
                            <div class="timeline-step text-center position-relative" style="z-index: 1; flex: 1;">
                                <div class="step-indicator bg-white border border-2 border-<?php echo (!empty($fua['fecha_ingreso_cotrl_ptal'])) ? 'success' : 'secondary'; ?> rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 36px; height: 36px;">
                                    <i class="bi bi-<?php echo (!empty($fua['fecha_ingreso_cotrl_ptal'])) ? 'check-lg text-success' : 'currency-dollar text-secondary'; ?> fw-bold small"></i>
                                </div>
                                <h6 class="fw-bold text-<?php echo (!empty($fua['fecha_ingreso_cotrl_ptal'])) ? 'dark' : 'muted'; ?> mb-1" style="font-size: 0.75rem;">Control Ptal</h6>
                                <div class="px-1">
                                    <input type="date" name="fecha_ingreso_cotrl_ptal" onchange="updateStepStatus(this)" class="form-control form-control-sm text-center border-0 bg-transparent p-0" style="font-size: 0.7rem;" value="<?php echo $is_editing ? $fua['fecha_ingreso_cotrl_ptal'] : ''; ?>">
                                </div>
                            </div>

                            <!-- PASO 3: FIRMAS -->
                            <div class="timeline-step text-center position-relative" style="z-index: 1; flex: 1;">
                                <?php 
                                $hasTitular = !empty($fua['fecha_titular']);
                                $hasRegreso = !empty($fua['fecha_firma_regreso']);
                                $stepColor = ($hasTitular && $hasRegreso) ? 'success' : 'secondary';
                                ?>
                                <div class="step-indicator bg-white border border-2 border-<?php echo $stepColor; ?> rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 36px; height: 36px;">
                                    <i class="bi bi-pen text-<?php echo $stepColor; ?> fw-bold small"></i>
                                </div>
                                <h6 class="fw-bold text-<?php echo ($stepColor != 'secondary') ? 'dark' : 'muted'; ?> mb-1" style="font-size: 0.75rem;">Firmas</h6>
                                <div class="d-flex justify-content-center gap-1">
                                    <input type="date" name="fecha_titular" title="Titular" class="form-control form-control-sm text-center border-0 bg-transparent p-0" style="font-size: 0.7rem; width: 75px;" value="<?php echo $is_editing ? $fua['fecha_titular'] : ''; ?>">
                                    <input type="date" name="fecha_firma_regreso" title="Regreso" class="form-control form-control-sm text-center border-0 bg-transparent p-0" style="font-size: 0.7rem; width: 75px;" value="<?php echo $is_editing ? $fua['fecha_firma_regreso'] : ''; ?>">
                                </div>
                            </div>

                            <!-- PASO 4: SFyA -->
                            <div class="timeline-step text-center position-relative" style="z-index: 1; flex: 1;">
                                <?php 
                                $hasAcuse = !empty($fua['fecha_acuse_antes_fa']);
                                $hasRespuesta = !empty($fua['fecha_respuesta_sfa']);
                                $stepColorSFA = ($hasAcuse && $hasRespuesta) ? 'primary' : 'secondary';
                                ?>
                                <div class="step-indicator bg-white border border-2 border-<?php echo $stepColorSFA; ?> rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 36px; height: 36px;">
                                    <i class="bi bi-building text-<?php echo $stepColorSFA; ?> fw-bold small"></i>
                                </div>
                                <h6 class="fw-bold text-<?php echo ($stepColorSFA != 'secondary') ? 'dark' : 'muted'; ?> mb-1" style="font-size: 0.75rem;">Trámite SFyA</h6>
                                <div class="d-flex justify-content-center gap-1">
                                    <input type="date" name="fecha_acuse_antes_fa" title="Acuse" class="form-control form-control-sm text-center border-0 bg-transparent p-0" style="font-size: 0.7rem; width: 75px;" value="<?php echo $is_editing ? $fua['fecha_acuse_antes_fa'] : ''; ?>">
                                    <input type="date" name="fecha_respuesta_sfa" title="Respuesta" class="form-control form-control-sm text-center border-0 bg-transparent p-0" style="font-size: 0.7rem; width: 75px;" value="<?php echo $is_editing ? $fua['fecha_respuesta_sfa'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                </div>
            </div>

            <!-- SECCIÓN DATOS -->
            <h5 class="text-secondary border-bottom pb-2 mb-4 mt-5"><i class="bi bi-info-circle me-2"></i>Información del Proyecto</h5>
            
            <div class="row g-4">
                
                <!-- 1. Proyecto Vinculado -->
                <div class="col-12">
                     <label class="form-label">Proyecto Vinculado (Catálogo)</label>
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

                <!-- 2. Nombre del Proyecto o Acción (Manual) -->
                <div class="col-12">
                    <label class="form-label">Descripción Específica / Acción</label>
                    <textarea name="nombre_proyecto_accion" class="form-control" rows="2" style="resize: none;" required><?php echo $is_editing ? htmlspecialchars($fua['nombre_proyecto_accion']) : ''; ?></textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Tipo de Movimiento</label>
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

                <div class="col-md-4">
                     <label class="form-label">Estatus Interno</label>
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

                <div class="col-md-4">
                    <label class="form-label">Estado Oficial</label>
                    <select name="resultado_tramite" class="form-select bg-light">
                        <option value="PENDIENTE" <?php echo ($is_editing && $fua['resultado_tramite'] == 'PENDIENTE') ? 'selected' : ''; ?>>PENDIENTE</option>
                        <option value="AUTORIZADO" <?php echo ($is_editing && $fua['resultado_tramite'] == 'AUTORIZADO') ? 'selected' : ''; ?>>AUTORIZADO</option>
                        <option value="NO AUTORIZADO" <?php echo ($is_editing && $fua['resultado_tramite'] == 'NO AUTORIZADO') ? 'selected' : ''; ?>>NO AUTORIZADO</option>
                    </select>
                </div>

                 <!-- FUENTE DE RECURSOS -->
                 <div class="col-md-8">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0">Fuente de Recursos</label>
                        <a href="/pao/index.php?route=recursos_financieros/cat_fuentes" target="_blank" class="text-secondary small text-decoration-none" title="Administrar Catálogo">
                            <i class="bi bi-gear-fill"></i>
                        </a>
                    </div>
                    <select name="fuente_recursos" class="form-select">
                        <option value="">-- Seleccione Fuente --</option>
                        <?php foreach ($fuentesFinanciamiento as $ff): ?>
                                <option value="<?php echo $ff['abreviatura']; ?>" 
                                    <?php echo ($is_editing && $fua['fuente_recursos'] == $ff['abreviatura']) ? 'selected' : ''; ?>>
                                    <?php echo $ff['anio'] . ' - ' . $ff['abreviatura'] . ' (' . $ff['nombre_fuente'] . ')'; ?>
                                </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- IMPORTE -->
                <div class="col-md-4">
                    <label class="form-label">Importe Solicitado</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" name="importe" class="form-control text-end fw-bold" 
                            style="font-size: 1.1rem;"
                            value="<?php echo $is_editing ? $fua['importe'] : ''; ?>">
                    </div>
                </div>

                <div class="col-12 mt-4">
                     <h5 class="text-secondary border-bottom pb-2 mb-3"><i class="bi bi-clipboard-data me-2"></i>Datos Administrativos</h5>
                </div>
                
                 <!-- OFICIO ENTRADA -->
                <div class="col-md-4">
                    <label class="form-label">No. Oficio Entrada</label>
                    <input type="text" name="no_oficio_entrada" class="form-control" value="<?php echo $is_editing ? htmlspecialchars($fua['no_oficio_entrada']) : ''; ?>">
                </div>
                <!-- OFICIO DESF -->
                <div class="col-md-4">
                    <label class="form-label">No. Oficio Desf.</label>
                    <input type="text" name="oficio_desf_ya" class="form-control" value="<?php echo $is_editing ? htmlspecialchars($fua['oficio_desf_ya']) : ''; ?>">
                </div>
                 <!-- CLAVE PPTAL -->
                 <div class="col-md-4">
                    <label class="form-label">Clave Presupuestal</label>
                    <input type="text" name="clave_presupuestal" class="form-control font-monospace" placeholder="00-000-000..." value="<?php echo $is_editing ? htmlspecialchars($fua['clave_presupuestal']) : ''; ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2" style="resize: none;"><?php echo $is_editing ? htmlspecialchars($fua['observaciones']) : ''; ?></textarea>
                </div>

                <!-- SECCIÓN DOCUMENTOS ADJUNTOS -->
                <div class="col-12 mt-4">
                     <h5 class="text-secondary border-bottom pb-2 mb-3"><i class="bi bi-paperclip me-2"></i>Documentación Soporte</h5>
                     
                     <!-- Lista de Existentes -->
                     <?php if (!empty($documentosExistentes)): ?>
                        <div class="list-group mb-3">
                            <?php foreach ($documentosExistentes as $doc): ?>
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center overflow-hidden">
                                        <div class="me-3 fs-4 text-danger"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                                        <div class="text-truncate">
                                            <h6 class="mb-0 text-truncate"><?php echo htmlspecialchars($doc['nombre_archivo_original']); ?></h6>
                                            <small class="text-muted"><?php echo strtolower($doc['tipo_documento']); ?> | <?php echo date('d/m/Y', strtotime($doc['fecha_creacion'])); ?></small>
                                        </div>
                                    </div>
                                    <div>
                                        <!-- Enlance directo al visor -->
                                        <a href="/pao/ver_archivo.php?uuid=<?php echo $doc['uuid']; ?>" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm">
                                            <i class="bi bi-eye"></i> Ver
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                     <?php endif; ?>

                     <!-- Input Nuevos -->
                     <label class="form-label">Adjuntar Nuevos Archivos (PDF)</label>
                     <input type="file" name="documentos_adjuntos[]" class="form-control" multiple accept="application/pdf">
                     <div class="form-text">Puede seleccionar múltiples archivos. Se integrarán al Archivo Digital.</div>
                </div>
            </div>

            <!-- Action Buttons (Bottom of Sheet) -->
            <div class="mt-5 pt-4 border-top text-end">
                <a href="/pao/index.php?route=recursos_financieros/fuas" class="btn btn-outline-secondary px-4 me-2">
                    CANCELAR
                </a>
                <button type="submit" class="btn btn-dark px-5">
                    GUARDAR FUA
                </button>
            </div>
            
        </div> <!-- End Hoja Papel -->
    </form>
</div>

<style>
    /* Estilo para campos con información */
    .is-filled {
        background-color: #e8f5e9 !important; /* Verde muy suave */
        border-right: 3px solid #66bb6a !important; /* Indicador lateral verde */
    }
    /* Estilo para estatus CANCELADO */
    .is-cancelled {
        background-color: #ffebee !important; /* Rojo muy suave */
        border-right: 3px solid #ef5350 !important; /* Indicador lateral rojo */
        color: #c62828 !important; /* Texto rojo oscuro */
    }
    .form-control, .form-select {
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }
</style>

<script>
    function updateStepStatus(input) {
        const stepContainer = input.closest('.timeline-step');
        const indicator = stepContainer.querySelector('.step-indicator');
        const icon = indicator.querySelector('i');
        const title = stepContainer.querySelector('h6');
        const isFilled = input.value !== '';
        
        // Determine colors based on whether it's the last step (primary) or intermediate (success)
        // For simplicity, we'll use success for all when filled, or custom logic if required
        let colorClass = 'success';
        if (input.name.includes('sfa')) { // For SFyA steps
            colorClass = 'primary';
        }
        
        if (isFilled) {
            indicator.className = `step-indicator bg-white border border-2 border-${colorClass} rounded-circle d-inline-flex align-items-center justify-content-center mb-2`;
            
            let newIconClass = 'check-lg';
            if (input.name.includes('titular') || input.name.includes('regreso')) {
                // For signature steps, if both are filled, show check-all, otherwise pen
                const titularInput = stepContainer.querySelector('input[name="fecha_titular"]');
                const regresoInput = stepContainer.querySelector('input[name="fecha_firma_regreso"]');
                if (titularInput && regresoInput && titularInput.value && regresoInput.value) {
                    newIconClass = 'check-all';
                } else {
                    newIconClass = 'pen';
                }
            } else if (input.name.includes('sfa')) {
                const acuseInput = stepContainer.querySelector('input[name="fecha_acuse_antes_fa"]');
                const respuestaInput = stepContainer.querySelector('input[name="fecha_respuesta_sfa"]');
                if (acuseInput && respuestaInput && acuseInput.value && respuestaInput.value) {
                    newIconClass = 'building-check';
                } else {
                    newIconClass = 'building';
                }
            }
            icon.className = `bi bi-${newIconClass} text-${colorClass} fw-bold small`;
            
            title.classList.remove('text-muted');
            title.classList.add('text-dark');
            // input.classList.add(`border-${colorClass}`); // No border on input for this design
        } else {
            indicator.className = 'step-indicator bg-white border border-2 border-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-2';
            
            let originalIcon = 'circle';
            if(input.name.includes('admvo')) originalIcon = 'card-list';
            if(input.name.includes('ptal')) originalIcon = 'currency-dollar';
            if(input.name.includes('titular') || input.name.includes('regreso')) originalIcon = 'pen';
            if(input.name.includes('sfa') || input.name.includes('fa')) originalIcon = 'building';
            
            icon.className = `bi bi-${originalIcon} text-secondary fw-bold`;
            title.classList.add('text-muted');
            title.classList.remove('text-dark');
            input.classList.remove(`border-${colorClass}`);
        }
    }
    </script>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Init Select2
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
             console.log("Initializing Select2");
             $('.select2').select2({
                 theme: 'bootstrap-5',
                 width: '100%',
                 dropdownParent: $('body') // Ensure dropdown works if inside modal/special containers
             });
        }

        // 1. Enforce Uppercase
        const textInputs = document.querySelectorAll('input[type="text"], textarea');
        textInputs.forEach(function (input) {
            input.classList.add('text-uppercase');
            input.addEventListener('input', function () {
                this.value = this.value.toUpperCase();
            });
        });

        // 2. Timeline Status Update Init
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => updateStepStatus(input));

        // 3. Auto-resize Textareas
        const textareas = document.querySelectorAll('textarea');
        function autoResize(el) {
            el.style.height = 'auto';
            el.style.height = (el.scrollHeight + 2) + 'px'; // +2 for border correction
        }
        textareas.forEach(ta => {
            ta.style.overflowY = 'hidden'; 
            ta.style.resize = 'none'; // Disable manual resize
            ta.addEventListener('input', function() {
                autoResize(this);
            });
            // Initial resize (delay slightly to ensure render)
            setTimeout(() => autoResize(ta), 100);
        });
    });

    // --- Validación de Saldo de Proyecto ---
    document.addEventListener('DOMContentLoaded', function() {
        const importeInput = document.querySelector('input[name="importe"]');
        const proyectoSelectQuery = 'select[name="id_proyecto"]';
        // FUA ID Actual para excluirlo de la suma si es edición
        const currentFuaId = "<?php echo $is_editing ? $fua['id_fua'] : 0; ?>";
        let saldoMaximo = 0;

        function consultarSaldo() {
            const idProyecto = $(proyectoSelectQuery).val();
            console.log("Consultando saldo para proyecto:", idProyecto);
            if(!idProyecto) return;

            fetch('<?php echo BASE_URL; ?>/modulos/recursos_financieros/fuas/get_saldo_proyecto.php?id_proyecto=' + idProyecto + '&id_fua=' + currentFuaId)
                .then(response => {
                    console.log("Respuesta recibida (status):", response.status);
                    if (!response.ok) throw new Error('Error en la petición: ' + response.status);
                    return response.json();
                })
                .then(data => {
                    console.log("Datos recibidos:", data);
                    if(data.error) {
                        console.error("Error al obtener saldo:", data.error);
                        return;
                    }
                    saldoMaximo = parseFloat(data.saldo_disponible);
                    validarMonto(); 
                })
                .catch(err => {
                    console.error("Error de red o parsing:", err);
                    // Opcional: mostrar error en el UI
                });
        }

        function validarMonto() {
            if(!importeInput.value) return;
            const monto = parseFloat(importeInput.value);
            
            // Limpiar alertas previas
            const parent = importeInput.parentElement;
            const existingAlert = parent.querySelector('.alert-saldo-proyecto');
            if(existingAlert) existingAlert.remove();
            
            importeInput.classList.remove('is-invalid');
            
            // Validar
            // Nota: Se permite guardar (advertencia) o se bloquea? 
            // "no debe ser mayor del proyecto" sugiere prohibición. Marcaremos como inválido.
            if (monto > saldoMaximo) {
                importeInput.classList.add('is-invalid'); // Esto pone el borde rojo si usas Bootstrap validation styles
                
                const div = document.createElement('div');
                div.className = 'invalid-feedback alert-saldo-proyecto d-block fw-bold mt-2';
                div.innerHTML = `
                    <i class="bi bi-exclamation-triangle-fill"></i> El importe ($${monto.toLocaleString('es-MX')}) excede el saldo disponible del proyecto.<br>
                    <span class="text-dark">Saldo Disponible: $${saldoMaximo.toLocaleString('es-MX', {minimumFractionDigits: 2})}</span>
                `;
                parent.appendChild(div);
            }
        }

        // Listeners
        if (typeof jQuery !== 'undefined') {
            $(proyectoSelectQuery).on('change', consultarSaldo);
        }
        importeInput.addEventListener('input', validarMonto);
        
        // Ejecutar al inicio si ya hay proyecto seleccionado
        if($(proyectoSelectQuery).val()) {
            consultarSaldo();
        }
    });
</script>
