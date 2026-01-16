<?php
// DB Connection
$db = (new Database())->getConnection();

// --- 1. Fetch Catalogs (reusing some relevant ones if needed, mainly Municipio from CSV) ---
// Just in case we need them, though CSV overrides logic.
// $cat_municipios = $db->query("SELECT * FROM cat_municipios")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Unidades Responsables (From Areas)
$stmtUnidades = $db->query("SELECT id, nombre, tipo FROM areas WHERE activo = 1 AND tipo IN ('Secretaria', 'Subsecretaria', 'Direccion') ORDER BY nombre ASC");
$cat_unidades = $stmtUnidades->fetchAll(PDO::FETCH_ASSOC);

// Fetch Prioridades, Ejes, Objetivos
$cat_prioridades = $db->query("SELECT * FROM cat_prioridades WHERE activo = 1 ORDER BY nombre_prioridad")->fetchAll(PDO::FETCH_ASSOC);
$cat_ejes = $db->query("SELECT * FROM cat_ejes WHERE activo = 1 ORDER BY nombre_eje")->fetchAll(PDO::FETCH_ASSOC);
$cat_objetivos = $db->query("SELECT * FROM cat_objetivos WHERE activo = 1 ORDER BY nombre_objetivo")->fetchAll(PDO::FETCH_ASSOC);
$cat_ramos = $db->query("SELECT * FROM cat_ramos WHERE activo = 1 ORDER BY nombre_ramo")->fetchAll(PDO::FETCH_ASSOC);
$cat_tipos = $db->query("SELECT * FROM cat_tipos_proyecto WHERE activo = 1 ORDER BY nombre_tipo")->fetchAll(PDO::FETCH_ASSOC);



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
        if (!isset($localidades_map[$id_mun])) {
            $localidades_map[$id_mun] = [];
        }
        if (!in_array($nom_loc, $localidades_map[$id_mun])) {
            $localidades_map[$id_mun][] = $nom_loc;
        }
    }
    fclose($handle);
}
$cat_municipios = [];
foreach ($municipios_csv as $id => $nombre) {
    $cat_municipios[] = ['id_municipio' => $id, 'nombre_municipio' => $nombre];
}

// Sort alphabetically by name
usort($cat_municipios, function ($a, $b) {
    return strcmp($a['nombre_municipio'], $b['nombre_municipio']);
});

$localidades_json = json_encode($localidades_map);


// --- Edit Mode vs New Mode Logic ---
$id_proyecto = isset($_GET['id']) ? (int) $_GET['id'] : null;
$id_programa_parent = isset($_GET['id_programa']) ? (int) $_GET['id_programa'] : 0;

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
$nombre_programa_parent = $prog_info['nombre'] ?? 'Desconocido';
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a
                        href="/pao/index.php?route=recursos_financieros/programas_operativos">Programas Operativos</a>
                </li>
                <li class="breadcrumb-item"><a
                        href="/pao/index.php?route=recursos_financieros/programas_operativos/proyectos&id_programa=<?php echo $id_programa_parent; ?>"><?php echo htmlspecialchars($prog_info['nombre'] ?? 'POA'); ?></a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo $is_editing ? 'Editar Proyecto' : 'Nuevo Proyecto'; ?>
                </li>
            </ol>
        </nav>
        <h2 class="text-primary fw-bold"><i class="bi bi-bricks"></i>
            <?php echo $is_editing ? 'Editar' : 'Captura de'; ?> Proyecto de Obra</h2>
        <p class="text-muted border-bottom pb-2">
            Programado en el Ejercicio: <strong><?php echo $prog_info['ejercicio'] ?? 'N/A'; ?></strong>
        </p>
    </div>
</div>

<style>
    body {
        background-color: #e9ecef; /* Fondo escritorio */
    }
    .hoja-papel {
        background: #fff;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: 1px solid #d1d5db;
        border-radius: 0; /* Bordes rectos como papel */
        padding: 40px 50px; /* Márgenes internos tipo documento */
        max-width: 1000px;
        margin: 0 auto;
        min-height: 800px; /* Altura mínima de hoja */
    }
    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 500;
        background: transparent;
        padding: 12px 25px;
        border-radius: 5px;
        transition: all 0.2s ease;
    }
    .nav-tabs .nav-link:hover {
         background-color: #f1f3f5;
         color: #212529;
    }
    .nav-tabs .nav-link.active {
        color: #ffffff !important;
        background-color: #6c757d !important; /* Gris */
        font-weight: 700;
        box-shadow: 0 4px 6px rgba(108, 117, 125, 0.3);
        transform: translateY(-2px);
    }
    .nav-tabs {
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 30px;
        justify-content: center; /* Centrar tabs */
        gap: 10px; /* Espacio entre tabs */
    }
    .form-label {
        color: #374151; /* Gris oscuro para texto */
        font-weight: 600;
    }
    .form-control, .form-select {
        border-radius: 0; /* Inputs cuadrados para look técnico */
        border-color: #ced4da;
        background-color: #fcfcfc;
    }
    .form-control:focus, .form-select:focus {
        box-shadow: none;
        border-color: #86b7fe;
        background-color: #fff;
    }
    .sheet-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #000;
        padding-bottom: 15px;
    }
    .badge-total {
        font-size: 2rem; /* Tamaño más controlado, no gigante */
        padding: 10px 40px;
        border: 2px solid #adb5bd; /* Borde Gris */
        color: #495057; /* Texto Gris Oscuro */
        background: #f8f9fa; /* Fondo Gris Muy Claro */
        border-radius: 8px;
        display: inline-block; /* El recuadro se ajusta al contenido */
        font-family: 'Courier New', monospace; /* Fuente tipo número contable */
    }
</style>

<form action="/pao/index.php?route=recursos_financieros/proyectos/guardar" method="POST" class="needs-validation" novalidate>
    <input type="hidden" name="id_programa" value="<?php echo $id_programa_parent; ?>">
    <?php if ($is_editing): ?>
            <input type="hidden" name="id_proyecto" value="<?php echo $proyecto['id_proyecto']; ?>">
    <?php endif; ?>

    <!-- Contenedor Hoja -->
    <div class="hoja-papel position-relative">
        
        <!-- Encabezado Tipo Documento -->
        <div class="sheet-header">
            <h4 class="text-uppercase fw-bold mb-1">Cédula de Proyecto de Obra</h4>
            <span class="text-muted small">Ejercicio Fiscal <?php echo $prog_info['ejercicio'] ?? '2024'; ?></span>
        </div>

        <!-- Tabs Navigation -->
    <ul class="nav nav-tabs" id="projectTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active text-uppercase small" id="cedula-tab" data-bs-toggle="tab" data-bs-target="#cedula" type="button" role="tab">1. Cédula Técnica</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link text-uppercase small" id="finanzas-tab" data-bs-toggle="tab" data-bs-target="#finanzas" type="button" role="tab">2. Estructura Financiera</button>
        </li>
    </ul>

    <div class="tab-content pt-2" id="projectTabsContent">
        
        <!-- CARPETA 1: CÉDULA TÉCNICA (Datos + Ubicación) -->
        <div class="tab-pane fade show active" id="cedula" role="tabpanel">
            
            <h5 class="text-secondary border-bottom pb-2 mb-4"><i class="bi bi-info-circle me-2"></i>Información General y Ubicación</h5>
            
            <div class="row g-4">
                
                <!-- 1. Unidad Responsable -->
                <div class="col-12">
                    <label class="form-label d-block text-uppercase small text-muted mb-1">1. Unidad Responsable</label>
                    <select name="id_unidad_responsable" class="form-select form-select-lg">
                        <option value="">-- SELECCIONE UNIDAD --</option>
                        <?php foreach ($cat_unidades as $u): ?>
                            <option value="<?php echo $u['id']; ?>" 
                                <?php echo ($is_editing && isset($proyecto['id_unidad_responsable']) && $proyecto['id_unidad_responsable'] == $u['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 2. Nombre del Proyecto -->
                <div class="col-12">
                    <label class="form-label d-block text-uppercase small text-muted mb-1">2. Nombre del Proyecto / Acción</label>
                    <textarea name="nombre_proyecto" class="form-control" rows="2" style="resize: none;" required placeholder="Nombre oficial..."><?php echo $is_editing ? htmlspecialchars($proyecto['nombre_proyecto']) : ''; ?></textarea>
                </div>

                <!-- 3. Breve Descripción -->
                <div class="col-12">
                    <label class="form-label d-block text-uppercase small text-muted mb-1">3. Breve Descripción</label>
                    <textarea name="breve_descripcion" class="form-control" rows="3" style="resize: none;" placeholder="Descripción ejecutiva..."><?php echo $is_editing ? htmlspecialchars($proyecto['breve_descripcion']) : ''; ?></textarea>
                </div>

                <!-- SECCIÓN UBICACIÓN (Integrada aquí) -->
                <div class="col-md-6">
                    <label class="form-label d-block text-uppercase small text-muted mb-1">4. Municipio</label>
                    <select name="id_municipio" class="form-select" required id="select_municipio">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($cat_municipios as $item): ?>
                            <option value="<?php echo $item['id_municipio']; ?>" <?php echo ($is_editing && $proyecto['id_municipio'] == $item['id_municipio']) ? 'selected' : ''; ?>>
                                <?php echo $item['nombre_municipio']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label d-block text-uppercase small text-muted mb-1">5. Localidad</label>
                    <select name="localidad" id="select_localidad" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <?php if ($is_editing): ?>
                            <option value="<?php echo $proyecto['localidad']; ?>" selected><?php echo $proyecto['localidad']; ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label d-block text-uppercase small text-muted mb-1">6. Impacto</label>
                    <select name="impacto_proyecto" class="form-select">
                        <option value="">-- SELECCIONE --</option>
                        <option value="MUNICIPAL" <?php echo ($is_editing && $proyecto['impacto_proyecto'] == 'MUNICIPAL') ? 'selected' : ''; ?>>MUNICIPAL</option>
                        <option value="REGIONAL" <?php echo ($is_editing && $proyecto['impacto_proyecto'] == 'REGIONAL') ? 'selected' : ''; ?>>REGIONAL</option>
                        <option value="ESTATAL" <?php echo ($is_editing && $proyecto['impacto_proyecto'] == 'ESTATAL') ? 'selected' : ''; ?>>ESTATAL</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label d-block text-uppercase small text-muted mb-1">7. Núm. Beneficiarios</label>
                    <input type="number" name="num_beneficiarios" class="form-control" value="<?php echo $is_editing ? $proyecto['num_beneficiarios'] : '0'; ?>">
                </div>

                <div class="col-12 my-2"><hr class="text-secondary"></div>
                <h6 class="text-uppercase small fw-bold text-muted mb-2">Clasificación Administrativa</h6>

                <div class="col-md-4">
                    <label class="form-label d-block text-uppercase small text-muted mb-1">8. Prioridad</label>
                    <select name="id_prioridad" class="form-select">
                        <option value="">-- SELECCIONE --</option>
                        <?php foreach ($cat_prioridades as $cp): ?>
                            <option value="<?php echo $cp['id_prioridad']; ?>" <?php echo ($is_editing && isset($proyecto['id_prioridad']) && $proyecto['id_prioridad'] == $cp['id_prioridad']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cp['nombre_prioridad']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block text-uppercase small text-muted mb-1">9. Tipo de Proyecto</label>
                    <select name="id_tipo_proyecto" class="form-select">
                        <option value="">-- SELECCIONE --</option>
                        <?php foreach ($cat_tipos as $ct): ?>
                            <option value="<?php echo $ct['id_tipo_proyecto']; ?>" <?php echo ($is_editing && isset($proyecto['id_tipo_proyecto']) && $proyecto['id_tipo_proyecto'] == $ct['id_tipo_proyecto']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ct['nombre_tipo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block text-uppercase small text-muted mb-1">10. Clave SHCP</label>
                    <input type="text" name="clave_cartera_shcp" class="form-control" value="<?php echo $is_editing ? htmlspecialchars($proyecto['clave_cartera_shcp']) : ''; ?>">
                </div>

                <!-- Eje y Objetivo -->
                <div class="col-12">
                    <div class="p-3 bg-light border">
                        <h6 class="text-uppercase small fw-bold text-muted mb-3">Alineación Estratégica</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small">11. Eje del P. Estado</label>
                                <select name="id_eje" id="selectEje" class="form-select" onchange="filtrarObjetivos()">
                                    <option value="">-- SELECCIONE EJE --</option>
                                    <?php foreach ($cat_ejes as $ce): ?>
                                        <option value="<?php echo $ce['id_eje']; ?>" <?php echo ($is_editing && isset($proyecto['id_eje']) && $proyecto['id_eje'] == $ce['id_eje']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ce['nombre_eje']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">12. Objetivo Estratégico</label>
                                <select name="id_objetivo" id="selectObjetivo" class="form-select">
                                    <option value="">-- SELECCIONE OBJETIVO --</option>
                                    <?php foreach ($cat_objetivos as $co): ?>
                                        <option value="<?php echo $co['id_objetivo']; ?>" data-eje="<?php echo $co['id_eje']; ?>" <?php echo ($is_editing && isset($proyecto['id_objetivo']) && $proyecto['id_objetivo'] == $co['id_objetivo']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($co['nombre_objetivo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Programa y Ramo -->
                <div class="col-md-6">
                    <label class="form-label d-block text-uppercase small text-muted mb-1">13. Programa</label>
                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($nombre_programa_parent); ?>" disabled readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label d-block text-uppercase small text-muted mb-1">14. Ramo</label>
                    <select name="id_ramo" class="form-select">
                        <option value="">-- SELECCIONE RAMO --</option>
                        <?php foreach ($cat_ramos as $cr): ?>
                            <option value="<?php echo $cr['id_ramo']; ?>" <?php echo ($is_editing && isset($proyecto['id_ramo']) && $proyecto['id_ramo'] == $cr['id_ramo']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cr['nombre_ramo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
        </div>

        <!-- CARPETA 2: ESTRUCTURA FINANCIERA -->
        <div class="tab-pane fade" id="finanzas" role="tabpanel">
            <h5 class="text-secondary border-bottom pb-2 mb-4"><i class="bi bi-cash-stack me-2"></i>Presupuesto y Multianualidad</h5>

            <div class="text-center mb-5 mt-4">
                <span class="d-block text-muted text-uppercase small mb-2">Presupuesto Total Estimado</span>
                <!-- Eliminado display-4, el tamaño ahora lo controla CSS .badge-total -->
                <span class="badge-total fw-bold" id="badgeTotal">$0.00</span>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead class="table-light text-center small text-uppercase">
                        <tr>
                            <th width="25%">15. Federal</th>
                            <th width="25%">16. Estatal</th>
                            <th width="25%">17. Municipal</th>
                            <th width="25%">18. Otros</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text border-0 bg-transparent">$</span>
                                    <input type="number" step="0.01" name="monto_federal" class="form-control border-0 text-end fw-bold monto-input" placeholder="0.00" value="<?php echo $is_editing ? $proyecto['monto_federal'] : ''; ?>">
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text border-0 bg-transparent">$</span>
                                    <input type="number" step="0.01" name="monto_estatal" class="form-control border-0 text-end fw-bold monto-input" placeholder="0.00" value="<?php echo $is_editing ? $proyecto['monto_estatal'] : ''; ?>">
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text border-0 bg-transparent">$</span>
                                    <input type="number" step="0.01" name="monto_municipal" class="form-control border-0 text-end fw-bold monto-input" placeholder="0.00" value="<?php echo $is_editing ? $proyecto['monto_municipal'] : ''; ?>">
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text border-0 bg-transparent">$</span>
                                    <input type="number" step="0.01" name="monto_otros" class="form-control border-0 text-end fw-bold monto-input" placeholder="0.00" value="<?php echo $is_editing ? $proyecto['monto_otros'] : ''; ?>">
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-5">
                <div class="form-check form-switch p-3 border rounded bg-light">
                    <input class="form-check-input" type="checkbox" role="switch" id="es_multianual" name="es_multianual" value="1" <?php echo ($is_editing && $proyecto['es_multianual']) ? 'checked' : ''; ?>>
                    <label class="form-check-label fw-bold ms-2" for="es_multianual">19. ¿Proyecto Multianual?</label>
                </div>
            </div>
        </div>

    </div> <!-- End Tab Content -->

    <!-- Action Buttons (Bottom of Sheet) -->
    <div class="mt-5 pt-4 border-top text-end">
        <a href="/pao/index.php?route=recursos_financieros/programas_operativos/proyectos&id_programa=<?php echo $id_programa_parent; ?>" class="btn btn-outline-secondary px-4 me-2">
            CANCELAR
        </a>
        <button type="submit" class="btn btn-dark px-5">
            GUARDAR CÉDULA
        </button>
    </div>

    </div> <!-- End Hoja Papel -->
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
    function filtrarObjetivos() {
        const ejeId = document.getElementById('selectEje').value;
        const opts = document.getElementById('selectObjetivo').options;
        let count = 0;
        
        for (let i = 1; i < opts.length; i++) { // Skip placeholder
            const optEje = opts[i].getAttribute('data-eje');
            if (!ejeId || optEje == ejeId) {
                opts[i].style.display = '';
                count++;
            } else {
                opts[i].style.display = 'none';
            }
        }
        // Deseleccionar si el actual está oculto (opcional, mejor dejar que el usuario cambie)
        if(ejeId && count > 0 && opts[document.getElementById('selectObjetivo').selectedIndex].style.display === 'none') {
             document.getElementById('selectObjetivo').value = "";
        }
    }
    // Init filter on load
    document.addEventListener('DOMContentLoaded', filtrarObjetivos);
</script>
