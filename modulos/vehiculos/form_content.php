<?php
// Vista parcial para el formulario de vehículos
// Variables esperadas: $v (datos vehículo), $areas (lista áreas), $isEdit (bool)
?>
<div class="col-md-3">
    <label class="form-label">No. Económico <span class="text-danger">*</span></label>
    <div class="input-group">
        <?php if($isEdit): ?>
            <input type="text" class="form-control fw-bold" value="<?= e($v['numero_economico']) ?>" disabled>
            <input type="hidden" name="numero_economico" value="<?= e($v['numero_economico']) ?>">
        <?php else: ?>
            <input type="text" name="numero_economico" class="form-control fw-bold" required>
        <?php endif; ?>
    </div>
</div>

<div class="col-md-3">
    <label class="form-label">No. Patrimonio</label>
    <input type="text" name="numero_patrimonio" class="form-control" value="<?= e($v['numero_patrimonio'] ?? '') ?>">
</div>

<div class="col-md-3">
    <label class="form-label">No. Placas <span class="text-danger">*</span></label>
    <input type="text" name="numero_placas" class="form-control" value="<?= e($v['numero_placas'] ?? '') ?>" required>
</div>

<div class="col-md-3">
    <label class="form-label">Póliza</label>
    <input type="text" name="poliza" class="form-control" value="<?= e($v['poliza'] ?? '') ?>">
</div>

<!-- Row 2 -->
<div class="col-md-3">
    <label class="form-label">Marca <span class="text-danger">*</span></label>
    <input type="text" name="marca" class="form-control" value="<?= e($v['marca'] ?? '') ?>" required>
</div>

<div class="col-md-3">
    <label class="form-label">Tipo</label>
    <input type="text" name="tipo" class="form-control" value="<?= e($v['tipo'] ?? '') ?>">
</div>

<div class="col-md-3">
    <label class="form-label">Modelo (Año) <span class="text-danger">*</span></label>
    <input type="text" name="modelo" class="form-control" value="<?= e($v['modelo'] ?? '') ?>" required>
</div>

<div class="col-md-3">
    <label class="form-label">Color</label>
    <input type="text" name="color" class="form-control" value="<?= e($v['color'] ?? '') ?>">
</div>

<!-- Row 3 -->
<div class="col-md-4">
    <label class="form-label">No. Serie</label>
    <input type="text" name="numero_serie" class="form-control" value="<?= e($v['numero_serie'] ?? '') ?>">
</div>



<!-- Row 4 -->
<div class="col-md-4">
    <label class="form-label">Departamento / Área <span class="text-danger">*</span></label>
    <select name="area_id" class="form-select" required>
        <option value="">-- Seleccionar Área --</option>
        <?php foreach ($areas as $area): ?>
            <option value="<?= $area['id'] ?>" <?= ($v['area_id'] ?? '') == $area['id'] ? 'selected' : '' ?>>
                <?= e($area['nombre_area']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="col-md-4">
    <label class="form-label">Resguardo a Nombre De</label>
    <input type="text" name="resguardo_nombre" class="form-control" value="<?= e($v['resguardo_nombre'] ?? '') ?>">
</div>

<div class="col-md-4">
    <label class="form-label">Factura a Nombre De</label>
    <input type="text" name="factura_nombre" class="form-control" value="<?= e($v['factura_nombre'] ?? '') ?>">
</div>

<!-- Row 5: Observations Split -->
<!-- Observaciones legacy eliminadas. Utilizar Bitácora de Notas en Listado. -->

<!-- Special Toggles Row -->
<div class="col-12 mt-4">
    <div class="card bg-light border-0">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                     <label class="form-label mb-0 me-3">Región</label>
                     <select name="region" class="form-select d-inline-block w-auto">
                        <option value="SECOPE" <?= ($v['region'] ?? '') == 'SECOPE' ? 'selected' : '' ?>>SECOPE</option>
                        <option value="REGION LAGUNA" <?= ($v['region'] ?? '') == 'REGION LAGUNA' ? 'selected' : '' ?>>REGION LAGUNA</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="con_logotipos" value="SI" id="switchLogos" 
                               <?= ($v['con_logotipos'] ?? 'NO') == 'SI' ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold" for="switchLogos">¿Tiene Logotipos?</label>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="en_proceso_baja" value="SI" id="switchBaja"
                               <?= ($v['en_proceso_baja'] ?? 'NO') == 'SI' ? 'checked' : '' ?> disabled>
                        <label class="form-check-label text-muted fw-bold" for="switchBaja" title="Gestionar en Bandeja de Bajas">En Proceso de Baja</label>
                    </div>
                </div>
            </div>
            
             <div class="row mt-3">
                <div class="col-md-3">
                     <label class="form-label">Kilometraje</label>
                     <input type="text" name="kilometraje" class="form-control" value="<?= e($v['kilometraje'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                     <label class="form-label">Teléfono</label>
                     <input type="text" name="telefono" class="form-control" value="<?= e($v['telefono'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-12 mt-4 text-end">
    <hr>
    <?php if($isEdit): ?>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
    <?php else: ?>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Registrar Vehículo</button>
    <?php endif; ?>
</div>
