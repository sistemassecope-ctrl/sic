<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
// Header is included by wrapper

// DB Connection
if (!class_exists('Database')) {
    require_once 'config/db.php';
}
$db = (new Database())->getConnection();

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'];

    // EJES
    if ($accion === 'guardar_eje') {
        $nombre = mb_strtoupper(trim($_POST['nombre']));
        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $db->prepare("UPDATE cat_ejes SET nombre_eje = ? WHERE id_eje = ?");
            $stmt->execute([$nombre, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO cat_ejes (nombre_eje) VALUES (?)");
            $stmt->execute([$nombre]);
        }
    } elseif ($accion === 'toggle_eje') {
        $db->prepare("UPDATE cat_ejes SET activo = ? WHERE id_eje = ?")->execute([$_POST['valor'], $_POST['id']]);
    }

    // OBJETIVOS
    elseif ($accion === 'guardar_objetivo') {
        $nombre = mb_strtoupper(trim($_POST['nombre']));
        $id_eje = $_POST['id_eje'];
        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $db->prepare("UPDATE cat_objetivos SET nombre_objetivo = ?, id_eje = ? WHERE id_objetivo = ?");
            $stmt->execute([$nombre, $id_eje, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO cat_objetivos (nombre_objetivo, id_eje) VALUES (?, ?)");
            $stmt->execute([$nombre, $id_eje]);
        }
    } elseif ($accion === 'toggle_objetivo') {
        $db->prepare("UPDATE cat_objetivos SET activo = ? WHERE id_objetivo = ?")->execute([$_POST['valor'], $_POST['id']]);
    }

    // PRIORIDADES
    elseif ($accion === 'guardar_prioridad') {
        $nombre = mb_strtoupper(trim($_POST['nombre']));
        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $db->prepare("UPDATE cat_prioridades SET nombre_prioridad = ? WHERE id_prioridad = ?");
            $stmt->execute([$nombre, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO cat_prioridades (nombre_prioridad) VALUES (?)");
            $stmt->execute([$nombre]);
        }
    } elseif ($accion === 'toggle_prioridad') {
        $db->prepare("UPDATE cat_prioridades SET activo = ? WHERE id_prioridad = ?")->execute([$_POST['valor'], $_POST['id']]);
    }

    // Redirect to avoid resubreception
    echo "<script>window.location.href = window.location.href;</script>";
    exit;
}

// --- Fetch Data ---
$ejes = $db->query("SELECT * FROM cat_ejes ORDER BY nombre_eje")->fetchAll(PDO::FETCH_ASSOC);
$prioridades = $db->query("SELECT * FROM cat_prioridades ORDER BY nombre_prioridad")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Objetivos with Eje Name
$sqlObj = "SELECT o.*, e.nombre_eje 
           FROM cat_objetivos o 
           LEFT JOIN cat_ejes e ON o.id_eje = e.id_eje 
           ORDER BY e.nombre_eje, o.nombre_objetivo";
$objetivos = $db->query($sqlObj)->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold"><i class="bi bi-list-check"></i> Catálogos del P.A.O.</h2>
        <a href="/pao/index.php?route=recursos_financieros/programas_operativos"
            class="btn btn-outline-secondary">Regresar</a>
    </div>

    <!-- Vars for JS -->
    <script>
        const ejesData = <?php echo json_encode($ejes); ?>;
    </script>

    <ul class="nav nav-tabs mb-3" id="catTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-ejes">Ejes Estratégicos</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-objetivos">Objetivos</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-prioridades">Prioridades</button>
        </li>
    </ul>

    <div class="tab-content">

        <!-- EJES -->
        <div class="tab-pane fade show active" id="tab-ejes">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between">
                    <h5 class="fw-bold text-secondary">Listado de Ejes</h5>
                    <button class="btn btn-primary btn-sm" onclick="modalEje()"><i class="bi bi-plus"></i> Nuevo
                        Eje</button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Nombre del Eje</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ejes as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?php echo htmlspecialchars($row['nombre_eje']); ?></td>
                                    <td class="text-center">
                                        <span
                                            class="badge bg-<?php echo $row['activo'] ? 'success' : 'secondary'; ?>"><?php echo $row['activo'] ? 'Activo' : 'Inactivo'; ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-link"
                                            onclick="modalEje(<?php echo $row['id_eje']; ?>, '<?php echo $row['nombre_eje']; ?>')"><i
                                                class="bi bi-pencil"></i></button>
                                        <button
                                            class="btn btn-sm btn-link text-<?php echo $row['activo'] ? 'danger' : 'success'; ?>"
                                            onclick="toggle('eje', <?php echo $row['id_eje']; ?>, <?php echo $row['activo'] ? 0 : 1; ?>)">
                                            <i
                                                class="bi bi-<?php echo $row['activo'] ? 'slash-circle' : 'check-circle'; ?>"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- OBJETIVOS -->
        <div class="tab-pane fade" id="tab-objetivos">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between">
                    <h5 class="fw-bold text-secondary">Listado de Objetivos</h5>
                    <button class="btn btn-primary btn-sm" onclick="modalObjetivo()"><i class="bi bi-plus"></i> Nuevo
                        Objetivo</button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Eje</th>
                                <th>Objetivo</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($objetivos as $row): ?>
                                <tr>
                                    <td class="ps-4 text-secondary small">
                                        <?php echo htmlspecialchars($row['nombre_eje']); ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['nombre_objetivo']); ?></td>
                                    <td class="text-center">
                                        <span
                                            class="badge bg-<?php echo $row['activo'] ? 'success' : 'secondary'; ?>"><?php echo $row['activo'] ? 'Activo' : 'Inactivo'; ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-link"
                                            onclick="modalObjetivo(<?php echo $row['id_objetivo']; ?>, '<?php echo $row['nombre_objetivo']; ?>', <?php echo $row['id_eje']; ?>)"><i
                                                class="bi bi-pencil"></i></button>
                                        <button
                                            class="btn btn-sm btn-link text-<?php echo $row['activo'] ? 'danger' : 'success'; ?>"
                                            onclick="toggle('objetivo', <?php echo $row['id_objetivo']; ?>, <?php echo $row['activo'] ? 0 : 1; ?>)">
                                            <i
                                                class="bi bi-<?php echo $row['activo'] ? 'slash-circle' : 'check-circle'; ?>"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PRIORIDADES -->
        <div class="tab-pane fade" id="tab-prioridades">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between">
                    <h5 class="fw-bold text-secondary">Listado de Prioridades</h5>
                    <button class="btn btn-primary btn-sm" onclick="modalPrioridad()"><i class="bi bi-plus"></i> Nueva
                        Prioridad</button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Nombre</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prioridades as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?php echo htmlspecialchars($row['nombre_prioridad']); ?></td>
                                    <td class="text-center">
                                        <span
                                            class="badge bg-<?php echo $row['activo'] ? 'success' : 'secondary'; ?>"><?php echo $row['activo'] ? 'Activo' : 'Inactivo'; ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-link"
                                            onclick="modalPrioridad(<?php echo $row['id_prioridad']; ?>, '<?php echo $row['nombre_prioridad']; ?>')"><i
                                                class="bi bi-pencil"></i></button>
                                        <button
                                            class="btn btn-sm btn-link text-<?php echo $row['activo'] ? 'danger' : 'success'; ?>"
                                            onclick="toggle('prioridad', <?php echo $row['id_prioridad']; ?>, <?php echo $row['activo'] ? 0 : 1; ?>)">
                                            <i
                                                class="bi bi-<?php echo $row['activo'] ? 'slash-circle' : 'check-circle'; ?>"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Forms Hidden -->
<form id="actionForm" method="POST" style="display:none;">
    <input type="hidden" name="accion" id="formAccion">
    <input type="hidden" name="id" id="formId">
    <input type="hidden" name="valor" id="formValor">
</form>

<!-- Modal Genérico -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="accion" id="modalAccion">
                <input type="hidden" name="id" id="modalId">

                <!-- Campo Eje (Solo visible para objetivos) -->
                <div class="mb-3" id="divSelectEje" style="display:none;">
                    <label class="form-label">Eje Estratégico</label>
                    <select name="id_eje" id="modalSelectEje" class="form-select">
                        <?php foreach ($ejes as $e): ?>
                            <option value="<?php echo $e['id_eje']; ?>"><?php echo htmlspecialchars($e['nombre_eje']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nombre / Descripción</label>
                    <input type="text" name="nombre" id="modalNombre" class="form-control text-uppercase" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modalEl = new bootstrap.Modal(document.getElementById('editModal'));

    function toggle(tipo, id, valor) {
        document.getElementById('formAccion').value = 'toggle_' + tipo;
        document.getElementById('formId').value = id;
        document.getElementById('formValor').value = valor;
        document.getElementById('actionForm').submit();
    }

    function modalEje(id = null, nombre = '') {
        configModal('eje', id, nombre);
        document.getElementById('divSelectEje').style.display = 'none';
        modalEl.show();
    }

    function modalPrioridad(id = null, nombre = '') {
        configModal('prioridad', id, nombre);
        document.getElementById('divSelectEje').style.display = 'none';
        modalEl.show();
    }

    function modalObjetivo(id = null, nombre = '', idEje = null) {
        configModal('objetivo', id, nombre);
        document.getElementById('divSelectEje').style.display = 'block';
        if (idEje) document.getElementById('modalSelectEje').value = idEje;
        modalEl.show();
    }

    function configModal(tipo, id, nombre) {
        document.getElementById('modalTitle').innerText = (id ? 'Editar ' : 'Nuevo ') + tipo.charAt(0).toUpperCase() + tipo.slice(1);
        document.getElementById('modalAccion').value = 'guardar_' + tipo;
        document.getElementById('modalId').value = id || '';
        document.getElementById('modalNombre').value = nombre;
    }
</script>
</script>