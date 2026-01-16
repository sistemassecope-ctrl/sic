<?php
// admin_certificados.php - Panel de administración de certificados (Padrón de Contratistas)
include("proteger_admin.php");
include("conexion.php");

$mensaje = '';
$usuario_sistema = $_SESSION['user_username'] ?? 'Admin';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['seleccionar_registro'])) {
        $rfc_seleccionado = $_POST['rfc_seleccionado'];
        header("Location: admin_certificados.php?rfc=" . urlencode($rfc_seleccionado));
        exit();
    }
    
    if (isset($_POST['crear_certificado'])) {
        $rfc_certificado = $_POST['rfc_certificado'];
        $numero_certificado = $_POST['numero_certificado'];
        $numero_registro = $_POST['numero_registro'] ?? '';
        $nombre_razon_social = $_POST['nombre_razon_social'];
        $representante_apoderado = $_POST['representante_apoderado'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $capital_contable = $_POST['capital_contable'] ?? null;
        $fecha_emision = $_POST['fecha_emision'];
        $fecha_vigencia = $_POST['fecha_vigencia'];
        $refrendo = $_POST['refrendo'] ?? null;
        $papeleria_correcta = isset($_POST['papeleria_correcta']) ? 1 : 0;
        
        // Generar hash de validación
        $hash_validacion = hash('sha256', $rfc_certificado . $numero_certificado . $fecha_emision);
        
        $stmt = $conexion->prepare("
            INSERT INTO certificados 
            (rfc, numero_certificado, numero_registro, nombre_razon_social, representante_apoderado, 
             telefono, capital_contable, fecha_emision, fecha_vigencia, refrendo, papeleria_correcta, 
             hash_validacion, fecha_expedicion, vigente) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $stmt->bind_param("sssssssdsssss", 
            $rfc_certificado, $numero_certificado, $numero_registro, $nombre_razon_social, 
            $representante_apoderado, $telefono, $capital_contable, $fecha_emision, 
            $fecha_vigencia, $refrendo, $papeleria_correcta, $hash_validacion, $fecha_emision);
        
        if ($stmt->execute()) {
            $mensaje = '<div class="alert alert-success">Certificado del Padrón de Contratistas creado exitosamente.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error al crear el certificado: ' . $conexion->error . '</div>';
        }
    }
}

// Obtener datos del registro seleccionado si existe
$datos_registro = null;
if (isset($_GET['rfc'])) {
    $rfc_sel = $_GET['rfc'];
    $is_moral = (strlen($rfc_sel) === 12);
    
    if ($is_moral) {
        $stmt = $conexion->prepare("SELECT rfc, nombre_empresa as nombre, rep_nombre as representante, calle, colonia, municipio, estado, cp, telefono, capital, imss, infonavit, reg_cmic as regCmic, especialidad, 'moral' as tipo FROM persona_moral WHERE rfc = ?");
    } else {
        $stmt = $conexion->prepare("SELECT rfc, nombre, nombre as representante, calle, colonia, municipio, estado, cp, telefono, capital, imss, infonavit, regCmic, especialidad, 'fisica' as tipo FROM persona_fisica WHERE rfc = ?");
    }
    
    $stmt->bind_param("s", $rfc_sel);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        $datos_registro = $resultado->fetch_assoc();
    }
}

// Obtener lista de certificados con nombres de ambas tablas
$stmt = $conexion->prepare("
    SELECT c.*, COALESCE(pf.nombre, pm.nombre_empresa) as nombre_contratista
    FROM certificados c 
    LEFT JOIN persona_fisica pf ON c.rfc = pf.rfc 
    LEFT JOIN persona_moral pm ON c.rfc = pm.rfc 
    ORDER BY c.fecha_emision DESC
");
$stmt->execute();
$certificados = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Administración de Certificados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">Administración de Certificados</h2>
                
                <?php if ($mensaje): ?>
                    <?php echo $mensaje; ?>
                <?php endif; ?>
                
                <!-- Selección de Registro -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Seleccionar Registro para Certificado</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Obtener lista de registros (Persona Física y Moral) sin certificado vigente
                        $stmt_registros = $conexion->prepare("
                            SELECT t.rfc, t.nombre, t.tipo, c.id as tiene_certificado 
                            FROM (
                                SELECT rfc, nombre, 'fisica' as tipo FROM persona_fisica
                                UNION
                                SELECT rfc, nombre_empresa as nombre, 'moral' as tipo FROM persona_moral
                            ) t
                            LEFT JOIN certificados c ON t.rfc = c.rfc AND c.vigente = 1
                            WHERE c.id IS NULL
                            ORDER BY t.nombre
                        ");
                        $stmt_registros->execute();
                        $registros = $stmt_registros->get_result();
                        ?>
                        
                        <form method="POST" class="mb-3">
                            <div class="row">
                                <div class="col-md-8">
                                    <select class="form-select" name="rfc_seleccionado" required>
                                        <option value="">-- Selecciona un registro --</option>
                                        <?php while ($registro = $registros->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($registro['rfc']); ?>" 
                                                <?php echo (isset($_GET['rfc']) && $_GET['rfc'] == $registro['rfc']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($registro['nombre'] . ' (' . strtoupper($registro['tipo']) . ') - ' . $registro['rfc']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="seleccionar_registro" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Cargar Datos
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <?php if ($datos_registro): ?>
                            <div class="alert alert-info">
                                <strong>Registro Seleccionado:</strong> (<?php echo strtoupper($datos_registro['tipo']); ?>)<br>
                                <strong>Nombre/Razón Social:</strong> <?php echo htmlspecialchars($datos_registro['nombre']); ?><br>
                                <strong>RFC:</strong> <?php echo htmlspecialchars($datos_registro['rfc']); ?><br>
                                <strong>Especialidad:</strong> <?php echo htmlspecialchars($datos_registro['especialidad'] ?? 'N/A'); ?><br>
                                <strong>Dirección:</strong> <?php echo htmlspecialchars($datos_registro['calle'] . ', ' . $datos_registro['colonia'] . ', ' . $datos_registro['municipio'] . ', ' . $datos_registro['estado']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Formulario para crear certificado -->
                <?php if ($datos_registro): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Crear Nuevo Certificado - PADRÓN DE CONTRATISTAS</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <!-- Datos básicos -->
                            <h6 class="text-primary mb-3">Datos Básicos</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="rfc_certificado" class="form-label">RFC</label>
                                        <input type="text" class="form-control" id="rfc_certificado" name="rfc_certificado" 
                                               value="<?php echo htmlspecialchars($datos_registro['rfc']); ?>" 
                                               required readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="numero_certificado" class="form-label">Número de Certificado</label>
                                        <input type="text" class="form-control" id="numero_certificado" name="numero_certificado" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="numero_registro" class="form-label">No. de Registro</label>
                                        <input type="text" class="form-control" id="numero_registro" name="numero_registro" placeholder="010/25">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Datos de la empresa -->
                            <h6 class="text-primary mb-3 mt-4">Datos de la Empresa / Contratista</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nombre_razon_social" class="form-label">Nombre o Razón Social</label>
                                        <input type="text" class="form-control" id="nombre_razon_social" name="nombre_razon_social" 
                                               value="<?php echo htmlspecialchars($datos_registro['nombre']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="representante_apoderado" class="form-label">Representante o Apoderado Legal</label>
                                        <input type="text" class="form-control" id="representante_apoderado" name="representante_apoderado"
                                               value="<?php echo htmlspecialchars($datos_registro['representante'] ?? ''); ?>" 
                                               placeholder="Nombre del representante">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="text" class="form-control" id="telefono" name="telefono" 
                                               value="<?php echo htmlspecialchars($datos_registro['telefono']); ?>"
                                               placeholder="(871) 715 20 67">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="capital_contable" class="form-label">Capital Contable</label>
                                        <input type="number" class="form-control" id="capital_contable" name="capital_contable" step="0.01" 
                                               value="<?php echo htmlspecialchars($datos_registro['capital']); ?>"
                                               placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Domicilio -->
                            <div class="mb-3">
                                <label class="form-label">Domicilio (Base de datos)</label>
                                <div class="form-control-plaintext bg-light p-2 border rounded">
                                    <?php echo htmlspecialchars($datos_registro['calle'] . ' ' . $datos_registro['colonia'] . ', ' . $datos_registro['municipio'] . ', ' . $datos_registro['estado'] . '. C.P. ' . $datos_registro['cp']); ?>
                                </div>
                            </div>
                            
                            <!-- Datos fiscales y laborales -->
                            <h6 class="text-primary mb-3 mt-4">Datos Fiscales y Laborales</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="imss" class="form-label">IMSS</label>
                                        <input type="text" class="form-control" id="imss" name="imss" 
                                               value="<?php echo htmlspecialchars($datos_registro['imss']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="infonavit" class="form-label">INFONAVIT</label>
                                        <input type="text" class="form-control" id="infonavit" name="infonavit" 
                                               value="<?php echo htmlspecialchars($datos_registro['infonavit']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="camara" class="form-label">CÁMARA (CMIC)</label>
                                        <input type="text" class="form-control" id="camara" name="camara" 
                                               value="<?php echo htmlspecialchars($datos_registro['regCmic']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Datos de registro -->
                            <h6 class="text-primary mb-3 mt-4">Vigencia del Certificado</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="fecha_emision" class="form-label">Fecha de Emisión</label>
                                        <input type="date" class="form-control" id="fecha_emision" name="fecha_emision" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="fecha_vigencia" class="form-label">Fecha de Vigencia</label>
                                        <input type="date" class="form-control" id="fecha_vigencia" name="fecha_vigencia" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="refrendo" class="form-label">Refrendo (Opcional)</label>
                                        <input type="date" class="form-control" id="refrendo" name="refrendo">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Estado de papelería -->
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="papeleria_correcta" name="papeleria_correcta" checked>
                                    <label class="form-check-label" for="papeleria_correcta">
                                        Papelería Correcta y Validada
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-block">
                                <button type="submit" name="crear_certificado" class="btn btn-success">
                                    <i class="fas fa-certificate"></i> Generar y Guardar Certificado
                                </button>
                                <a href="admin_certificados.php" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="card mb-4 border-primary">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x text-primary mb-3"></i>
                        <h5 class="text-primary">Listo para emitir certificados</h5>
                        <p class="text-muted">Seleccione un contratista del listado superior para cargar su información y generar el documento oficial.</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Lista de certificados -->
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Certificados Emitidos Recientemente</h5>
                        <span class="badge bg-secondary"><?php echo $certificados->num_rows; ?> Registros</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>RFC</th>
                                        <th>Nombre / Razón Social</th>
                                        <th>No. Certificado</th>
                                        <th>Emisión</th>
                                        <th>Vigencia</th>
                                        <th>Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($certificados->num_rows > 0): ?>
                                        <?php while ($cert = $certificados->fetch_assoc()): ?>
                                            <tr>
                                                <td class="small fw-bold"><?php echo htmlspecialchars($cert['rfc']); ?></td>
                                                <td><?php echo htmlspecialchars($cert['nombre_contratista'] ?? 'CONTRATISTA NO ENCONTRADO'); ?></td>
                                                <td><?php echo htmlspecialchars($cert['numero_certificado']); ?></td>
                                                <td class="small"><?php echo date('d/m/Y', strtotime($cert['fecha_emision'])); ?></td>
                                                <td class="small"><?php echo date('d/m/Y', strtotime($cert['fecha_vigencia'])); ?></td>
                                                <td>
                                                    <?php if ($cert['vigente'] && date('Y-m-d') <= $cert['fecha_vigencia']): ?>
                                                        <span class="badge bg-success">Vigente</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Vencido</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="generar_certificado_simple.php?id=<?php echo $cert['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">No se han emitido certificados aún.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 mb-5">
                    <a href="../../../index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Volver al Inicio
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

</html>
