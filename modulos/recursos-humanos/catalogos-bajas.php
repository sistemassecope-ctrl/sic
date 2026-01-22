<?php
/**
 * Módulo: Catálogos de Bajas y Desvinculación
 * Ubicación: /modulos/recursos-humanos/catalogos-bajas.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getConnection();
$user = getCurrentUser();

if (!isAdmin()) {
    setFlashMessage('error', 'No tienes permiso para acceder a la gestión de catálogos');
    redirect('/index.php');
}

// --- Procesar Acciones ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $tabla = $_POST['target_table'] === 'docs' ? 'cat_tipos_documento_baja' : 'cat_tipos_baja';
    $label = $_POST['target_table'] === 'docs' ? 'Documento' : 'Tipo de Baja';

    if ($accion === 'crear') {
        $nombre = sanitize($_POST['nombre'] ?? '');
        if (!empty($nombre)) {
            $stmt = $pdo->prepare("INSERT INTO $tabla (nombre) VALUES (?)");
            $stmt->execute([$nombre]);
            setFlashMessage('success', "$label guardado correctamente");
        }
    }

    if ($accion === 'editar') {
        $id = (int) $_POST['id'];
        $nombre = sanitize($_POST['nombre'] ?? '');
        if ($id > 0 && !empty($nombre)) {
            $stmt = $pdo->prepare("UPDATE $tabla SET nombre = ? WHERE id = ?");
            $stmt->execute([$nombre, $id]);
            setFlashMessage('success', "$label actualizado correctamente");
        }
    }

    if ($accion === 'toggle_status') {
        $id = (int) $_POST['id'];
        $nuevoEstado = (int) $_POST['estado'];
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE $tabla SET activo = ? WHERE id = ?");
            $stmt->execute([$nuevoEstado, $id]);
            setFlashMessage('success', "Estado actualizado");
        }
    }

    redirect('/modulos/recursos-humanos/catalogos-bajas.php');
}

// Fetch Items
$tiposBaja = $pdo->query("SELECT * FROM cat_tipos_baja ORDER BY nombre ASC")->fetchAll();
$tiposDoc = $pdo->query("SELECT * FROM cat_tipos_documento_baja ORDER BY nombre ASC")->fetchAll();

?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-list-ul text-primary"></i> Catálogos de Bajas</h1>
            <p class="page-description">Gestiona las opciones disponibles para el proceso de desvinculación</p>
        </div>
        <a href="empleados.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Directorio
        </a>
    </div>

    <?= renderFlashMessage() ?>

    <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">

        <!-- Catálogo: Tipos de Baja -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title"><i class="fas fa-user-slash text-danger me-2"></i> Tipos de Baja</h3>
                <button class="btn btn-sm btn-primary" onclick="openModal('crear', 'baja')">
                    <i class="fas fa-plus"></i> Agregar
                </button>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Opción</th>
                            <th style="width: 100px;">Estatus</th>
                            <th style="width: 120px;" class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tiposBaja as $t): ?>
                            <tr>
                                <td class="ps-4 fw-medium text-dark">
                                    <?= e($t['nombre']) ?>
                                </td>
                                <td>
                                    <span
                                        class="badge <?= $t['activo'] ? 'bg-success text-white' : 'bg-secondary text-white' ?> rounded-pill"
                                        style="font-size: 0.7rem;">
                                        <?= $t['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-secondary"
                                        onclick="openModal('editar', 'baja', <?= $t['id'] ?>, '<?= e($t['nombre']) ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="accion" value="toggle_status">
                                        <input type="hidden" name="target_table" value="baja">
                                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                        <input type="hidden" name="estado" value="<?= $t['activo'] ? 0 : 1 ?>">
                                        <button type="submit"
                                            class="btn btn-sm <?= $t['activo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                            title="<?= $t['activo'] ? 'Desactivar' : 'Activar' ?>">
                                            <i class="fas fa-<?= $t['activo'] ? 'ban' : 'check' ?>"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Catálogo: Tipos de Documento -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title"><i class="fas fa-file-alt text-info me-2"></i> Documentos de Sustento</h3>
                <button class="btn btn-sm btn-primary" onclick="openModal('crear', 'docs')">
                    <i class="fas fa-plus"></i> Agregar
                </button>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Opción</th>
                            <th style="width: 100px;">Estatus</th>
                            <th style="width: 120px;" class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tiposDoc as $t): ?>
                            <tr>
                                <td class="ps-4 fw-medium text-dark">
                                    <?= e($t['nombre']) ?>
                                </td>
                                <td>
                                    <span
                                        class="badge <?= $t['activo'] ? 'bg-success text-white' : 'bg-secondary text-white' ?> rounded-pill"
                                        style="font-size: 0.7rem;">
                                        <?= $t['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-secondary"
                                        onclick="openModal('editar', 'docs', <?= $t['id'] ?>, '<?= e($t['nombre']) ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="accion" value="toggle_status">
                                        <input type="hidden" name="target_table" value="docs">
                                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                        <input type="hidden" name="estado" value="<?= $t['activo'] ? 0 : 1 ?>">
                                        <button type="submit"
                                            class="btn btn-sm <?= $t['activo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                            title="<?= $t['activo'] ? 'Desactivar' : 'Activar' ?>">
                                            <i class="fas fa-<?= $t['activo'] ? 'ban' : 'check' ?>"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<!-- Modal Genérico -->
<div id="modalCatalogo" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Título del Modal</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" id="formCatalogo">
            <input type="hidden" name="accion" id="formAccion" value="crear">
            <input type="hidden" name="target_table" id="formTarget" value="baja">
            <input type="hidden" name="id" id="formId" value="">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre de la Opción *</label>
                    <input type="text" name="nombre" id="formNombre" class="form-control" required
                        placeholder="Ej: Renuncia, Oficio, etc.">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
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
        max-width: 450px;
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
</style>

<script>
    function openModal(accion, target, id = '', nombre = '') {
        const modal = document.getElementById('modalCatalogo');
        const title = document.getElementById('modalTitle');

        document.getElementById('formAccion').value = accion;
        document.getElementById('formTarget').value = target;
        document.getElementById('formId').value = id;
        document.getElementById('formNombre').value = nombre;

        const entityLabel = target === 'docs' ? 'Documento' : 'Tipo de Baja';
        title.innerHTML = (accion === 'crear' ? '<i class="fas fa-plus text-primary me-2"></i> Nuevo ' : '<i class="fas fa-edit text-info me-2"></i> Editar ') + entityLabel;

        modal.style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('modalCatalogo').style.display = 'none';
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>