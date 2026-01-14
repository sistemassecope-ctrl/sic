<?php
// DB Connection
$db = (new Database())->getConnection();

// --- Logic Handling ---
$selected_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$msg = "";

// Save Permissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_permissions') {
    $selected_user_id = $_POST['user_id'];
    $perms = isset($_POST['permisos']) ? $_POST['permisos'] : [];
    
    if ($selected_user_id) {
        try {
            $db->beginTransaction();
            
            // 1. Wipe existing specific permissions for this user
            $stmt = $db->prepare("DELETE FROM usuarios_permisos WHERE id_usuario = ?");
            $stmt->execute([$selected_user_id]);
            
            // 2. Insert selected
            if (!empty($perms)) {
                $stmt = $db->prepare("INSERT INTO usuarios_permisos (id_usuario, id_permiso) VALUES (?, ?)");
                foreach ($perms as $perm_id) {
                    $stmt->execute([$selected_user_id, $perm_id]);
                }
            }
            
            $db->commit();
            $msg = "<div class='alert alert-success'>Permisos actualizados correctamente.</div>";
        } catch (Exception $e) {
            $db->rollBack();
            $msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// --- Data Fetching ---

// fetch Users
$stmt = $db->query("SELECT u.id_usuario, u.usuario, u.nombre_completo, r.nombre_rol 
                    FROM usuarios u 
                    JOIN roles r ON u.id_rol = r.id_rol 
                    ORDER BY u.nombre_completo");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch All Permissions
$stmt = $db->query("SELECT * FROM permisos ORDER BY clave_permiso");
$all_perms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Selected User's EXTRA permissions
$user_extra_perms = [];
$user_role_perms = [];

if ($selected_user_id) {
    // Specific
    $stmt = $db->prepare("SELECT id_permiso FROM usuarios_permisos WHERE id_usuario = ?");
    $stmt->execute([$selected_user_id]);
    $user_extra_perms = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Inherited from Role (for display purposes)
    // First get role id of user
    $stmt = $db->prepare("SELECT id_rol FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$selected_user_id]);
    $rid = $stmt->fetchColumn();
    
    if ($rid) {
        $stmt = $db->prepare("SELECT id_permiso FROM roles_permisos WHERE id_rol = ?");
        $stmt->execute([$rid]);
        $user_role_perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="text-primary"><i class="bi bi-key-fill"></i> Permisos Adicionales por Usuario</h4>
        <p class="text-muted">Asigne permisos específicos a usuarios, adicionales a los que ya tienen por su Rol.</p>
    </div>
</div>

<?php echo $msg; ?>

<div class="row">
    <!-- Left: Select User -->
    <div class="col-md-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-light fw-bold">
                <i class="bi bi-person-lines-fill"></i> Seleccionar Usuario
            </div>
            <div class="list-group list-group-flush overflow-auto" style="max-height: 600px;">
                <?php foreach ($users as $u): ?>
                    <?php $active = ($u['id_usuario'] == $selected_user_id) ? 'active' : ''; ?>
                    <a href="/pao/index.php?route=configuracion/permisos_usuarios&user_id=<?php echo $u['id_usuario']; ?>" 
                       class="list-group-item list-group-item-action <?php echo $active; ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?php echo htmlspecialchars($u['nombre_completo']); ?></h6>
                            <small><?php echo htmlspecialchars($u['usuario']); ?></small>
                        </div>
                        <small class="<?php echo $active ? 'text-light' : 'text-muted'; ?>">
                            Rol: <?php echo htmlspecialchars($u['nombre_rol']); ?>
                        </small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right: Check Permissions -->
    <div class="col-md-8 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-primary text-white">
                <?php if ($selected_user_id): ?>
                    <i class="bi bi-sliders"></i> Gestionando permisos para: <strong><?php 
                        // Find name simply
                        foreach($users as $u) { if($u['id_usuario'] == $selected_user_id) { echo $u['nombre_completo']; break; } }
                    ?></strong>
                <?php else: ?>
                    <i class="bi bi-sliders"></i> Permisos
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$selected_user_id): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-arrow-left-circle display-4"></i>
                        <p class="mt-3">Seleccione un usuario de la lista izquierda.</p>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_permissions">
                        <input type="hidden" name="user_id" value="<?php echo $selected_user_id; ?>">
                        
                        <div class="alert alert-info py-2 small">
                            <i class="bi bi-info-circle"></i> Los permisos marcados en <strong>gris (deshabilitados)</strong> son heredados del Rol actual y no se pueden quitar aquí.
                        </div>

                        <div class="row">
                            <?php foreach ($all_perms as $perm): ?>
                                <?php 
                                    $is_inherited = in_array($perm['id_permiso'], $user_role_perms);
                                    $is_assigned = in_array($perm['id_permiso'], $user_extra_perms);
                                    $checked = ($is_inherited || $is_assigned) ? 'checked' : '';
                                    $disabled = $is_inherited ? 'disabled' : '';
                                    $bg_class = $is_inherited ? 'bg-light text-muted' : '';
                                ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check p-2 border rounded <?php echo $bg_class; ?>">
                                        <input class="form-check-input ms-1" type="checkbox" name="permisos[]" 
                                               value="<?php echo $perm['id_permiso']; ?>" 
                                               id="perm_<?php echo $perm['id_permiso']; ?>"
                                               <?php echo $checked; ?> <?php echo $disabled; ?>>
                                        <label class="form-check-label ms-2" for="perm_<?php echo $perm['id_permiso']; ?>">
                                            <strong><?php echo htmlspecialchars($perm['clave_permiso']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($perm['descripcion']); ?></small>
                                            <?php if($is_inherited): ?>
                                                <span class="badge bg-secondary ms-1" style="font-size: 0.6em;">ROLES</span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Guardar Permisos
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
