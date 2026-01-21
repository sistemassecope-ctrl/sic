<?php
$db = (new Database())->getConnection();
$id = $_GET['id'] ?? null;
$vehiculo = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM vehiculos WHERE id = ?");
    $stmt->execute([$id]);
    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vehiculo) {
        echo "<div class='alert alert-danger'>Vehículo no encontrado.</div>";
        exit;
    }
}
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="h4 mb-0 text-primary">
            <?php echo $id ? '<i class="bi bi-pencil-square me-2"></i>Editar Vehículo' : '<i class="bi bi-plus-circle me-2"></i>Nuevo Vehículo'; ?>
        </h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php?route=vehiculos/padron_vehicular">Padrón Vehicular</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $id ? 'Editar' : 'Nuevo'; ?></li>
            </ol>
        </nav>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo BASE_URL; ?>/index.php?route=vehiculos/padron_vehicular" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Regresar
        </a>
    </div>
</div>

<form action="<?php echo BASE_URL; ?>/index.php?route=vehiculos/padron_vehicular/guardar" method="POST">
    <?php if ($id): ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
    <?php endif; ?>

    <!-- SECCIÓN 0: Configuración del Sistema (Región y Estatus) -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label class="form-label fw-bold text-primary mb-0">Región</label>
                    <select name="region" class="form-select border-primary" required>
                        <option value="SECOPE" <?php echo ($vehiculo['region'] ?? 'SECOPE') == 'SECOPE' ? 'selected' : ''; ?>>SECOPE</option>
                        <option value="REGION LAGUNA" <?php echo ($vehiculo['region'] ?? '') == 'REGION LAGUNA' ? 'selected' : ''; ?>>REGION LAGUNA</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch pt-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="logotiposSwitch" name="con_logotipos" value="SI" 
                               <?php echo ($vehiculo['con_logotipos'] ?? 'SI') == 'SI' ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="logotiposSwitch">¿Tiene Logotipos?</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch pt-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="bajaSwitch" name="en_proceso_baja" value="SI" 
                               <?php echo ($vehiculo['en_proceso_baja'] ?? 'NO') == 'SI' ? 'checked' : ''; ?>>
                        <label class="form-check-label text-danger fw-bold" for="bajaSwitch">En Proceso de Baja</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN PRINCIPAL: Datos del Vehículo (Orden Excel) -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold text-secondary border-bottom-0 pt-3">
            <i class="bi bi-table me-2"></i>Información del Vehículo
        </div>
        <div class="card-body pt-0">
            <div class="row g-3">
                <!-- Fila 1 Excel: No. Economico, No. Patrimonio, No. Placas, Poliza -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small text-uppercase text-muted">No. Económico <span class="text-danger">*</span></label>
                    <input type="text" name="numero_economico" class="form-control fw-bold" 
                           value="<?php echo htmlspecialchars($vehiculo['numero_economico'] ?? ''); ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium small text-uppercase text-muted">No. Patrimonio</label>
                    <input type="text" name="numero_patrimonio" class="form-control" 
                           value="<?php echo htmlspecialchars($vehiculo['numero_patrimonio'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium small text-uppercase text-muted">No. Placas <span class="text-danger">*</span></label>
                    <input type="text" name="numero_placas" class="form-control text-uppercase text-uppercase" 
                           value="<?php echo htmlspecialchars($vehiculo['numero_placas'] ?? ''); ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium small text-uppercase text-muted">Póliza</label>
                    <input type="text" name="poliza" class="form-control" 
                           value="<?php echo htmlspecialchars($vehiculo['poliza'] ?? ''); ?>">
                </div>

                <!-- Fila 2 Excel: Marca, Tipo, Modelo, Color -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small text-uppercase text-muted">Marca <span class="text-danger">*</span></label>
                    <input type="text" name="marca" class="form-control" 
                           value="<?php echo htmlspecialchars($vehiculo['marca'] ?? ''); ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium small text-uppercase text-muted">Tipo</label>
                    <input type="text" name="tipo" class="form-control" 
                           value="<?php echo htmlspecialchars($vehiculo['tipo'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium small text-uppercase text-muted">Modelo (Año)</label>
                    <input type="text" name="modelo" class="form-control" 
                           value="<?php echo htmlspecialchars($vehiculo['modelo'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium small text-uppercase text-muted">Color</label>
                    <input type="text" name="color" class="form-control" 
                           value="<?php echo htmlspecialchars($vehiculo['color'] ?? ''); ?>">
                </div>

                <!-- Fila 3 Excel: No. Serie, Secretaria, Direccion -->
                <div class="col-md-4">
                    <label class="form-label fw-medium small text-uppercase text-muted">No. Serie</label>
                    <input type="text" name="numero_serie" class="form-control" 
                           value="<?php echo htmlspecialchars($vehiculo['numero_serie'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium small text-uppercase text-muted">Secretaría / Subsecretaría</label>
                    <input type="text" name="secretaria_subsecretaria" class="form-control" 
                           value="<?php echo htmlspecialchars($vehiculo['secretaria_subsecretaria'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium small text-uppercase text-muted">Dirección / Depto</label>
                    <input type="text" name="direccion_departamento" class="form-control" 
                           value="<?php echo htmlspecialchars($vehiculo['direccion_departamento'] ?? ''); ?>">
                </div>

                <!-- Fila 4 Excel: Resguardo, Factura -->
                <div class="col-md-6">
                    <label class="form-label fw-medium small text-uppercase text-muted">Resguardo a Nombre De</label>
                    <input type="text" name="resguardo_nombre" class="form-control" 
                           value="<?php echo htmlspecialchars($vehiculo['resguardo_nombre'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium small text-uppercase text-muted">Factura a Nombre De</label>
                    <input type="text" name="factura_nombre" class="form-control" 
                           value="<?php echo htmlspecialchars($vehiculo['factura_nombre'] ?? ''); ?>">
                </div>

                <!-- Fila 5 Excel: Obs 1, Obs 2 -->
                <div class="col-md-6">
                    <label class="form-label fw-medium small text-uppercase text-muted">Observación 1</label>
                    <textarea name="observacion_1" class="form-control" rows="2"><?php echo htmlspecialchars($vehiculo['observacion_1'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium small text-uppercase text-muted">Observación 2</label>
                    <textarea name="observacion_2" class="form-control" rows="2"><?php echo htmlspecialchars($vehiculo['observacion_2'] ?? ''); ?></textarea>
                </div>

                <!-- Fila 6 Excel: Telefono, Kilometraje -->
                <div class="col-md-6">
                    <label class="form-label fw-medium small text-uppercase text-muted">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" 
                           value="<?php echo htmlspecialchars($vehiculo['telefono'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium small text-uppercase text-muted">Kilometraje</label>
                    <input type="text" name="kilometraje" class="form-control" 
                           value="<?php echo htmlspecialchars($vehiculo['kilometraje'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="mb-5 pb-5 text-end">
        <a href="<?php echo BASE_URL; ?>/index.php?route=vehiculos/padron_vehicular" class="btn btn-secondary me-2">Cancelar</a>
        <button type="submit" class="btn btn-primary px-4 btn-lg shadow">
            <i class="bi bi-save me-1"></i> Guardar Vehículo
        </button>
    </div>
</form>
