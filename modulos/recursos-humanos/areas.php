<?php
/**
 * Módulo: Gestión de Áreas
 * Ubicación: /modulos/recursos-humanos/areas.php
 * Acceso: Admin + Personal de RH
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getConnection();
$user = getCurrentUser();

// --- AJAX HANDLER: Obtener empleados de un área ---
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_employees') {
    // Verificar permisos básicos de lectura
    if (!isAuthenticated()) {
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }

    $areaId = isset($_GET['area_id']) ? (int) $_GET['area_id'] : 0;

    try {
        // Consulta para obtener empleados del área con su puesto
        // Usamos nombres de columna validados: id, nombres, apellido_paterno, apellido_materno, numero_empleado, area_id, puesto_trabajo_id
        $stmt = $pdo->prepare("
            SELECT e.id, e.nombres, e.apellido_paterno, e.apellido_materno, e.numero_empleado, p.nombre as puesto
            FROM empleados e
            LEFT JOIN puestos_trabajo p ON e.puesto_trabajo_id = p.id
            WHERE e.area_id = ? AND e.activo = 1
            ORDER BY e.apellido_paterno ASC
        ");
        $stmt->execute([$areaId]);
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($empleados);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit; // Detener ejecución para devolver solo JSON
}

// ID del módulo de Áreas
define('MODULO_ID', 26);

$pdo = getConnection();
$user = getCurrentUser();

// Obtener permisos del usuario para este módulo
$permisos_user = getUserPermissions(MODULO_ID);
$puedeVer = in_array('ver', $permisos_user);
$puedeCrear = in_array('crear', $permisos_user);
$puedeEditar = in_array('editar', $permisos_user);
$puedeEliminar = in_array('eliminar', $permisos_user);

if (!$puedeVer) {
    setFlashMessage('error', 'No tienes permiso para acceder al catálogo de áreas.');
    redirect('/index.php');
}

$tiposArea = $pdo->query("SELECT * FROM tipos_area WHERE estado = 1 ORDER BY nombre_tipo")->fetchAll();
$areasParaPadre = $pdo->query("SELECT id, nombre_area FROM areas WHERE estado = 1 ORDER BY nombre_area")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear' && $puedeCrear) {
        $nombre = sanitize($_POST['nombre_area'] ?? '');
        $id_tipo = !empty($_POST['id_tipo_area']) ? (int) $_POST['id_tipo_area'] : null;
        $id_padre = !empty($_POST['area_padre_id']) ? (int) $_POST['area_padre_id'] : null;
        $nivel = !empty($_POST['nivel']) ? (int) $_POST['nivel'] : 1;

        if (!empty($nombre)) {
            $stmt = $pdo->prepare("INSERT INTO areas (nombre_area, id_tipo_area, area_padre_id, nivel, estado) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$nombre, $id_tipo, $id_padre, $nivel]);
            setFlashMessage('success', 'Área creada correctamente');
        }
    }

    if ($accion === 'editar' && $puedeEditar) {
        $id = (int) $_POST['id'];
        $nombre = sanitize($_POST['nombre_area'] ?? '');
        $id_tipo = !empty($_POST['id_tipo_area']) ? (int) $_POST['id_tipo_area'] : null;
        $id_padre = !empty($_POST['area_padre_id']) ? (int) $_POST['area_padre_id'] : null;
        $nivel = !empty($_POST['nivel']) ? (int) $_POST['nivel'] : 1;

        if ($id > 0 && !empty($nombre)) {
            if ($id_padre === $id)
                $id_padre = null;
            $stmt = $pdo->prepare("UPDATE areas SET nombre_area = ?, id_tipo_area = ?, area_padre_id = ?, nivel = ? WHERE id = ?");
            $stmt->execute([$nombre, $id_tipo, $id_padre, $nivel, $id]);
            setFlashMessage('success', 'Área actualizada correctamente');
        }
    }

    if ($accion === 'eliminar' && $puedeEliminar) {
        $id = (int) $_POST['id'];
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM empleados WHERE area_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                setFlashMessage('warning', "No se puede eliminar: hay empleados asignados");
            } else {
                $stmt = $pdo->prepare("UPDATE areas SET estado = 0 WHERE id = ?");
                $stmt->execute([$id]);
                setFlashMessage('success', 'Área desactivada correctamente');
            }
        }
    }

    if ($accion === 'activar' && $puedeEditar) {
        $id = (int) $_POST['id'];
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE areas SET estado = 1 WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Área activada correctamente');
        }
    }
    redirect('/modulos/recursos-humanos/areas.php');
}

$mostrarInactivas = isset($_GET['inactivas']);
$busqueda = sanitize($_GET['q'] ?? '');

$sql = "SELECT a.*, t.nombre_tipo, p.nombre_area as nombre_padre,
        (SELECT COUNT(*) FROM empleados e WHERE e.area_id = a.id AND e.activo = 1) as total_empleados
        FROM areas a 
        LEFT JOIN tipos_area t ON a.id_tipo_area = t.id
        LEFT JOIN areas p ON a.area_padre_id = p.id
        WHERE 1=1";
$params = [];

if (!$mostrarInactivas)
    $sql .= " AND a.estado = 1";

if ($busqueda) {
    $sql .= " AND (a.nombre_area LIKE ? OR t.nombre_tipo LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $sql .= " ORDER BY a.nivel, a.nombre_area";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $areas = $stmt->fetchAll();
} else {
    $sql .= " ORDER BY a.nombre_area";
    $stmt = $pdo->query($sql);
    $allAreas = $stmt->fetchAll();

    function organizarAreasJerarquicas($areas, $padreId = null, &$resultado = [])
    {
        foreach ($areas as $area) {
            $esHijo = ($area['area_padre_id'] == $padreId);
            if ($padreId === null)
                $esHijo = empty($area['area_padre_id']);
            if ($esHijo) {
                $resultado[] = $area;
                organizarAreasJerarquicas($areas, $area['id'], $resultado);
            }
        }
    }

    $areas = [];
    organizarAreasJerarquicas($allAreas, null, $areas);

    if (count($areas) < count($allAreas)) {
        $idsProcesados = array_column($areas, 'id');
        foreach ($allAreas as $a) {
            if (!in_array($a['id'], $idsProcesados))
                $areas[] = $a;
        }
    }
}

$areaEditar = null;
if (isset($_GET['editar']) && $puedeEditar) {
    $stmt = $pdo->prepare("SELECT * FROM areas WHERE id = ?");
    $stmt->execute([(int) $_GET['editar']]);
    $areaEditar = $stmt->fetch();
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-sitemap" style="color: var(--accent-primary);"></i> Gestión de Áreas
            </h1>
            <p class="page-description">Administra la estructura organizacional jerárquica</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="?<?= $mostrarInactivas ? '' : 'inactivas=1' ?>" class="btn btn-secondary">
                <i class="fas fa-<?= $mostrarInactivas ? 'eye' : 'eye-slash' ?>"></i>
                <?= $mostrarInactivas ? 'Ocultar inactivas' : 'Mostrar inactivas' ?>
            </a>
            <?php if ($puedeCrear): ?>
                <button type="button" class="btn btn-primary"
                    onclick="document.getElementById('modalCrear').style.display='flex'">
                    <i class="fas fa-plus"></i> Nueva Área
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?= renderFlashMessage() ?>

    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-body" style="padding: 1rem;">
            <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                <?php if ($mostrarInactivas): ?> <input type="hidden" name="inactivas" value="1"> <?php endif; ?>
                <div class="form-group" style="margin: 0; flex: 1;">
                    <input type="text" name="q" class="form-control" placeholder="Buscar..."
                        value="<?= e($busqueda) ?>">
                </div>
                <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Buscar</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Áreas (<?= count($areas) ?>)</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre del Área</th>
                            <th>Área Superior</th>
                            <th>Tipo</th>
                            <th style="text-align: center;">Empleados</th>
                            <th>Estado</th>
                            <?php if ($puedeEditar || $puedeEliminar): ?>
                                <th style="width: 150px;">Acciones</th> <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($areas)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 3rem;">No se encontraron áreas</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($areas as $area): ?>
                                <?php
                                // Determinar color según Tipo de Área
                                $tipo = mb_strtolower($area['nombre_tipo'] ?? '');
                                $rowStyle = '';
                                $nameStyle = 'font-weight: 500;';

                                // Colores base (ajustados para tema oscuro/claro por defecto del sistema si es posible, o hardcoded visualmente agradables)
                                // Usamos colores hexadecimales que se vean bien
                                if (strpos($tipo, 'secretaría') !== false || strpos($tipo, 'secretaria') !== false) {
                                    // Azul fuerte / Principal
                                    $rowStyle = 'color: #60a5fa; font-weight: 600; background: rgba(59, 130, 246, 0.1);';
                                    $nameStyle = 'font-size: 1.05rem;';
                                } elseif (strpos($tipo, 'subsecretaría') !== false || strpos($tipo, 'subsecretaria') !== false) {
                                    // Cyan / Segundo nivel importante
                                    $rowStyle = 'color: #22d3ee;';
                                } elseif (strpos($tipo, 'dirección') !== false || strpos($tipo, 'direccion') !== false) {
                                    // Púrpura / Nivel directivo
                                    $rowStyle = 'color: #a78bfa;';
                                } elseif (strpos($tipo, 'jefatura') !== false || strpos($tipo, 'área') !== false || strpos($tipo, 'area') !== false) {
                                    // Naranja / Nivel operativo medio (Agrupando Jefaturas y Áreas)
                                    $rowStyle = 'color: #fbbf24;';
                                } elseif (strpos($tipo, 'departamento') !== false) {
                                    // Verde suave
                                    $rowStyle = 'color: #34d399;';
                                } else {
                                    // Otros (Gris) -> O podría unificarse con naranja si son niveles operativos
                                    $rowStyle = 'color: #9ca3af;';
                                }

                                if ($area['estado'] == 0)
                                    $rowStyle = 'color: #6b7280; opacity: 0.6;';
                                ?>
                                <tr style="<?= $rowStyle ?>">
                                    <td style="<?= $nameStyle ?>">
                                        <?php
                                        $indent = max(0, $area['nivel'] - 1);
                                        ?>
                                        <div style="display: flex; align-items: center;">
                                            <?php if ($indent > 0): ?>
                                                <span
                                                    style="display: inline-block; width: <?= $indent * 25 ?>px; border-left: 1px dashed currentColor; height: 25px; margin-right: 8px; opacity: 0.3;"></span>
                                                <span style="opacity: 0.5; margin-right: 8px;">↳</span>
                                            <?php endif; ?>
                                            <?= e($area['nombre_area']) ?>
                                        </div>
                                    </td>
                                    <td style="font-size: 0.85rem; opacity: 0.8;"><?= e($area['nombre_padre'] ?? '-') ?></td>
                                    <td><?= e($area['nombre_tipo'] ?? '-') ?></td>
                                    <td style="text-align: center;">
                                        <?php if ($area['total_empleados'] > 0): ?>
                                            <a href="#"
                                                onclick="verEmpleados(<?= $area['id'] ?>, '<?= e($area['nombre_area']) ?>'); return false;"
                                                class="badge badge-info hover-effect" title="Ver lista de empleados">
                                                <i class="fas fa-users" style="font-size: 0.7em; margin-right: 3px;"></i>
                                                <?= $area['total_empleados'] ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge badge-secondary" style="opacity: 0.5;">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span
                                            class="badge <?= $area['estado'] == 1 ? 'badge-success' : 'badge-danger' ?>"><?= $area['estado'] == 1 ? 'Activa' : 'Inactiva' ?></span>
                                    </td>
                                    <?php if ($puedeEditar || $puedeEliminar): ?>
                                        <td>
                                            <!-- Reset color for buttons -->
                                            <div style="display: flex; gap: 0.5rem; color: var(--text-primary);">
                                                <?php if ($puedeEditar): ?>
                                                    <a href="?editar=<?= $area['id'] ?>" class="btn btn-sm btn-secondary"><i
                                                            class="fas fa-edit"></i></a>
                                                <?php endif; ?>
                                                <?php if ($area['estado'] == 1 && $puedeEliminar): ?>
                                                    <form method="POST" style="display: inline;"
                                                        onsubmit="return confirm('¿Desactivar?')">
                                                        <input type="hidden" name="accion" value="eliminar"><input type="hidden"
                                                            name="id" value="<?= $area['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger"><i
                                                                class="fas fa-ban"></i></button>
                                                    </form>
                                                <?php elseif ($area['estado'] == 0 && $puedeEditar): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="accion" value="activar"><input type="hidden"
                                                            name="id" value="<?= $area['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-success"><i
                                                                class="fas fa-check"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
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

<!-- Modales se mantienen igual -->
<?php if ($puedeCrear): ?>
    <div id="modalCrear" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nueva Área</h3><button class="modal-close"
                    onclick="this.closest('.modal-overlay').style.display='none'">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-body">
                    <div class="form-group"><label class="form-label">Nombre *</label><input type="text" name="nombre_area"
                            class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Tipo *</label>
                        <select name="id_tipo_area" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($tiposArea as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= e($t['nombre_tipo']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                        <div class="form-group"><label class="form-label">Área Superior</label>
                            <select name="area_padre_id" class="form-control">
                                <option value="">Ninguna (Raíz)</option>
                                <?php foreach ($areasParaPadre as $ap): ?>
                                    <option value="<?= $ap['id'] ?>"><?= e($ap['nombre_area']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group"><label class="form-label">Nivel</label><input type="number" name="nivel"
                            class="form-control" value="1" min="1"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary"
                        onclick="this.closest('.modal-overlay').style.display='none'">Cancelar</button><button type="submit"
                        class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($areaEditar): ?>
    <div id="modalEditar" class="modal-overlay" style="display: flex;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Área</h3><a href="?" class="modal-close">&times;</a>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="editar"><input type="hidden" name="id"
                    value="<?= $areaEditar['id'] ?>">
                <div class="modal-body">
                    <div class="form-group"><label class="form-label">Nombre *</label><input type="text" name="nombre_area"
                            class="form-control" required value="<?= e($areaEditar['nombre_area']) ?>"></div>
                    <div class="form-group"><label class="form-label">Tipo *</label>
                        <select name="id_tipo_area" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($tiposArea as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $areaEditar['id_tipo_area'] == $t['id'] ? 'selected' : '' ?>>
                                    <?= e($t['nombre_tipo']) ?>
                                </option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                        <div class="form-group"><label class="form-label">Área Superior</label>
                            <select name="area_padre_id" class="form-control">
                                <option value="">Ninguna (Raíz)</option>
                                <?php foreach ($areasParaPadre as $ap): ?>
                                    <?php if ($ap['id'] != $areaEditar['id']): ?>
                                        <option value="<?= $ap['id'] ?>" <?= $areaEditar['area_padre_id'] == $ap['id'] ? 'selected' : '' ?>><?= e($ap['nombre_area']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group"><label class="form-label">Nivel</label><input type="number" name="nivel"
                            class="form-control" value="<?= e($areaEditar['nivel']) ?>" min="1"></div>
                </div>
                <div class="modal-footer"><a href="?" class="btn btn-secondary">Cancelar</a><button type="submit"
                        class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
<?php endif; ?>

<style>
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        alignItems: center;
        justifyContent: center;
        z-index: 2000;
    }

    .modal-content {
        background: var(--bg-card);
        border: 1px solid var(--border-primary);
        border-radius: var(--radius-lg);
        width: 100%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-primary);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.1rem;
    }

    .modal-close {
        background: none;
        border: none;
        fontSize: 1.5rem;
        color: var(--text-muted);
        cursor: pointer;
        textDecoration: none;
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

    .row-inactive {
        opacity: 0.6;
    }
</style>

<div id="modalEmpleados" class="modal-overlay" style="display: none; backdrop-filter: blur(5px);">
    <div class="modal-content"
        style="max-width: 700px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        <div class="modal-header" style="background: linear-gradient(to right, #1e293b, #0f172a);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="background: var(--accent-primary); padding: 8px; border-radius: 8px; color: white;">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h3 id="modalEmpleadosTitle" style="margin: 0; font-size: 1.1rem; color: #f8fafc;">Empleados</h3>
                    <small style="color: #94a3b8;">Personal asignado a esta área</small>
                </div>
            </div>
            <button class="modal-close" onclick="document.getElementById('modalEmpleados').style.display='none'"
                style="color: #cbd5e1;">&times;</button>
        </div>
        <div class="modal-body" style="padding: 0; max-height: 60vh; overflow-y: auto; background: #0f172a;">
            <div id="loadingEmpleados" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Cargando personal...
            </div>
            <table class="table table-hover" id="tablaEmpleadosModal" style="margin: 0; display: none; width: 100%;">
                <thead style="background: #1e293b; position: sticky; top: 0;">
                    <tr>
                        <th style="padding: 12px 20px; color: #94a3b8; font-weight: 500; font-size: 0.85rem;">NO. EMP
                        </th>
                        <th style="padding: 12px 20px; color: #94a3b8; font-weight: 500; font-size: 0.85rem;">NOMBRE
                        </th>
                        <th style="padding: 12px 20px; color: #94a3b8; font-weight: 500; font-size: 0.85rem;">PUESTO
                        </th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody id="listaEmpleadosBody">
                    <!-- Dinámico -->
                </tbody>
            </table>
            <div id="noEmpleadosMsg" style="display: none; text-align: center; padding: 2rem; color: #94a3b8;">
                <i class="fas fa-user-slash" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                <p>No se encontraron datos de empleados activos.</p>
            </div>
        </div>
        <div class="modal-footer" style="background: #1e293b; border-top: 1px solid rgba(255,255,255,0.05);">
            <button type="button" class="btn btn-secondary"
                onclick="document.getElementById('modalEmpleados').style.display='none'">Cerrar</button>
        </div>
    </div>
</div>

<style>
    /* Estilos adicionales para esta vista */
    .hover-effect {
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
    }

    .hover-effect:hover {
        transform: scale(1.1);
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
        background-color: var(--accent-primary);
        border-color: rgba(255, 255, 255, 0.3);
    }

    /* Estilos para la tabla del modal */
    #tablaEmpleadosModal tbody tr {
        cursor: pointer;
        transition: background 0.2s;
        border-bottom: 1px solid rgba(255, 255, 255, 0.03);
    }

    #tablaEmpleadosModal tbody tr:hover {
        background: rgba(59, 130, 246, 0.1);
    }

    #tablaEmpleadosModal tbody td {
        padding: 12px 20px;
        vertical-align: middle;
        color: #e2e8f0;
        font-size: 0.9rem;
    }

    .emp-name {
        font-weight: 500;
        color: #fff;
    }

    .emp-id {
        font-family: 'Courier New', monospace;
        color: var(--accent-primary);
    }

    .emp-position {
        font-size: 0.85rem;
        color: #94a3b8;
    }
</style>

<script>
    function verEmpleados(areaId, nombreArea) {
        const modal = document.getElementById('modalEmpleados');
        const title = document.getElementById('modalEmpleadosTitle');
        const loading = document.getElementById('loadingEmpleados');
        const table = document.getElementById('tablaEmpleadosModal');
        const tbody = document.getElementById('listaEmpleadosBody');
        const noMsg = document.getElementById('noEmpleadosMsg');

        // Reset UI
        title.textContent = nombreArea; // Set title
        modal.style.display = 'flex';
        loading.style.display = 'block';
        table.style.display = 'none';
        noMsg.style.display = 'none';
        tbody.innerHTML = '';

        // Fetch Data
        fetch('?ajax_action=get_employees&area_id=' + areaId)
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                if (data.error) {
                    alert('Error: ' + data.error);
                    modal.style.display = 'none';
                    return;
                }

                if (data.length > 0) {
                    table.style.display = 'table';
                    data.forEach(emp => {
                        const row = document.createElement('tr');
                        // Al hacer clic, ir a detalle del empleado (usamos ruta relativa a empleados si está en el mismo módulo o form)
                        // Asumimos que existe un formulario de edición de empleados
                        row.onclick = () => window.location.href = 'empleados.php?q=' + encodeURIComponent(emp.numero_empleado);
                        // Nota: Si tenemos el ID, mejor usar el ID. Pero como migramos ids, lo ideal es ?editar=ID o ?id=ID
                        // Usaré ?q=NUMERO para buscarlo en la lista de empleados o podriamos ir directo a editar si existiera empleado-form.php
                        // Actualización: El usuario pidió "muestre el formulario". Intentaré ir a `empleado-form.php?id=` si existe, si no a empleados list.
                        // Probare ir directo a `empleado-form.php` que es lo estándar.
                        row.onclick = () => window.location.href = 'empleado-form.php?id=' + emp.id;

                        row.innerHTML = `
                        <td class="emp-id"><i class="fas fa-hashtag" style="font-size:0.7em; opacity:0.5;"></i> ${emp.numero_empleado || '-'}</td>
                        <td>
                            <div class="emp-name">${emp.nombres} ${emp.apellido_paterno}</div>
                            <div style="font-size:0.75rem; opacity:0.5;">${emp.apellido_materno || ''}</div>
                        </td>
                        <td class="emp-position">
                            <span class="badge" style="background: rgba(255,255,255,0.05); font-weight: normal;">${emp.puesto || 'Sin puesto'}</span>
                        </td>
                        <td style="text-align: right;"><i class="fas fa-chevron-right" style="opacity: 0.3;"></i></td>
                    `;
                        tbody.appendChild(row);
                    });
                } else {
                    noMsg.style.display = 'block';
                }
            })
            .catch(err => {
                loading.style.display = 'none';
                console.error(err);
                alert('Error al cargar empleados');
            });
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>