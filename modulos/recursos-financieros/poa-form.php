<?php
/**
 * Módulo: Formulario de POA
 * Ubicación: /modulos/recursos-financieros/poa-form.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getConnection();
$user = getCurrentUser();

$id_programa = isset($_GET['id']) ? (int) $_GET['id'] : null;
$programa = null;
$is_editing = false;

if ($id_programa) {
    $stmt = $pdo->prepare("SELECT * FROM programas_anuales WHERE id_programa = ?");
    $stmt->execute([$id_programa]);
    $programa = $stmt->fetch();
    if ($programa)
        $is_editing = true;
}

// --- Procesar Guardado ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ejercicio = (int) $_POST['ejercicio'];
    $nombre = mb_strtoupper(trim($_POST['nombre']));
    $descripcion = trim($_POST['descripcion']);
    $estatus = $_POST['estatus'];
    $monto = (float) str_replace(',', '', $_POST['monto_autorizado']);

    try {
        if ($is_editing) {
            $sql = "UPDATE programas_anuales SET ejercicio = ?, nombre = ?, descripcion = ?, estatus = ?, monto_autorizado = ? WHERE id_programa = ?";
            $pdo->prepare($sql)->execute([$ejercicio, $nombre, $descripcion, $estatus, $monto, $id_programa]);
            setFlashMessage('success', 'Programa Anual actualizado correctamente');
        } else {
            // Check for duplicate exercise
            $stmt_check = $pdo->prepare("SELECT id_programa FROM programas_anuales WHERE ejercicio = ?");
            $stmt_check->execute([$ejercicio]);
            if ($stmt_check->fetch()) {
                throw new Exception("Ya existe un Programa Anual para el ejercicio $ejercicio.");
            }

            $sql = "INSERT INTO programas_anuales (ejercicio, nombre, descripcion, estatus, monto_autorizado) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$ejercicio, $nombre, $descripcion, $estatus, $monto]);
            setFlashMessage('success', 'Programa Anual creado correctamente');
        }
        redirect('poas.php');
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
                    <li class="breadcrumb-item"><a href="poas.php">Programas Operativos</a></li>
                    <li class="breadcrumb-item active">
                        <?= $is_editing ? 'Editar' : 'Nuevo' ?> POA
                    </li>
                </ol>
            </nav>
            <h1 class="page-title"><i class="fas fa-calendar-plus text-primary"></i>
                <?= $is_editing ? 'Editar' : 'Nuevo' ?> Programa Anual
            </h1>
        </div>
    </div>

    <?= renderFlashMessage() ?>

    <form method="POST" class="needs-validation" onsubmit="preSubmit()">
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <div class="card-header border-bottom py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2 text-primary"></i> Datos del POA</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label">Ejercicio Fiscal</label>
                        <input type="number" name="ejercicio" class="form-control fw-bold"
                            value="<?= $is_editing ? $programa['ejercicio'] : date('Y') ?>" required min="2020"
                            max="2040">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estatus</label>
                        <select name="estatus" class="form-control">
                            <option value="Abierto" <?= ($is_editing && $programa['estatus'] == 'Abierto') ? 'selected' : '' ?>>Abierto</option>
                            <option value="En Revisión" <?= ($is_editing && $programa['estatus'] == 'En Revisión') ? 'selected' : '' ?>>En Revisión</option>
                            <option value="Cerrado" <?= ($is_editing && $programa['estatus'] == 'Cerrado') ? 'selected' : '' ?>>Cerrado</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-success">Monto Autorizado</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" id="monto_input" name="monto_autorizado"
                                class="form-control text-end fw-bold" placeholder="0.00"
                                value="<?= $is_editing ? number_format($programa['monto_autorizado'], 2) : '' ?>"
                                oninput="formatCurrency(this)">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nombre del Programa</label>
                        <input type="text" name="nombre" class="form-control text-uppercase"
                            placeholder="Ej: PROGRAMA OPERATIVO ANUAL 2026" required
                            value="<?= $is_editing ? e($programa['nombre']) : '' ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripción / Justificación</label>
                        <textarea name="descripcion" class="form-control"
                            rows="4"><?= $is_editing ? e($programa['descripcion']) : '' ?></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer p-4 border-top d-flex justify-content-end gap-3">
                <a href="poas.php" class="btn btn-secondary px-4">Cancelar</a>
                <button type="submit" class="btn btn-primary px-5"><i class="fas fa-save me-2"></i> Guardar
                    Programa</button>
            </div>
        </div>
    </form>
</main>

<script>
    function formatCurrency(input) {
        let value = input.value.replace(/[^0-9.]/g, '');
        const parts = value.split('.');
        if (parts.length > 2) value = parts[0] + '.' + parts.slice(1).join('');
        const numberParts = value.split('.');
        const integerPart = numberParts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        const decimalPart = numberParts.length > 1 ? '.' + numberParts[1].substring(0, 2) : '';
        input.value = integerPart + decimalPart;
    }

    function preSubmit() {
        const monto = document.getElementById('monto_input');
        monto.value = monto.value.replace(/,/g, '');
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>