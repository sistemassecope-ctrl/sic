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

// 2. Tipos de Acción FUA
$stmtTipos = $db->query("SELECT * FROM cat_tipos_fua_accion ORDER BY nombre_tipo_accion ASC");
$tiposAccion = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="text-primary fw-bold"><i class="bi bi-file-earmark-richtext"></i>
            <?php echo $is_editing ? 'Editar' : 'Captura de'; ?> FUA</h2>
        <p class="text-muted border-bottom pb-2">
            Registro del Formato Único de Atención / Acción.
        </p>
    </div>
</div>

<form action="/pao/index.php?route=recursos_financieros/fuas/guardar" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
    
    <?php if ($is_editing): ?>
        <input type="hidden" name="id_fua" value="<?php echo $fua['id_fua']; ?>">
    <?php endif; ?>

    <div class="card shadow-sm mb-4 border-0 rounded-3">
        <div class="card-header bg-light text-dark fw-bold py-3">
            <i class="bi bi-info-circle me-2 text-primary"></i> Información General
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                
                <!-- ESTATUS -->
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Estatus</label>
                    <select name="estatus" class="form-select">
                        <?php 
                        $opts = ['ACTIVO', 'CANCELADO', 'CONTROL'];
                        foreach($opts as $o) {
                            $sel = ($is_editing && $fua['estatus'] == $o) ? 'selected' : '';
                            echo "<option value='$o' $sel>$o</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- TIPO DE FUA -->
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Tipo de FUA</label>
                    <select name="tipo_fua" class="form-select">
                        <?php 
                        $opts = ['NUEVA', 'SALDO POR EJERCER', 'CONTROL'];
                        foreach($opts as $o) {
                            $sel = ($is_editing && $fua['tipo_fua'] == $o) ? 'selected' : '';
                            echo "<option value='$o' $sel>$o</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- FOLIO DE FUA -->
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Folio de FUA</label>
                    <input type="text" name="folio_fua" class="form-control" value="<?php echo $is_editing ? $fua['folio_fua'] : ''; ?>">
                </div>

                <!-- NOMBRE DEL PROYECTO O ACCIÓN -->
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
                         <?php foreach($proyectos as $p): ?>
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

                <!-- TIPO DE OBRA / ACCION (CATALOGO) -->
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Tipo De Obra/Accion</label>
                    <select name="id_tipo_obra_accion" class="form-select">
                        <option value="">-- Seleccione Tipo --</option>
                        <?php foreach($tiposAccion as $t): ?>
                            <option value="<?php echo $t['id_tipo_accion']; ?>"
                                <?php echo ($is_editing && $fua['id_tipo_obra_accion'] == $t['id_tipo_accion']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['nombre_tipo_accion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                 <!-- DIRECCION SOLICITANTE -->
                 <div class="col-md-6">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Dirección Solicitante</label>
                    <select name="direccion_solicitante" class="form-select">
                        <option value="">-- Seleccione --</option>
                        <?php 
                        $opts = ['CAMINOS', 'EDIFICACION', 'PROYECTOS DE CAMINOS', 'PROYECTOS DE EDIFICACIÓN', 'ADMINISTRACIÓN'];
                        foreach($opts as $o) {
                            $sel = ($is_editing && $fua['direccion_solicitante'] == $o) ? 'selected' : '';
                            echo "<option value='$o' $sel>$o</option>";
                        }
                        ?>
                    </select>
                </div>

                 <!-- FUENTE DE RECURSOS -->
                 <div class="col-md-6">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Fuente de Recursos</label>
                    <select name="fuente_recursos" class="form-select">
                        <option value="">-- Seleccione --</option>
                        <?php 
                        $opts = ['INGRESOS PROPIOS', 'PEFM', 'FAFEF'];
                        foreach($opts as $o) {
                            $sel = ($is_editing && $fua['fuente_recursos'] == $o) ? 'selected' : '';
                            echo "<option value='$o' $sel>$o</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- IMPORTE -->
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Importe</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" name="importe" class="form-control text-end fw-bold" 
                            value="<?php echo $is_editing ? $fua['importe'] : ''; ?>">
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- SECCION: FECHAS Y OFICIOS -->
    <div class="card shadow-sm mb-4 border-0 rounded-3">
        <div class="card-header bg-light text-dark fw-bold py-3">
            <i class="bi bi-calendar-range me-2 text-primary"></i> Fechas y Oficios
        </div>
        <div class="card-body p-4">
             <div class="row g-3">
                
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-secondary">No. Oficio Entrada</label>
                    <input type="text" name="no_oficio_entrada" class="form-control" value="<?php echo $is_editing ? $fua['no_oficio_entrada'] : ''; ?>">
                </div>
                 <div class="col-md-4">
                    <label class="form-label fw-bold small text-secondary">Oficio DESF Y A</label>
                    <input type="text" name="oficio_desf_ya" class="form-control" value="<?php echo $is_editing ? $fua['oficio_desf_ya'] : ''; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-secondary">Clave Presupuestal</label>
                    <input type="text" name="clave_presupuestal" class="form-control" value="<?php echo $is_editing ? $fua['clave_presupuestal'] : ''; ?>">
                </div>


                <div class="col-md-4">
                    <label class="form-label fw-bold small text-secondary">F. Ingreso Admvo</label>
                    <input type="date" name="fecha_ingreso_admvo" class="form-control" value="<?php echo $is_editing ? $fua['fecha_ingreso_admvo'] : ''; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-secondary">F. Ingreso Control Ptal</label>
                    <input type="date" name="fecha_ingreso_cotrl_ptal" class="form-control" value="<?php echo $is_editing ? $fua['fecha_ingreso_cotrl_ptal'] : ''; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-secondary">F. Acuse Ante SFA</label>
                    <input type="date" name="fecha_acuse_antes_fa" class="form-control" value="<?php echo $is_editing ? $fua['fecha_acuse_antes_fa'] : ''; ?>">
                </div>

             </div>
        </div>
    </div>


    <!-- SECCION: DETALLES ADICIONALES -->
    <div class="card shadow-sm mb-4 border-0 rounded-3">
        <div class="card-header bg-light text-dark fw-bold py-3">
            <i class="bi bi-paperclip me-2 text-primary"></i> Detalles Adicionales
        </div>
         <div class="card-body p-4">
             <div class="row g-3">
                 <div class="col-md-12">
                    <label class="form-label fw-bold small text-secondary">Tarea</label>
                    <input type="text" name="tarea" class="form-control" value="<?php echo $is_editing ? htmlspecialchars($fua['tarea']) : ''; ?>">
                 </div>
                  <div class="col-md-12">
                    <label class="form-label fw-bold small text-secondary">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="3"><?php echo $is_editing ? htmlspecialchars($fua['observaciones']) : ''; ?></textarea>
                 </div>
                 
                 <div class="col-md-12">
                    <label class="form-label fw-bold small text-secondary">Documentos Adjuntos</label>
                    <input type="file" name="documentos_adjuntos[]" class="form-control" multiple>
                    <?php if($is_editing && !empty($fua['documentos_adjuntos'])): ?>
                        <div class="form-text mt-2">
                            <i class="bi bi-file-check"></i> Archivos actuales: <?php echo htmlspecialchars($fua['documentos_adjuntos']); ?> (La subida reemplaza o añade, lógica a implementar)
                        </div>
                    <?php endif; ?>
                 </div>
             </div>
         </div>
    </div>

    <!-- BOTONES -->
    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
        <a href="/pao/index.php?route=recursos_financieros/fuas"
            class="btn btn-secondary btn-lg px-4 me-md-2">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg px-5 shadow"><i class="bi bi-save2"></i> Guardar FUA</button>
    </div>

</form>

<script>
    // Enforce Uppercase
    document.addEventListener('DOMContentLoaded', function () {
        const textInputs = document.querySelectorAll('input[type="text"], textarea');
        textInputs.forEach(function (input) {
            input.classList.add('text-uppercase');
            input.addEventListener('input', function () {
                this.value = this.value.toUpperCase();
            });
        });
    });
</script>
