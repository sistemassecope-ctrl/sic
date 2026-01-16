<?php
$db = (new Database())->getConnection();

$id_fuente = isset($_GET['id']) ? (int) $_GET['id'] : null;
$item = null;
$is_editing = false;

if ($id_fuente) {
    $stmt = $db->prepare("SELECT * FROM cat_fuentes_financiamiento WHERE id_fuente = ?");
    $stmt->execute([$id_fuente]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($item)
        $is_editing = true;
}
?>

<div class="row mb-4 justify-content-center">
    <div class="col-md-8">
        <div class="card shadow border-0">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0"><i class="bi bi-bank"></i>
                    <?php echo $is_editing ? 'Editar' : 'Nueva'; ?> Fuente de Financiamiento
                </h5>
            </div>
            <div class="card-body p-4">
                <form action="/pao/index.php?route=recursos_financieros/cat_fuentes/guardar" method="POST">

                    <?php if ($is_editing): ?>
                        <input type="hidden" name="id_fuente" value="<?php echo $item['id_fuente']; ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase text-secondary">Año Fiscal</label>
                            <input type="number" name="anio" class="form-control"
                                value="<?php echo $is_editing ? $item['anio'] : date('Y'); ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase text-secondary">Abreviatura</label>
                            <input type="text" name="abreviatura" class="form-control text-uppercase"
                                value="<?php echo $is_editing ? $item['abreviatura'] : ''; ?>" placeholder="EJ. PEFM"
                                required>
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch p-3 border rounded w-100 bg-light">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="activo" value="1"
                                    id="checkActivo" <?php echo (!$is_editing || $item['activo']) ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-bold" for="checkActivo">¿Activo (Disponible)?</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold small text-uppercase text-secondary">Nombre de la
                                Fuente</label>
                            <input type="text" name="nombre_fuente" class="form-control text-uppercase"
                                value="<?php echo $is_editing ? $item['nombre_fuente'] : ''; ?>" required>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <a href="/pao/index.php?route=recursos_financieros/cat_fuentes"
                            class="btn btn-secondary me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Fuente</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('input[type="text"]').forEach(input => {
        input.addEventListener('input', e => e.target.value = e.target.value.toUpperCase());
    });
</script>