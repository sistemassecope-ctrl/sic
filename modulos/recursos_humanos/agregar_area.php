<?php
require_once '../../includes/functions.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$pageTitle = 'Agregar Dependencia - PAO';
$breadcrumb = [
    ['url' => '../../index.php', 'text' => 'Inicio'],
    ['url' => 'areas.php', 'text' => 'Areas'],
    ['url' => 'agregar_area.php', 'text' => 'Agregar Area']
];

$pdo = conectarDB();

$padre_preseleccionado = isset($_GET['padre']) && $_GET['padre'] !== '' ? (int)$_GET['padre'] : null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $tipo = $_POST['tipo'] ?? '';
    $dependencia_padre_id = !empty($_POST['dependencia_padre_id']) ? $_POST['dependencia_padre_id'] : null;
    $descripcion = trim($_POST['descripcion']);
    
    $errores = [];
    
    if (empty($nombre)) $errores[] = "El nombre de la dependencia es obligatorio.";
    if (empty($tipo)) $errores[] = "El tipo de dependencia es obligatorio.";
    
    if (empty($errores)) {
        try {
            $nivel = calcularNivel($pdo, $dependencia_padre_id);
            
            $sql = "INSERT INTO area (nombre, tipo, area_padre_id, descripcion, nivel, activo, fecha_creacion) 
                    VALUES (?, ?, ?, ?, ?, 1, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $tipo, $dependencia_padre_id, $descripcion, $nivel]);
            
            logActivity('dependencia_creada', "Dependencia creada: $nombre (Tipo: $tipo)", $_SESSION['user_id']);
            redirectWithMessage('areas.php', 'success', 'Area agregada exitosamente.');
            
        } catch (PDOException $e) {
            $errores[] = "Error al guardar la dependencia: " . $e->getMessage();
        }
    }
}

$todas_dependencias = obtenerDependencias($pdo);

require_once '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Agregar Nueva Dependencia</h1>
            </div>
            <a href="areas.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errores as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="formDependencia">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre de la Dependencia *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tipo" class="form-label">Tipo de Dependencia *</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="Secretaria" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Secretaria') ? 'selected' : ''; ?>>Secretaría</option>
                                    <option value="Subsecretaria" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Subsecretaria') ? 'selected' : ''; ?>>Subsecretaría</option>
                                    <option value="Secretaria Técnica" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Secretaria Técnica') ? 'selected' : ''; ?>>Secretaría Técnica</option>
                                    <option value="Direccion" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Direccion') ? 'selected' : ''; ?>>Dirección</option>
                                    <option value="Subdireccion" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Subdireccion') ? 'selected' : ''; ?>>Subdirección</option>
                                    <option value="Área" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Área') ? 'selected' : ''; ?>>Área</option>
                                    <option value="Jefatura" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Jefatura') ? 'selected' : ''; ?>>Jefatura</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="dependencia_padre_id" class="form-label">Dependencia Padre</label>
                                <select class="form-select" id="dependencia_padre_id" name="dependencia_padre_id">
                                    <option value="">Sin dependencia padre (Nivel superior)</option>
                                    <?php foreach ($todas_dependencias as $dep): ?>
                                        <?php
                                            $selected = '';
                                            if (isset($_POST['dependencia_padre_id']) && $_POST['dependencia_padre_id'] == $dep['id']) {
                                                $selected = 'selected';
                                            } elseif (!isset($_POST['dependencia_padre_id']) && $padre_preseleccionado !== null && $padre_preseleccionado == (int)$dep['id']) {
                                                $selected = 'selected';
                                            }
                                        ?>
                                        <option value="<?php echo $dep['id']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($dep['nombre'] ?? ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                                          placeholder="Descripción opcional"><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="areas.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
