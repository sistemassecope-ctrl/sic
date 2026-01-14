<?php
// DB Connection
$db = (new Database())->getConnection();

// --- Logic Handling ---

// 1. Add Role
if (isset($_POST['action']) && $_POST['action'] === 'add_role') {
    $nombre = trim($_POST['nombre_rol']);
    $desc = trim($_POST['descripcion']);
    if (!empty($nombre)) {
        try {
            $stmt = $db->prepare("INSERT INTO roles (nombre_rol, descripcion) VALUES (?, ?)");
            $stmt->execute([$nombre, $desc]);
            $success = "Rol '$nombre' creado correctamente.";
            // Redirect to avoid resubmission
            // header("Location: /pao/index.php?route=configuracion/roles_usuarios&role_id=" . $db->lastInsertId());
        } catch (PDOException $e) {
            $error = "Error al crear rol: " . $e->getMessage();
        }
    }
}

// 2. Delete Role
if (isset($_POST['action']) && $_POST['action'] === 'delete_role') {
    $id_rol_del = $_POST['id_rol'];
    // Avoid deleting vital roles like SuperAdmin (1)
    if ($id_rol_del == 1) {
        $error = "No se puede eliminar el rol SuperAdmin por seguridad.";
    } else {
        try {
            $stmt = $db->prepare("DELETE FROM roles WHERE id_rol = ?");
            $stmt->execute([$id_rol_del]);
            $success = "Rol eliminado correctamente.";
            $selected_role_id = 1; // Reset to default
        } catch (PDOException $e) {
            // Usually FK constraint fails here if users exist
            $error = "No se puede eliminar el rol porque tiene usuarios asignados. Mueva los usuarios a otro rol primero.";
        }
    }
}

// 3. Move User to Role
if (isset($_POST['action']) && $_POST['action'] === 'add_user_to_role') {
    $user_ids = isset($_POST['user_ids']) ? $_POST['user_ids'] : [];
    $target_role = $_POST['target_role_id'];

    if (!empty($user_ids) && is_array($user_ids) && !empty($target_role)) {
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE usuarios SET id_rol = ? WHERE id_usuario = ?");
            $count = 0;
            foreach ($user_ids as $uid) {
                $stmt->execute([$target_role, $uid]);
                $count++;
            }
            $db->commit();
            $success = "Usuarios asignados correctamente ($count usuarios).";
        } catch (PDOException $e) {
            $db->rollBack();
            $error = "Error al asignar usuarios: " . $e->getMessage();
        }
    } elseif (empty($user_ids)) {
         $error = "No seleccionó ningún usuario.";
    }
}

// 4. Change User Role (Push)
if (isset($_POST['action']) && $_POST['action'] === 'change_user_role') {
    $user_id = $_POST['user_id'];
    $target_role = $_POST['new_role_id'];
    if (!empty($user_id) && !empty($target_role)) {
        try {
            $stmt = $db->prepare("UPDATE usuarios SET id_rol = ? WHERE id_usuario = ?");
            $stmt->execute([$target_role, $user_id]);
            $success = "Usuario movido de rol correctamente.";
        } catch (PDOException $e) {
            $error = "Error al mover usuario: " . $e->getMessage();
        }
    }
}

// 5. Deactivate User
if (isset($_POST['action']) && $_POST['action'] === 'deactivate_user') {
    $user_id = $_POST['user_id'];
    if (!empty($user_id)) {
        try {
            $stmt = $db->prepare("UPDATE usuarios SET activo = 0 WHERE id_usuario = ?");
            $stmt->execute([$user_id]);
            $success = "Usuario desactivado. El acceso al sistema ha sido revocado.";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// 6. Activate User
if (isset($_POST['action']) && $_POST['action'] === 'activate_user') {
    $user_id = $_POST['user_id'];
    if (!empty($user_id)) {
        try {
            $stmt = $db->prepare("UPDATE usuarios SET activo = 1 WHERE id_usuario = ?");
            $stmt->execute([$user_id]);
            $success = "Usuario reactivado exitosamente.";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// --- Data Fetching ---

// Get current selected role
$selected_role_id = isset($_GET['role_id']) ? $_GET['role_id'] : 1;

// Get All Roles
$stmt = $db->query("SELECT * FROM roles ORDER BY id_rol ASC");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Validate selected role existence
$selected_role = null;
$role_exists = false;
foreach ($roles as $r) {
    if ($r['id_rol'] == $selected_role_id) {
        $selected_role = $r;
        $role_exists = true;
        break;
    }
}
if (!$role_exists && count($roles) > 0) {
    $selected_role = $roles[0];
    $selected_role_id = $selected_role['id_rol'];
}

// Get Users in Selected Role (INCLUDING INACTIVE for management visibility)
$users_in_role = [];
if ($selected_role_id) {
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id_rol = ? ORDER BY activo DESC, nombre_completo ASC");
    $stmt->execute([$selected_role_id]);
    $users_in_role = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get Users NOT in Selected Role (availables to add) - ONLY ACTIVE
$users_available = [];
if ($selected_role_id) {
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id_rol != ? AND activo = 1 ORDER BY nombre_completo");
    $stmt->execute([$selected_role_id]);
    $users_available = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="text-primary"><i class="bi bi-shield-lock-fill"></i> Gestión de Roles y Usuarios</h4>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i> <?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-octagon me-2"></i> <?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row">
    <!-- Left Column: Roles List -->
    <div class="col-md-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-list-stars"></i> Roles Definidos</span>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal"><i class="bi bi-plus-lg"></i> Nuevo</button>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($roles as $rol): ?>
                    <?php $isActive = ($rol['id_rol'] == $selected_role_id); ?>
                    <a href="/pao/index.php?route=configuracion/roles_usuarios&role_id=<?php echo $rol['id_rol']; ?>"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $isActive ? 'active' : ''; ?>">
                        <div>
                            <strong><?php echo htmlspecialchars($rol['nombre_rol']); ?></strong>
                            <div class="small <?php echo $isActive ? 'text-light opacity-75' : 'text-muted'; ?>"><?php echo htmlspecialchars($rol['descripcion']); ?></div>
                        </div>
                        <?php if ($rol['id_rol'] != 1): // Don't delete SuperAdmin ?>
                            <form method="POST" action="" onsubmit="return confirm('¿Está seguro de eliminar este rol?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete_role">
                                <input type="hidden" name="id_rol" value="<?php echo $rol['id_rol']; ?>">
                                <button type="submit" class="btn btn-link btn-sm p-0 ms-2 <?php echo $isActive ? 'text-white' : 'text-danger'; ?>"><i class="bi bi-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Users in Selected Role -->
    <div class="col-md-8 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-people-fill"></i> Usuarios en rol: <strong><?php echo htmlspecialchars($selected_role['nombre_rol']); ?></strong>
                </span>
                <button class="btn btn-light btn-sm text-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus-fill"></i> Agregar Empleado
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">ID/Usuario</th>
                                <th>Nombre Completo</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end pe-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users_in_role)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">No hay usuarios asignados a este rol.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users_in_role as $u): ?>
                                    <tr class="<?php echo $u['activo'] ? '' : 'table-secondary text-muted'; ?>">
                                        <td class="ps-3 fw-bold font-monospace"><?php echo $u['usuario']; ?></td>
                                        <td><?php echo $u['nombre_completo']; ?></td>
                                        <td class="text-center">
                                            <?php if ($u['activo']): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="openMoveModal('<?php echo $u['id_usuario']; ?>', '<?php echo htmlspecialchars($u['nombre_completo']); ?>')"
                                                        title="Mover a otro rol">
                                                    <i class="bi bi-arrow-left-right"></i>
                                                </button>
                                                
                                                <?php if ($u['activo']): ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('⚠️ ADVERTENCIA: Esta acción BLOQUEARÁ el acceso de este usuario al sistema.\n\n¿Desea continuar?');">
                                                         <input type="hidden" name="action" value="deactivate_user">
                                                         <input type="hidden" name="user_id" value="<?php echo $u['id_usuario']; ?>">
                                                         <button type="submit" class="btn btn-sm btn-outline-danger" title="Desactivar y bloquear acceso">
                                                            <i class="bi bi-power"></i>
                                                         </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display:inline;">
                                                         <input type="hidden" name="action" value="activate_user">
                                                         <input type="hidden" name="user_id" value="<?php echo $u['id_usuario']; ?>">
                                                         <button type="submit" class="btn btn-sm btn-outline-success" title="Reactivar usuario">
                                                            <i class="bi bi-check-lg"></i>
                                                         </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODALES ================= -->

<!-- Modal 1: Agregar Nuevo Rol -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="action" value="add_role">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addRoleLabel">Crear Nuevo Rol</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Rol</label>
                        <input type="text" name="nombre_rol" class="form-control" placeholder="Ej. Supervisor de Obras" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2" placeholder="Breve descripción de las funciones..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Rol</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Asignar Usuario a Rol -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="action" value="add_user_to_role">
            <input type="hidden" name="target_role_id" value="<?php echo $selected_role_id; ?>">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addUserLabel">Asignar Empleados</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Seleccione usuarios para moverlos al rol: 
                        <strong class="text-success"><?php echo htmlspecialchars($selected_role['nombre_rol']); ?></strong>
                    </p>
                    <div class="alert alert-info py-1 px-2 small mb-3">
                        <i class="bi bi-info-circle"></i> Use <strong>Ctrl + Click</strong> para seleccionar varios.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Usuarios Disponibles (Activos)</label>
                        <?php if (empty($users_available)): ?>
                            <div class="alert alert-warning text-center">
                                No hay usuarios disponibles en otros roles para asignar.
                            </div>
                        <?php else: ?>
                            <select name="user_ids[]" class="form-select" size="10" multiple required>
                                <?php foreach ($users_available as $ua): ?>
                                    <option value="<?php echo $ua['id_usuario']; ?>" class="py-1">
                                        <?php echo $ua['nombre_completo']; ?> (<?php echo $ua['usuario']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-end mt-1">
                                <?php echo count($users_available); ?> usuarios encontrados.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success" <?php echo empty($users_available) ? 'disabled' : ''; ?>>
                        <i class="bi bi-plus-lg"></i> Asignar Seleccionados
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Mover Usuario (Cambio Individual) -->
<div class="modal fade" id="moveUserModal" tabindex="-1" aria-labelledby="moveUserLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="action" value="change_user_role">
            <input type="hidden" name="user_id" id="move_user_id">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="moveUserLabel">Reasignar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="lead text-center mb-4">
                        Mover a <strong id="move_user_name" class="text-primary">Usuario</strong><br>
                        del rol actual <u><?php echo htmlspecialchars($selected_role['nombre_rol']); ?></u>
                    </p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Seleccione Nuevo Rol Destino:</label>
                        <select name="new_role_id" class="form-select form-select-lg" required>
                            <option value="">-- Seleccione Rol --</option>
                            <?php foreach ($roles as $r): ?>
                                <?php if ($r['id_rol'] != $selected_role_id): ?>
                                    <option value="<?php echo $r['id_rol']; ?>">
                                        <?php echo htmlspecialchars($r['nombre_rol']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"> Confirmar Cambio</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Validar que Bootstrap esté cargado
document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap no está cargado. Los modales no funcionarán.');
        alert('Error: Librería Bootstrap no detectada. Revise la consola.');
    }
});

function openMoveModal(userId, userName) {
    // Set Values
    const idInput = document.getElementById('move_user_id');
    const nameSpan = document.getElementById('move_user_name');
    
    if(idInput) idInput.value = userId;
    if(nameSpan) nameSpan.textContent = userName;
    
    // Open Modal
    const modalEl = document.getElementById('moveUserModal');
    if(modalEl && typeof bootstrap !== 'undefined') {
        const myModal = new bootstrap.Modal(modalEl);
        myModal.show();
    } else {
        alert('No se pudo abrir el modal. Verifique los scripts.');
    }
}
</script>