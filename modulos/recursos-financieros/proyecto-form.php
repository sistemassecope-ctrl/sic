<?php
/**
 * Módulo: Formulario de Proyecto de Obra
 * Ubicación: /modulos/recursos-financieros/proyecto-form.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getConnection();
$user = getCurrentUser();

$id_proyecto = isset($_GET['id']) ? (int) $_GET['id'] : null;
$id_programa_parent = isset($_GET['id_programa']) ? (int) $_GET['id_programa'] : 0;
$proyecto = null;
$is_editing = false;

if ($id_proyecto) {
    $stmt = $pdo->prepare("SELECT * FROM proyectos_obra WHERE id_proyecto = ?");
    $stmt->execute([$id_proyecto]);
    $proyecto = $stmt->fetch();
    if ($proyecto) {
        $is_editing = true;
        $id_programa_parent = $proyecto['id_programa'];
    }
}

if (!$id_programa_parent) {
    setFlashMessage('error', 'Programa no especificado.');
    redirect('modulos/recursos-financieros/poas.php');
}

// Info del Programa
$stmt_prog = $pdo->prepare("SELECT nombre, ejercicio FROM programas_anuales WHERE id_programa = ?");
$stmt_prog->execute([$id_programa_parent]);
$prog_info = $stmt_prog->fetch();

// --- Cargar Catálogos ---
$cat_unidades = $pdo->query("SELECT id, nombre_area as nombre FROM areas WHERE estado = 1 AND id_tipo_area IN (1, 2, 3) ORDER BY nombre_area")->fetchAll();
$cat_prioridades = $pdo->query("SELECT * FROM cat_prioridades WHERE activo = 1 ORDER BY nombre_prioridad")->fetchAll();
$cat_ejes = $pdo->query("SELECT * FROM cat_ejes WHERE activo = 1 ORDER BY nombre_eje")->fetchAll();
$cat_objetivos = $pdo->query("SELECT * FROM cat_objetivos WHERE activo = 1 ORDER BY nombre_objetivo")->fetchAll();
$cat_ramos = $pdo->query("SELECT * FROM cat_ramos WHERE activo = 1 ORDER BY nombre_ramo")->fetchAll();
$cat_tipos = $pdo->query("SELECT * FROM cat_tipos_proyectos WHERE activo = 1 ORDER BY nombre_tipo")->fetchAll();

// --- CSV Municipios ---
$csvFile = __DIR__ . '/../../comun/municipios.csv';
$municipios_map = [];
$localidades_map = [];
if (file_exists($csvFile) && ($handle = fopen($csvFile, "r")) !== FALSE) {
    fgetcsv($handle); // skip header
    while (($data = fgetcsv($handle)) !== FALSE) {
        $id_mun = trim($data[0]);
        $nom_mun = trim($data[1]);
        $nom_loc = trim($data[2]);
        $municipios_map[$id_mun] = $nom_mun;
        if (!isset($localidades_map[$id_mun]))
            $localidades_map[$id_mun] = [];
        $localidades_map[$id_mun][] = $nom_loc;
    }
    fclose($handle);
}

// --- Procesar Guardado ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id_programa' => $id_programa_parent,
        'ejercicio' => $prog_info['ejercicio'],
        'nombre_proyecto' => mb_strtoupper(trim($_POST['nombre_proyecto'])),
        'breve_descripcion' => mb_strtoupper(trim($_POST['breve_descripcion'])),
        'clave_cartera_shcp' => trim($_POST['clave_cartera_shcp']),
        'id_unidad_responsable' => (int) $_POST['id_unidad_responsable'],
        'id_prioridad' => (int) $_POST['id_prioridad'],
        'id_eje' => (int) $_POST['id_eje'],
        'id_objetivo' => (int) $_POST['id_objetivo'],
        'id_ramo' => (int) $_POST['id_ramo'],
        'id_tipo_proyecto' => (int) $_POST['id_tipo_proyecto'],
        'id_municipio' => (int) $_POST['id_municipio'],
        'localidad' => mb_strtoupper(trim($_POST['localidad'])),
        'impacto_proyecto' => $_POST['impacto_proyecto'],
        'num_beneficiarios' => (int) $_POST['num_beneficiarios'],
        'monto_federal' => (float) str_replace(',', '', $_POST['monto_federal']),
        'monto_estatal' => (float) str_replace(',', '', $_POST['monto_estatal']),
        'monto_municipal' => (float) str_replace(',', '', $_POST['monto_municipal']),
        'monto_otros' => (float) str_replace(',', '', $_POST['monto_otros']),
        'es_multianual' => isset($_POST['es_multianual']) ? 1 : 0,
        'id_usuario_registro' => $user['id']
    ];

    try {
        if ($is_editing) {
            // Validation: New total >= Committed
            $stmtC = $pdo->prepare("SELECT SUM(importe) FROM fuas WHERE id_proyecto = ? AND estatus != 'CANCELADO'");
            $stmtC->execute([$id_proyecto]);
            $comprometido = (float) $stmtC->fetchColumn();
            $nuevo_total = $data['monto_federal'] + $data['monto_estatal'] + $data['monto_municipal'] + $data['monto_otros'];

            if ($nuevo_total < $comprometido) {
                throw new Exception("El nuevo monto total ($" . number_format($nuevo_total, 2) . ") es menor a lo comprometido ($" . number_format($comprometido, 2) . ").");
            }

            $sql = "UPDATE proyectos_obra SET id_programa=?, ejercicio=?, nombre_proyecto=?, breve_descripcion=?, clave_cartera_shcp=?, id_unidad_responsable=?, id_prioridad=?, id_eje=?, id_objetivo=?, id_ramo=?, id_tipo_proyecto=?, id_municipio=?, localidad=?, impacto_proyecto=?, num_beneficiarios=?, monto_federal=?, monto_estatal=?, monto_municipal=?, monto_otros=?, es_multianual=? WHERE id_proyecto=?";
            unset($data['id_usuario_registro']);
            $pdo->prepare($sql)->execute(array_merge(array_values($data), [$id_proyecto]));
            setFlashMessage('success', 'Proyecto actualizado');
        } else {
            $sql = "INSERT INTO proyectos_obra (id_programa, ejercicio, nombre_proyecto, breve_descripcion, clave_cartera_shcp, id_unidad_responsable, id_prioridad, id_eje, id_objetivo, id_ramo, id_tipo_proyecto, id_municipio, localidad, impacto_proyecto, num_beneficiarios, monto_federal, monto_estatal, monto_municipal, monto_otros, es_multianual, id_usuario_registro) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $pdo->prepare($sql)->execute(array_values($data));
            setFlashMessage('success', 'Proyecto registrado');
        }
        redirect("modulos/recursos-financieros/proyectos.php?id_programa=$id_programa_parent");
    } catch (Exception $e) {
        setFlashMessage('error', $e->getMessage());
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="poas.php">Programas</a></li>
                    <li class="breadcrumb-item"><a href="proyectos.php?id_programa=<?= $id_programa_parent ?>">
                            <?= e($prog_info['nombre']) ?>
                        </a></li>
                    <li class="breadcrumb-item active">
                        <?= $is_editing ? 'Editar' : 'Capturar' ?> Proyecto
                    </li>
                </ol>
            </nav>
            <h1 class="page-title"><i class="fas fa-edit text-primary"></i>
                <?= $is_editing ? 'Editar' : 'Nueva' ?> Cédula de Proyecto
            </h1>
        </div>
    </div>

    <?= renderFlashMessage() ?>

    <form method="POST" onsubmit="preSubmit()">
        <div class="hoja-papel">
            <div class="sheet-header">
                <h3>CÉDULA TÉCNICA DE PROYECTO / ACCIÓN</h3>
                <p>EJERCICIO FISCAL
                    <?= $prog_info['ejercicio'] ?>
                </p>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <label class="form-label text-muted x-small">1. UNIDAD RESPONSABLE</label>
                    <select name="id_unidad_responsable" class="form-control" required>
                        <option value="">-- SELECCIONE --</option>
                        <?php foreach ($cat_unidades as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($is_editing && $proyecto['id_unidad_responsable'] == $u['id']) ? 'selected' : '' ?>>
                                <?= e($u['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label text-muted x-small">2. NOMBRE DEL PROYECTO</label>
                    <textarea name="nombre_proyecto" class="form-control text-uppercase" rows="2"
                        required><?= $is_editing ? e($proyecto['nombre_proyecto']) : '' ?></textarea>
                </div>

                <div class="col-12">
                    <label class="form-label text-muted x-small">3. DESCRIPCIÓN EJECUTIVA</label>
                    <textarea name="breve_descripcion" class="form-control text-uppercase"
                        rows="3"><?= $is_editing ? e($proyecto['breve_descripcion']) : '' ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label text-muted x-small">4. MUNICIPIO</label>
                    <select name="id_municipio" id="select_mun" class="form-control" required onchange="updateLocs()">
                        <option value="">-- SELECCIONE --</option>
                        <?php foreach ($municipios_map as $id => $nom): ?>
                            <option value="<?= $id ?>" <?= ($is_editing && $proyecto['id_municipio'] == $id) ? 'selected' : '' ?>>
                                <?= e($nom) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted x-small">5. LOCALIDAD</label>
                    <select name="localidad" id="select_loc" class="form-control" required>
                        <option value="">-- SELECCIONE MUNICIPIO PRIMERO --</option>
                    </select>
                </div>

                <div class="col-12">
                    <hr class="border-secondary">
                </div>

                <div class="col-md-6">
                    <div class="p-4 border rounded bg-dark-card text-center h-100">
                        <label class="form-label text-muted x-small mb-3">PRESUPUESTO TOTAL ESTIMADO</label>
                        <div id="badgeTotal" class="display-6 fw-bold text-primary">$0.00</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label x-small">FEDERAL</label>
                            <input type="text" name="monto_federal" class="form-control text-end monto-input"
                                value="<?= $is_editing ? number_format($proyecto['monto_federal'], 2) : '' ?>"
                                oninput="formatCurrency(this)">
                        </div>
                        <div class="col-6">
                            <label class="form-label x-small">ESTATAL</label>
                            <input type="text" name="monto_estatal" class="form-control text-end monto-input"
                                value="<?= $is_editing ? number_format($proyecto['monto_estatal'], 2) : '' ?>"
                                oninput="formatCurrency(this)">
                        </div>
                        <div class="col-6">
                            <label class="form-label x-small">MUNICIPAL</label>
                            <input type="text" name="monto_municipal" class="form-control text-end monto-input"
                                value="<?= $is_editing ? number_format($proyecto['monto_municipal'], 2) : '' ?>"
                                oninput="formatCurrency(this)">
                        </div>
                        <div class="col-6">
                            <label class="form-label x-small">OTROS</label>
                            <input type="text" name="monto_otros" class="form-control text-end monto-input"
                                value="<?= $is_editing ? number_format($proyecto['monto_otros'], 2) : '' ?>"
                                oninput="formatCurrency(this)">
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <hr class="border-secondary">
                </div>

                <div class="col-md-4">
                    <label class="form-label x-small text-muted">PRIORIDAD</label>
                    <select name="id_prioridad" class="form-control">
                        <?php foreach ($cat_prioridades as $cp): ?>
                            <option value="<?= $cp['id_prioridad'] ?>" <?= ($is_editing && $proyecto['id_prioridad'] == $cp['id_prioridad']) ? 'selected' : '' ?>>
                                <?= e($cp['nombre_prioridad']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label x-small text-muted">TIPO PROYECTO</label>
                    <select name="id_tipo_proyecto" class="form-control">
                        <?php foreach ($cat_tipos as $ct): ?>
                            <option value="<?= $ct['id_tipo'] ?>" <?= ($is_editing && $proyecto['id_tipo_proyecto'] == $ct['id_tipo']) ? 'selected' : '' ?>>
                                <?= e($ct['nombre_type'] ?? $ct['nombre_tipo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label x-small text-muted">CLAVE SHCP</label>
                    <input type="text" name="clave_cartera_shcp" class="form-control"
                        value="<?= $is_editing ? e($proyecto['clave_cartera_shcp']) : '' ?>">
                </div>

                <div class="col-12">
                    <div class="p-4 rounded border" style="background: rgba(255,255,255,0.02);">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label x-small text-muted">EJE ESTRATÉGICO</label>
                                <select name="id_eje" id="select_eje" class="form-control" onchange="filterObjs()">
                                    <option value="">-- SELECCIONE --</option>
                                    <?php foreach ($cat_ejes as $ce): ?>
                                        <option value="<?= $ce['id_eje'] ?>" <?= ($is_editing && $proyecto['id_eje'] == $ce['id_eje']) ? 'selected' : '' ?>>
                                            <?= e($ce['nombre_eje']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label x-small text-muted">OBJETIVO</label>
                                <select name="id_objetivo" id="select_obj" class="form-control">
                                    <option value="">-- SELECCIONE EJE --</option>
                                    <?php foreach ($cat_objetivos as $co): ?>
                                        <option value="<?= $co['id_objetivo'] ?>" data-eje="<?= $co['id_eje'] ?>"
                                            <?= ($is_editing && $proyecto['id_objetivo'] == $co['id_objetivo']) ? 'selected' : '' ?>>
                                            <?= e($co['nombre_objetivo']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label x-small text-muted">IMPACTO</label>
                    <select name="impacto_proyecto" class="form-control">
                        <option value="MUNICIPAL" <?= ($is_editing && $proyecto['impacto_proyecto'] == 'MUNICIPAL') ? 'selected' : '' ?>>MUNICIPAL</option>
                        <option value="REGIONAL" <?= ($is_editing && $proyecto['impacto_proyecto'] == 'REGIONAL') ? 'selected' : '' ?>>REGIONAL</option>
                        <option value="ESTATAL" <?= ($is_editing && $proyecto['impacto_proyecto'] == 'ESTATAL') ? 'selected' : '' ?>>ESTATAL</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label x-small text-muted">RAMO</label>
                    <select name="id_ramo" class="form-control">
                        <?php foreach ($cat_ramos as $cr): ?>
                            <option value="<?= $cr['id_ramo'] ?>" <?= ($is_editing && $proyecto['id_ramo'] == $cr['id_ramo']) ? 'selected' : '' ?>>
                                <?= e($cr['nombre_ramo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-center justify-content-center">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="es_multianual" id="cm" value="1"
                            <?= ($is_editing && $proyecto['es_multianual']) ? 'checked' : '' ?>>
                        <label class="form-check-label ms-2" for="cm">¿Proyecto Multianual?</label>
                    </div>
                </div>

                <div class="col-12 mt-5 text-end border-top pt-4">
                    <a href="proyectos.php?id_programa=<?= $id_programa_parent ?>"
                        class="btn btn-secondary px-4 me-2">CANCELAR</a>
                    <button type="submit" class="btn btn-primary px-5 fw-bold">GUARDAR CÉDULA</button>
                </div>
            </div>
        </div>
    </form>
</main>

<style>
    .hoja-papel {
        background: var(--bg-card);
        border: 1px solid var(--border-primary);
        max-width: 1000px;
        margin: 0 auto;
        padding: 3rem;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
    }

    .sheet-header {
        text-align: center;
        border-bottom: 2px solid var(--accent-primary);
        padding-bottom: 1.5rem;
        margin-bottom: 2rem;
    }

    .sheet-header h3 {
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .sheet-header p {
        font-weight: 600;
        color: var(--accent-primary);
        letter-spacing: 2px;
    }

    .x-small {
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.5px;
    }

    .bg-dark-card {
        background: rgba(0, 0, 0, 0.2);
    }
</style>

<script>
    const locs = <?= json_encode($localidades_map) ?>;
    const curLoc = "<?= $is_editing ? e($proyecto['localidad']) : '' ?>";

    function updateLocs() {
        const munId = document.getElementById('select_mun').value;
        const select = document.getElementById('select_loc');
        select.innerHTML = '<option value="">-- SELECCIONE --</option>';
        if (locs[munId]) {
            locs[munId].forEach(l => {
                const opt = document.createElement('option');
                opt.value = l; opt.textContent = l;
                if (l === curLoc) opt.selected = true;
                select.appendChild(opt);
            });
        }
    }

    function filterObjs() {
        const ejeId = document.getElementById('select_eje').value;
        const opts = document.getElementById('select_obj').options;
        for (let i = 1; i < opts.length; i++) {
            opts[i].style.display = (!ejeId || opts[i].getAttribute('data-eje') == ejeId) ? '' : 'none';
        }
    }

    function formatCurrency(input) {
        let value = input.value.replace(/[^0-9.]/g, '');
        const parts = value.split('.');
        if (parts.length > 2) value = parts[0] + '.' + parts.slice(1).join('');
        const numberParts = value.split('.');
        const integerPart = numberParts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        const decimalPart = numberParts.length > 1 ? '.' + numberParts[1].substring(0, 2) : '';
        input.value = integerPart + decimalPart;
        calcTotal();
    }

    function calcTotal() {
        let total = 0;
        document.querySelectorAll('.monto-input').forEach(i => {
            total += parseFloat(i.value.replace(/,/g, '')) || 0;
        });
        document.getElementById('badgeTotal').textContent = '$' + total.toLocaleString('es-MX', { minimumFractionDigits: 2 });
    }

    function preSubmit() {
        document.querySelectorAll('.monto-input').forEach(i => i.value = i.value.replace(/,/g, ''));
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateLocs();
        filterObjs();
        calcTotal();
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>