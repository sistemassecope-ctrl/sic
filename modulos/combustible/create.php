<?php
$db = (new Database())->getConnection();
$id = $_GET['id'] ?? null;
$data = [];
if ($id) {
    $stmt = $db->prepare("SELECT * FROM solicitudes_combustible WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch Obras (assuming table exists based on previous file analysis)
try {
    $stmtO = $db->query("SELECT id_proyecto, nombre_proyecto FROM proyectos_obra ORDER BY nombre_proyecto");
    $obras = $stmtO->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $obras = []; // Fallback if table doesn't exist
}
?>

<div class="card shadow border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-secondary"><?php echo $id ? 'Editar' : 'Nueva'; ?> Solicitud de Combustible</h5>
    </div>
    <div class="card-body">
        <form action="<?php echo BASE_URL; ?>/index.php?route=combustible/guardar" method="POST">
            <?php if ($id): ?>
                <input type="hidden" name="id" value="<?php echo $id; ?>">
            <?php endif; ?>

            <!-- Row 1: Header Info -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Fecha</label>
                    <input type="date" class="form-control" name="fecha" value="<?php echo $data['fecha'] ?? date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-3 offset-md-6">
                    <label class="form-label">Folio</label>
                    <input type="text" class="form-control" name="folio" value="<?php echo $data['folio'] ?? ''; ?>" placeholder="Auto/Manual">
                </div>
            </div>

            <!-- Row 2: Obra -->
            <div class="mb-3">
                <label class="form-label">Obras</label>
                <select class="form-select" name="obra_id">
                    <option value="">Seleccione una obra...</option>
                    <?php foreach ($obras as $obra): ?>
                        <option value="<?php echo $obra['id_proyecto']; ?>" <?php echo ($data['obra_id'] ?? '') == $obra['id_proyecto'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($obra['nombre_proyecto']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Row 3: Mix -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">No. Solicitud</label>
                    <input type="text" class="form-control" name="no_solicitud" value="<?php echo $data['no_solicitud'] ?? ''; ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Beneficiario</label>
                    <input type="text" class="form-control" name="beneficiario" value="<?php echo $data['beneficiario'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Departamento</label>
                    <!-- TODO: Replace with Select if Catalog exists -->
                    <input type="text" class="form-control" name="departamento_id" value="<?php echo $data['departamento_id'] ?? ''; ?>" placeholder="ID o Nombre">
                </div>
            </div>

            <!-- Row 4: Usuario / Status -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Usuario</label>
                    <input type="text" class="form-control" name="usuario" value="<?php echo $data['usuario'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estatus</label>
                    <select class="form-select" name="estatus">
                        <?php $estatus = $data['estatus'] ?? 'Pendiente'; ?>
                        <option value="Pendiente" <?php echo $estatus == 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="Autorizado" <?php echo $estatus == 'Autorizado' ? 'selected' : ''; ?>>Autorizado</option>
                        <option value="Cancelado" <?php echo $estatus == 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estatus Cédula</label>
                    <select class="form-select" name="estatus_cedula">
                         <?php $ec = $data['estatus_cedula'] ?? ''; ?>
                        <option value="SIN ESTATUS" <?php echo $ec == 'SIN ESTATUS' ? 'selected' : ''; ?>>SIN ESTATUS</option>
                        <option value="CON ESTATUS" <?php echo $ec == 'CON ESTATUS' ? 'selected' : ''; ?>>CON ESTATUS</option>
                    </select>
                </div>
            </div>

            <!-- Row 5: Vehiculo -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Vehículo</label>
                    <!-- TODO: Replace with Select -->
                    <input type="text" class="form-control" name="vehiculo_id" value="<?php echo $data['vehiculo_id'] ?? ''; ?>" placeholder="ID o Descripción">
                     <div class="form-text">Ej: 304-004663 STILL DESMALEZADORA 2008</div>
                </div>
                 <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="surtir_laguna" value="1" id="surtirLaguna" <?php echo ($data['surtir_laguna'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="surtirLaguna">
                            Surtir Laguna
                        </label>
                    </div>
                </div>
            </div>
            
             <div class="mb-3">
                <label class="form-label">Dirección</label>
                <input type="text" class="form-control" name="direccion" value="<?php echo $data['direccion'] ?? ''; ?>">
            </div>

            <hr>

            <!-- Row 6: Quantities & Vale -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <table class="table table-bordered table-sm text-center">
                        <thead class="table-light">
                            <tr>
                                <th>CANTIDAD</th>
                                <th>INT/DEC</th> <!-- Placeholder for unit? logic -->
                                <th>CONCEPTO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="litros_premium" value="<?php echo $data['litros_premium'] ?? '0.00'; ?>"></td>
                                <td>LITROS</td>
                                <td>PREMIUM</td>
                            </tr>
                            <tr>
                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="litros_magna" value="<?php echo $data['litros_magna'] ?? '0.00'; ?>"></td>
                                <td>LITROS</td>
                                <td>MAGNA</td>
                            </tr>
                            <tr>
                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="litros_diesel" value="<?php echo $data['litros_diesel'] ?? '0.00'; ?>"></td>
                                <td>LITROS</td>
                                <td>DIESEL</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <label class="form-label fw-bold">NUMERO DE VALE:</label>
                            <input type="text" class="form-control form-control-lg text-center" name="numero_vale" value="<?php echo $data['numero_vale'] ?? ''; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 7: Mileage & Financials -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header text-center small py-1">KILOMETRAJE POR RECORRER:</div>
                        <div class="card-body p-0">
                           <table class="table table-sm table-borderless mb-0">
                               <tbody>
                                   <tr>
                                       <td>A CARRETERA:</td>
                                       <td><input type="number" step="0.01" class="form-control form-control-sm" name="km_carretera" value="<?php echo $data['km_carretera'] ?? ''; ?>"></td>
                                       <td>KM</td>
                                   </tr>
                                   <tr>
                                       <td>B TERRACERIA:</td>
                                       <td><input type="number" step="0.01" class="form-control form-control-sm" name="km_terraceria" value="<?php echo $data['km_terraceria'] ?? ''; ?>"></td>
                                       <td>KM</td>
                                   </tr>
                                   <tr>
                                       <td>C BRECHA:</td>
                                       <td><input type="number" step="0.01" class="form-control form-control-sm" name="km_brecha" value="<?php echo $data['km_brecha'] ?? ''; ?>"></td>
                                       <td>KM</td>
                                   </tr>
                               </tbody>
                           </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row mb-2">
                        <div class="col-6">
                            <label class="form-label small">Año</label>
                            <select class="form-select form-select-sm" name="anio">
                                <?php $year = $data['anio'] ?? date('Y'); ?>
                                <option value="2025" <?php echo $year == 2025 ? 'selected' : ''; ?>>2025</option>
                                <option value="2026" <?php echo $year == 2026 ? 'selected' : ''; ?>>2026</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Semana</label>
                            <select class="form-select form-select-sm" name="semana">
                               <?php 
                                $sem = $data['semana'] ?? date('W');
                                for($i=1; $i<=52; $i++) {
                                    $sel = ($sem == $i) ? 'selected' : '';
                                    echo "<option value='$i' $sel>$i</option>";
                                }
                               ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                         <label class="form-label small">Importe</label>
                         <input type="number" step="0.01" class="form-control" name="importe" value="<?php echo $data['importe'] ?? ''; ?>">
                    </div>
                </div>
            </div>

            <!-- Row 8: Objectives -->
            <div class="mb-3">
                <label class="form-label">OBJETIVO:</label>
                <textarea class="form-control" name="objetivo" rows="2"><?php echo htmlspecialchars($data['objetivo'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">OBSERVACIONES:</label>
                <textarea class="form-control" name="observaciones" rows="2"><?php echo htmlspecialchars($data['observaciones'] ?? ''); ?></textarea>
            </div>

            <!-- Row 9: Signatures -->
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small">RECIBE</label>
                    <input type="text" class="form-control form-control-sm" name="recibe" value="<?php echo $data['recibe'] ?? ''; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small">VO.BO.</label>
                    <input type="text" class="form-control form-control-sm" name="vobo" value="<?php echo $data['vobo'] ?? ''; ?>">
                </div>
                <div class="col-md-6 offset-md-6">
                     <label class="form-label small">AUTORIZA</label>
                    <input type="text" class="form-control form-control-sm" name="autoriza" value="<?php echo $data['autoriza'] ?? ''; ?>">
                </div>
                 <div class="col-md-6 offset-md-6">
                     <label class="form-label small">SOLICITA</label>
                    <input type="text" class="form-control form-control-sm" name="solicita" value="<?php echo $data['solicita'] ?? ''; ?>">
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4 gap-2">
                <a href="<?php echo BASE_URL; ?>/index.php?route=combustible/index" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar Solicitud</button>
            </div>
        </form>
    </div>
</div>
