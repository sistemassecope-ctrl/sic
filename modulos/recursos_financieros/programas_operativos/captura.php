<?php
// DB Connection
$db = (new Database())->getConnection();

// --- Edit Mode Logic ---
$id_programa = isset($_GET['id']) ? (int) $_GET['id'] : null;
$programa = null;
$is_editing = false;

if ($id_programa) {
    $stmt = $db->prepare("SELECT * FROM programas_anuales WHERE id_programa = ?");
    $stmt->execute([$id_programa]);
    $programa = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($programa) {
        $is_editing = true;
    }
}
?>
<div class="row mb-4">
    <div class="col-12">
        <h2 class="text-primary fw-bold"><i class="bi bi-folder-plus"></i>
            <?php echo $is_editing ? 'Editar' : 'Captura de'; ?> Programa Operativo</h2>
        <p class="text-muted border-bottom pb-2">
            <?php echo $is_editing ? 'Modifique los datos del Programa Anual.' : 'Complete el formulario para registrar un nuevo Programa Anual.'; ?>
        </p>
    </div>
</div>

<form action="/pao/index.php?route=recursos_financieros/programas_operativos/guardar" method="POST"
    class="needs-validation" novalidate id="formCaptura">

    <?php if ($is_editing): ?>
        <input type="hidden" name="id_programa" value="<?php echo $programa['id_programa']; ?>">
    <?php endif; ?>

    <!-- ================= SECCIÓN: DATOS DEL PROGRAMA ANUAL ================= -->
    <div class="card shadow-sm mb-4 border-0 rounded-3">
        <div class="card-header bg-gradient bg-light text-dark fw-bold py-3">
            <i class="bi bi-calendar-event me-2 text-primary"></i> Datos del Programa Operativo Anual
        </div>
        <div class="card-body p-4">
            <div class="row g-3">

                <!-- Ejercicio -->
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Ejercicio</label>
                    <input type="number" name="ejercicio" class="form-control fw-bold" 
                        value="<?php echo $is_editing ? $programa['ejercicio'] : date('Y'); ?>"
                        min="2020" max="2030" required>
                </div>

                <!-- Estatus -->
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Estatus</label>
                    <select name="estatus" class="form-select">
                        <option value="Abierto" <?php echo ($is_editing && $programa['estatus'] == 'Abierto') ? 'selected' : ''; ?>>Abierto</option>
                        <option value="En Revisión" <?php echo ($is_editing && $programa['estatus'] == 'En Revisión') ? 'selected' : ''; ?>>En Revisión</option>
                        <option value="Cerrado" <?php echo ($is_editing && $programa['estatus'] == 'Cerrado') ? 'selected' : ''; ?>>Cerrado</option>
                    </select>
                </div>

                <!-- Monto Autorizado -->
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Monto Autorizado</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" name="monto_autorizado" class="form-control text-end fw-bold" 
                            value="<?php echo $is_editing ? $programa['monto_autorizado'] : ''; ?>"
                            placeholder="0.00" min="0">
                    </div>
                </div>

                <!-- Nombre del Programa -->
                <div class="col-md-12">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Nombre del Programa</label>
                    <input type="text" name="nombre" class="form-control" 
                        value="<?php echo $is_editing ? htmlspecialchars($programa['nombre']) : ''; ?>"
                        placeholder="Ej. Programa Operativo Anual 2026" required>
                </div>

                <!-- Descripción -->
                <div class="col-12">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3"
                        placeholder="Descripción general del programa..."><?php echo $is_editing ? htmlspecialchars($programa['descripcion']) : ''; ?></textarea>
                </div>

            </div>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
        <a href="/pao/index.php?route=recursos_financieros/programas_operativos"
            class="btn btn-secondary btn-lg px-4 me-md-2">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg px-5 shadow"><i class="bi bi-save2"></i> Guardar
            Programa</button>
    </div>

</form>

<script>
    // --- ENFORCE UPPERCASE ---
    document.addEventListener('DOMContentLoaded', function () {
        const textInputs = document.querySelectorAll('input[type="text"], textarea');
        textInputs.forEach(function (input) {
            // CSS visual
            input.classList.add('text-uppercase');
            // JS force update value
            input.addEventListener('input', function () {
                this.value = this.value.toUpperCase();
            });
        });
    });
</script>