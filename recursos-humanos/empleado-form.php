<?php
/**
 * Módulo de Recursos Humanos - Formulario de Empleado
 * Crear/Editar empleados con verificación de permisos
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAuth();

define('MODULO_ID', 2);

$pdo = getConnection();
$user = getCurrentUser();
$permisos = getUserPermissions(MODULO_ID);
$areasUsuario = getUserAreas();

$empleadoId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$esEdicion = $empleadoId !== null;

// Verificar permisos
if ($esEdicion && !in_array('editar', $permisos)) {
    setFlashMessage('error', 'No tienes permiso para editar empleados');
    redirect('/recursos-humanos/empleados.php');
}

if (!$esEdicion && !in_array('crear', $permisos)) {
    setFlashMessage('error', 'No tienes permiso para crear empleados');
    redirect('/recursos-humanos/empleados.php');
}

$empleado = null;
$errors = [];

// Si es edición, cargar datos del empleado
if ($esEdicion) {
    $stmt = $pdo->prepare("SELECT * FROM empleados WHERE id = ? AND " . getAreaFilterSQL('id_area'));
    $stmt->execute([$empleadoId]);
    $empleado = $stmt->fetch();
    
    if (!$empleado) {
        setFlashMessage('error', 'Empleado no encontrado o no tienes acceso');
        redirect('/recursos-humanos/empleados.php');
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize($_POST['nombre'] ?? '');
    $apellidoPaterno = sanitize($_POST['apellido_paterno'] ?? '');
    $apellidoMaterno = sanitize($_POST['apellido_materno'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $telefono = sanitize($_POST['telefono'] ?? '');
    $idArea = (int)($_POST['id_area'] ?? 0);
    $idPuesto = (int)($_POST['id_puesto'] ?? 0);
    
    // Validaciones
    if (empty($nombre)) $errors[] = 'El nombre es requerido';
    if (empty($apellidoPaterno)) $errors[] = 'El apellido paterno es requerido';
    if ($idArea <= 0) $errors[] = 'Selecciona un área';
    if ($idPuesto <= 0) $errors[] = 'Selecciona un puesto';
    
    // Verificar que el área seleccionada esté en las áreas permitidas
    if (!in_array($idArea, $areasUsuario)) {
        $errors[] = 'No tienes permiso para asignar empleados a esa área';
    }
    
    if (empty($errors)) {
        try {
            if ($esEdicion) {
                $stmt = $pdo->prepare("
                    UPDATE empleados SET 
                        nombre = ?, apellido_paterno = ?, apellido_materno = ?,
                        email = ?, telefono = ?, id_area = ?, id_puesto = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $nombre, $apellidoPaterno, $apellidoMaterno,
                    $email, $telefono, $idArea, $idPuesto, $empleadoId
                ]);
                setFlashMessage('success', 'Empleado actualizado correctamente');
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO empleados (nombre, apellido_paterno, apellido_materno, email, telefono, id_area, id_puesto)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $nombre, $apellidoPaterno, $apellidoMaterno,
                    $email, $telefono, $idArea, $idPuesto
                ]);
                setFlashMessage('success', 'Empleado creado correctamente');
            }
            redirect('/recursos-humanos/empleados.php');
        } catch (Exception $e) {
            $errors[] = 'Error al guardar: ' . $e->getMessage();
        }
    }
    
    // Mantener datos en caso de error
    $empleado = [
        'nombre' => $nombre,
        'apellido_paterno' => $apellidoPaterno,
        'apellido_materno' => $apellidoMaterno,
        'email' => $email,
        'telefono' => $telefono,
        'id_area' => $idArea,
        'id_puesto' => $idPuesto
    ];
}

// Obtener áreas (solo las que el usuario puede ver)
$areas = $pdo->query("
    SELECT * FROM areas WHERE estado = 1 AND " . getAreaFilterSQL('id') . " ORDER BY nombre_area
")->fetchAll();

// Obtener puestos
$puestos = $pdo->query("SELECT * FROM puestos WHERE estado = 1 ORDER BY nivel_jerarquico DESC")->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fas fa-<?= $esEdicion ? 'user-edit' : 'user-plus' ?>" style="color: var(--accent-primary);"></i>
                <?= $esEdicion ? 'Editar Empleado' : 'Nuevo Empleado' ?>
            </h1>
            <p class="page-description">
                <?= $esEdicion ? 'Modifica la información del empleado' : 'Registra un nuevo empleado en el sistema' ?>
            </p>
        </div>
        <a href="<?= url('/recursos-humanos/empleados.php') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <ul style="margin: 0; padding-left: 1.5rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    <!-- Datos personales -->
                    <div>
                        <h4 style="margin-bottom: 1rem; color: var(--accent-primary);">
                            <i class="fas fa-user"></i> Datos Personales
                        </h4>
                        
                        <div class="form-group">
                            <label class="form-label">Nombre(s) *</label>
                            <input type="text" name="nombre" class="form-control" required
                                   value="<?= e($empleado['nombre'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Apellido Paterno *</label>
                            <input type="text" name="apellido_paterno" class="form-control" required
                                   value="<?= e($empleado['apellido_paterno'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Apellido Materno</label>
                            <input type="text" name="apellido_materno" class="form-control"
                                   value="<?= e($empleado['apellido_materno'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <!-- Información de contacto y laboral -->
                    <div>
                        <h4 style="margin-bottom: 1rem; color: var(--accent-secondary);">
                            <i class="fas fa-briefcase"></i> Información Laboral
                        </h4>
                        
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= e($empleado['email'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control"
                                   value="<?= e($empleado['telefono'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Área * 
                                <small style="color: var(--text-muted);">(Solo áreas a las que tienes acceso)</small>
                            </label>
                            <select name="id_area" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?= $area['id'] ?>" 
                                            <?= ($empleado['id_area'] ?? '') == $area['id'] ? 'selected' : '' ?>>
                                        <?= e($area['nombre_area']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Puesto *</label>
                            <select name="id_puesto" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($puestos as $puesto): ?>
                                    <option value="<?= $puesto['id'] ?>"
                                            <?= ($empleado['id_puesto'] ?? '') == $puesto['id'] ? 'selected' : '' ?>>
                                        <?= e($puesto['nombre_puesto']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-primary); display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="<?= url('/recursos-humanos/empleados.php') ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= $esEdicion ? 'Guardar Cambios' : 'Crear Empleado' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
