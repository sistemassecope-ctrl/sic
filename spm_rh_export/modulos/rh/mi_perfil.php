<?php
require_once '../../config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/user_sync.php';

requireAuth();

$currentUser = getCurrentUser();

// Limitar acceso a niveles 1-6
$nivelActual = (int) ($currentUser['nivel_id'] ?? 999);
if ($nivelActual < 1 || $nivelActual > 6) {
    redirectWithMessage('../../index.php', 'warning', 'No tienes permisos para acceder a esta sección.');
}

$pageTitle = 'Mi Perfil - SIC';
$breadcrumb = null;

$pdo = conectarDB();
$empleadoId = $currentUser['empleado_id'] ?? null;

if (!$empleadoId) {
    redirectWithMessage('../../index.php', 'warning', 'Tu usuario no está vinculado a un expediente de empleado.');
}

function obtenerColumnasEmpleadosCache(PDO $pdo, $forceRefresh = false) {
    static $cache = null;

    if ($forceRefresh || $cache === null) {
        try {
            $stmt = $pdo->query('SHOW COLUMNS FROM empleados');
            $cache = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        } catch (PDOException $e) {
            error_log('Error obteniendo columnas de empleados: ' . $e->getMessage());
            $cache = [];
        }
    }

    return $cache;
}

function obtenerEmpleadoActual(PDO $pdo, $empleadoId) {
    $campos = [
        'id', 'numero_empleado', 'nombres', 'apellido_paterno', 'apellido_materno',
        'telefono_celular', 'telefono_particular', 'email', 'email_institucional',
        'direccion', 'fraccionamiento_colonia', 'ultimo_grado_estudios', 'profesion',
        'curp', 'rfc', 'fecha_nacimiento'
    ];

    $columnas = obtenerColumnasEmpleadosCache($pdo);
    $camposValidos = array_values(array_intersect($campos, $columnas));

    if (empty($camposValidos)) {
        return null;
    }

    $sql = 'SELECT ' . implode(', ', $camposValidos) . ' FROM empleados WHERE id = ? LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$empleadoId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$empleado = obtenerEmpleadoActual($pdo, $empleadoId);
if (!$empleado) {
    redirectWithMessage('../../index.php', 'warning', 'No se encontró tu expediente de empleado.');
}

$errores = [];
$datosForm = [
    'telefono_celular' => $empleado['telefono_celular'] ?? '',
    'telefono_particular' => $empleado['telefono_particular'] ?? '',
    'email' => $empleado['email'] ?? '',
    // 'email_institucional' => $empleado['email_institucional'] ?? '', // Campo de solo lectura, no editable
    'direccion' => $empleado['direccion'] ?? '',
    'fraccionamiento_colonia' => $empleado['fraccionamiento_colonia'] ?? '',
    'ultimo_grado_estudios' => $empleado['ultimo_grado_estudios'] ?? '',
    'profesion' => $empleado['profesion'] ?? '',
    'fecha_nacimiento' => $empleado['fecha_nacimiento'] ?? '',
];

$requiereCambioPassword = (int) ($currentUser['requiere_cambio_password'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errores[] = 'La sesión ha expirado. Vuelve a intentar.';
    } else {
        foreach ($datosForm as $campo => $valor) {
            $valorPost = $_POST[$campo] ?? '';
            if (in_array($campo, ['email', 'email_institucional'], true)) {
                $datosForm[$campo] = trim($valorPost);
            } elseif ($campo === 'direccion') {
                $datosForm[$campo] = sanitizeText($valorPost);
            } else {
                $datosForm[$campo] = sanitizeText($valorPost);
            }
        }

        // Validar correo personal (login)
        if (empty($datosForm['email']) || !filter_var($datosForm['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Debes proporcionar un correo electrónico válido.';
        }
        // Validación de unicidad de correo deshabilitada temporalmente
        // else {
        //     $stmtCorreo = $pdo->prepare('SELECT id FROM usuarios_sistema WHERE email = ? AND id != ? LIMIT 1');
        //     $stmtCorreo->execute([$datosForm['email'], $currentUser['id']]);
        //     if ($stmtCorreo->fetch()) {
        //         $errores[] = 'El correo electrónico proporcionado ya está en uso por otro usuario.';
        //     }
        // }

        $nuevoPassword = $_POST['nuevo_password'] ?? '';
        $confirmPassword = $_POST['confirmar_password'] ?? '';
        // Cambio de contraseña solo obligatorio si el usuario ingresa datos en los campos
        $forcePasswordChange = $nuevoPassword !== '' || $confirmPassword !== '';

        if ($forcePasswordChange) {
            if ($nuevoPassword === '' || $confirmPassword === '') {
                $errores[] = 'Debes capturar y confirmar la nueva contraseña.';
            } elseif ($nuevoPassword !== $confirmPassword) {
                $errores[] = 'La confirmación de contraseña no coincide.';
            } elseif (!validarPasswordSegura($nuevoPassword)) {
                $errores[] = 'La contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas, números y caracteres especiales.';
            }
        }

        if (empty($errores)) {
            try {
                $pdo->beginTransaction();

                $columnasEmpleados = array_flip(obtenerColumnasEmpleadosCache($pdo));
                $setsEmpleado = [];
                $paramsEmpleado = [];

                foreach ($datosForm as $campo => $valor) {
                    if (!isset($columnasEmpleados[$campo])) {
                        continue;
                    }
                    $setsEmpleado[] = "$campo = ?";
                    $paramsEmpleado[] = $valor !== '' ? $valor : null;
                }

                if (!empty($setsEmpleado)) {
                    if (isset($columnasEmpleados['fecha_actualizacion'])) {
                        $setsEmpleado[] = 'fecha_actualizacion = NOW()';
                    }
                    $paramsEmpleado[] = $empleadoId;
                    $sqlEmpleado = 'UPDATE empleados SET ' . implode(', ', $setsEmpleado) . ' WHERE id = ?';
                    $stmtEmp = $pdo->prepare($sqlEmpleado);
                    $stmtEmp->execute($paramsEmpleado);
                }

                $columnasUsuarios = obtenerColumnasUsuarios($pdo);
                $setsUsuario = [];
                $paramsUsuario = [];

                if ($forcePasswordChange) {
                    $nuevoPasswordHash = password_hash($nuevoPassword, PASSWORD_DEFAULT);
                    $setsUsuario[] = 'password_hash = ?';
                    $paramsUsuario[] = $nuevoPasswordHash;
                    error_log("DEBUG: Password hash generado para usuario {$currentUser['id']}");
                } else {
                    error_log("DEBUG: forcePasswordChange es FALSE, no se actualiza contraseña");
                }

                // Actualización de correo deshabilitada temporalmente para evitar conflictos de unicidad
                // El correo de acceso se mantiene sin cambios
                // $nuevoEmail = strtolower(trim($datosForm['email']));
                // $emailActual = strtolower(trim($currentUser['email'] ?? ''));
                // if ($nuevoEmail !== $emailActual) {
                //     $setsUsuario[] = 'email = ?';
                //     $paramsUsuario[] = $nuevoEmail;
                // }

                if ($forcePasswordChange && in_array('requiere_cambio_password', $columnasUsuarios, true)) {
                    $setsUsuario[] = 'requiere_cambio_password = 0';
                }
                if (in_array('intentos_fallidos', $columnasUsuarios, true)) {
                    $setsUsuario[] = 'intentos_fallidos = 0';
                }
                if (in_array('bloqueado_hasta', $columnasUsuarios, true)) {
                    $setsUsuario[] = 'bloqueado_hasta = NULL';
                }
                if (in_array('fecha_actualizacion', $columnasUsuarios, true)) {
                    $setsUsuario[] = 'fecha_actualizacion = NOW()';
                }
                if (in_array('ultimo_acceso', $columnasUsuarios, true)) {
                    $setsUsuario[] = 'ultimo_acceso = NOW()';
                }

                if (!empty($setsUsuario)) {
                    $paramsUsuario[] = $currentUser['id'];

                    $sqlUsuario = 'UPDATE usuarios_sistema SET ' . implode(', ', $setsUsuario) . ' WHERE id = ?';
                    $stmtUsuario = $pdo->prepare($sqlUsuario);
                    $stmtUsuario->execute($paramsUsuario);

                    // Sincronizar sesión con los valores actuales
                    $stmtReload = $pdo->prepare('SELECT email, requiere_cambio_password FROM usuarios_sistema WHERE id = ? LIMIT 1');
                    $stmtReload->execute([$currentUser['id']]);
                    $userRefreshed = $stmtReload->fetch();

                    if ($forcePasswordChange) {
                        $_SESSION['user_requiere_cambio_password'] = (int) ($userRefreshed['requiere_cambio_password'] ?? 0);
                    }
                    $_SESSION['user_email'] = strtolower($userRefreshed['email'] ?? $datosForm['email']);
                }

                $pdo->commit();
                
                logActivity('perfil_actualizado', 'Usuario actualizó sus datos personales y contraseña', $currentUser['id']);

                redirectWithMessage('mi_perfil.php', 'success', 'Datos personales actualizados correctamente.');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Error actualizando perfil: ' . $e->getMessage());
                // DEBUG: Mostrar error real temporalmente
                $errores[] = 'Error de base de datos: ' . $e->getMessage();
            }
        }
    }
}

// Actualizar datos en caso de errores para mostrar valores actuales desde BD
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $empleado = obtenerEmpleadoActual($pdo, $empleadoId);
    if ($empleado) {
        foreach ($datosForm as $campo => $valor) {
            $datosForm[$campo] = $empleado[$campo] ?? '';
        }
    }
}

require_once '../../includes/header.php';
?>

<div class="card shadow-sm border-0 overflow-hidden mt-3">
    <div class="row g-0">
        <!-- Columna Izquierda: Perfil (Estilo Sidebar) -->
        <div class="col-md-4 bg-light bg-gradient border-end d-flex flex-column align-items-center justify-content-center p-5 text-center">
            <div class="mb-4 position-relative">
                <?php 
                $fotoPerfil = (!empty($currentUser['empleado_foto'])) ? 'uploads/empleados/' . $currentUser['empleado_foto'] : 'img/user-placeholder.svg';
                ?>
                <img src="<?php echo htmlspecialchars($fotoPerfil); ?>" 
                     alt="Foto de perfil" 
                     class="rounded-circle shadow" 
                     style="width: 160px; height: 160px; object-fit: cover; border: 4px solid #fff;">
                <div class="position-absolute bottom-0 end-0 bg-white rounded-circle p-1 shadow-sm" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-camera text-muted"></i>
                </div>
            </div>
            
            <!-- Layout Updated: <?php echo date('Y-m-d H:i:s'); ?> - Unified Card -->
            
            <h4 class="mb-1 text-dark fw-bold"><?php echo htmlspecialchars(reparar_texto(trim(($empleado['nombres'] ?? '') . ' ' . ($empleado['apellido_paterno'] ?? '')))); ?></h4>
            <p class="text-secondary mb-4 font-monospace small"><?php echo htmlspecialchars(reparar_texto($empleado['profesion'] ?? 'Empleado')); ?></p>
            
            <div class="d-flex flex-column gap-2 w-100 px-3">
                <div class="p-2 bg-white rounded shadow-sm border">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Número de Empleado</small>
                    <span class="fw-bold text-primary"><?php echo htmlspecialchars($empleado['numero_empleado'] ?? ''); ?></span>
                </div>
                <div class="p-2 bg-white rounded shadow-sm border">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Nivel de Usuario</small>
                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($currentUser['nivel_nombre'] ?? 'Usuario'); ?></span>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Formulario -->
        <div class="col-md-8 p-4 p-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <h4 class="mb-0 text-primary"><i class="fas fa-user-circle me-2"></i>Editar Perfil</h4>
                <span class="badge bg-light text-muted border">
                    <i class="fas fa-clock me-1"></i> Último acceso: <?php echo htmlspecialchars($currentUser['ultimo_acceso'] ?? 'Hoy'); ?>
                </span>
            </div>

            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger shadow-sm border-0 border-start border-4 border-danger small mb-4">
                    <h6 class="alert-heading fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Atención</h6>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($requiereCambioPassword && empty($errores) && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
                <div class="alert alert-warning shadow-sm border-0 border-start border-4 border-warning mb-4">
                    <div class="d-flex">
                        <div class="me-3"><i class="fas fa-key fa-2x text-warning"></i></div>
                        <div>
                            <h6 class="alert-heading fw-bold">Seguridad de la Cuenta</h6>
                            <p class="mb-0 small">Es necesario actualizar tu contraseña para continuar utilizando el sistema.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                 <div class="alert alert-success shadow-sm border-0 border-start border-4 border-success mb-4">
                    <i class="fas fa-check-circle me-2"></i> Tu perfil ha sido actualizado correctamente.
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="mb-4">
                    <h6 class="text-secondary text-uppercase small fw-bold mb-3">Datos de Contacto</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted" for="email">Email Personal (Login)</label>
                            <input type="email" class="form-control bg-light" id="email" name="email" required value="<?php echo htmlspecialchars($datosForm['email']); ?>">
                        </div>
                        <div class="col-md-6">
                             <label class="form-label small fw-bold text-muted">Email Institucional <small class="text-muted">(No editable)</small></label>
                             <input type="email" class="form-control bg-secondary bg-opacity-10" readonly disabled value="<?php echo htmlspecialchars($empleado['email_institucional'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted" for="telefono_celular">Móvil</label>
                            <input type="text" class="form-control bg-light" id="telefono_celular" name="telefono_celular" value="<?php echo htmlspecialchars($datosForm['telefono_celular']); ?>">
                        </div>
                         <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted" for="telefono_particular">Teléfono Fijo</label>
                            <input type="text" class="form-control bg-light" id="telefono_particular" name="telefono_particular" value="<?php echo htmlspecialchars($datosForm['telefono_particular']); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted" for="direccion">Domicilio</label>
                            <textarea class="form-control bg-light" id="direccion" name="direccion" rows="2" style="resize: none;"><?php echo htmlspecialchars($datosForm['direccion']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="text-secondary text-uppercase small fw-bold mb-3 border-top pt-3">Información Académica</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted" for="ultimo_grado_estudios">Grado Estudios</label>
                             <input type="text" class="form-control bg-light" id="ultimo_grado_estudios" name="ultimo_grado_estudios" value="<?php echo htmlspecialchars($datosForm['ultimo_grado_estudios']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted" for="profesion">Profesión</label>
                             <input type="text" class="form-control bg-light" id="profesion" name="profesion" value="<?php echo htmlspecialchars($datosForm['profesion']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted" for="fecha_nacimiento">Fecha Nacimiento</label>
                            <input type="date" class="form-control bg-light" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($datosForm['fecha_nacimiento']); ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="text-secondary text-uppercase small fw-bold mb-3 border-top pt-3">Seguridad</h6>
                    <div class="p-3 bg-light rounded border border-light">
                        <div class="row g-3">
                             <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted" for="nuevo_password">Nueva Contraseña</label>
                                <input type="password" class="form-control border-white shadow-sm" id="nuevo_password" name="nuevo_password" <?php echo $requiereCambioPassword ? 'required' : ''; ?> placeholder="***************">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted" for="confirmar_password">Confirmar Contraseña</label>
                                <input type="password" class="form-control border-white shadow-sm" id="confirmar_password" name="confirmar_password" <?php echo $requiereCambioPassword ? 'required' : ''; ?> placeholder="***************">
                            </div>
                        </div>
                         <?php if ($requiereCambioPassword): ?>
                            <div class="form-text mt-2 text-warning small"><i class="fas fa-info-circle me-1"></i> Es obligatorio cambiar tu contraseña.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-5">
                    <button type="submit" class="btn btn-primary px-5 rounded-pill shadow-sm">
                        <span class="d-flex align-items-center gap-2">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

