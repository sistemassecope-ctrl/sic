<?php
/**
 * PAO v2 - Gestión de Usuarios del Sistema
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

if (!isAdmin()) {
    setFlashMessage('error', 'No tienes permiso para acceder a esta sección');
    redirect('/index.php');
}

$pdo = getConnection();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_status'])) {
        $userId = (int)$_POST['user_id'];
        $newStatus = (int)$_POST['new_status'];
        
        $stmt = $pdo->prepare("UPDATE usuarios_sistema SET estado = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
        
        setFlashMessage('success', $newStatus ? 'Usuario activado' : 'Usuario desactivado');
        redirect('/admin/usuarios.php');
    }
    
    if (isset($_POST['reset_password'])) {
        $userId = (int)$_POST['user_id'];
        $newPassword = password_hash('password123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE usuarios_sistema SET contrasena = ?, intentos_fallidos = 0 WHERE id = ?");
        $stmt->execute([$newPassword, $userId]);
        
        setFlashMessage('success', 'Contraseña restablecida a: password123');
        redirect('/admin/usuarios.php');
    }
    
    if (isset($_POST['create_user'])) {
        $empleadoId = (int)$_POST['empleado_id'];
        $usuario = sanitize($_POST['usuario']);
        $tipo = (int)$_POST['tipo'];
        $password = password_hash('password123', PASSWORD_DEFAULT);
        
        // Verificar que el empleado no tenga usuario
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios_sistema WHERE id_empleado = ?");
        $stmt->execute([$empleadoId]);
        if ($stmt->fetchColumn() > 0) {
            setFlashMessage('error', 'Este empleado ya tiene un usuario asignado');
        } else {
            // Verificar que el nombre de usuario no exista
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios_sistema WHERE usuario = ?");
            $stmt->execute([$usuario]);
            if ($stmt->fetchColumn() > 0) {
                setFlashMessage('error', 'El nombre de usuario ya existe');
            } else {
                $stmt = $pdo->prepare("INSERT INTO usuarios_sistema (id_empleado, usuario, contrasena, tipo, estado) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$empleadoId, $usuario, $password, $tipo]);
                setFlashMessage('success', 'Usuario creado correctamente. Contraseña inicial: password123');
            }
        }
        redirect('/admin/usuarios.php');
    }
}

// Obtener usuarios
$usuarios = $pdo->query("
    SELECT 
        u.*,
        CONCAT(e.nombres, ' ', e.apellido_paterno, ' ', IFNULL(e.apellido_materno, '')) as nombre_completo,
        e.email,
        a.nombre_area,
        p.nombre as nombre_puesto
    FROM usuarios_sistema u
    INNER JOIN empleados e ON u.id_empleado = e.id
    INNER JOIN areas a ON e.area_id = a.id
    LEFT JOIN puestos_trabajo p ON e.puesto_trabajo_id = p.id
    ORDER BY u.tipo, e.nombres
")->fetchAll();

// Obtener empleados sin usuario
$empleadosSinUsuario = $pdo->query("
    SELECT e.id, CONCAT(e.nombres, ' ', e.apellido_paterno) as nombre_completo, a.nombre_area
    FROM empleados e
    INNER JOIN areas a ON e.area_id = a.id
    LEFT JOIN usuarios_sistema u ON e.id = u.id_empleado
    WHERE u.id IS NULL AND e.estado = 1
    ORDER BY e.nombres
")->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fas fa-users-cog" style="color: var(--accent-purple);"></i>
                Usuarios del Sistema
            </h1>
            <p class="page-description">Administra los usuarios que tienen acceso al sistema</p>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('modalNuevo').style.display='flex'">
            <i class="fas fa-plus"></i> Nuevo Usuario
        </button>
    </div>
    
    <?= renderFlashMessage() ?>
    
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Empleado</th>
                            <th>Área / Puesto</th>
                            <th>Tipo</th>
                            <th>Último Acceso</th>
                            <th>Estado</th>
                            <th style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr data-id="<?= $u['id'] ?>">
                            <td>
                                <strong>@<?= e($u['usuario']) ?></strong>
                                <?php if ($u['intentos_fallidos'] >= 3): ?>
                                    <span class="badge badge-warning" title="Intentos fallidos: <?= $u['intentos_fallidos'] ?>">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="width: 32px; height: 32px; background: var(--gradient-accent); 
                                                 border-radius: 50%; display: flex; align-items: center; 
                                                 justify-content: center; font-size: 0.7rem; color: white;">
                                        <?= strtoupper(substr($u['nombre_completo'], 0, 2)) ?>
                                    </span>
                                    <div>
                                        <div style="font-weight: 500;"><?= e($u['nombre_completo']) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 0.9rem;"><?= e($u['nombre_area']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($u['nombre_puesto']) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $u['tipo'] == 1 ? 'badge-info' : 'badge-success' ?>">
                                    <?= $u['tipo'] == 1 ? 'Administrador' : 'Usuario' ?>
                                </span>
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                <?= $u['ultimo_acceso'] ? formatDateTime($u['ultimo_acceso']) : 'Nunca' ?>
                            </td>
                            <td>
                                <span class="badge <?= $u['estado'] == 1 ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $u['estado'] == 1 ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="/admin/permisos.php?usuario=<?= $u['id'] ?>" 
                                       class="btn btn-sm btn-secondary" title="Configurar permisos">
                                        <i class="fas fa-key"></i>
                                    </a>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $u['estado'] == 1 ? 0 : 1 ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-sm btn-secondary" 
                                                title="<?= $u['estado'] == 1 ? 'Desactivar' : 'Activar' ?>">
                                            <i class="fas fa-<?= $u['estado'] == 1 ? 'ban' : 'check' ?>"></i>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('¿Restablecer contraseña a password123?')">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" name="reset_password" class="btn btn-sm btn-secondary" 
                                                title="Restablecer contraseña">
                                            <i class="fas fa-unlock-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal Nuevo Usuario -->
<div id="modalNuevo" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); 
     align-items: center; justify-content: center; z-index: 2000;">
    <div class="card" style="width: 100%; max-width: 500px; margin: 1rem;">
        <div class="card-header">
            <h3 class="card-title">Nuevo Usuario</h3>
            <button onclick="document.getElementById('modalNuevo').style.display='none'" 
                    style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.5rem;">
                &times;
            </button>
        </div>
        <form method="POST">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Empleado</label>
                    <select name="empleado_id" class="form-control" required>
                        <option value="">Seleccionar empleado...</option>
                        <?php foreach ($empleadosSinUsuario as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= e($emp['nombre_completo']) ?> (<?= e($emp['nombre_area']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nombre de Usuario</label>
                    <input type="text" name="usuario" class="form-control" required placeholder="Ej: jperez">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipo de Usuario</label>
                    <select name="tipo" class="form-control" required>
                        <option value="2">Usuario Normal</option>
                        <option value="1">Administrador</option>
                    </select>
                </div>
                
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 1rem;">
                    <i class="fas fa-info-circle"></i> 
                    La contraseña inicial será: <strong>password123</strong>
                </p>
            </div>
            <div class="card-footer" style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" 
                        onclick="document.getElementById('modalNuevo').style.display='none'">
                    Cancelar
                </button>
                <button type="submit" name="create_user" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Crear Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
