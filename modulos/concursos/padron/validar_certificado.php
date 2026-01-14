<?php
// validar_certificado.php - Módulo público para validar certificados
include("conexion.php");

$mensaje = '';
$certificado = null;
$valido = false;

// Procesar validación por hash
if (isset($_GET['hash'])) {
    $hash = $_GET['hash'];
    
    $stmt = $conexion->prepare("
        SELECT c.*, p.nombre, p.especialidad, p.calle, p.colonia, p.municipio, p.estado, p.cp 
        FROM certificados c 
        INNER JOIN persona_fisica p ON c.rfc = p.rfc 
        WHERE c.hash_validacion = ?
    ");
    
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $certificado = $resultado->fetch_assoc();
        
        // Verificar si está vigente
        $hoy = date('Y-m-d');
        if ($certificado['vigente'] && $certificado['papeleria_correcta'] && $hoy <= $certificado['fecha_vigencia']) {
            $valido = true;
            $mensaje = '<div class="alert alert-success"><strong>✓ CERTIFICADO VÁLIDO</strong><br>Este certificado está vigente y es auténtico.</div>';
        } else {
            $mensaje = '<div class="alert alert-warning"><strong>⚠ CERTIFICADO VENCIDO O INVÁLIDO</strong><br>Este certificado ha expirado o no es válido.</div>';
        }
    } else {
        $mensaje = '<div class="alert alert-danger"><strong>✗ CERTIFICADO NO ENCONTRADO</strong><br>El código de validación no corresponde a ningún certificado registrado.</div>';
    }
}

// Procesar validación por número de certificado
if (isset($_POST['validar_numero'])) {
    $numero_certificado = $_POST['numero_certificado'];
    
    $stmt = $conexion->prepare("
        SELECT c.*, p.nombre, p.especialidad, p.calle, p.colonia, p.municipio, p.estado, p.cp 
        FROM certificados c 
        INNER JOIN persona_fisica p ON c.rfc = p.rfc 
        WHERE c.numero_certificado = ?
    ");
    
    $stmt->bind_param("s", $numero_certificado);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $certificado = $resultado->fetch_assoc();
        
        // Verificar si está vigente
        $hoy = date('Y-m-d');
        if ($certificado['vigente'] && $certificado['papeleria_correcta'] && $hoy <= $certificado['fecha_vigencia']) {
            $valido = true;
            $mensaje = '<div class="alert alert-success"><strong>✓ CERTIFICADO VÁLIDO</strong><br>Este certificado está vigente y es auténtico.</div>';
        } else {
            $mensaje = '<div class="alert alert-warning"><strong>⚠ CERTIFICADO VENCIDO O INVÁLIDO</strong><br>Este certificado ha expirado o no es válido.</div>';
        }
    } else {
        $mensaje = '<div class="alert alert-danger"><strong>✗ CERTIFICADO NO ENCONTRADO</strong><br>El número de certificado no existe en nuestro sistema.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validador de Certificados - SECOPE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/pao/assets/css/styles.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
        }
        .validation-card {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: none;
        }
        .qr-validator {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .certificate-info {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 15px 0;
        }
        .status-badge {
            font-size: 1.2em;
            padding: 10px 20px;
            border-radius: 25px;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 fw-bold mb-3">
                        <i class="fas fa-shield-alt me-3"></i>
                        Validador de Certificados
                    </h1>
                    <p class="lead mb-0">
                        Verifica la autenticidad y vigencia de los certificados del Padrón de Contratistas del Estado de Durango
                    </p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-qrcode" style="font-size: 8rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Formulario de Validación -->
                <div class="card validation-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-search me-2"></i>Validar Certificado</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($mensaje): ?>
                            <?php echo $mensaje; ?>
                        <?php endif; ?>

                        <!-- Método 1: Por número de certificado -->
                        <form method="POST" class="mb-4">
                            <div class="row">
                                <div class="col-md-8">
                                    <label for="numero_certificado" class="form-label">Número de Certificado</label>
                                    <input type="text" class="form-control form-control-lg" 
                                           id="numero_certificado" name="numero_certificado" 
                                           placeholder="Ingresa el número de certificado"
                                           value="<?php echo isset($_POST['numero_certificado']) ? htmlspecialchars($_POST['numero_certificado']) : ''; ?>">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" name="validar_numero" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-search me-2"></i>Validar
                                    </button>
                                </div>
                            </div>
                        </form>

                        <hr class="my-4">

                        <!-- Método 2: Por QR -->
                        <div class="qr-validator text-center">
                            <h5><i class="fas fa-qrcode me-2"></i>Validación por Código QR</h5>
                            <p class="text-muted mb-3">
                                Escanea el código QR del certificado con tu dispositivo móvil para validarlo automáticamente
                            </p>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Instrucciones:</strong> Abre la cámara de tu teléfono y apunta al código QR del certificado
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Certificado (si se validó) -->
                <?php if ($certificado): ?>
                <div class="card validation-card">
                    <div class="card-header <?php echo $valido ? 'bg-success' : 'bg-warning'; ?> text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-certificate me-2"></i>
                            Información del Certificado
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Estado del certificado -->
                        <div class="text-center mb-4">
                            <?php if ($valido): ?>
                                <span class="badge bg-success status-badge">
                                    <i class="fas fa-check-circle me-2"></i>CERTIFICADO VÁLIDO
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning status-badge">
                                    <i class="fas fa-exclamation-triangle me-2"></i>CERTIFICADO VENCIDO
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Datos del certificado -->
                        <div class="certificate-info">
                            <h5><i class="fas fa-building me-2"></i>Datos de la Empresa</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Nombre/Razón Social:</strong><br><?php echo htmlspecialchars($certificado['nombre_razon_social']); ?></p>
                                    <p><strong>RFC:</strong> <?php echo htmlspecialchars($certificado['rfc']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Representante:</strong><br><?php echo htmlspecialchars($certificado['representante_apoderado']); ?></p>
                                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($certificado['telefono']); ?></p>
                                </div>
                            </div>
                            <p><strong>Domicilio:</strong><br><?php echo htmlspecialchars($certificado['calle'] . ' ' . $certificado['colonia'] . ', ' . $certificado['municipio'] . ', ' . $certificado['estado'] . '. CP: ' . $certificado['cp']); ?></p>
                        </div>

                        <!-- Datos del registro -->
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6><i class="fas fa-file-alt me-2"></i>Información del Registro</h6>
                                <p><strong>Número de Certificado:</strong> <?php echo htmlspecialchars($certificado['numero_certificado']); ?></p>
                                <p><strong>Número de Registro:</strong> <?php echo htmlspecialchars($certificado['numero_registro']); ?></p>
                                <p><strong>Fecha de Emisión:</strong> <?php echo date('d/m/Y', strtotime($certificado['fecha_emision'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-calendar-alt me-2"></i>Vigencia</h6>
                                <p><strong>Fecha de Vigencia:</strong> <?php echo date('d/m/Y', strtotime($certificado['fecha_vigencia'])); ?></p>
                                <p><strong>Refrendo:</strong> <?php echo $certificado['refrendo'] ? date('d/m/Y', strtotime($certificado['refrendo'])) : 'N/A'; ?></p>
                                <p><strong>Capital Contable:</strong> $<?php echo number_format($certificado['capital_contable'], 2); ?></p>
                            </div>
                        </div>

                        <!-- Datos fiscales -->
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <p><strong>IMSS:</strong> <?php echo htmlspecialchars($certificado['imss'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>INFONAVIT:</strong> <?php echo htmlspecialchars($certificado['infonavit'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>CÁMARA:</strong> <?php echo htmlspecialchars($certificado['camara'] ?? 'N/A'); ?></p>
                            </div>
                        </div>

                        <!-- Información adicional -->
                        <div class="alert alert-light mt-3">
                            <h6><i class="fas fa-info-circle me-2"></i>Información Adicional</h6>
                            <p><strong>Autoridad Emisora:</strong> <?php echo htmlspecialchars($certificado['autoridad_emisora']); ?></p>
                            <p><strong>Lugar de Expedición:</strong> <?php echo htmlspecialchars($certificado['lugar_expedicion']); ?></p>
                            <p><strong>Hash de Validación:</strong> <code><?php echo htmlspecialchars($certificado['hash_validacion']); ?></code></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Información del Sistema -->
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <h5><i class="fas fa-shield-alt me-2"></i>Sistema de Validación SECOPE</h5>
                        <p class="text-muted mb-0">
                            Este sistema permite verificar la autenticidad de los certificados emitidos por la 
                            Secretaría de Comunicaciones y Obras Públicas del Estado de Durango.
                        </p>
                        <hr>
                        <small class="text-muted">
                            <i class="fas fa-lock me-1"></i>
                            Validación segura mediante hash SHA-256
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
