<?php
require_once '../../includes/functions.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$pageTitle = 'Editar Dependencia - PAO';
$breadcrumb = [
    ['url' => '../../index.php', 'text' => 'Inicio'],
    ['url' => 'areas.php', 'text' => 'Areas'],
    ['url' => 'editar_area.php', 'text' => 'Editar Area']
];

$pdo = conectarDB();
$id = $_GET['id'] ?? null;

if (!$id) {
    redirectWithMessage('areas.php', 'danger', 'ID no proporcionado.');
}

$stmt = $pdo->prepare("SELECT * FROM area WHERE id = ?");
$stmt->execute([$id]);
$dependencia = $stmt->fetch();

if (!$dependencia) {
    redirectWithMessage('areas.php', 'danger', 'Area no encontrada.');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $tipo = $_POST['tipo'] ?? '';
    $dependencia_padre_id = !empty($_POST['dependencia_padre_id']) ? $_POST['dependencia_padre_id'] : null;
    $descripcion = trim($_POST['descripcion']);
    
    $errores = [];
    
    if (empty($nombre)) $errores[] = "Nombre obligatorio.";
    if (empty($tipo)) $errores[] = "Tipo obligatorio.";
    if ($dependencia_padre_id == $id) $errores[] = "Loop no permitido.";
    
    if (empty($errores)) {
        try {
            $nivel = calcularNivel($pdo, $dependencia_padre_id);
            
            $sql = "UPDATE area 
                    SET nombre = ?, tipo = ?, area_padre_id = ?, descripcion = ?, nivel = ?, fecha_actualizacion = NOW() 
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $tipo, $dependencia_padre_id, $descripcion, $nivel, $id]);
            
            logActivity('dependencia_editada', "Area editada: $nombre (ID: $id)", $_SESSION['user_id']);
            redirectWithMessage('areas.php', 'success', 'Actualizado exitosamente.');
            
        } catch (PDOException $e) {
            $errores[] = "Error: " . $e->getMessage();
        }
    }
    
    $dependencia['nombre'] = $nombre;
    $dependencia['tipo'] = $tipo;
    $dependencia['area_padre_id'] = $dependencia_padre_id;
    $dependencia['descripcion'] = $descripcion;
}

$todas_dependencias = $pdo->query("SELECT * FROM area WHERE id != $id AND activo = 1 ORDER BY nombre")->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Editar Dependencia</h1>
            </div>
            <a href="areas.php" class="btn btn-secondary">Volver</a>
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
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo htmlspecialchars($dependencia['nombre']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tipo" class="form-label">Tipo *</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="">Seleccionar...</option>
                                    <?php
                                    $tipos = ['Secretaria','Subsecretaria','Secretaria Técnica','Direccion','Subdireccion','Área','Jefatura'];
                                    foreach($tipos as $t) {
                                        $sel = ($dependencia['tipo'] == $t) ? 'selected' : '';
                                        echo "<option value='$t' $sel>$t</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="dependencia_padre_id" class="form-label">Dependencia Padre</label>
                                <select class="form-select" id="dependencia_padre_id" name="dependencia_padre_id">
                                    <option value="">Sin padre</option>
                                    <?php foreach ($todas_dependencias as $dep): ?>
                                        <option value="<?php echo $dep['id']; ?>" 
                                                <?php echo ((isset($dependencia['area_padre_id']) ? $dependencia['area_padre_id'] : '') == $dep['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dep['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($dependencia['descripcion'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 text-end">
                            <a href="areas.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
