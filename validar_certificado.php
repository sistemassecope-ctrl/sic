<?php
// validar_certificado.php - Módulo público para validar certificados
require_once 'config/db.php';

// Crear conexión mysqli
$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conexion->connect_error) {
    die('Error de conexión: ' . $conexion->connect_error);
}
$conexion->set_charset(DB_CHARSET);

$mensaje = '';
$certificado = null;
$valido = false;

// Consulta base con soporte para Física y Moral
$sql_base = "
    SELECT c.*, 
           COALESCE(pf.nombre, pm.nombre_empresa) as nombre_contratista,
           COALESCE(pf.especialidad, pm.especialidad) as especialidad_contratista,
           COALESCE(pf.calle, pm.calle) as calle_c,
           COALESCE(pf.colonia, pm.colonia) as colonia_c,
           COALESCE(pf.municipio, pm.municipio) as municipio_c,
           COALESCE(pf.estado, pm.estado) as estado_c,
           COALESCE(pf.cp, pm.cp) as cp_c,
           COALESCE(pf.telefono, pm.telefono) as telefono_c
    FROM certificados c 
    LEFT JOIN persona_fisica pf ON c.rfc = pf.rfc 
    LEFT JOIN persona_moral pm ON c.rfc = pm.rfc 
";

// Procesar validación por hash
if (isset($_GET['hash'])) {
    $hash = $_GET['hash'];
    $stmt = $conexion->prepare($sql_base . " WHERE c.hash_validacion = ?");
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
            $mensaje = '<div class="alert alert-warning"><strong>⚠ CERTIFICADO VENCIDO O INVÁLIDO</strong><br>Este certificado ha expirado o no es válido actualmente.</div>';
        }
    } else {
        $mensaje = '<div class="alert alert-danger"><strong>✗ CERTIFICADO NO ENCONTRADO</strong><br>El código de validación no corresponde a ningún certificado registrado.</div>';
    }
}

// Procesar validación por número de certificado
if (isset($_POST['validar_numero'])) {
    $numero_certificado = $_POST['numero_certificado'];
    $stmt = $conexion->prepare($sql_base . " WHERE c.numero_certificado = ?");
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
            $mensaje = '<div class="alert alert-warning"><strong>⚠ CERTIFICADO VENCIDO O INVÁLIDO</strong><br>Este certificado ha expirado o no es válido actualmente.</div>';
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
    <style>
        .hero-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 60px 0;
        }
        .validation-card {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: none;
            border-radius: 15px;
        }
        .qr-validator {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .certificate-info {
            background: #f0f7ff;
            border-left: 5px solid #0d6efd;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 10px 10px 0;
        }
        .status-badge {
            font-size: 1.4em;
            padding: 12px 30px;
            border-radius: 50px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="fas fa-check-shield me-3"></i>
                        Validador Oficial de Certificados
                    </h1>
                    <p class="lead mb-0">
                        Secretaría de Comunicaciones y Obras Públicas del Estado de Durango
                    </p>
                </div>
                <div class="col-md-4 text-center d-none d-md-block">
                    <i class="fas fa-qrcode" style="font-size: 7rem; opacity: 0.4;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Formulario de Validación -->
                <div class="card validation-card mb-4">
                    <div class="card-header bg-white border-0 pt-4 pb-0 text-center">
                        <h4 class="fw-bold text-dark">Búsqueda de Registro</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($mensaje): ?>
                            <div class="mb-4"><?php echo $mensaje; ?></div>
                        <?php endif; ?>

                        <!-- Método 1: Por número de certificado -->
                        <form method="POST" class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="numero_certificado" class="form-label fw-bold">Número de Certificado</label>
                                    <input type="text" class="form-control form-control-lg" 
                                           id="numero_certificado" name="numero_certificado" 
                                           placeholder="Ej: 001/2025"
                                           value="<?php echo isset($_POST['numero_certificado']) ? htmlspecialchars($_POST['numero_certificado']) : ''; ?>">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" name="validar_numero" class="btn btn-primary btn-lg w-100 shadow-sm">
                                        <i class="fas fa-search me-2"></i>Validar
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="d-flex align-items-center my-4">
                            <hr class="flex-grow-1">
                            <span class="mx-3 text-muted">O ESCANEE EL CÓDIGO QR</span>
                            <hr class="flex-grow-1">
                        </div>

                        <div class="qr-validator text-center">
                            <p class="text-muted mb-0">
                                Los certificados emitidos a partir de 2025 cuentan con un código QR único para validación instantánea.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Información del Certificado (si se validó) -->
                <?php if ($certificado): ?>
                <div class="card validation-card shadow">
                    <div class="card-header <?php echo $valido ? 'bg-success' : 'bg-danger'; ?> text-white text-center py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-info-circle me-2"></i>
                            DETALLES DEL REGISTRO
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <!-- Estado del certificado -->
                        <div class="text-center mb-4">
                            <?php if ($valido): ?>
                                <span class="badge bg-success status-badge">
                                    <i class="fas fa-check-circle me-2"></i>CERTIFICADO VIGENTE
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger status-badge">
                                    <i class="fas fa-times-circle me-2"></i>CERTIFICADO INVÁLIDO / VENCIDO
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Datos del contratista -->
                        <div class="certificate-info">
                            <h5 class="fw-bold mb-3"><i class="fas fa-building text-primary me-2"></i>Información del Contratista</h5>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="text-muted small text-uppercase fw-bold">Nombre o Razón Social</label>
                                    <div class="h5 fw-bold"><?php echo htmlspecialchars($certificado['nombre_contratista']); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small text-uppercase fw-bold">RFC</label>
                                    <div class="fw-bold"><?php echo htmlspecialchars($certificado['rfc']); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small text-uppercase fw-bold">Representante Legal</label>
                                    <div class="fw-bold"><?php echo htmlspecialchars($certificado['representante_apoderado'] ?: 'N/A'); ?></div>
                                </div>
                                <div class="col-12 mb-2">
                                    <label class="text-muted small text-uppercase fw-bold">Domicilio Fiscal Registrado</label>
                                    <div class="text-secondary"><?php echo htmlspecialchars($certificado['calle_c'] . ' ' . $certificado['colonia_c'] . ', ' . $certificado['municipio_c'] . ', ' . $certificado['estado_c'] . '. CP: ' . $certificado['cp_c']); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Datos del trámite -->
                        <div class="row mt-4">
                            <div class="col-md-6 border-end">
                                <h6 class="fw-bold"><i class="fas fa-id-card text-primary me-2"></i>Datos del Registro</h6>
                                <p class="mb-1"><strong>No. Certificado:</strong> <?php echo htmlspecialchars($certificado['numero_certificado']); ?></p>
                                <p class="mb-1"><strong>No. Registro:</strong> <?php echo htmlspecialchars($certificado['numero_registro']); ?></p>
                                <p class="mb-1"><strong>Fecha de Emisión:</strong> <?php echo date('d/m/Y', strtotime($certificado['fecha_emision'])); ?></p>
                            </div>
                            <div class="col-md-6 ps-md-4">
                                <h6 class="fw-bold"><i class="fas fa-calendar-check text-primary me-2"></i>Vigencia</h6>
                                <p class="mb-1 text-<?php echo $valido ? 'success' : 'danger'; ?>"><strong>Fecha de Vencimiento:</strong> <?php echo date('d/m/Y', strtotime($certificado['fecha_vigencia'])); ?></p>
                                <p class="mb-1"><strong>Refrendo:</strong> <?php echo $certificado['refrendo'] ? date('d/m/Y', strtotime($certificado['refrendo'])) : 'NO APLICA'; ?></p>
                                <p class="mb-1"><strong>Capital Contable:</strong> $<?php echo number_format($certificado['capital_contable'], 2); ?></p>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <div class="row text-center small text-muted">
                                <div class="col-4">
                                    <div class="fw-bold">IMSS</div>
                                    <div><?php echo htmlspecialchars($certificado['imss'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold">INFONAVIT</div>
                                    <div><?php echo htmlspecialchars($certificado['infonavit'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold">CÁMARA</div>
                                    <div><?php echo htmlspecialchars($certificado['camara'] ?? 'N/A'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light text-center py-3">
                        <small class="text-muted">
                            <i class="fas fa-fingerprint me-1"></i>
                            ID de Validación: <code><?php echo htmlspecialchars($certificado['hash_validacion']); ?></code>
                        </small>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Footer del validador -->
                <div class="text-center mt-5">
                    <p class="text-muted small">
                        &copy; <?php echo date('Y'); ?> Secretaría de Comunicaciones y Obras Públicas - Estado de Durango<br>
                        Desarrollado para la transparencia en las contrataciones públicas.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
