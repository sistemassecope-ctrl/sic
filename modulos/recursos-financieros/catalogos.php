<?php
/**
 * Módulo: Catálogos del P.A.O.
 * Ubicación: /modulos/recursos-financieros/catalogos.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// ID del módulo de Catálogos (Financieros)
define('MODULO_ID', 55);

// Obtener permisos del usuario para este módulo
$permisos_user = getUserPermissions(MODULO_ID);
$puedeVer = in_array('ver', $permisos_user);
$puedeCrear = in_array('crear', $permisos_user);
$puedeEditar = in_array('editar', $permisos_user);
$puedeEliminar = in_array('eliminar', $permisos_user);

if (!$puedeVer) {
    setFlashMessage('error', 'No tienes permiso para acceder a los catálogos.');
    redirect('modulos/recursos-financieros/poas.php');
}

$pdo = getConnection();
$user = getCurrentUser();

// --- Manejo de Acciones (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'guardar_eje') {
            $nombre = mb_strtoupper(trim($_POST['nombre']));
            $id = $_POST['id'] ?? null;
            if ($id) {
                $pdo->prepare("UPDATE cat_ejes SET nombre_eje = ? WHERE id_eje = ?")->execute([$nombre, $id]);
            } else {
                $pdo->prepare("INSERT INTO cat_ejes (nombre_eje) VALUES (?)")->execute([$nombre]);
            }
            setFlashMessage('success', 'Eje guardado correctamente');
        } elseif ($accion === 'toggle_eje') {
            $pdo->prepare("UPDATE cat_ejes SET activo = ? WHERE id_eje = ?")->execute([(int) $_POST['valor'], (int) $_POST['id']]);
            setFlashMessage('info', 'Estatus de eje actualizado');
        } elseif ($accion === 'guardar_objetivo') {
            $nombre = mb_strtoupper(trim($_POST['nombre']));
            $id_eje = (int) $_POST['id_eje'];
            $id = $_POST['id'] ?? null;
            if ($id) {
                $pdo->prepare("UPDATE cat_objetivos SET nombre_objetivo = ?, id_eje = ? WHERE id_objetivo = ?")->execute([$nombre, $id_eje, $id]);
            } else {
                $pdo->prepare("INSERT INTO cat_objetivos (nombre_objetivo, id_eje) VALUES (?, ?)")->execute([$nombre, $id_eje]);
            }
            setFlashMessage('success', 'Objetivo guardado correctamente');
        } elseif ($accion === 'toggle_objetivo') {
            $pdo->prepare("UPDATE cat_objetivos SET activo = ? WHERE id_objetivo = ?")->execute([(int) $_POST['valor'], (int) $_POST['id']]);
            setFlashMessage('info', 'Estatus de objetivo actualizado');
        } elseif ($accion === 'guardar_prioridad') {
            $nombre = mb_strtoupper(trim($_POST['nombre']));
            $id = $_POST['id'] ?? null;
            if ($id) {
                $pdo->prepare("UPDATE cat_prioridades SET nombre_prioridad = ? WHERE id_prioridad = ?")->execute([$nombre, $id]);
            } else {
                $pdo->prepare("INSERT INTO cat_prioridades (nombre_prioridad) VALUES (?)")->execute([$nombre]);
            }
            setFlashMessage('success', 'Prioridad guardada correctamente');
        } elseif ($accion === 'toggle_prioridad') {
            $pdo->prepare("UPDATE cat_prioridades SET activo = ? WHERE id_prioridad = ?")->execute([(int) $_POST['valor'], (int) $_POST['id']]);
            setFlashMessage('info', 'Estatus de prioridad actualizado');
        } elseif ($accion === 'guardar_partida') {
            $nombre = mb_strtoupper(trim($_POST['nombre']));
            $clave = trim($_POST['clave']);
            $descripcion = trim($_POST['descripcion']);
            $id = $_POST['id'] ?? null;
            if ($id) {
                $pdo->prepare("UPDATE cat_partidas_presupuestales SET nombre = ?, clave = ?, descripcion = ? WHERE id_partida = ?")->execute([$nombre, $clave, $descripcion, $id]);
            } else {
                $pdo->prepare("INSERT INTO cat_partidas_presupuestales (nombre, clave, descripcion) VALUES (?, ?, ?)")->execute([$nombre, $clave, $descripcion]);
            }
            setFlashMessage('success', 'Partida guardada correctamente');
        } elseif ($accion === 'toggle_partida') {
            $pdo->prepare("UPDATE cat_partidas_presupuestales SET activo = ? WHERE id_partida = ?")->execute([(int) $_POST['valor'], (int) $_POST['id']]);
            setFlashMessage('info', 'Estatus de partida actualizado');
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'Error: ' . $e->getMessage());
    }

    redirect('modulos/recursos-financieros/catalogos.php');
}

// --- Obtener Datos ---
$ejes = $pdo->query("SELECT * FROM cat_ejes ORDER BY nombre_eje")->fetchAll();
$prioridades = $pdo->query("SELECT * FROM cat_prioridades ORDER BY nombre_prioridad")->fetchAll();
$partidas = $pdo->query("SELECT * FROM cat_partidas_presupuestales ORDER BY clave ASC")->fetchAll();
$objetivos = $pdo->query("
    SELECT o.*, e.nombre_eje 
    FROM cat_objetivos o 
    LEFT JOIN cat_ejes e ON o.id_eje = e.id_eje 
    ORDER BY e.nombre_eje, o.nombre_objetivo
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-list-ul text-primary"></i> Catálogos Financieros</h1>
            <p class="page-description">Administración de ejes, objetivos y prioridades del sistema</p>
        </div>
        <div class="page-actions">
            <a href="poas.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
    </div>

    <?= renderFlashMessage() ?>

    <div class="card tabs-container">
        <div class="card-header p-0 border-bottom">
            <div class="nav nav-tabs border-0" id="catTabs" role="tablist">
                <button class="nav-link active py-3 px-4" data-target="tab-ejes" role="tab"
                    onclick="switchTab(this)">Ejes Estratégicos</button>
                <button class="nav-link py-3 px-4" data-target="tab-objetivos" role="tab"
                    onclick="switchTab(this)">Objetivos</button>
                <button class="nav-link py-3 px-4" data-target="tab-prioridades" role="tab"
                    onclick="switchTab(this)">Prioridades</button>
                <button class="nav-link py-3 px-4" data-target="tab-partidas" role="tab"
                    onclick="switchTab(this)">Partidas Presupuestales</button>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- TAB: EJES -->
            <div class="tab-pane active" id="tab-ejes">
                <div class="p-4 bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Listado de Ejes</h5>
                    <?php if ($puedeCrear): ?>
                        <button class="btn btn-primary btn-sm" onclick="modalEje()"><i class="fas fa-plus"></i> Nuevo
                            Eje</button>
                    <?php endif; ?>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="ps-4">Nombre del Eje</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ejes as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bold">
                                        <?= e($row['nombre_eje']) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $row['activo'] ? 'badge-success' : 'badge-secondary' ?>">
                                            <?= $row['activo'] ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <?php if ($puedeEditar): ?>
                                                <button class="btn btn-sm btn-secondary"
                                                    onclick="modalEje(<?= $row['id_eje'] ?>, '<?= e($row['nombre_eje']) ?>')"><i
                                                        class="fas fa-edit"></i></button>
                                                <button class="btn btn-sm <?= $row['activo'] ? 'btn-danger' : 'btn-success' ?>"
                                                    onclick="toggle('eje', <?= $row['id_eje'] ?>, <?= $row['activo'] ? 0 : 1 ?>)">
                                                    <i class="fas <?= $row['activo'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: OBJETIVOS -->
            <div class="tab-pane d-none" id="tab-objetivos">
                <div class="p-4 bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Listado de Objetivos</h5>
                    <?php if ($puedeCrear): ?>
                        <button class="btn btn-primary btn-sm" onclick="modalObjetivo()"><i class="fas fa-plus"></i> Nuevo
                            Objetivo</button>
                    <?php endif; ?>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="ps-4">Eje Estratégico</th>
                                <th>Objetivo</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($objetivos as $row): ?>
                                <tr>
                                    <td class="ps-4 small text-muted">
                                        <?= e($row['nombre_eje']) ?>
                                    </td>
                                    <td class="fw-bold">
                                        <?= e($row['nombre_objetivo']) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $row['activo'] ? 'badge-success' : 'badge-secondary' ?>">
                                            <?= $row['activo'] ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <?php if ($puedeEditar): ?>
                                                <button class="btn btn-sm btn-secondary"
                                                    onclick="modalObjetivo(<?= $row['id_objetivo'] ?>, '<?= e($row['nombre_objetivo']) ?>', <?= $row['id_eje'] ?>)"><i
                                                        class="fas fa-edit"></i></button>
                                                <button class="btn btn-sm <?= $row['activo'] ? 'btn-danger' : 'btn-success' ?>"
                                                    onclick="toggle('objetivo', <?= $row['id_objetivo'] ?>, <?= $row['activo'] ? 0 : 1 ?>)">
                                                    <i class="fas <?= $row['activo'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: PRIORIDADES -->
            <div class="tab-pane d-none" id="tab-prioridades">
                <div class="p-4 bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Listado de Prioridades</h5>
                    <?php if ($puedeCrear): ?>
                        <button class="btn btn-primary btn-sm" onclick="modalPrioridad()"><i class="fas fa-plus"></i> Nueva
                            Prioridad</button>
                    <?php endif; ?>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="ps-4">Nombre</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prioridades as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bold">
                                        <?= e($row['nombre_prioridad']) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $row['activo'] ? 'badge-success' : 'badge-secondary' ?>">
                                            <?= $row['activo'] ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <?php if ($puedeEditar): ?>
                                                <button class="btn btn-sm btn-secondary"
                                                    onclick="modalPrioridad(<?= $row['id_prioridad'] ?>, '<?= e($row['nombre_prioridad']) ?>')"><i
                                                        class="fas fa-edit"></i></button>
                                                <button class="btn btn-sm <?= $row['activo'] ? 'btn-danger' : 'btn-success' ?>"
                                                    onclick="toggle('prioridad', <?= $row['id_prioridad'] ?>, <?= $row['activo'] ? 0 : 1 ?>)">
                                                    <i class="fas <?= $row['activo'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: PARTIDAS -->
        <div class="tab-pane d-none" id="tab-partidas">
            <div class="p-4 bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Partidas Presupuestales</h5>
                <?php if ($puedeCrear): ?>
                    <button class="btn btn-primary btn-sm" onclick="modalPartida()"><i class="fas fa-plus"></i> Nueva
                        Partida</button>
                <?php endif; ?>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="ps-4">Clave</th>
                            <th>Nombre / Descripción</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($partidas as $row): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary">
                                    <?= e($row['clave']) ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= e($row['nombre']) ?></div>
                                    <div class="small text-muted"><?= e($row['descripcion']) ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $row['activo'] ? 'badge-success' : 'badge-secondary' ?>">
                                        <?= $row['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <?php if ($puedeEditar): ?>
                                            <button class="btn btn-sm btn-secondary"
                                                onclick="modalPartida(<?= $row['id_partida'] ?>, '<?= e($row['nombre']) ?>', '<?= e($row['clave']) ?>', '<?= e($row['descripcion']) ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm <?= $row['activo'] ? 'btn-danger' : 'btn-success' ?>"
                                                onclick="toggle('partida', <?= $row['id_partida'] ?>, <?= $row['activo'] ? 0 : 1 ?>)">
                                                <i class="fas <?= $row['activo'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
</main>

<!-- Forms Hidden -->
<form id="actionForm" method="POST" style="display:none;">
    <input type="hidden" name="accion" id="formAccion">
    <input type="hidden" name="id" id="formId">
    <input type="hidden" name="valor" id="formValor">
</form>

<!-- Modal Genérico -->
<div id="editModal" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 id="modalTitle">Registro</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="accion" id="modalAccion">
                <input type="hidden" name="id" id="modalId">

                <div class="form-group" id="divInputClave" style="display:none;">
                    <label class="form-label">Clave Presupuestal</label>
                    <input type="text" name="clave" id="modalClave" class="form-control fw-bold" placeholder="Ej: 1000">
                </div>

                <div class="form-group" id="divSelectEje" style="display:none;">
                    <label class="form-label">Eje Estratégico</label>
                    <select name="id_eje" id="modalSelectEje" class="form-control">
                        <?php foreach ($ejes as $e): ?>
                            <option value="<?= $e['id_eje'] ?>">
                                <?= e($e['nombre_eje']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre / Descripción</label>
                    <input type="text" name="nombre" id="modalNombre" class="form-control text-uppercase" required>
                </div>

                <div class="form-group mt-3" id="divInputDesc" style="display:none;">
                    <label class="form-label">Descripción Detallada</label>
                    <textarea name="descripcion" id="modalDescripcion" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<style>
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        backdrop-filter: blur(4px);
    }

    .modal-content {
        background: var(--bg-card);
        border: 1px solid var(--border-primary);
        border-radius: var(--radius-lg);
        width: 100%;
        box-shadow: var(--shadow-lg);
    }

    .modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-primary);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--text-muted);
        cursor: pointer;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--border-primary);
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .nav-tabs .nav-link {
        background: transparent;
        color: var(--text-muted);
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
    }

    .nav-tabs .nav-link.active {
        border-bottom-color: var(--accent-primary);
        color: var(--accent-primary);
        font-weight: 600;
    }
</style>

<script>
    function switchTab(btn) {
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('d-none'));
        btn.classList.add('active');
        document.getElementById(btn.getAttribute('data-target')).classList.remove('d-none');
    }

    function toggle(tipo, id, valor) {
        document.getElementById('formAccion').value = 'toggle_' + tipo;
        document.getElementById('formId').value = id;
        document.getElementById('formValor').value = valor;
        document.getElementById('actionForm').submit();
    }

    function modalEje(id = null, nombre = '') {
        configModal('eje', id, nombre);
        document.getElementById('divSelectEje').style.display = 'none';
        document.getElementById('editModal').style.display = 'flex';
    }

    function modalPrioridad(id = null, nombre = '') {
        configModal('prioridad', id, nombre);
        document.getElementById('divSelectEje').style.display = 'none';
        document.getElementById('divInputClave').style.display = 'none';
        document.getElementById('divInputDesc').style.display = 'none';
        document.getElementById('editModal').style.display = 'flex';
    }

    function modalPartida(id = null, nombre = '', clave = '', desc = '') {
        configModal('partida', id, nombre);
        document.getElementById('divSelectEje').style.display = 'none';
        document.getElementById('divInputClave').style.display = 'block';
        document.getElementById('divInputDesc').style.display = 'block';

        document.getElementById('modalClave').value = clave;
        document.getElementById('modalDescripcion').value = desc;

        document.getElementById('editModal').style.display = 'flex';
    }

    function modalObjetivo(id = null, nombre = '', idEje = null) {
        configModal('objetivo', id, nombre);
        document.getElementById('divSelectEje').style.display = 'block';
        if (idEje) document.getElementById('modalSelectEje').value = idEje;
        document.getElementById('editModal').style.display = 'flex';
    }

    function configModal(tipo, id, nombre) {
        document.getElementById('modalTitle').innerText = (id ? 'Editar ' : 'Nuevo ') + tipo.charAt(0).toUpperCase() + tipo.slice(1);
        document.getElementById('modalAccion').value = 'guardar_' + tipo;
        document.getElementById('modalId').value = id || '';
        document.getElementById('modalNombre').value = nombre;
    }

    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>