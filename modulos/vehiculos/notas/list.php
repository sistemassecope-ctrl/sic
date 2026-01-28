<?php
/**
 * Componente: Listado de Notas
 * Espera variable: $vehiculo_id
 * Opcional: $readOnly (para modo visualización solo lectura)
 */
$tipo_origen = isset($tipo_origen) ? $tipo_origen : 'ACTIVO';
$readOnly = isset($readOnly) ? $readOnly : false;
$redirect_to = isset($redirect_to) ? $redirect_to : '../index.php'; // URL actual para return

// Obtener notas
$pdo = getConnection();
// Para tipo BAJA, buscar todos los subtipos (SOLICITUD_BAJA, AUTORIZACION_BAJA, BAJA, etc.)
if ($tipo_origen === 'BAJA') {
    $stmtNotas = $pdo->prepare("SELECT * FROM vehiculos_notas WHERE vehiculo_id = ? AND tipo_origen LIKE '%BAJA%' ORDER BY created_at DESC");
    $stmtNotas->execute([$vehiculo_id]);
} else {
    $stmtNotas = $pdo->prepare("SELECT * FROM vehiculos_notas WHERE vehiculo_id = ? AND tipo_origen = ? ORDER BY created_at DESC");
    $stmtNotas->execute([$vehiculo_id, $tipo_origen]);
}
$notas = $stmtNotas->fetchAll();
?>

<div class="notas-container mt-3">
    <!-- Header y Botón Agregar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 text-info"><i class="fas fa-clipboard-list"></i> Bitácora de Notas (<?= $tipo_origen ?>)</h5>
        <?php if (!$readOnly): ?>
        <button class="btn btn-sm btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNuevaNota-<?= $vehiculo_id ?>" aria-expanded="false">
            <i class="fas fa-plus"></i> Nueva Nota
        </button>
        <?php endif; ?>
    </div>

    <!-- Formulario Nueva Nota (Colapsable) -->
    <?php if (!$readOnly): ?>
    <div class="collapse mb-4" id="collapseNuevaNota-<?= $vehiculo_id ?>">
        <div class="card card-body bg-dark text-white border-secondary shadow-sm">
            <h6 class="card-title">📝 Agregar Nueva Nota</h6>
            <form action="notas/create.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="vehiculo_id" value="<?= $vehiculo_id ?>">
                <input type="hidden" name="tipo_origen" value="<?= $tipo_origen ?>">
                <input type="hidden" name="redirect_to" value="<?= $redirect_to ?>">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Contenido de la nota</label>
                    <textarea name="nota" class="form-control" rows="3" required placeholder="Escribe los detalles aquí..."></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Adjuntar Imagen (Opcional)</label>
                    <input type="file" name="imagen" class="form-control form-control-sm" accept="image/*,application/pdf">
                    <div class="form-text">Formatos: JPG, PNG, PDF. Máx 5MB.</div>
                </div>
                
                <div class="text-end">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#collapseNuevaNota-<?= $vehiculo_id ?>">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Guardar Nota</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Listado de Notas -->
    <div class="list-group list-group-flush border-top border-secondary">
        <?php if (count($notas) == 0): ?>
            <div class="text-center p-4 text-muted">
                <i class="fas fa-sticky-note fa-2x mb-2 text-light-gray"></i>
                <p>No hay notas registradas para este vehículo.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notas as $nota): ?>
                <div class="list-group-item bg-transparent text-white border-secondary px-0 py-3">
                    <div class="d-flex w-100 justify-content-between mb-1">
                        <small class="text-white-50 fw-bold">
                            <i class="far fa-calendar-alt"></i> <?= date('d/m/Y h:i A', strtotime($nota['created_at'])) ?>
                        </small>
                        <?php if (!$readOnly): ?>
                        <div class="d-flex align-items-center">
                            <button class="btn btn-link btn-sm text-info p-0 me-3" title="Editar" onclick="openEditNotaModal(<?= htmlspecialchars(json_encode($nota)) ?>, '<?= $redirect_to ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="notas/delete.php" method="POST" onsubmit="return confirm('¿Eliminar esta nota permanentemente?');" class="d-inline">
                                <input type="hidden" name="nota_id" value="<?= $nota['id'] ?>">
                                <input type="hidden" name="vehiculo_id" value="<?= $vehiculo_id ?>">
                                <input type="hidden" name="redirect_to" value="<?= $redirect_to ?>">
                                <button type="submit" class="btn btn-link btn-sm text-danger p-0" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    $fullNote = e($nota['nota']);
                    $isLong = strlen($fullNote) > 150;
                    $isModalView = (isset($isModal) && $isModal);
                    
                    if ($isLong) {
                        $shortNote = substr($fullNote, 0, 150) . '...';
                        
                        if ($isModalView) {
                            // Caso Modal: Link a Edit
                             ?>
                            <p class="mb-2" style="white-space: pre-wrap;"><?= nl2br($shortNote) ?> <a href="/pao/modulos/vehiculos/edit.php?id=<?= $vehiculo_id ?>" class="text-info text-decoration-none fw-bold small">Leer más...</a></p>
                            <?php
                        } else {
                            // Caso Edit View: Toggle (Expandir/Colapsar)
                            $uniqueId = 'note-' . $nota['id'];
                            ?>
                            <div id="short-<?= $uniqueId ?>" class="mb-2">
                                <span style="white-space: pre-wrap;"><?= nl2br($shortNote) ?></span>
                                <a href="javascript:void(0);" onclick="document.getElementById('short-<?= $uniqueId ?>').classList.add('d-none'); document.getElementById('full-<?= $uniqueId ?>').classList.remove('d-none');" class="text-info text-decoration-none fw-bold small ms-1">Mostrar más <i class="fas fa-chevron-down"></i></a>
                            </div>
                            <div id="full-<?= $uniqueId ?>" class="mb-2 d-none">
                                <span style="white-space: pre-wrap;"><?= nl2br($fullNote) ?></span>
                                <a href="javascript:void(0);" onclick="document.getElementById('full-<?= $uniqueId ?>').classList.add('d-none'); document.getElementById('short-<?= $uniqueId ?>').classList.remove('d-none');" class="text-info text-decoration-none fw-bold small ms-1">Mostrar menos <i class="fas fa-chevron-up"></i></a>
                            </div>
                            <?php
                        }
                    } else {
                        // Nota corta: Mostrar completa siempre
                        ?>
                        <p class="mb-2" style="white-space: pre-wrap;"><?= nl2br($fullNote) ?></p>
                        <?php
                    }
                    ?>
                    
                    <?php if (!empty($nota['imagen_path'])): ?>
                        <div class="mt-2">
                            <a href="notas/uploads/<?= $nota['imagen_path'] ?>" target="_blank" class="text-decoration-none badge bg-info text-dark">
                                <i class="fas fa-paperclip"></i> Ver Adjunto
                            </a>
                            <?php 
                                $ext = strtolower(pathinfo($nota['imagen_path'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg','jpeg','png','gif'])): 
                            ?>
                                <div class="mt-1">
                                    <a href="notas/uploads/<?= $nota['imagen_path'] ?>" target="_blank">
                                        <img src="notas/uploads/<?= $nota['imagen_path'] ?>" class="img-thumbnail" style="max-height: 100px;">
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
