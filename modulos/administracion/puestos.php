<?php
/**
 * Módulo: Gestión de Puestos de Trabajo
 * Ubicación: /modulos/administracion/puestos.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// ID del módulo de Puestos (verificado en BD)
define('MODULO_ID', 33);

$pdo = getConnection();
$user = getCurrentUser();

// Obtener permisos
$permisos_user = getUserPermissions(MODULO_ID);
$puedeVer = in_array('ver', $permisos_user);
$puedeCrear = in_array('crear', $permisos_user);
$puedeEditar = in_array('editar', $permisos_user);
$puedeEliminar = in_array('eliminar', $permisos_user); // Usamos 'eliminar' para desactivar

if (!$puedeVer) {
    setFlashMessage('error', 'No tienes permiso para acceder al catálogo de puestos.');
    redirect('/index.php');
}

// --- LÓGICA CRUD ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // CREAR
    if ($accion === 'crear' && $puedeCrear) {
        $nombre = sanitize($_POST['nombre_puesto'] ?? '');
        $descripcion = sanitize($_POST['descripcion'] ?? '');

        if (!empty($nombre)) {
            // Verificar duplicados
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM puestos WHERE nombre_puesto = ? AND estado = 1");
            $stmt->execute([$nombre]);
            if ($stmt->fetchColumn() > 0) {
                setFlashMessage('warning', 'Ya existe un puesto con ese nombre.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO puestos (nombre_puesto, descripcion, estado, fecha_creacion) VALUES (?, ?, 1, NOW())");
                if ($stmt->execute([$nombre, $descripcion])) {
                    setFlashMessage('success', 'Puesto creado correctamente.');
                } else {
                    setFlashMessage('error', 'Error al crear el puesto.');
                }
            }
        } else {
            setFlashMessage('warning', 'El nombre del puesto es obligatorio.');
        }
    }

    // EDITAR
    if ($accion === 'editar' && $puedeEditar) {
        $id = (int) $_POST['id'];
        $nombre = sanitize($_POST['nombre_puesto'] ?? '');
        $descripcion = sanitize($_POST['descripcion'] ?? '');

        if ($id > 0 && !empty($nombre)) {
            $stmt = $pdo->prepare("UPDATE puestos SET nombre_puesto = ?, descripcion = ? WHERE id = ?");
            if ($stmt->execute([$nombre, $descripcion, $id])) {
                setFlashMessage('success', 'Puesto actualizado correctamente.');
            } else {
                setFlashMessage('error', 'Error al actualizar.');
            }
        }
    }

    // ELIMINAR (Desactivar)
    if ($accion === 'eliminar' && $puedeEliminar) {
        $id = (int) $_POST['id'];
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE puestos SET estado = 0 WHERE id = ?");
            if ($stmt->execute([$id])) {
                setFlashMessage('success', 'Puesto desactivado correctamente.');
            } else {
                setFlashMessage('error', 'Error al procesar la baja.');
            }
        }
    }

    // ACTIVAR
    if ($accion === 'activar' && $puedeEditar) {
        $id = (int) $_POST['id'];
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE puestos SET estado = 1 WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Puesto reactivado.');
        }
    }

    redirect('/modulos/administracion/puestos.php');
}

// --- VISTA ---

$mostrarInactivos = isset($_GET['inactivos']);
$busqueda = sanitize($_GET['q'] ?? '');

$sql = "SELECT * FROM puestos WHERE 1=1";
$params = [];

if (!$mostrarInactivos) {
    $sql .= " AND estado = 1";
}

if ($busqueda) {
    $sql .= " AND (nombre_puesto LIKE ? OR descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$sql .= " ORDER BY nombre_puesto ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$puestos = $stmt->fetchAll();

// Para modal editar
$puestoEditar = null;
if (isset($_GET['editar']) && $puedeEditar) {
    $stmt = $pdo->prepare("SELECT * FROM puestos WHERE id = ?");
    $stmt->execute([(int) $_GET['editar']]);
    $puestoEditar = $stmt->fetch();
}

?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-briefcase" style="color: var(--accent-orange);"></i> Gestión de Puestos</h1>
            <p class="page-description">Catálogo de puestos de trabajo</p>
        </div>
        <div class="d-flex gap-2">
            <a href="?<?= $mostrarInactivos ? '' : 'inactivos=1' ?>" class="btn btn-secondary">
                <i class="fas fa-<?= $mostrarInactivos ? 'eye' : 'eye-slash' ?>"></i> <?= $mostrarInactivos ? 'Ocultar Inactivos' : 'Mostrar Inactivos' ?>
            </a>
            <?php if ($puedeCrear): ?>
                <button onclick="document.getElementById('modalCrear').style.display='flex'" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Puesto
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?= renderFlashMessage() ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="d-flex gap-2">
                <?php if ($mostrarInactivos): ?><input type="hidden" name="inactivos" value="1"><?php endif; ?>
                <input type="text" name="q" class="form-control" placeholder="Buscar por nombre..." value="<?= e($busqueda) ?>">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Buscar</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre del Puesto</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <?php if ($puedeEditar): ?><th>Acciones</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($puestos)): ?>
                            <tr><td colspan="4" class="text-center py-4">No hay registros encontrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($puestos as $p): ?>
                                <tr class="<?= $p['estado'] == 0 ? 'text-muted' : '' ?>" style="<?= $p['estado'] == 0 ? 'opacity: 0.6;' : '' ?>">
                                    <td class="fw-bold"><?= e($p['nombre_puesto']) ?></td>
                                    <td><?= e($p['descripcion']) ?></td>
                                    <td>
                                        <span class="badge <?= $p['estado'] == 1 ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $p['estado'] == 1 ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <?php if ($puedeEditar): ?>
                                        <td>
                                            <a href="?editar=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                            <?php if ($p['estado'] == 1 && $puedeEliminar): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Desactivar este puesto?');">
                                                    <input type="hidden" name="accion" value="eliminar">
                                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Desactivar"><i class="fas fa-ban"></i></button>
                                                </form>
                                            <?php elseif ($p['estado'] == 0): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="accion" value="activar">
                                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Reactivar"><i class="fas fa-check"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal Crear -->
<div id="modalCrear" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Nuevo Puesto</h3>
            <button class="modal-close" onclick="this.closest('.modal-overlay').style.display='none'">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="accion" value="crear">
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label class="form-label">Nombre del Puesto *</label>
                    <input type="text" name="nombre_puesto" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="this.closest('.modal-overlay').style.display='none'">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<?php if ($puestoEditar): ?>
<div id="modalEditar" class="modal-overlay" style="display: flex;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Editar Puesto</h3>
            <a href="?" class="modal-close">&times;</a>
        </div>
        <form method="POST">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" value="<?= $puestoEditar['id'] ?>">
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label class="form-label">Nombre del Puesto *</label>
                    <input type="text" name="nombre_puesto" class="form-control" required value="<?= e($puestoEditar['nombre_puesto']) ?>">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2"><?= e($puestoEditar['descripcion']) ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <a href="?" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Actualizar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5); z-index: 1050;
    display: flex; align-items: center; justify-content: center;
}
.modal-content {
    background: var(--bg-card, #fff); width: 100%; max-width: 500px;
    border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    display: flex; flex-direction: column;
}
.modal-header {
    padding: 1rem; border-bottom: 1px solid var(--border-color, #eee);
    display: flex; justify-content: space-between; align-items: center;
}
.modal-body { padding: 1rem; }
.modal-footer {
    padding: 1rem; border-top: 1px solid var(--border-color, #eee);
    display: flex; justify-content: flex-end; gap: 0.5rem;
}
.modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666; text-decoration: none;}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
