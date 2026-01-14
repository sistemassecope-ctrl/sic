<?php
// DB Connection
$db = (new Database())->getConnection();

// --- 1. Fetch Catalogs (reusing some relevant ones if needed, mainly Municipio from CSV) ---
// Just in case we need them, though CSV overrides logic.
// $cat_municipios = $db->query("SELECT * FROM cat_municipios")->fetchAll(PDO::FETCH_ASSOC);

// --- Load CSV Data for Municipios and Localidades ---
$csvFile = $_SERVER['DOCUMENT_ROOT'] . '/pao/comun/municipios.csv';
$municipios_csv = [];
$localidades_map = [];

if (file_exists($csvFile) && ($handle = fopen($csvFile, "r")) !== FALSE) {
    fgetcsv($handle, 1000, ","); // header
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $id_mun = trim($data[0]);
        $nom_mun = trim($data[1]);
        $nom_loc = trim($data[2]);
        $municipios_csv[$id_mun] = $nom_mun;
        if (!isset($localidades_map[$id_mun])) { $localidades_map[$id_mun] = []; }
        if (!in_array($nom_loc, $localidades_map[$id_mun])) { $localidades_map[$id_mun][] = $nom_loc; }
    }
    fclose($handle);
}
$cat_municipios = [];
foreach ($municipios_csv as $id => $nombre) {
    $cat_municipios[] = ['id_municipio' => $id, 'nombre_municipio' => $nombre];
}

// Sort alphabetically by name
usort($cat_municipios, function($a, $b) {
    return strcmp($a['nombre_municipio'], $b['nombre_municipio']);
});

$localidades_json = json_encode($localidades_map);


// --- Edit Mode vs New Mode Logic ---
$id_proyecto = isset($_GET['id']) ? (int)$_GET['id'] : null;
$id_programa_parent = isset($_GET['id_programa']) ? (int)$_GET['id_programa'] : 0;

$proyecto = null;
$is_editing = false;

if ($id_proyecto) {
    $stmt = $db->prepare("SELECT * FROM proyectos_obra WHERE id_proyecto = ?");
    $stmt->execute([$id_proyecto]);
    $proyecto = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($proyecto) {
        $is_editing = true;
        $id_programa_parent = $proyecto['id_programa']; // Ensure we go back to the right place
    }
}

// Ensure we have a parent program ID
if (!$id_programa_parent) {
    echo "<div class='alert alert-danger'>Error: No se especificó el programa operativo.</div>";
    return;
}

// Fetch Parent Program Name for display
$stmt_prog = $db->prepare("SELECT nombre, ejercicio FROM programas_anuales WHERE id_programa = ?");
$stmt_prog->execute([$id_programa_parent]);
$prog_info = $stmt_prog->fetch(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/pao/index.php?route=recursos_financieros/programas_operativos">Programas Operativos</a></li>
                <li class="breadcrumb-item"><a href="/pao/index.php?route=recursos_financieros/programas_operativos/proyectos&id_programa=<?php echo $id_programa_parent; ?>"><?php echo htmlspecialchars($prog_info['nombre'] ?? 'POA'); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $is_editing ? 'Editar Proyecto' : 'Nuevo Proyecto'; ?></li>
            </ol>
        </nav>
        <h2 class="text-primary fw-bold"><i class="bi bi-bricks"></i> <?php echo $is_editing ? 'Editar' : 'Captura de'; ?> Proyecto de Obra</h2>
        <p class="text-muted border-bottom pb-2">
            Programado en el Ejercicio: <strong><?php echo $prog_info['ejercicio'] ?? 'N/A'; ?></strong>
        </p>
    </div>
</div>

<form action="/pao/index.php?route=recursos_financieros/proyectos/guardar" method="POST" class="needs-validation" novalidate>
    <input type="hidden" name="id_programa" value="<?php echo $id_programa_parent; ?>">
    <?php if ($is_editing): ?>
        <input type="hidden" name="id_proyecto" value="<?php echo $proyecto['id_proyecto']; ?>">
    <?php endif; ?>

    <!-- ================= SECCIÓN 1: DATOS GENERALES ================= -->
    <div class="card shadow-sm mb-4 border-0 rounded-3">
        <div class="card-header bg-gradient bg-light text-dark fw-bold py-3">
            <i class="bi bi-info-circle-fill me-2 text-primary"></i> Información del Proyecto
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                
                <!-- Nombre del Proyecto -->
                <div class="col-12">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Nombre del Proyecto / Acción</label>
                    <textarea name="nombre_proyecto" class="form-control" rows="2" required placeholder="Nombre oficial..."><?php echo $is_editing ? htmlspecialchars($proyecto['nombre_proyecto']) : ''; ?></textarea>
                </div>

                <!-- Breve Descripción -->
                <div class="col-12">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Breve Descripción</label>
                    <textarea name="breve_descripcion" class="form-control" rows="2" placeholder="Detalles adicionales..."><?php echo $is_editing ? htmlspecialchars($proyecto['breve_descripcion']) : ''; ?></textarea>
                </div>

                <!-- Clave SHCP -->
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Clave Cartera SHCP</label>
                    <input type="text" name="clave_cartera_shcp" class="form-control font-monospace" placeholder="Ej. 2024-..."
                        value="<?php echo $is_editing ? htmlspecialchars($proyecto['clave_cartera_shcp']) : ''; ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ================= SECCIÓN 2: UBICACIÓN Y BENEFICIOS ================= -->
    <div class="card shadow-sm mb-4 border-0 rounded-3">
        <div class="card-header bg-gradient bg-light text-dark fw-bold py-3">
            <i class="bi bi-geo-alt-fill me-2 text-success"></i> Ubicación e Impacto
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <!-- Municipio -->
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Municipio</label>
                    <select name="id_municipio" class="form-select" required id="select_municipio">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($cat_municipios as $item): ?>
                            <option value="<?php echo $item['id_municipio']; ?>" 
                                <?php echo ($is_editing && $proyecto['id_municipio'] == $item['id_municipio']) ? 'selected' : ''; ?>>
                                <?php echo $item['nombre_municipio']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Localidad -->
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Localidad</label>
                    <select name="localidad" id="select_localidad" class="form-select" required>
                        <option value="">-- Seleccione Municipio Primero --</option>
                        <!-- Populated by JS -->
                        <?php if ($is_editing): ?>
                            <option value="<?php echo $proyecto['localidad']; ?>" selected><?php echo $proyecto['localidad']; ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Impacto -->
                <div class="col-md-8">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Impacto del Proyecto</label>
                    <textarea name="impacto_proyecto" class="form-control" rows="1"><?php echo $is_editing ? htmlspecialchars($proyecto['impacto_proyecto']) : ''; ?></textarea>
                </div>

                <!-- Beneficiarios -->
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-uppercase text-secondary">Núm. Beneficiarios</label>
                    <input type="number" name="num_beneficiarios" class="form-control text-end" min="0"
                        value="<?php echo $is_editing ? $proyecto['num_beneficiarios'] : '0'; ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ================= SECCIÓN 3: ESTRUCTURA FINANCIERA ================= -->
    <div class="card shadow-lg mb-4 border-0 rounded-3">
        <div class="card-header bg-gradient bg-primary text-white fw-bold py-3 d-flex justify-content-between">
            <span><i class="bi bi-currency-dollar me-2"></i> Estructura Financiera</span>
            <span class="badge bg-light text-primary fs-6" id="badgeTotal">$0.00</span>
        </div>
        <div class="card-body p-4 bg-light bg-opacity-10">
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-bold text-secondary">Federal</label>
                    <input type="number" step="0.01" name="monto_federal" class="form-control text-end monto-input" 
                        value="<?php echo $is_editing ? $proyecto['monto_federal'] : ''; ?>" placeholder="0.00">
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-bold text-secondary">Estatal</label>
                    <input type="number" step="0.01" name="monto_estatal" class="form-control text-end monto-input" 
                        value="<?php echo $is_editing ? $proyecto['monto_estatal'] : ''; ?>" placeholder="0.00">
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-bold text-secondary">Municipal</label>
                    <input type="number" step="0.01" name="monto_municipal" class="form-control text-end monto-input" 
                        value="<?php echo $is_editing ? $proyecto['monto_municipal'] : ''; ?>" placeholder="0.00">
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-bold text-secondary">Otros</label>
                    <input type="number" step="0.01" name="monto_otros" class="form-control text-end monto-input" 
                        value="<?php echo $is_editing ? $proyecto['monto_otros'] : ''; ?>" placeholder="0.00">
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12 d-flex justify-content-end">
                    <div class="form-check form-switch fs-5">
                        <input class="form-check-input" type="checkbox" role="switch" id="es_multianual" name="es_multianual" value="1"
                            <?php echo ($is_editing && $proyecto['es_multianual']) ? 'checked' : ''; ?>>
                        <label class="form-check-label ms-2" for="es_multianual">¿Es un Proyecto Multianual?</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones -->
    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
        <a href="/pao/index.php?route=recursos_financieros/programas_operativos/proyectos&id_programa=<?php echo $id_programa_parent; ?>" class="btn btn-secondary btn-lg px-4 me-md-2">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg px-5 shadow"><i class="bi bi-save2"></i> Guardar Proyecto</button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Enforce Uppercase
        const textInputs = document.querySelectorAll('input[type="text"], textarea');
        textInputs.forEach(function (input) {
            input.classList.add('text-uppercase');
            input.addEventListener('input', function () { this.value = this.value.toUpperCase(); });
        });

        // Totals
        const inputs = document.querySelectorAll('.monto-input');
        const badgeTotal = document.getElementById('badgeTotal');
        function calcularTotal() {
            let total = 0;
            inputs.forEach(input => { total += parseFloat(input.value) || 0; });
            badgeTotal.textContent = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        inputs.forEach(input => { input.addEventListener('input', calcularTotal); });
        calcularTotal(); // Init

        // Localidades
        const localidadesMap = <?php echo $localidades_json ?: '{}'; ?>;
        const selectMunicipio = document.getElementById('select_municipio');
        const selectLocalidad = document.getElementById('select_localidad');
        const currentLoc = "<?php echo $is_editing ? $proyecto['localidad'] : ''; ?>";

        function actualizarLocalidades() {
            const munId = selectMunicipio.value;
            // Guardar selección actual si no cambia municipio
            const selectedVal = selectLocalidad.value || currentLoc;
            
            selectLocalidad.innerHTML = '<option value="">-- Seleccione --</option>';

            if (munId && localidadesMap[munId]) {
                localidadesMap[munId].forEach(loc => {
                    const option = document.createElement('option');
                    option.value = loc; 
                    option.textContent = loc;
                    if (loc === selectedVal) option.selected = true;
                    selectLocalidad.appendChild(option);
                });
                selectLocalidad.disabled = false;
            } else {
                selectLocalidad.disabled = true;
            }
        }
        selectMunicipio.addEventListener('change', actualizarLocalidades);
        if (selectMunicipio.value) actualizarLocalidades();
    });
</script>
