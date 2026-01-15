<!-- dashboard.php -->
<?php 
include("proteger.php");
include("conexion.php");

$rfc = $_SESSION['rfc'];
$mensaje = '';
$modo_edicion = false;
$persona = null;

// Verificar si existe en persona_fisica
$stmt = $conexion->prepare("SELECT * FROM persona_fisica WHERE rfc = ?");
$stmt->bind_param("s", $rfc);
$stmt->execute();
$resultado = $stmt->get_result();
$existe_persona = $resultado->num_rows > 0;

// Cargar datos del usuario si existe
if ($existe_persona) {
    $persona = $resultado->fetch_assoc();
}

// Procesar formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && $_POST['accion'] === 'editar') {
        // Modo edición - cargar datos existentes
        $modo_edicion = true;
    } elseif (isset($_POST['accion']) && $_POST['accion'] === 'guardar') {
        // Guardar cambios
        $nombre = isset($_POST['nombre']) ? mb_strtoupper(trim($_POST['nombre']), 'UTF-8') : '';
        $calle = isset($_POST['calle']) ? mb_strtoupper(trim($_POST['calle']), 'UTF-8') : '';
        $cp = isset($_POST['cp']) ? trim($_POST['cp']) : '';
        $colonia = isset($_POST['colonia']) ? mb_strtoupper(trim($_POST['colonia']), 'UTF-8') : '';
        $municipio = isset($_POST['municipio']) ? mb_strtoupper(trim($_POST['municipio']), 'UTF-8') : '';
        $estado = isset($_POST['estado']) ? mb_strtoupper(trim($_POST['estado']), 'UTF-8') : '';
        $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
        $celular = isset($_POST['celular']) ? trim($_POST['celular']) : '';
        $documento = isset($_POST['documento']) ? mb_strtoupper(trim($_POST['documento']), 'UTF-8') : '';
        $numero_documento = isset($_POST['numero_documento']) ? mb_strtoupper(trim($_POST['numero_documento']), 'UTF-8') : '';
        $imss = isset($_POST['imss']) ? mb_strtoupper(trim($_POST['imss']), 'UTF-8') : '';
        $infonavit = isset($_POST['infonavit']) ? mb_strtoupper(trim($_POST['infonavit']), 'UTF-8') : '';
        $capital = isset($_POST['capital']) ? str_replace(',', '', trim($_POST['capital'])) : '';
        $regCmic = isset($_POST['regCmic']) ? mb_strtoupper(trim($_POST['regCmic']), 'UTF-8') : '';
        $especialidad = isset($_POST['especialidad']) ? mb_strtoupper(trim($_POST['especialidad']), 'UTF-8') : '';
        $descripcion = isset($_POST['descripcion']) ? mb_strtoupper(trim($_POST['descripcion']), 'UTF-8') : '';
        
        // Validar campos requeridos
        if (empty($nombre) || empty($calle) || empty($cp) || empty($colonia) || empty($municipio) || empty($estado)) {
            $mensaje = '<div class="alert alert-warning">Por favor, completa todos los campos obligatorios.</div>';
        } elseif (strlen($cp) != 5 || !is_numeric($cp)) {
            $mensaje = '<div class="alert alert-warning">El código postal debe tener 5 dígitos numéricos.</div>';
        } elseif (strlen($telefono) != 10 || !is_numeric($telefono)) {
            $mensaje = '<div class="alert alert-warning">El teléfono debe tener 10 dígitos numéricos.</div>';
        } elseif (strlen($celular) != 10 || !is_numeric($celular)) {
            $mensaje = '<div class="alert alert-warning">El celular debe tener 10 dígitos numéricos.</div>';
        } elseif (!empty($capital) && !preg_match('/^\d+(\.\d{1,2})?$/', $capital)) {
            $mensaje = '<div class="alert alert-warning">El capital debe ser un valor numérico válido (ejemplo: 1000 o 1000.50).</div>';
        } else {
            // Actualizar información
            $stmt = $conexion->prepare("UPDATE persona_fisica SET nombre = ?, calle = ?, cp = ?, colonia = ?, municipio = ?, estado = ?, telefono = ?, celular = ?, documento = ?, numero_documento = ?, imss = ?, infonavit = ?, capital = ?, regCmic = ?, especialidad = ?, descripcion = ? WHERE rfc = ?");
            $stmt->bind_param("sssssssssssssssss", $nombre, $calle, $cp, $colonia, $municipio, $estado, $telefono, $celular, $documento, $numero_documento, $imss, $infonavit, $capital, $regCmic, $especialidad, $descripcion, $rfc);
            
            if ($stmt->execute()) {
                $mensaje = '<div class="alert alert-success">Información actualizada exitosamente.</div>';
                $existe_persona = true;
                $modo_edicion = false;
                // Recargar datos
                $stmt = $conexion->prepare("SELECT * FROM persona_fisica WHERE rfc = ?");
                $stmt->bind_param("s", $rfc);
                $stmt->execute();
                $resultado = $stmt->get_result();
                $persona = $resultado->fetch_assoc();
            } else {
                $mensaje = '<div class="alert alert-danger">Error al actualizar la información: ' . $conexion->error . '</div>';
            }
        }
    } else {
        // Procesar formulario de nuevo registro
        $nombre = isset($_POST['nombre']) ? mb_strtoupper(trim($_POST['nombre']), 'UTF-8') : '';
        $calle = isset($_POST['calle']) ? mb_strtoupper(trim($_POST['calle']), 'UTF-8') : '';
        $cp = isset($_POST['cp']) ? trim($_POST['cp']) : '';
        $colonia = isset($_POST['colonia']) ? mb_strtoupper(trim($_POST['colonia']), 'UTF-8') : '';
        $municipio = isset($_POST['municipio']) ? mb_strtoupper(trim($_POST['municipio']), 'UTF-8') : '';
        $estado = isset($_POST['estado']) ? mb_strtoupper(trim($_POST['estado']), 'UTF-8') : '';
        $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
        $celular = isset($_POST['celular']) ? trim($_POST['celular']) : '';
        $documento = isset($_POST['documento']) ? mb_strtoupper(trim($_POST['documento']), 'UTF-8') : '';
        $numero_documento = isset($_POST['numero_documento']) ? mb_strtoupper(trim($_POST['numero_documento']), 'UTF-8') : '';
        $imss = isset($_POST['imss']) ? mb_strtoupper(trim($_POST['imss']), 'UTF-8') : '';
        $infonavit = isset($_POST['infonavit']) ? mb_strtoupper(trim($_POST['infonavit']), 'UTF-8') : '';
        $capital = isset($_POST['capital']) ? str_replace(',', '', trim($_POST['capital'])) : '';
        $regCmic = isset($_POST['regCmic']) ? mb_strtoupper(trim($_POST['regCmic']), 'UTF-8') : '';
        $especialidad = isset($_POST['especialidad']) ? mb_strtoupper(trim($_POST['especialidad']), 'UTF-8') : '';
        $descripcion = isset($_POST['descripcion']) ? mb_strtoupper(trim($_POST['descripcion']), 'UTF-8') : '';
        
        // Validar campos requeridos
        if (empty($nombre) || empty($calle) || empty($cp) || empty($colonia) || empty($municipio) || empty($estado)) {
            $mensaje = '<div class="alert alert-warning">Por favor, completa todos los campos obligatorios.</div>';
        } elseif (strlen($cp) != 5 || !is_numeric($cp)) {
            $mensaje = '<div class="alert alert-warning">El código postal debe tener 5 dígitos numéricos.</div>';
        } elseif (strlen($telefono) != 10 || !is_numeric($telefono)) {
            $mensaje = '<div class="alert alert-warning">El teléfono debe tener 10 dígitos numéricos.</div>';
        } elseif (strlen($celular) != 10 || !is_numeric($celular)) {
            $mensaje = '<div class="alert alert-warning">El celular debe tener 10 dígitos numéricos.</div>';
        } elseif (!empty($capital) && !preg_match('/^\d+(\.\d{1,2})?$/', $capital)) {
            $mensaje = '<div class="alert alert-warning">El capital debe ser un valor numérico válido (ejemplo: 1000 o 1000.50).</div>';
        } else {
            // Insertar en persona_fisica
            $stmt = $conexion->prepare("INSERT INTO persona_fisica (rfc, nombre, calle, cp, colonia, municipio, estado, telefono, celular, documento, numero_documento, imss, infonavit, capital, regCmic, especialidad, descripcion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssssssss", $rfc, $nombre, $calle, $cp, $colonia, $municipio, $estado, $telefono, $celular, $documento, $numero_documento, $imss, $infonavit, $capital, $regCmic, $especialidad, $descripcion);
            
            if ($stmt->execute()) {
                $mensaje = '<div class="alert alert-success">Información registrada exitosamente.</div>';
                $existe_persona = true;
                // Recargar datos
                $stmt = $conexion->prepare("SELECT * FROM persona_fisica WHERE rfc = ?");
                $stmt->bind_param("s", $rfc);
                $stmt->execute();
                $resultado = $stmt->get_result();
                $persona = $resultado->fetch_assoc();
            } else {
                $mensaje = '<div class="alert alert-danger">Error al registrar la información: ' . $conexion->error . '</div>';
            }
        }
    }
}

// Función para obtener valor seguro de array
function getValue($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Panel - Padrón de Contratistas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
</head>
<body class="bg-light min-vh-100 d-flex flex-column align-items-center justify-content-start">
    <div class="text-center my-4">
        <a href="index.php"><img src="<?php echo BASE_URL; ?>/img/logo_secope.png" alt="Logo SECOPE" style="max-height:100px;"></a>
    </div>
    <div class="container bg-white rounded-4 shadow p-4 mb-4" style="max-width:800px;">
        <h2 class="mb-3 text-primary">Bienvenido, <?php echo htmlspecialchars($rfc); ?></h2>
        
        <?php if ($mensaje): ?>
            <?php echo $mensaje; ?>
        <?php endif; ?>
        
        <?php if ($existe_persona && !$modo_edicion && $persona): ?>
            <!-- Mostrar información del usuario -->
            <div class="row">
                <div class="col-md-6">
                    <h4 class="text-primary">Información Personal</h4>
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars(getValue($persona, 'nombre')); ?></p>
                    <p><strong>RFC:</strong> <?php echo htmlspecialchars(getValue($persona, 'rfc')); ?></p>
                    <p><strong>Especialidad:</strong> <?php echo htmlspecialchars(getValue($persona, 'especialidad')); ?></p>
                </div>
                <div class="col-md-6">
                    <h4 class="text-primary">Información de Contacto</h4>
                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars(getValue($persona, 'telefono')); ?></p>
                    <p><strong>Celular:</strong> <?php echo htmlspecialchars(getValue($persona, 'celular')); ?></p>
                    <p><strong>Dirección:</strong> <?php echo htmlspecialchars(getValue($persona, 'calle') . ', ' . getValue($persona, 'colonia') . ', ' . getValue($persona, 'municipio') . ', ' . getValue($persona, 'estado') . ' CP: ' . getValue($persona, 'cp')); ?></p>
                </div>
            </div>
            <!-- Sección de Estado del Certificado -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Estado del Certificado</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Verificar estado del certificado desde la tabla certificados
                            $stmt = $conexion->prepare("
                                SELECT * FROM certificados 
                                WHERE rfc = ? 
                                ORDER BY fecha_emision DESC 
                                LIMIT 1
                            ");
                            $stmt->bind_param("s", $rfc);
                            $stmt->execute();
                            $resultado_cert = $stmt->get_result();
                            
                            $puede_imprimir = false;
                            $mensaje_certificado = '';
                            $certificado_data = null;
                            
                            if ($resultado_cert->num_rows > 0) {
                                $certificado_data = $resultado_cert->fetch_assoc();
                                
                                if ($certificado_data['papeleria_correcta'] && $certificado_data['vigente']) {
                                    $fecha_vigencia = $certificado_data['fecha_vigencia'];
                                    $hoy = date('Y-m-d');
                                    
                                    if ($hoy <= $fecha_vigencia) {
                                        $puede_imprimir = true;
                                        $mensaje_certificado = '<div class="alert alert-success"><strong>✓ Certificado Disponible</strong><br>Número: ' . htmlspecialchars($certificado_data['numero_certificado']) . '<br>Vigente hasta: ' . date('d/m/Y', strtotime($fecha_vigencia)) . '</div>';
                                    } else {
                                        $mensaje_certificado = '<div class="alert alert-warning"><strong>⚠ Certificado Vencido</strong><br>Número: ' . htmlspecialchars($certificado_data['numero_certificado']) . '<br>Expiró el: ' . date('d/m/Y', strtotime($fecha_vigencia)) . '</div>';
                                    }
                                } else {
                                    $mensaje_certificado = '<div class="alert alert-danger"><strong>✗ Papelería Pendiente</strong><br>Tu papelería aún no ha sido validada.</div>';
                                }
                            } else {
                                $mensaje_certificado = '<div class="alert alert-info"><strong>ℹ Sin Certificado</strong><br>No tienes un certificado registrado aún.</div>';
                            }
                            
                            echo $mensaje_certificado;
                            ?>
                            
                            <div class="text-center mt-3">
                                <?php if ($puede_imprimir): ?>
                                    <a href="generar_certificado_simple.php" class="btn btn-success btn-lg" target="_blank">
                                        <i class="fas fa-file-pdf"></i> IMPRIMIR CERTIFICADO
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-lg" disabled>
                                        <i class="fas fa-file-pdf"></i> IMPRIMIR CERTIFICADO
                                    </button>
                                    <p class="text-muted mt-2">El certificado no está disponible</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-3 d-flex justify-content-between">
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="accion" value="editar">
                    <button type="submit" class="btn btn-primary">Editar información</button>
                </form>
                <a href="logout.php" class="btn btn-danger">Cerrar sesión</a>
            </div>
        <?php elseif ($existe_persona && !$modo_edicion && !$persona): ?>
            <div class="alert alert-warning">
                <strong>Error:</strong> No se pudo cargar la información del usuario.
            </div>
            <div class="mt-3">
                <a href="logout.php" class="btn btn-danger">Cerrar sesión</a>
            </div>
        <?php else: ?>
            <!-- Formulario para capturar/editar información -->
            <h4 class="text-primary mb-3"><?php echo $modo_edicion ? 'Editar información personal' : 'Complete su información personal'; ?></h4>
            <form method="POST" action="">
                <input type="hidden" name="accion" value="<?php echo $modo_edicion ? 'guardar' : ''; ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-secondary">Información Personal</h5>
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre completo *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $modo_edicion ? htmlspecialchars(getValue($persona, 'nombre')) : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="especialidad" class="form-label">Especialidad</label>
                            <input type="text" class="form-control" id="especialidad" name="especialidad" value="<?php echo $modo_edicion ? htmlspecialchars(getValue($persona, 'especialidad')) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="documento" class="form-label">Documento de Identificación</label>
                            <select class="form-select" id="documento" name="documento">
                                <option value="">Seleccione una opción</option>
                                <option value="Cédula Profesional" <?php echo ($modo_edicion && getValue($persona, 'documento') == 'Cédula Profesional') ? 'selected' : ''; ?>>Cédula Profesional</option>
                                <option value="INE" <?php echo ($modo_edicion && getValue($persona, 'documento') == 'INE') ? 'selected' : ''; ?>>INE</option>
                                <option value="Pasaporte" <?php echo ($modo_edicion && getValue($persona, 'documento') == 'Pasaporte') ? 'selected' : ''; ?>>Pasaporte</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="numero_documento" class="form-label">Número del Documento</label>
                            <input type="text" class="form-control" id="numero_documento" name="numero_documento" value="<?php echo $modo_edicion ? htmlspecialchars(getValue($persona, 'numero_documento')) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-secondary">Información de Contacto</h5>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono *</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" maxlength="10" pattern="[0-9]{10}" value="<?php echo $modo_edicion ? htmlspecialchars(getValue($persona, 'telefono')) : ''; ?>" required>
                            <div class="form-text">10 dígitos sin espacios ni guiones</div>
                        </div>
                        <div class="mb-3">
                            <label for="celular" class="form-label">Celular *</label>
                            <input type="tel" class="form-control" id="celular" name="celular" maxlength="10" pattern="[0-9]{10}" value="<?php echo $modo_edicion ? htmlspecialchars(getValue($persona, 'celular')) : ''; ?>" required>
                            <div class="form-text">10 dígitos sin espacios ni guiones</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-secondary">Dirección</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cp" class="form-label">Código Postal *</label>
                                    <input type="text" class="form-control" id="cp" name="cp" maxlength="5" pattern="[0-9]{5}" value="<?php echo $modo_edicion ? htmlspecialchars(getValue($persona, 'cp')) : ''; ?>" required>
                                    <div class="form-text">Ingresa el código postal para autocompletar</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="calle" class="form-label">Calle y número *</label>
                                    <input type="text" class="form-control" id="calle" name="calle" value="<?php echo $modo_edicion ? htmlspecialchars(getValue($persona, 'calle')) : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="estado" class="form-label">Estado *</label>
                                    <input type="text" class="form-control" id="estado" name="estado" value="<?php echo $modo_edicion ? htmlspecialchars(getValue($persona, 'estado')) : ''; ?>" <?php echo $modo_edicion ? '' : 'readonly'; ?> required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="municipio" class="form-label">Municipio *</label>
                                    <input type="text" class="form-control" id="municipio" name="municipio" value="<?php echo $modo_edicion ? htmlspecialchars(getValue($persona, 'municipio')) : ''; ?>" <?php echo $modo_edicion ? '' : 'readonly'; ?> required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="colonia" class="form-label">Colonia *</label>
                                    <select class="form-control" id="colonia" name="colonia" required>
                                        <?php if ($modo_edicion): ?>
                                            <option value="<?php echo htmlspecialchars(getValue($persona, 'colonia')); ?>" selected><?php echo htmlspecialchars(getValue($persona, 'colonia')); ?></option>
                                        <?php else: ?>
                                            <option value="">Selecciona una colonia</option>
                                        <?php endif; ?>
                                    </select>
                                    <input type="hidden" id="colonia-actual" value="<?php echo $modo_edicion ? htmlspecialchars(getValue($persona, 'colonia')) : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-secondary">Información Laboral</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="imss" class="form-label">IMSS</label>
                                    <input type="text" class="form-control" id="imss" name="imss" value="<?php echo $modo_edicion ? htmlspecialchars(getValue($persona, 'imss')) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="infonavit" class="form-label">Infonavit</label>
                                    <input type="text" class="form-control" id="infonavit" name="infonavit" value="<?php echo $modo_edicion ? htmlspecialchars(getValue($persona, 'infonavit')) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="capital" class="form-label">Capital Contable</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control text-end" id="capital" name="capital" placeholder="0.00" value="<?php echo $modo_edicion ? number_format((float)getValue($persona, 'capital'), 2, '.', ',') : ''; ?>">
                                    </div>
                                    <div class="form-text">Ejemplo: 1,000,000.00</div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="regCmic" class="form-label">Registro CMIC</label>
                            <input type="text" class="form-control" id="regCmic" name="regCmic" value="<?php echo $modo_edicion ? htmlspecialchars(getValue($persona, 'regCmic')) : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="4"><?php echo $modo_edicion ? htmlspecialchars(getValue($persona, 'descripcion')) : ''; ?></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary"><?php echo $modo_edicion ? 'Guardar cambios' : 'Guardar información'; ?></button>
                    <?php if ($modo_edicion): ?>
                        <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-danger">Cerrar sesión</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Autocompletado de códigos postales (funciona tanto en nuevo registro como en edición)
        document.addEventListener('DOMContentLoaded', function() {
            const cpInput = document.getElementById('cp');
            const estadoInput = document.getElementById('estado');
            const municipioInput = document.getElementById('municipio');
            const coloniaSelect = document.getElementById('colonia');
            const coloniaActual = document.getElementById('colonia-actual');
            
            // Verificar si estamos en modo edición
            const modoEdicion = <?php echo $modo_edicion ? 'true' : 'false'; ?>;
            
            // Función para limpiar campos
            function limpiarCampos() {
                estadoInput.value = '';
                municipioInput.value = '';
                if (coloniaSelect) {
                    coloniaSelect.innerHTML = '<option value="">Selecciona una colonia</option>';
                }
            }
            
            // Función para buscar código postal
            function buscarCodigoPostal(cp) {
                if (cp.length === 5 && /^\d{5}$/.test(cp)) {
                    // Mostrar indicador de carga
                    if (coloniaSelect) {
                        coloniaSelect.innerHTML = '<option value="">Buscando...</option>';
                    }
                    estadoInput.value = 'Cargando...';
                    municipioInput.value = 'Cargando...';
                    
                    // Hacer petición AJAX
                    fetch(`buscar_cp.php?cp=${cp}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Llenar campos
                                estadoInput.value = data.estado;
                                municipioInput.value = data.municipio;
                                
                                // Hacer campos de solo lectura
                                estadoInput.readOnly = true;
                                municipioInput.readOnly = true;
                                
                                // Llenar select de colonias
                                if (coloniaSelect) {
                                    coloniaSelect.innerHTML = '<option value="">Selecciona una colonia</option>';
                                    
                                    if (data.colonias && data.colonias.length > 0) {
                                        data.colonias.forEach(colonia => {
                                            const option = document.createElement('option');
                                            option.value = colonia;
                                            option.textContent = colonia;
                                            coloniaSelect.appendChild(option);
                                        });
                                        
                                        // Si estamos en modo edición, seleccionar la colonia actual
                                        if (modoEdicion && coloniaActual && coloniaActual.value) {
                                            const currentColonia = coloniaActual.value;
                                            
                                            // Buscar y seleccionar la opción
                                            for (let i = 0; i < coloniaSelect.options.length; i++) {
                                                if (coloniaSelect.options[i].value === currentColonia) {
                                                    coloniaSelect.options[i].selected = true;
                                                    break;
                                                }
                                            }
                                        }
                                    } else {
                                        coloniaSelect.innerHTML = '<option value="">No hay colonias disponibles</option>';
                                    }
                                }
                            } else {
                                // Mostrar error
                                limpiarCampos();
                                if (coloniaSelect) {
                                    coloniaSelect.innerHTML = '<option value="">Código postal no encontrado</option>';
                                }
                                alert('Código postal no encontrado en la base de datos');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            limpiarCampos();
                            if (coloniaSelect) {
                                coloniaSelect.innerHTML = '<option value="">Error al buscar</option>';
                            }
                            alert('Error al buscar el código postal. Verifica tu conexión.');
                        });
                } else if (cp.length > 0) {
                    // Si no es válido, limpiar campos
                    limpiarCampos();
                    estadoInput.readOnly = false;
                    municipioInput.readOnly = false;
                }
            }
            
            // Event listener para el campo CP
            cpInput.addEventListener('input', function() {
                const cp = this.value.trim();
                if (cp.length === 0) {
                    limpiarCampos();
                    estadoInput.readOnly = false;
                    municipioInput.readOnly = false;
                }
            });
            
            cpInput.addEventListener('blur', function() {
                const cp = this.value.trim();
                buscarCodigoPostal(cp);
            });
            
            // Event listener para permitir búsqueda con Enter
            cpInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const cp = this.value.trim();
                    buscarCodigoPostal(cp);
                }
            });
            
            // Si estamos en modo edición, cargar colonias al inicio si hay un CP válido
            if (modoEdicion && cpInput.value.length === 5 && /^\d{5}$/.test(cpInput.value)) {
                const cp = cpInput.value.trim();
                
                if (coloniaSelect) {
                    coloniaSelect.innerHTML = '<option value="">Cargando colonias...</option>';
                    
                    fetch(`buscar_cp.php?cp=${cp}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.colonias && data.colonias.length > 0) {
                                coloniaSelect.innerHTML = '<option value="">Selecciona una colonia</option>';
                                
                                data.colonias.forEach(colonia => {
                                    const option = document.createElement('option');
                                    option.value = colonia;
                                    option.textContent = colonia;
                                    coloniaSelect.appendChild(option);
                                });
                                
                                // Seleccionar la colonia actual
                                if (coloniaActual && coloniaActual.value) {
                                    const currentColonia = coloniaActual.value.trim().toUpperCase();
                                    
                                    // Buscar y seleccionar la opción
                                    for (let i = 0; i < coloniaSelect.options.length; i++) {
                                        if (coloniaSelect.options[i].value.trim().toUpperCase() === currentColonia) {
                                            coloniaSelect.options[i].selected = true;
                                            break;
                                        }
                                    }
                                }
                            } else {
                                coloniaSelect.innerHTML = '<option value="">No hay colonias disponibles</option>';
                            }
                        })
                        .catch(error => {
                            console.error('Error cargando colonias:', error);
                            coloniaSelect.innerHTML = '<option value="">Error al cargar colonias</option>';
                        });
                }
            }
            // Formato de moneda para Capital
            const capitalInput = document.getElementById('capital');
            
            if (capitalInput) {
                // Función para formatear moneda
                const formatCurrency = (value) => {
                    // Si está vacío, retornar vacío
                    if (value === '') return '';
                    
                    // Asegurar que es un número válido
                    let number = parseFloat(value.replace(/,/g, ''));
                    if (isNaN(number)) return '';
                    
                    // Formatear con 2 decimales y comas
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(number);
                };

                // Al perder el foco, formatear
                capitalInput.addEventListener('blur', function() {
                    this.value = formatCurrency(this.value);
                });

                // Al ganar el foco, quitar comas para editar
                capitalInput.addEventListener('focus', function() {
                    let val = this.value.replace(/,/g, '');
                    if (val !== '') {
                        // Si termina en .00, quitarlo para facilitar edición, opcional
                        // this.value = val.replace(/\.00$/, '');
                        this.value = val;
                    }
                });

                // Permitir solo números y punto decimal
                capitalInput.addEventListener('input', function(e) {
                    // Este input no debe convertirse a mayúsculas si el script global lo afecta
                    // Filtrar caracteres no numéricos
                    this.value = this.value.replace(/[^0-9.]/g, '');
                    
                    // Evitar múltiples puntos decimales
                    if ((this.value.match(/\./g) || []).length > 1) {
                         this.value = this.value.replace(/\.+$/, '');
                    }
                });
            }

            // Convertir a mayúsculas todos los inputs excepto correos, contraseñas y capital
            const textInputs = document.querySelectorAll('input:not([type="email"]):not([type="password"]):not([type="hidden"]):not([type="file"]):not(#capital), textarea');
            textInputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value) {
                        const start = this.selectionStart;
                        const end = this.selectionEnd;
                        this.value = this.value.toUpperCase();
                        this.setSelectionRange(start, end);
                    }
                });
            });
        });
    </script>
</body>
</html>