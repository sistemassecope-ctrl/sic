<?php
/**
 * Módulo: Formulario de POA
 * Ubicación: /modulos/recursos-financieros/poa-form.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// ID del módulo de Programas Operativos (Listado de Programas)
define('MODULO_ID', 56);

// Obtener permisos del usuario para este módulo
$permisos_user = getUserPermissions(MODULO_ID);
$puedeCrear = in_array('crear', $permisos_user);
$puedeEditar = in_array('editar', $permisos_user);

$pdo = getConnection();
$user = getCurrentUser();

$id_programa = isset($_GET['id']) ? (int) $_GET['id'] : null;
$programa = null;
$is_editing = false;
$partidas_asignadas = [];
$partidas_catalogo = [];

if ($id_programa) {
    if (!$puedeEditar) {
        setFlashMessage('error', 'No tienes permiso para editar Programas Anuales.');
        redirect('modulos/recursos-financieros/poas.php');
    }
    $stmt = $pdo->prepare("SELECT * FROM programas_anuales WHERE id_programa = ?");
    $stmt->execute([$id_programa]);
    $programa = $stmt->fetch();
    if ($programa)
        $is_editing = true;

    // Obtener partidas ya asignadas
    $stmtPartidas = $pdo->prepare("
        SELECT pp.*, c.clave, c.nombre 
        FROM programa_partidas pp
        JOIN cat_partidas_presupuestales c ON pp.id_partida = c.id_partida
        WHERE pp.id_programa = ?
        ORDER BY c.clave
    ");
    $stmtPartidas->execute([$id_programa]);
    $partidas_asignadas = $stmtPartidas->fetchAll();

    // Obtener catálogo completo de partidas activas
    $stmtCat = $pdo->query("SELECT * FROM cat_partidas_presupuestales WHERE activo = 1 ORDER BY clave");
    $partidas_catalogo = $stmtCat->fetchAll();
} else {
    if (!$puedeCrear) {
        setFlashMessage('error', 'No tienes permiso para crear Programas Anuales.');
        redirect('modulos/recursos-financieros/poas.php');
    }
}

// --- Procesar Acciones de Partidas (AJAX/Form Submit Disguised) ---
if (isset($_POST['accion_partida']) && $is_editing) {
    try {
        if ($_POST['accion_partida'] === 'agregar') {
            $id_partida = (int) $_POST['id_partida'];
            $monto_partida = (float) str_replace(',', '', $_POST['monto_partida']);

            // Validar que no exista ya
            $check = $pdo->prepare("SELECT id FROM programa_partidas WHERE id_programa = ? AND id_partida = ?");
            $check->execute([$id_programa, $id_partida]);
            if ($check->fetch()) {
                throw new Exception("Esta partida ya está agregada al programa.");
            }

            $pdo->prepare("INSERT INTO programa_partidas (id_programa, id_partida, monto_asignado) VALUES (?, ?, ?)")
                ->execute([$id_programa, $id_partida, $monto_partida]);
            setFlashMessage('success', 'Partida agregada correctamente');

        } elseif ($_POST['accion_partida'] === 'eliminar') {
            $id_rel = (int) $_POST['id_relacion'];
            $pdo->prepare("DELETE FROM programa_partidas WHERE id = ?")->execute([$id_rel]);
            setFlashMessage('info', 'Partida eliminada del programa');

        } elseif ($_POST['accion_partida'] === 'actualizar_monto') { // Nuevo
            $id_rel = (int) $_POST['id_relacion'];
            $nuevo_monto = (float) str_replace(',', '', $_POST['nuevo_monto']);
            $pdo->prepare("UPDATE programa_partidas SET monto_asignado = ? WHERE id = ?")->execute([$nuevo_monto, $id_rel]);
            setFlashMessage('success', 'Monto actualizado');
        }

    } catch (Exception $e) {
        setFlashMessage('error', $e->getMessage());
    }
    // Redireccionar a la misma página para evitar reenvío de formulario
    redirect("modulos/recursos-financieros/poa-form.php?id=$id_programa");
}

// --- Procesar Guardado del POA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['accion_partida'])) {
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
            setFlashMessage('success', 'Programa Anual creado correctamente. Ahora configura las partidas presupuestales.');
            $id_programa = $pdo->lastInsertId(); // Get ID for redirect
            redirect("modulos/recursos-financieros/poa-form.php?id=$id_programa");
        }
        redirect('modulos/recursos-financieros/poas.php');
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

            <?php if ($is_editing): ?>
                <!-- SECCIÓN DE PARTIDAS PRESUPUESTALES -->
                <!-- SECCIÓN DE PARTIDAS PRESUPUESTALES -->
                <div class="card-body p-4 border-top" style="background: rgba(0,0,0,0.1);">
                    <h5 class="fw-bold mb-3"><i class="fas fa-coins me-2 text-success"></i> Desglose Presupuestal</h5>

                    <!-- Resumen de Asignación -->
                    <?php
                    $total_asignado = 0;
                    foreach ($partidas_asignadas as $pa)
                        $total_asignado += $pa['monto_asignado'];
                    $porcentaje = ($programa['monto_autorizado'] > 0) ? ($total_asignado / $programa['monto_autorizado']) * 100 : 0;
                    $color_bar = $porcentaje > 100 ? 'bg-danger' : 'bg-success';
                    ?>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Asignado: <strong>$<?= number_format($total_asignado, 2) ?></strong></span>
                            <span>Total Autorizado:
                                <strong>$<?= number_format($programa['monto_autorizado'], 2) ?></strong></span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar <?= $color_bar ?>" role="progressbar"
                                style="width: <?= min($porcentaje, 100) ?>%"></div>
                        </div>
                        <?php if ($porcentaje > 100): ?>
                            <div class="text-danger small mt-1"><i class="fas fa-exclamation-triangle"></i> Has excedido el
                                monto autorizado.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Formulario para agregar partida -->
                    <div class="row g-2 align-items-end mb-4">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Partida Presupuestal</label>
                            <select id="select_partida" class="form-select">
                                <option value="">Seleccione una partida...</option>
                                <?php foreach ($partidas_catalogo as $cat): ?>
                                    <option value="<?= $cat['id_partida'] ?>"><?= $cat['clave'] ?> - <?= e($cat['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Monto a Asignar</label>
                            <input type="text" id="monto_partida_new" class="form-control text-end" placeholder="0.00"
                                oninput="formatCurrency(this)">
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-success w-100" onclick="agregarPartida()">
                                <i class="fas fa-plus-circle"></i> Agregar
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de Partidas Asignadas -->
                    <div class="table-responsive rounded border border-secondary" style="background: transparent;">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr class="text-muted border-bottom border-secondary">
                                <tr>
                                    <th class="ps-3">Clave</th>
                                    <th>Descripción</th>
                                    <th class="text-end">Monto Asignado</th>
                                    <th class="text-end pe-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($partidas_asignadas)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted">No hay partidas asignadas a este
                                            programa.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($partidas_asignadas as $p): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold text-primary"><?= $p['clave'] ?></td>
                                            <td class="text-white"><?= e($p['nombre']) ?></td>
                                            <td class="text-end fw-bold text-success">
                                                $<?= number_format($p['monto_asignado'], 2) ?>
                                            </td>
                                            <td class="text-end pe-3">
                                                <button type="button" class="btn btn-sm btn-outline-info me-1"
                                                    onclick="editarPartida(<?= $p['id'] ?>, '<?= $p['monto_asignado'] ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="eliminarPartida(<?= $p['id'] ?>)">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card-footer p-4 border-top d-flex justify-content-end gap-3">
                <a href="poas.php" class="btn btn-secondary px-4">Cancelar</a>
                <button type="submit" class="btn btn-primary px-5"><i class="fas fa-save me-2"></i> Guardar
                    Programa</button>
            </div>
        </div>
    </form>

    <!-- Form oculto para acciones de partidas -->
    <form id="formPartidas" method="POST">
        <input type="hidden" name="accion_partida" id="accion_partida">
        <input type="hidden" name="id_partida" id="input_id_partida">
        <input type="hidden" name="monto_partida" id="input_monto_partida">
        <input type="hidden" name="id_relacion" id="input_id_relacion">
        <input type="hidden" name="nuevo_monto" id="input_nuevo_monto">
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

    function agregarPartida() {
        const idPartida = document.getElementById('select_partida').value;
        const monto = document.getElementById('monto_partida_new').value;

        if (!idPartida) {
            alert('Seleccione una partida valida');
            return;
        }
        if (!monto) {
            alert('Ingrese un monto');
            return;
        }

        document.getElementById('accion_partida').value = 'agregar';
        document.getElementById('input_id_partida').value = idPartida;
        document.getElementById('input_monto_partida').value = monto;
        document.getElementById('formPartidas').submit();
    }

    function eliminarPartida(idRelacion) {
        if (confirm('¿Está seguro de quitar esta partida del programa?')) {
            document.getElementById('accion_partida').value = 'eliminar';
            document.getElementById('input_id_relacion').value = idRelacion;
            document.getElementById('formPartidas').submit();
        }
    }

    function editarPartida(idRelacion, montoActual) {
        const nuevoMonto = prompt("Ingrese el nuevo monto para esta partida:", montoActual);
        if (nuevoMonto !== null && nuevoMonto.trim() !== "") {
            document.getElementById('accion_partida').value = 'actualizar_monto';
            document.getElementById('input_id_relacion').value = idRelacion;
            document.getElementById('input_nuevo_monto').value = nuevoMonto;
            document.getElementById('formPartidas').submit();
        }
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>