<?php
/**
 * Gestión de Permisos - Vista Jerárquica
 * Permite asignar permisos atómicos por módulo (con sub-módulos)
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// Solo administradores
if (!isAdmin()) {
    setFlashMessage('error', 'Acceso denegado');
    redirect('/index.php');
}

$pdo = getConnection();
$userId = isset($_GET['usuario']) ? (int)$_GET['usuario'] : null;

// Procesar formulario de permisos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId) {
    $permisos = $_POST['permisos'] ?? [];
    $areas = $_POST['areas'] ?? [];
    
    // Limpiar permisos existentes
    $stmt = $pdo->prepare("DELETE FROM usuario_modulo_permisos WHERE id_usuario = ?");
    $stmt->execute([$userId]);
    
    // Insertar nuevos permisos
    $stmtInsert = $pdo->prepare("INSERT INTO usuario_modulo_permisos (id_usuario, id_modulo, id_permiso) VALUES (?, ?, ?)");
    
    foreach ($permisos as $moduloId => $permisosModulo) {
        foreach ($permisosModulo as $permisoId => $value) {
            $stmtInsert->execute([$userId, $moduloId, $permisoId]);
        }
    }
    
    // Actualizar áreas
    $stmt = $pdo->prepare("DELETE FROM usuario_areas WHERE id_usuario = ?");
    $stmt->execute([$userId]);
    
    $stmtArea = $pdo->prepare("INSERT INTO usuario_areas (id_usuario, id_area) VALUES (?, ?)");
    foreach ($areas as $areaId) {
        $stmtArea->execute([$userId, $areaId]);
    }
    
    setFlashMessage('success', 'Permisos actualizados correctamente');
    redirect('/admin/permisos.php?usuario=' . $userId);
}

// Obtener usuarios
$usuarios = $pdo->query("
    SELECT u.*, CONCAT(e.nombre, ' ', e.apellido_paterno) as nombre_completo, a.nombre_area
    FROM usuarios_sistema u
    INNER JOIN empleados e ON u.id_empleado = e.id
    INNER JOIN areas a ON e.id_area = a.id
    WHERE u.estado = 1
    ORDER BY nombre_completo
")->fetchAll();

// Obtener módulos (estructura jerárquica)
$modulosRaw = $pdo->query("SELECT * FROM modulos WHERE estado = 1 ORDER BY orden")->fetchAll();

// Organizar módulos en árbol
$modulosTree = [];
$modulosHijos = [];

foreach ($modulosRaw as $mod) {
    if ($mod['id_padre'] === null) {
        $modulosTree[$mod['id']] = $mod;
        $modulosTree[$mod['id']]['children'] = [];
    } else {
        $modulosHijos[$mod['id_padre']][] = $mod;
    }
}

foreach ($modulosHijos as $parentId => $hijos) {
    if (isset($modulosTree[$parentId])) {
        $modulosTree[$parentId]['children'] = $hijos;
    }
}

// Obtener permisos
$permisos = $pdo->query("SELECT * FROM permisos ORDER BY id")->fetchAll();

// Obtener áreas
$areas = $pdo->query("SELECT * FROM areas WHERE estado = 1 ORDER BY nombre_area")->fetchAll();

// Si hay usuario seleccionado, obtener sus permisos y áreas
$permisosUsuario = [];
$areasUsuario = [];

if ($userId) {
    // Permisos
    $stmt = $pdo->prepare("SELECT id_modulo, id_permiso FROM usuario_modulo_permisos WHERE id_usuario = ?");
    $stmt->execute([$userId]);
    foreach ($stmt->fetchAll() as $row) {
        $permisosUsuario[$row['id_modulo']][$row['id_permiso']] = true;
    }
    
    // Áreas
    $stmt = $pdo->prepare("SELECT id_area FROM usuario_areas WHERE id_usuario = ?");
    $stmt->execute([$userId]);
    $areasUsuario = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Obtener datos del usuario seleccionado
$usuarioSeleccionado = null;
if ($userId) {
    foreach ($usuarios as $u) {
        if ($u['id'] == $userId) {
            $usuarioSeleccionado = $u;
            break;
        }
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fas fa-key" style="color: var(--accent-purple);"></i>
                Gestión de Permisos
            </h1>
            <p class="page-description">Asigna permisos atómicos por módulo y controla el acceso por áreas</p>
        </div>
    </div>
    
    <?= renderFlashMessage() ?>
    
    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 1.5rem;">
        <!-- Lista de usuarios -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users"></i> Usuarios
                </h3>
            </div>
            <div class="card-body" style="padding: 0.5rem;">
                <div style="max-height: 600px; overflow-y: auto;">
                    <?php foreach ($usuarios as $u): ?>
                        <a href="?usuario=<?= $u['id'] ?>" 
                           class="user-list-item <?= $userId == $u['id'] ? 'active' : '' ?>">
                            <div class="user-avatar-sm">
                                <?= strtoupper(substr($u['nombre_completo'], 0, 2)) ?>
                            </div>
                            <div class="user-info-sm">
                                <div class="user-name-sm"><?= e($u['nombre_completo']) ?></div>
                                <div class="user-meta-sm">
                                    @<?= e($u['usuario']) ?> · <?= e($u['nombre_area']) ?>
                                </div>
                            </div>
                            <?php if ($u['tipo'] == 1): ?>
                                <span class="badge badge-info" style="font-size: 0.65rem;">Admin</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Panel de permisos -->
        <div>
            <?php if ($usuarioSeleccionado): ?>
                <form method="POST">
                    <!-- Info del usuario -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <div class="card-body" style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 60px; height: 60px; background: var(--gradient-accent); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white;">
                                <?= strtoupper(substr($usuarioSeleccionado['nombre_completo'], 0, 2)) ?>
                            </div>
                            <div>
                                <h3 style="margin: 0;"><?= e($usuarioSeleccionado['nombre_completo']) ?></h3>
                                <p style="margin: 0; color: var(--text-secondary);">
                                    @<?= e($usuarioSeleccionado['usuario']) ?> · <?= e($usuarioSeleccionado['nombre_area']) ?>
                                    <?php if ($usuarioSeleccionado['tipo'] == 1): ?>
                                        <span class="badge badge-info" style="margin-left: 0.5rem;">Administrador</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Matriz de permisos jerárquica -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-cubes"></i> Permisos por Módulo
                            </h3>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <div class="permissions-matrix">
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="text-align: left; width: 250px;">Módulo</th>
                                            <?php foreach ($permisos as $p): ?>
                                                <th title="<?= e($p['descripcion'] ?? '') ?>">
                                                    <?= e($p['nombre_permiso']) ?>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($modulosTree as $modulo): ?>
                                            <?php $hasChildren = !empty($modulo['children']); ?>
                                            
                                            <!-- Módulo padre -->
                                            <tr class="module-parent <?= $hasChildren ? 'has-children' : '' ?>">
                                                <td class="module-name">
                                                    <i class="fas <?= e($modulo['icono'] ?? 'fa-cube') ?>" style="color: var(--accent-primary); margin-right: 0.5rem;"></i>
                                                    <strong><?= e($modulo['nombre_modulo']) ?></strong>
                                                </td>
                                                <?php if ($hasChildren): ?>
                                                    <?php foreach ($permisos as $p): ?>
                                                        <td style="background: var(--bg-tertiary);">—</td>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <?php foreach ($permisos as $p): ?>
                                                        <td>
                                                            <input type="checkbox" 
                                                                   name="permisos[<?= $modulo['id'] ?>][<?= $p['id'] ?>]" 
                                                                   value="1"
                                                                   class="permission-checkbox"
                                                                   <?= isset($permisosUsuario[$modulo['id']][$p['id']]) ? 'checked' : '' ?>>
                                                        </td>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tr>
                                            
                                            <!-- Sub-módulos hijos -->
                                            <?php if ($hasChildren): ?>
                                                <?php foreach ($modulo['children'] as $child): ?>
                                                    <tr class="module-child">
                                                        <td class="module-name" style="padding-left: 2.5rem;">
                                                            <i class="fas <?= e($child['icono'] ?? 'fa-circle') ?>" style="color: var(--text-muted); margin-right: 0.5rem; font-size: 0.75rem;"></i>
                                                            <?= e($child['nombre_modulo']) ?>
                                                        </td>
                                                        <?php foreach ($permisos as $p): ?>
                                                            <td>
                                                                <input type="checkbox" 
                                                                       name="permisos[<?= $child['id'] ?>][<?= $p['id'] ?>]" 
                                                                       value="1"
                                                                       class="permission-checkbox"
                                                                       <?= isset($permisosUsuario[$child['id']][$p['id']]) ? 'checked' : '' ?>>
                                                            </td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Áreas accesibles -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-building"></i> Áreas Accesibles
                            </h3>
                        </div>
                        <div class="card-body">
                            <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.9rem;">
                                <i class="fas fa-info-circle"></i>
                                El usuario solo podrá ver información de las áreas seleccionadas.
                            </p>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem;">
                                <?php foreach ($areas as $area): ?>
                                    <label class="area-checkbox">
                                        <input type="checkbox" 
                                               name="areas[]" 
                                               value="<?= $area['id'] ?>"
                                               <?= in_array($area['id'], $areasUsuario) ? 'checked' : '' ?>>
                                        <span class="area-checkbox-label">
                                            <i class="fas fa-building"></i>
                                            <?= e($area['nombre_area']) ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                        <a href="<?= url('/admin/permisos.php') ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Permisos
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 4rem;">
                        <i class="fas fa-user-shield" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                        <h3 style="color: var(--text-secondary);">Selecciona un usuario</h3>
                        <p style="color: var(--text-muted);">Elige un usuario de la lista para gestionar sus permisos y accesos</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
/* Estilos específicos para el gestor de permisos */
.user-list-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: var(--radius-md);
    color: var(--text-primary);
    transition: all var(--transition-fast);
    text-decoration: none;
}

.user-list-item:hover {
    background: var(--bg-hover);
}

.user-list-item.active {
    background: rgba(88, 166, 255, 0.15);
    border-left: 3px solid var(--accent-primary);
}

.user-avatar-sm {
    width: 36px;
    height: 36px;
    background: var(--gradient-primary);
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    color: white;
    font-weight: 600;
    flex-shrink: 0;
}

.user-info-sm {
    flex: 1;
    min-width: 0;
}

.user-name-sm {
    font-weight: 500;
    font-size: 0.875rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-meta-sm {
    font-size: 0.7rem;
    color: var(--text-muted);
}

.module-parent.has-children td:first-child {
    background: var(--bg-tertiary);
}

.module-child td:first-child {
    border-left: 3px solid var(--border-primary);
}

.area-checkbox {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.area-checkbox input {
    display: none;
}

.area-checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-md);
    transition: all var(--transition-fast);
    width: 100%;
}

.area-checkbox input:checked + .area-checkbox-label {
    background: rgba(88, 166, 255, 0.15);
    border-color: var(--accent-primary);
    color: var(--accent-primary);
}

.area-checkbox:hover .area-checkbox-label {
    border-color: var(--border-hover);
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
