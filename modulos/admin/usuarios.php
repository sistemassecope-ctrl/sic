<?php
require_once '../../config.php';
require_once '../../includes/functions.php';
require_once '../../includes/user_sync.php';
require_once '../../includes/auth.php';

$pageTitle = 'Usuarios del Sistema - SIC';
$breadcrumb = [
    ['url' => '../../modulos/rh/empleados.php', 'text' => 'Inicio'],
    ['url' => 'usuarios.php', 'text' => 'Usuarios del Sistema']
];

// Verificar permisos
requireModuleAccess('usuarios.php', 'leer');

$pdo = conectarDB();

// Sincronizar empleados activos con usuarios del sistema
$syncSummary = sincronizarEmpleadosUsuarios($pdo, [
    'autor_id' => $_SESSION['user_id'] ?? null,
]);

if (!empty($syncSummary['nuevos'])) {
    $_SESSION['sync_nuevos_usuarios'] = $syncSummary['nuevos'];
}

if (!empty($syncSummary['errores'])) {
    $_SESSION['sync_errores_usuarios'] = $syncSummary['errores'];
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'agregar':
                if (canAccessModule('usuarios.php', 'escribir')) {
                    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) { $error = 'Sesión expirada. Recarga la página.'; break; }
                    $username = strtolower(trim($_POST['username'] ?? ''));
                    $email = sanitizeInput($_POST['email']);
                    $nivel_usuario_id = $_POST['nivel_usuario_id'];
                    $password = $_POST['password'];
                    
                    if ($username === '') {
                        $error = 'El nombre de usuario es obligatorio.';
                        break;
                    }

                    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
                        $error = 'El nombre de usuario solo puede contener letras, números, puntos, guiones y guiones bajos.';
                        break;
                    }

                    // Validar username único
                    $stmt = $pdo->prepare("SELECT id FROM usuarios_sistema WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $error = 'El nombre de usuario ya está registrado en el sistema.';
                        break;
                    }

                    // Validar email único
                    $stmt = $pdo->prepare("SELECT id FROM usuarios_sistema WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $error = 'El email ya está registrado en el sistema.';
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);

                        // Inserción tolerante a columnas faltantes
                        $cols = $pdo->query("SHOW COLUMNS FROM usuarios_sistema")->fetchAll(PDO::FETCH_COLUMN);
                        if (!in_array('username', $cols, true)) {
                            asegurarColumnasUsuarios($pdo);
                            $cols = $pdo->query("SHOW COLUMNS FROM usuarios_sistema")->fetchAll(PDO::FETCH_COLUMN);
                        }
                        if (!in_array('username', $cols, true)) {
                            $error = 'No se encontró la columna de usuario en la base de datos.';
                            break;
                        }
                        $hasActivo = in_array('activo', $cols);
                        $columns = ['username', 'email', 'password_hash', 'nivel_usuario_id'];
                        $placeholders = ['?', '?', '?', '?'];
                        $values = [$username, $email, $password_hash, $nivel_usuario_id];
                        if ($hasActivo) { $columns[] = 'activo'; $placeholders[] = 'TRUE'; }
                        $sqlIns = 'INSERT INTO usuarios_sistema (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
                        $stmt = $pdo->prepare($sqlIns);
                        if ($stmt->execute($values)) {
                            $success = 'Usuario agregado exitosamente.';
                            logActivity('usuario_creado', "Usuario creado: $username");
                        } else {
                            $error = 'Error al agregar el usuario.';
                        }
                    }
                }
                break;
                
            case 'editar':
                if (canAccessModule('usuarios.php', 'escribir')) {
                    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) { $error = 'Sesión expirada. Recarga la página.'; break; }
                    $id = $_POST['id'];
                    $username = strtolower(trim($_POST['username'] ?? ''));
                    $email = sanitizeInput($_POST['email']);
                    $nivel_usuario_id = $_POST['nivel_usuario_id'];
                    $activo = isset($_POST['activo']) ? 1 : 0;
                    
                    if ($username === '') {
                        $error = 'El nombre de usuario es obligatorio.';
                        break;
                    }

                    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
                        $error = 'El nombre de usuario solo puede contener letras, números, puntos, guiones y guiones bajos.';
                        break;
                    }

                    // Verificar username único (excluyendo el actual)
                    $stmt = $pdo->prepare("SELECT id FROM usuarios_sistema WHERE username = ? AND id != ?");
                    $stmt->execute([$username, $id]);
                    if ($stmt->fetch()) {
                        $error = 'El nombre de usuario ya está registrado en el sistema.';
                        break;
                    }

                    // Verificar email único (excluyendo el usuario actual)
                    $stmt = $pdo->prepare("SELECT id FROM usuarios_sistema WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $id]);
                    if ($stmt->fetch()) {
                        $error = 'El email ya está registrado en el sistema.';
                    } else {
                        // UPDATE tolerante a columnas faltantes
                        $cols = $pdo->query("SHOW COLUMNS FROM usuarios_sistema")->fetchAll(PDO::FETCH_COLUMN);
                        if (!in_array('username', $cols, true)) {
                            asegurarColumnasUsuarios($pdo);
                            $cols = $pdo->query("SHOW COLUMNS FROM usuarios_sistema")->fetchAll(PDO::FETCH_COLUMN);
                        }
                        if (!in_array('username', $cols, true)) {
                            $error = 'No se encontró la columna de usuario en la base de datos.';
                            break;
                        }
                        $sets = ['username = ?', 'email = ?', 'nivel_usuario_id = ?'];
                        $params = [$username, $email, $nivel_usuario_id];
                        if (in_array('activo', $cols)) { $sets[] = 'activo = ?'; $params[] = $activo; }
                        if (in_array('fecha_actualizacion', $cols)) { $sets[] = 'fecha_actualizacion = NOW()'; }
                        $sqlUpd = 'UPDATE usuarios_sistema SET ' . implode(', ', $sets) . ' WHERE id = ?';
                        $params[] = $id;
                        $stmt = $pdo->prepare($sqlUpd);
                        if ($stmt->execute($params)) {
                            $success = 'Usuario actualizado exitosamente.';
                            logActivity('usuario_actualizado', "Usuario actualizado: $username");
                        } else {
                            $error = 'Error al actualizar el usuario.';
                        }
                    }
                }
                break;
                
            case 'eliminar':
                if (canAccessModule('usuarios.php', 'eliminar')) {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("UPDATE usuarios_sistema SET activo = FALSE WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $success = 'Usuario desactivado exitosamente.';
                        logActivity('usuario_desactivado', "Usuario desactivado ID: $id");
                    } else {
                        $error = 'Error al desactivar el usuario.';
                    }
                }
                break;
                
            case 'reset_password':
                if (canAccessModule('usuarios.php', 'escribir')) {
                    $id = $_POST['id'];
                    $new_password = $_POST['new_password'];
                    
                    if (strlen($new_password) >= 8) {
                        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            UPDATE usuarios_sistema 
                            SET password_hash = ?, fecha_actualizacion = NOW(), requiere_cambio_password = 1, intentos_fallidos = 0, bloqueado_hasta = NULL 
                            WHERE id = ?
                        ");
                        if ($stmt->execute([$password_hash, $id])) {
                            $success = 'Contraseña restablecida exitosamente.';
                            logActivity('password_reset_admin', "Contraseña restablecida para usuario ID: $id");
                        } else {
                            $error = 'Error al restablecer la contraseña.';
                        }
                    } else {
                        $error = 'La contraseña debe tener al menos 12 caracteres.';
                    }
                }
                break;
        }
    }
}

// Obtener datos
$usuarios = [];
$empleados = [];
$niveles = [];

try {
         // Obtener usuarios
     $stmt = $pdo->prepare("
         SELECT u.*, n.nombre as nivel_nombre, 
                CONCAT(e.nombres, ' ', e.apellido_paterno, ' ', e.apellido_materno) as empleado_nombre, 
                e.numero_empleado
         FROM usuarios_sistema u
         LEFT JOIN niveles_usuario n ON u.nivel_usuario_id = n.id
         LEFT JOIN empleados e ON u.empleado_id = e.id
         ORDER BY u.fecha_creacion DESC
     ");
    $stmt->execute();
    $usuarios = $stmt->fetchAll();
    
         // Ya no necesitamos obtener empleados sin usuario asignado
     // porque los usuarios se crean automáticamente
     $empleados = [];
    
    // Obtener niveles de usuario
    $stmt = $pdo->prepare("SELECT * FROM niveles_usuario WHERE activo = TRUE ORDER BY nivel_prioridad");
    $stmt->execute();
    $niveles = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Error al cargar los datos: ' . $e->getMessage();
}

// Estadísticas
$total_usuarios = count($usuarios);
$usuarios_activos = count(array_filter($usuarios, fn($u) => $u['activo']));
$usuarios_inactivos = $total_usuarios - $usuarios_activos;

require_once '../../includes/header.php';
?>

<?php if (!empty($_SESSION['sync_nuevos_usuarios'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <h5 class="mb-2"><i class="fas fa-user-check me-2"></i>Usuarios de empleados generados automáticamente</h5>
        <p class="mb-2">Comparte las siguientes credenciales con los empleados para su primer acceso. El usuario corresponde a su número de empleado y se les solicitará cambiar la contraseña al ingresar al formulario de datos personales.</p>
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Empleado</th>
                        <th>Email</th>
                        <th>Contraseña temporal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['sync_nuevos_usuarios'] as $nuevo): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($nuevo['username']); ?></code></td>
                            <td><?php echo htmlspecialchars($nuevo['nombre'] ?: ('ID ' . $nuevo['empleado_id'])); ?></td>
                            <td><code><?php echo htmlspecialchars($nuevo['email']); ?></code></td>
                            <td><code><?php echo htmlspecialchars($nuevo['password']); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['sync_nuevos_usuarios']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['sync_errores_usuarios'])): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h5 class="mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Algunos usuarios no pudieron generarse</h5>
        <ul class="mb-0">
            <?php foreach ($_SESSION['sync_errores_usuarios'] as $errorSync): ?>
                <li>Empleado ID <?php echo (int) $errorSync['empleado_id']; ?>: <?php echo htmlspecialchars($errorSync['mensaje']); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['sync_errores_usuarios']); ?>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Gestión de Usuarios del Sistema</h1>
                <p class="text-muted">Administra usuarios, roles y permisos del sistema</p>
            </div>
            <?php if (canAccessModule('usuarios.php', 'escribir')): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarUsuario">
                    <i class="fas fa-plus"></i> Usuario Administrativo
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $total_usuarios; ?></h3>
                        <p class="stat-label">Total Usuarios</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $usuarios_activos; ?></h3>
                        <p class="stat-label">Usuarios Activos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $usuarios_inactivos; ?></h3>
                        <p class="stat-label">Usuarios Inactivos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-info">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo count($niveles); ?></h3>
                        <p class="stat-label">Niveles de Usuario</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de usuarios -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list"></i> Lista de Usuarios
                    </h5>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Buscar usuarios...">
                        <button class="btn btn-sm btn-outline-secondary" onclick="limpiarBusqueda()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="usuariosTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Empleado</th>
                                <th>Nivel</th>
                                <th>Estado</th>
                                <th>Último Acceso</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo $usuario['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($usuario['username'] ?? ''); ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($usuario['email']); ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($usuario['empleado_nombre']): ?>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($usuario['empleado_nombre'] ?? ''); ?>
                                                (<?php echo $usuario['numero_empleado']; ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No asignado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars($usuario['nivel_nombre'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($usuario['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($usuario['ultimo_acceso']): ?>
                                            <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Nunca</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if (canAccessModule('usuarios.php', 'escribir')): ?>
                                                <button class="btn btn-outline-primary" onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($usuario)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (canAccessModule('usuarios.php', 'escribir')): ?>
                                                <button class="btn btn-outline-warning" onclick="resetPassword(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['email']); ?>')">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (canAccessModule('usuarios.php', 'eliminar') && $usuario['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-outline-danger" onclick="eliminarUsuario(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['email']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        Mostrando <span id="resultadosCount"><?php echo count($usuarios); ?></span> usuarios
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar Usuario -->
<?php if (canAccessModule('usuarios.php', 'escribir')): ?>
<div class="modal fade" id="modalAgregarUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i> Usuario Administrativo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="agregar">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Usuario *</label>
                                <input type="text" class="form-control" id="username" name="username" required pattern="[A-Za-z0-9._-]+">
                                <small class="form-text text-muted">Sugerencia: emplea el número de empleado o un identificador único.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">Mínimo 8 caracteres con mayúsculas, minúsculas, números y símbolos.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="nivel_usuario_id" class="form-label">Nivel de Usuario *</label>
                                <select class="form-control" id="nivel_usuario_id" name="nivel_usuario_id" required>
                                    <option value="">Seleccionar nivel</option>
                                    <?php foreach ($niveles as $nivel): ?>
                                        <option value="<?php echo $nivel['id']; ?>">
                                            <?php echo htmlspecialchars($nivel['nombre'] ?? ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    Los usuarios se crean automáticamente cuando se registra un empleado. 
                                    Este formulario es para crear usuarios administrativos adicionales.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit"></i> Editar Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="editar">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_username" class="form-label">Usuario *</label>
                                <input type="text" class="form-control" id="edit_username" name="username" required pattern="[A-Za-z0-9._-]+">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_activo" class="form-label">Estado</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="edit_activo" name="activo">
                                    <label class="form-check-label" for="edit_activo">Usuario activo</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="edit_nivel_usuario_id" class="form-label">Nivel de Usuario *</label>
                                <select class="form-control" id="edit_nivel_usuario_id" name="nivel_usuario_id" required>
                                    <option value="">Seleccionar nivel</option>
                                    <?php foreach ($niveles as $nivel): ?>
                                        <option value="<?php echo $nivel['id']; ?>">
                                            <?php echo htmlspecialchars($nivel['nombre'] ?? ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reset Password -->
<div class="modal fade" id="modalResetPassword" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-key"></i> Restablecer Contraseña
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" id="reset_id">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="modal-body">
                    <p>Nueva contraseña para: <strong id="reset_email"></strong></p>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nueva Contraseña *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">La nueva contraseña debe tener al menos 12 caracteres e incluir diferentes tipos de caracteres.</div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key"></i> Restablecer Contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Funciones JavaScript
function editarUsuario(usuario) {
    document.getElementById('edit_id').value = usuario.id;
    document.getElementById('edit_username').value = usuario.username ?? '';
    document.getElementById('edit_email').value = usuario.email;
    document.getElementById('edit_nivel_usuario_id').value = usuario.nivel_usuario_id;
    document.getElementById('edit_activo').checked = usuario.activo == 1;
    
    new bootstrap.Modal(document.getElementById('modalEditarUsuario')).show();
}

function resetPassword(id, email) {
    document.getElementById('reset_id').value = id;
    document.getElementById('reset_email').textContent = email;
    
    new bootstrap.Modal(document.getElementById('modalResetPassword')).show();
}

function eliminarUsuario(id, email) {
    if (confirm(`¿Estás seguro de que deseas desactivar al usuario "${email}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="eliminar">
            <input type="hidden" name="id" value="${id}">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function togglePassword(fieldId) {
    const input = document.getElementById(fieldId);
    const icon = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Búsqueda en tiempo real
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#usuariosTable tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    document.getElementById('resultadosCount').textContent = visibleCount;
});

function limpiarBusqueda() {
    document.getElementById('searchInput').value = '';
    document.getElementById('searchInput').dispatchEvent(new Event('input'));
}
</script>

<?php require_once '../../includes/footer.php'; ?>
