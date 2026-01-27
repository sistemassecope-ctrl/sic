<?php
/**
 * Módulo de Recursos Humanos - Expediente Digital de Empleado
 * Diseño tipo hoja/expediente con pestañas
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// ID del módulo de Empleados
define('MODULO_ID', 20);

// Obtener permisos del usuario para este módulo
$permisos_user = getUserPermissions(MODULO_ID);
$puedeVer = in_array('ver', $permisos_user);
$puedeCrear = in_array('crear', $permisos_user);
$puedeEditar = in_array('editar', $permisos_user);
$puedeEliminar = in_array('eliminar', $permisos_user);
$puedeVerSalarios = in_array('ver_salarios', $permisos_user);

$empleadoId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$esEdicion = $empleadoId !== null;

// Verificar permisos
if ($esEdicion && !$puedeEditar && !$puedeVer) {
    setFlashMessage('error', 'No tienes permiso para acceder a este expediente');
    redirect('/modulos/recursos-humanos/empleados.php');
}

if ($esEdicion && !$puedeEditar && $puedeVer) {
    // Si solo puede ver, permitir acceso pero bloquear POST y mostrar UI de solo lectura
}

if (!$esEdicion && !$puedeCrear) {
    setFlashMessage('error', 'No tienes permiso para crear nuevos empleados');
    redirect('/modulos/recursos-humanos/empleados.php');
}

$pdo = getConnection();
$user = getCurrentUser();
$areasUsuario = getUserAreas();

$empleado = null;
$errors = [];

$hijos = [];
if ($esEdicion) {
    // Nota: Se filtra por area_id para seguridad
    $stmt = $pdo->prepare("SELECT * FROM empleados WHERE id = ? AND " . getAreaFilterSQL('area_id'));
    $stmt->execute([$empleadoId]);
    $empleado = $stmt->fetch();
    
    if (!$empleado) {
        setFlashMessage('error', 'Empleado no encontrado o no tienes acceso a su expediente');
        redirect('/modulos/recursos-humanos/empleados.php');
    }

    // Cargar hijos
    $stmtH = $pdo->prepare("SELECT * FROM empleado_hijos WHERE empleado_id = ?");
    $stmtH->execute([$empleadoId]);
    $hijos = $stmtH->fetchAll();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($esEdicion && !$puedeEditar) || (!$esEdicion && !$puedeCrear)) {
        setFlashMessage('error', 'No tienes permiso para realizar esta acción');
        redirect("/modulos/recursos-humanos/empleado-form.php" . ($esEdicion ? "?id=$empleadoId" : ""));
    }
    // --- Recolección y Sanitización de Datos ---
    
    // Básicos
    $nombres = sanitize($_POST['nombres'] ?? '');
    $apellidoPaterno = sanitize($_POST['apellido_paterno'] ?? '');
    $apellidoMaterno = sanitize($_POST['apellido_materno'] ?? ''); // Nullable
    $fechaNacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
    $genero = sanitize($_POST['genero'] ?? '');
    $lugarNacimiento = sanitize($_POST['lugar_nacimiento'] ?? '');
    $estadoNacimiento = sanitize($_POST['estado_nacimiento'] ?? '');
    $rfc = sanitize($_POST['rfc'] ?? '');
    $curp = sanitize($_POST['curp'] ?? '');
    
    // Contacto
    $telefonoCelular = sanitize($_POST['telefono_celular'] ?? '');
    $telefonoParticular = sanitize($_POST['telefono_particular'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $emailInstitucional = sanitize($_POST['email_institucional'] ?? '');
    
    // Dirección Fragmentada
    $calle = sanitize($_POST['calle'] ?? '');
    $numExterior = sanitize($_POST['num_exterior'] ?? '');
    $numInterior = sanitize($_POST['num_interior'] ?? '');
    $codigoPostal = sanitize($_POST['codigo_postal'] ?? '');
    $colonia = sanitize($_POST['colonia'] ?? '');
    $ciudad = sanitize($_POST['ciudad'] ?? '');
    $municipio = sanitize($_POST['municipio'] ?? '');
    $estadoDir = sanitize($_POST['estado_dir'] ?? '');
    
    // Laboral
    $numeroEmpleado = sanitize($_POST['numero_empleado'] ?? '');
    $areaId = (int)($_POST['area_id'] ?? 0);
    $puestoId = (int)($_POST['puesto_trabajo_id'] ?? 0);
    $nombramiento = sanitize($_POST['nombramiento'] ?? '');
    $fechaIngreso = !empty($_POST['fecha_ingreso']) ? $_POST['fecha_ingreso'] : null;
    $horario = sanitize($_POST['horario'] ?? '');
    $estatus = sanitize($_POST['estatus'] ?? 'ACTIVO'); // ACTIVO, BAJA, LICENCIA
    
    // Académico
    $ultimoGrado = sanitize($_POST['ultimo_grado_estudios'] ?? '');
    $profesion = sanitize($_POST['profesion'] ?? '');
    
    // Familia
    $conyugeNombre = sanitize($_POST['conyuge_nombre'] ?? '');
    $conyugeFechaNac = !empty($_POST['conyuge_fecha_nacimiento']) ? $_POST['conyuge_fecha_nacimiento'] : null;
    $conyugeGenero = sanitize($_POST['conyuge_genero'] ?? '');
    $padreMadre = isset($_POST['padre_madre']) ? 1 : 0;
    $hijosPost = $_POST['hijos'] ?? [];
    
    // Sistema
    $rolSistema = sanitize($_POST['rol_sistema'] ?? 'usuario');
    // Validar JSON
    $permisosExtra = trim($_POST['permisos_extra'] ?? '');
    if (!empty($permisosExtra) && !json_validate($permisosExtra)) {
         $permisosExtra = '{}'; // Fallback simple
    }

    // Datos Financieros (Solo Admin/Permisos)
    // Si NO puede ver salarios, mantenemos los valores actuales de la base de datos si es edición
    if ($puedeVerSalarios) {
        $salario = !empty($_POST['salario']) ? (float)$_POST['salario'] : 0.00;
        $sueldoBruto = !empty($_POST['sueldo_bruto']) ? (float)$_POST['sueldo_bruto'] : 0.00;
        $sueldoNeto = !empty($_POST['sueldo_neto']) ? (float)$_POST['sueldo_neto'] : 0.00;
    } else {
        $salario = $empleado['salario'] ?? 0.00;
        $sueldoBruto = $empleado['sueldo_bruto'] ?? 0.00;
        $sueldoNeto = $empleado['sueldo_neto'] ?? 0.00;
    }
    
    // Baja y Desvinculación
    $fechaBaja = !empty($_POST['fecha_baja']) ? $_POST['fecha_baja'] : null;
    $tipoBaja = sanitize($_POST['tipo_baja'] ?? '');
    $docSustentoTipo = sanitize($_POST['documento_sustento_tipo'] ?? '');
    
    // Procesar archivo de baja si existe
    $docSustentoArchivo = $empleado['documento_sustento_archivo'] ?? null;
    if (isset($_FILES['documento_archivo']) && $_FILES['documento_archivo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../assets/uploads/bajas/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($_FILES['documento_archivo']['name'], PATHINFO_EXTENSION);
        $fileName = 'baja_' . ($empleadoId ?? 'nuevo') . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['documento_archivo']['tmp_name'], $destPath)) {
            $docSustentoArchivo = '/assets/uploads/bajas/' . $fileName;
        }
    }

    // Auto-ajustar estatus si hay fecha de baja
    if ($fechaBaja) {
        $estatus = 'BAJA';
    }
    
    // Vulnerabilidad
    $vulnerabilidad = sanitize($_POST['vulnerabilidad'] ?? '');

    // Validaciones
    if (empty($nombres)) $errors[] = 'El nombre es obligatorio.';
    if (empty($apellidoPaterno)) $errors[] = 'El apellido paterno es obligatorio.';
    if ($areaId <= 0) $errors[] = 'El área de adscripción es obligatoria.';
    if ($puestoId <= 0) $errors[] = 'El puesto es obligatorio.';
    
    // Validar acceso al área seleccionada
    if (!in_array($areaId, $areasUsuario) && !isAdmin()) {
        $errors[] = 'No tienes permiso para asignar empleados al área seleccionada.';
    }

    if (empty($errors)) {
        try {
            $columnasActualizar = [
                'nombres' => $nombres,
                'apellido_paterno' => $apellidoPaterno,
                'apellido_materno' => $apellidoMaterno,
                'fecha_nacimiento' => $fechaNacimiento,
                'genero' => $genero,
                'lugar_nacimiento' => $lugarNacimiento,
                'estado_nacimiento' => $estadoNacimiento,
                'rfc' => $rfc,
                'curp' => $curp,
                
                'telefono_celular' => $telefonoCelular,
                'telefono_particular' => $telefonoParticular,
                'email' => $email,
                'email_institucional' => $emailInstitucional,
                
                // Dirección Fragmentada
                'calle' => $calle,
                'num_exterior' => $numExterior,
                'num_interior' => $numInterior,
                'codigo_postal' => $codigoPostal,
                'colonia' => $colonia,
                'ciudad' => $ciudad,
                'municipio' => $municipio,
                'estado_dir' => $estadoDir,
                
                'numero_empleado' => $numeroEmpleado,
                'area_id' => $areaId,
                'puesto_trabajo_id' => $puestoId,
                'nombramiento' => $nombramiento,
                'fecha_ingreso' => $fechaIngreso,
                'horario' => $horario,
                'estatus' => $estatus,
                
                'ultimo_grado_estudios' => $ultimoGrado,
                'profesion' => $profesion,
                
                'conyuge_nombre' => $conyugeNombre,
                'conyuge_fecha_nacimiento' => $conyugeFechaNac,
                'conyuge_genero' => $conyugeGenero,
                'padre_madre' => $padreMadre,
                
                'rol_sistema' => $rolSistema,
                'permisos_extra' => $permisosExtra,
                
                // Campos de Sueldo
                'salario' => $salario,
                'sueldo_bruto' => $sueldoBruto,
                'sueldo_neto' => $sueldoNeto,
                'vulnerabilidad' => $vulnerabilidad,

                // Desvinculación
                'fecha_baja' => $fechaBaja,
                'tipo_baja' => $tipoBaja,
                'documento_sustento_tipo' => $docSustentoTipo,
                'documento_sustento_archivo' => $docSustentoArchivo,

                'fecha_actualizacion' => date('Y-m-d H:i:s')
            ];

            if ($esEdicion) {
                // Construir UPDATE dinámico
                $setPart = [];
                $params = [];
                foreach ($columnasActualizar as $col => $val) {
                    $setPart[] = "$col = ?";
                    $params[] = $val;
                }
                $params[] = $empleadoId;
                
                $sql = "UPDATE empleados SET " . implode(', ', $setPart) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                setFlashMessage('success', 'Expediente actualizado correctamente.');
                
                // Sync Hijos
                $pdo->prepare("DELETE FROM empleado_hijos WHERE empleado_id = ?")->execute([$empleadoId]);
                foreach ($hijosPost as $h) {
                    if (!empty($h['nombre'])) {
                        $stmtI = $pdo->prepare("INSERT INTO empleado_hijos (empleado_id, nombre_completo, fecha_nacimiento, genero) VALUES (?, ?, ?, ?)");
                        $stmtI->execute([$empleadoId, sanitize($h['nombre']), $h['fecha_nacimiento'] ?: null, $h['genero'] ?: null]);
                    }
                }
            } else {
                // INSERT
                $columnasActualizar['activo'] = 1;
                $columnasActualizar['fecha_creacion'] = date('Y-m-d H:i:s');
                
                $cols = array_keys($columnasActualizar);
                $vals = array_values($columnasActualizar);
                $placeholders = array_fill(0, count($cols), '?');
                
                $sql = "INSERT INTO empleados (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($vals);
                
                $newId = $pdo->lastInsertId();
                
                // Guardar Hijos para nuevo registro
                foreach ($hijosPost as $h) {
                    if (!empty($h['nombre'])) {
                        $stmtI = $pdo->prepare("INSERT INTO empleado_hijos (empleado_id, nombre_completo, fecha_nacimiento, genero) VALUES (?, ?, ?, ?)");
                        $stmtI->execute([$newId, sanitize($h['nombre']), $h['fecha_nacimiento'] ?: null, $h['genero'] ?: null]);
                    }
                }

                setFlashMessage('success', 'Empleado registrado y expediente creado.');
                $empleadoId = $newId;
            }
            
            // Redirigir de nuevo al formulario en lugar de sacarlo a la lista
            redirect("/modulos/recursos-humanos/empleado-form.php?id=$empleadoId");
            
            
        } catch (Exception $e) {
            $errors[] = 'Error en base de datos: ' . $e->getMessage();
        }
    }
    
    // Si hay error, recargar datos enviados para no perderlos
    $empleado = array_merge($empleado ?? [], $_POST);
}

// Catálogos
$areas = $pdo->query("SELECT * FROM areas WHERE estado = 1 AND " . getAreaFilterSQL('id') . " ORDER BY nombre_area")->fetchAll();
$puestos = $pdo->query("SELECT * FROM puestos_trabajo WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
$catTiposBaja = $pdo->query("SELECT * FROM cat_tipos_baja WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
$catTiposDocBaja = $pdo->query("SELECT * FROM cat_tipos_documento_baja WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();

// Obtener datos financieros si tiene permiso y existe la info
$puestoData = null;
if ($puedeVerSalarios && !empty($empleado['puesto_trabajo_id'])) {
    $stmtPuesto = $pdo->prepare("SELECT salario_minimo, salario_maximo FROM puestos_trabajo WHERE id = ?");
    $stmtPuesto->execute([$empleado['puesto_trabajo_id']]);
    $puestoData = $stmtPuesto->fetch();
}

?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<style>
    :root {
        /* Usar variables globales para mantener consistencia con el tema oscuro */
        --paper-bg: var(--bg-card);
        --section-header-color: var(--text-primary);
        --tab-active-border: var(--accent-primary);
        --tab-inactive-text: var(--text-secondary);
        --input-bg: var(--bg-tertiary);
    }
    
    .expediente-container {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 2rem;
        align-items: start;
    }
    
    /* Sidebar de Navegación del Expediente */
    .expediente-nav {
        background: var(--paper-bg);
        border-radius: 12px;
        border: 1px solid var(--border-primary);
        overflow: hidden;
        position: sticky;
        top: 2rem;
    }
    
    .expediente-profile {
        padding: 2rem 1rem;
        text-align: center;
        background: linear-gradient(135deg, rgba(33, 38, 45, 0.8) 0%, rgba(22, 27, 34, 0.9) 100%);
        color: var(--text-primary);
        border-bottom: 1px solid var(--border-primary);
    }
    
    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(255,255,255,0.05);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 2.5rem;
        border: 3px solid rgba(255,255,255,0.1);
        color: var(--text-secondary);
    }
    
    .nav-tabs {
        display: flex;
        flex-direction: column;
        border: none;
        padding: 0.5rem;
    }
    
    .nav-link {
        border: none !important;
        background: transparent;
        color: var(--text-secondary) !important;
        padding: 1rem 1.5rem;
        border-radius: 8px !important;
        text-align: left;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: all 0.2s;
    }
    
    .nav-link:hover {
        background: var(--bg-hover) !important;
        color: var(--accent-primary) !important;
    }
    
    .nav-link.active {
        background: rgba(88, 166, 255, 0.1) !important;
        color: var(--accent-primary) !important;
        box-shadow: none;
        border-left: 3px solid var(--accent-primary) !important;
    }
    
    /* Contenido tipo Hoja */
    .expediente-content {
        background: var(--paper-bg);
        border: 1px solid var(--border-primary);
        border-radius: 12px;
        box-shadow: var(--shadow-sm);
        min-height: 600px;
        padding: 2.5rem;
        position: relative;
    }
    
    .paper-header {
        border-bottom: 1px solid var(--border-primary);
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .section-title {
        font-size: 1.25rem;
        color: var(--text-primary);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .form-section {
        display: none;
        animation: fadeIn 0.3s ease-in-out;
    }
    
    .form-section.active {
        display: block;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }
    
    .form-label {
        color: #c9d1d9; /* Lighter than text-secondary for better contrast */
        margin-bottom: 0.5rem;
        display: block;
        font-weight: 500;
    }
    
    .form-control {
        background-color: var(--bg-tertiary);
        border: 1px solid var(--border-primary);
        color: var(--text-primary);
    }
    
    .form-control:focus {
        background-color: var(--bg-primary);
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.15);
        color: var(--text-primary);
    }

    .form-control::placeholder {
        color: var(--text-muted);
    }

    .action-bar {
        position: sticky;
        bottom: 0;
        background: var(--bg-card); /* Coincidir con el fondo de la tarjeta */
        padding: 1rem 0;
        border-top: 1px solid var(--border-primary);
        margin-top: 2rem;
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        z-index: 10;
    }
    
    @media (max-width: 900px) {
        .expediente-container {
            grid-template-columns: 1fr;
        }
        .expediente-nav {
            position: static;
        }
        .nav-tabs {
            flex-direction: row;
            overflow-x: auto;
            padding-bottom: 1rem;
        }
        .nav-link {
            white-space: nowrap;
        }
    }
</style>

<main class="main-content">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="margin-bottom: 2rem;">
            <i class="fas fa-exclamation-triangle"></i> Por favor corrige los siguientes errores:
            <ul style="margin: 0.5rem 0 0 1.5rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" id="expedienteForm" enctype="multipart/form-data">
        <div class="expediente-container">
            <!-- Sidebar: Foto y Navegación -->
            <aside class="expediente-nav">
                <div class="expediente-profile">
                    <div class="profile-avatar">
                        <?php if (!empty($empleado['foto'])): ?>
                            <img src="<?= e($empleado['foto']) ?>" alt="Foto" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <?php if ($esEdicion): ?>
                        <h3 style="margin:0; font-size:1.1rem;"><?= e($empleado['nombres']) ?></h3>
                        <p style="margin:0.25rem 0 0; opacity:0.8; font-size:0.9rem;"><?= e($empleado['apellido_paterno']) ?></p>
                        <span class="badge bg-success mt-2" style="font-weight:normal;"><?= e($empleado['estatus'] ?? 'ACTIVO') ?></span>
                    <?php else: ?>
                        <h3 style="margin:0; font-size:1.1rem;">Nuevo Empleado</h3>
                        <p style="margin:0.25rem 0 0; opacity:0.8; font-size:0.9rem;">Creación de Expediente</p>
                    <?php endif; ?>
                </div>
                
                <div class="nav-tabs" id="formTabs" role="tablist">
                    <button type="button" class="nav-link active" data-target="personal">
                        <i class="fas fa-id-card fa-fw"></i> Datos Personales
                    </button>
                    <button type="button" class="nav-link" data-target="contacto">
                        <i class="fas fa-address-book fa-fw"></i> Contacto
                    </button>
                    <button type="button" class="nav-link" data-target="laboral">
                        <i class="fas fa-briefcase fa-fw"></i> Info. Laboral
                    </button>
                    <button type="button" class="nav-link" data-target="academico">
                        <i class="fas fa-graduation-cap fa-fw"></i> Académico
                    </button>
                    <button type="button" class="nav-link" data-target="familiar">
                        <i class="fas fa-users fa-fw"></i> Familiar / Social
                    </button>
                    <button type="button" class="nav-link" data-target="sistema">
                        <i class="fas fa-shield-alt fa-fw"></i> Sistema y Accesos
                    </button>
                    <?php if ($puedeVerSalarios): ?>
                    <button type="button" class="nav-link" data-target="finanzas">
                        <i class="fas fa-hand-holding-usd fa-fw"></i> Compensación
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($esEdicion): ?>
                    <button type="button" class="nav-link" data-target="bajas">
                        <i class="fas fa-user-slash fa-fw"></i> Baja y Desvinculación
                    </button>
                    <?php endif; ?>
                </div>
            </aside>
            
            <!-- Contenido Principal: Hojas del Expediente -->
            <div class="expediente-content">
                
                <!-- 1. Datos Personales -->
                <div id="personal" class="form-section active">
                    <div class="paper-header">
                        <h2 class="section-title"><i class="fas fa-id-card text-primary"></i> Datos de Identidad</h2>
                        <span class="text-muted small">Información básica del expediente</span>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Nombres *</label>
                            <input type="text" name="nombres" class="form-control" required value="<?= e($empleado['nombres'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Apellido Paterno *</label>
                            <input type="text" name="apellido_paterno" class="form-control" required value="<?= e($empleado['apellido_paterno'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Apellido Materno</label>
                            <input type="text" name="apellido_materno" class="form-control" value="<?= e($empleado['apellido_materno'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control" value="<?= e($empleado['fecha_nacimiento'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Género</label>
                            <select name="genero" class="form-control">
                                <option value="">Seleccione...</option>
                                <option value="HOMBRE" <?= ($empleado['genero'] ?? '') == 'HOMBRE' ? 'selected' : '' ?>>Masculino</option>
                                <option value="MUJER" <?= ($empleado['genero'] ?? '') == 'MUJER' ? 'selected' : '' ?>>Femenino</option>
                                <option value="OTRO" <?= ($empleado['genero'] ?? '') == 'OTRO' ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Lugar de Nacimiento</label>
                            <input type="text" name="lugar_nacimiento" class="form-control" value="<?= e($empleado['lugar_nacimiento'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">RFC</label>
                            <input type="text" name="rfc" class="form-control" style="text-transform:uppercase" value="<?= e($empleado['rfc'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">CURP</label>
                            <input type="text" name="curp" class="form-control" style="text-transform:uppercase" value="<?= e($empleado['curp'] ?? '') ?>">
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Vulnerabilidad / Condición Especial</label>
                            <textarea name="vulnerabilidad" class="form-control" rows="2" placeholder="Describa si el empleado pertenece a algún grupo vulnerable o tiene alguna condición médica relevante..."><?= e($empleado['vulnerabilidad'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- 2. Información de Contacto -->
                <div id="contacto" class="form-section">
                    <div class="paper-header">
                        <h2 class="section-title"><i class="fas fa-address-book text-success"></i> Medios de Contacto</h2>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Teléfono Celular</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-mobile-alt"></i></span>
                                <input type="text" name="telefono_celular" class="form-control" value="<?= e($empleado['telefono_celular'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Teléfono Particular</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="text" name="telefono_particular" class="form-control" value="<?= e($empleado['telefono_particular'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email Personal</label>
                            <input type="email" name="email" class="form-control" value="<?= e($empleado['email'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email Institucional</label>
                            <input type="email" name="email_institucional" class="form-control" value="<?= e($empleado['email_institucional'] ?? '') ?>">
                        </div>
                        
                        <!-- Dirección Fragmentada -->
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">Calle</label>
                            <input type="text" name="calle" class="form-control" value="<?= e($empleado['calle'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Núm. Exterior</label>
                            <input type="text" name="num_exterior" class="form-control" value="<?= e($empleado['num_exterior'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Núm. Interior</label>
                            <input type="text" name="num_interior" class="form-control" placeholder="Opcional" value="<?= e($empleado['num_interior'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Código Postal</label>
                            <input type="text" name="codigo_postal" class="form-control" value="<?= e($empleado['codigo_postal'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Colonia / Localidad</label>
                            <input type="text" name="colonia" class="form-control" value="<?= e($empleado['colonia'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Ciudad</label>
                            <input type="text" name="ciudad" class="form-control" value="<?= e($empleado['ciudad'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Municipio / Delegación</label>
                            <input type="text" name="municipio" class="form-control" value="<?= e($empleado['municipio'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Estado</label>
                            <input type="text" name="estado_dir" class="form-control" value="<?= e($empleado['estado_dir'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                
                <!-- 3. Información Laboral -->
                <div id="laboral" class="form-section">
                    <div class="paper-header">
                        <h2 class="section-title"><i class="fas fa-briefcase text-warning"></i> Adscripción y Puesto</h2>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Número de Empleado</label>
                            <input type="text" name="numero_empleado" class="form-control font-monospace fw-bold" value="<?= e($empleado['numero_empleado'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Estatus</label>
                            <select name="estatus" class="form-control">
                                <option value="ACTIVO" <?= ($empleado['estatus'] ?? '') == 'ACTIVO' ? 'selected' : '' ?>>🟢 ACTIVO</option>
                                <option value="BAJA" <?= ($empleado['estatus'] ?? '') == 'BAJA' ? 'selected' : '' ?>>🔴 BAJA</option>
                                <option value="LICENCIA" <?= ($empleado['estatus'] ?? '') == 'LICENCIA' ? 'selected' : '' ?>>🟡 LICENCIA</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">Área de Adscripción *</label>
                            <select name="area_id" class="form-control" required>
                                <option value="">Seleccione Área...</option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?= $area['id'] ?>" <?= ($empleado['area_id'] ?? '') == $area['id'] ? 'selected' : '' ?>>
                                        <?= e($area['nombre_area']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">Puesto Oficial *</label>
                            <select name="puesto_trabajo_id" class="form-control" required>
                                <option value="">Seleccione Puesto...</option>
                                <?php foreach ($puestos as $puesto): ?>
                                    <option value="<?= $puesto['id'] ?>" <?= ($empleado['puesto_trabajo_id'] ?? '') == $puesto['id'] ? 'selected' : '' ?>>
                                        <?= e($puesto['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Tipo de Nombramiento</label>
                            <input type="text" name="nombramiento" class="form-control" value="<?= e($empleado['nombramiento'] ?? '') ?>" placeholder="Ej: Confianza, Base...">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Fecha de Ingreso</label>
                            <input type="date" name="fecha_ingreso" class="form-control" value="<?= e($empleado['fecha_ingreso'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Horario</label>
                            <input type="text" name="horario" class="form-control" value="<?= e($empleado['horario'] ?? '') ?>" placeholder="Ej: 9:00 - 17:00">
                        </div>
                    </div>
                </div>
                
                <!-- 4. Académico -->
                <div id="academico" class="form-section">
                    <div class="paper-header">
                        <h2 class="section-title"><i class="fas fa-graduation-cap text-info"></i> Perfil Académico</h2>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Último Grado de Estudios</label>
                            <select name="ultimo_grado_estudios" class="form-control">
                                <option value="">Seleccione...</option>
                                <?php 
                                $grados = ['Primaria', 'Secundaria', 'Bachillerato', 'Técnico', 'Licenciatura', 'Maestría', 'Doctorado'];
                                foreach ($grados as $g): ?>
                                    <option value="<?= $g ?>" <?= ($empleado['ultimo_grado_estudios'] ?? '') == $g ? 'selected' : '' ?>><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Profesión / Título</label>
                            <input type="text" name="profesion" class="form-control" value="<?= e($empleado['profesion'] ?? '') ?>" placeholder="Ej: Lic. en Derecho, Ing. Sistemas...">
                        </div>
                    </div>
                </div>
                
                <!-- 5. Familiar -->
                <div id="familiar" class="form-section">
                    <div class="paper-header">
                        <h2 class="section-title"><i class="fas fa-users text-danger"></i> Datos Familiares y Sociales</h2>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                             <h4 class="form-label" style="font-size: 1rem; color: var(--text-primary); border-bottom: 1px dashed var(--border-primary); padding-bottom: 0.5rem;">
                                <i class="fas fa-heart text-danger me-2"></i> Datos del Cónyuge
                             </h4>
                        </div>
                        
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">Nombre Completo del Cónyuge</label>
                            <input type="text" name="conyuge_nombre" class="form-control" value="<?= e($empleado['conyuge_nombre'] ?? '') ?>" placeholder="Nombre completo">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Fecha de Nacimiento</label>
                            <input type="date" name="conyuge_fecha_nacimiento" class="form-control" value="<?= e($empleado['conyuge_fecha_nacimiento'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Género</label>
                            <select name="conyuge_genero" class="form-control">
                                <option value="">Seleccione...</option>
                                <option value="MUJER" <?= ($empleado['conyuge_genero'] ?? '') == 'MUJER' ? 'selected' : '' ?>>MUJER</option>
                                <option value="HOMBRE" <?= ($empleado['conyuge_genero'] ?? '') == 'HOMBRE' ? 'selected' : '' ?>>HOMBRE</option>
                                <option value="OTRO" <?= ($empleado['conyuge_genero'] ?? '') == 'OTRO' ? 'selected' : '' ?>>OTRO</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid mt-4">
                        <div class="form-group" style="grid-column: 1 / -1;">
                             <h4 class="form-label" style="font-size: 1rem; color: var(--text-primary); border-bottom: 1px dashed var(--border-primary); padding-bottom: 0.5rem;">
                                <i class="fas fa-baby text-info me-2"></i> Información de los Hijos
                             </h4>
                        </div>

                        <div class="form-group">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="padreMadre" name="padre_madre" <?= !empty($empleado['padre_madre']) ? 'checked' : '' ?>>
                                <label class="form-check-label ms-2" for="padreMadre">¿Es Padre/Madre?</label>
                            </div>
                        </div>

                        <div class="col-12" style="grid-column: 1 / -1;">
                            <div class="table-responsive mt-2">
                                <table class="table table-bordered table-sm" style="background: var(--bg-tertiary);">
                                    <thead class="table-dark">
                                        <tr style="font-size: 0.85rem;">
                                            <th>Nombre Completo del Hijo/a</th>
                                            <th width="150">Fecha Nac.</th>
                                            <th width="120">Género</th>
                                            <th width="50"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="hijosContainer">
                                        <?php if (empty($hijos)): ?>
                                            <tr class="no-hijos-row">
                                                <td colspan="4" class="text-center text-muted py-3 small">No se han registrado hijos</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($hijos as $idx => $h): ?>
                                                <tr>
                                                    <td><input type="text" name="hijos[<?= $idx ?>][nombre]" class="form-control form-control-sm" value="<?= e($h['nombre_completo']) ?>" required></td>
                                                    <td><input type="date" name="hijos[<?= $idx ?>][fecha_nacimiento]" class="form-control form-control-sm" value="<?= e($h['fecha_nacimiento']) ?>"></td>
                                                    <td>
                                                        <select name="hijos[<?= $idx ?>][genero]" class="form-select form-select-sm">
                                                            <option value="HOMBRE" <?= $h['genero'] == 'HOMBRE' ? 'selected' : '' ?>>HOMBRE</option>
                                                            <option value="MUJER" <?= $h['genero'] == 'MUJER' ? 'selected' : '' ?>>MUJER</option>
                                                        </select>
                                                    </td>
                                                    <td><button type="button" class="btn btn-outline-danger btn-sm remove-hijo"><i class="fas fa-trash"></i></button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-outline-info btn-sm" id="addHijoBtn">
                                <i class="fas fa-plus me-1"></i> Agregar Hijo/a
                            </button>
                        </div>
                    </div>
                </div>
                
                 <!-- 6. Compensación (Restringido) -->
                 <?php if ($puedeVerSalarios): ?>
                 <div id="finanzas" class="form-section">
                    <div class="paper-header">
                        <h2 class="section-title"><i class="fas fa-hand-holding-usd text-success"></i> Información Financiera</h2>
                        <span class="badge bg-warning text-dark"><i class="fas fa-lock"></i> Acceso Restringido</span>
                    </div>
                    
                    <div class="alert alert-info border">
                        <i class="fas fa-info-circle"></i> La información salarial se basa en el tabulador del <strong>Puesto Oficial</strong> asignado.
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Sueldo Base</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" name="salario" class="form-control" value="<?= e($empleado['salario'] ?? '0.00') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Sueldo Bruto (Mensual)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" name="sueldo_bruto" class="form-control" value="<?= e($empleado['sueldo_bruto'] ?? '0.00') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Sueldo Neto (Mensual)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" name="sueldo_neto" class="form-control" value="<?= e($empleado['sueldo_neto'] ?? '0.00') ?>">
                            </div>
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1; margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--border-primary);">
                             <h4 class="form-label" style="color: var(--text-primary); margin-bottom: 1rem;"> Referencia del Tabulador (Solo Lectura)</h4>
                             <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                <div>
                                    <label class="text-muted small">Rango Mínimo Puesto</label>
                                    <div class="fw-bold">$<?= number_format($puestoData['salario_minimo'] ?? 0, 2) ?></div>
                                </div>
                                <div>
                                    <label class="text-muted small">Rango Máximo Puesto</label>
                                    <div class="fw-bold">$<?= number_format($puestoData['salario_maximo'] ?? 0, 2) ?></div>
                                </div>
                             </div>
                        </div>
                    </div>
                 </div>
                 <?php endif; ?>
                
                <!-- 6. Sistema -->
                <div id="sistema" class="form-section">
                    <div class="paper-header">
                        <h2 class="section-title"><i class="fas fa-shield-alt text-dark"></i> Configuración de Acceso</h2>
                        <span class="badge bg-danger">Zona Administrativa</span>
                    </div>
                    
                    <div class="alert alert-light border">
                        <i class="fas fa-info-circle"></i> Define el nivel de acceso de este empleado dentro del sistema PAO.
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Rol del Sistema</label>
                            <select name="rol_sistema" class="form-control">
                                <option value="usuario" <?= ($empleado['rol_sistema'] ?? '') === 'usuario' ? 'selected' : '' ?>>Usuario Estándar</option>
                                <option value="admin_area" <?= ($empleado['rol_sistema'] ?? '') === 'admin_area' ? 'selected' : '' ?>>Administrador de Área</option>
                                <option value="admin_global" <?= ($empleado['rol_sistema'] ?? '') === 'admin_global' ? 'selected' : '' ?>>👑 Administrador Global</option>
                                <option value="SUPERADMIN" <?= ($empleado['rol_sistema'] ?? '') === 'SUPERADMIN' ? 'selected' : '' ?>>🛡️ SUPERADMIN</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Permisos Extra (JSON)</label>
                            <textarea name="permisos_extra" class="form-control font-monospace" rows="4" placeholder='{"permiso_especial": true}'><?= e($empleado['permisos_extra'] ?? '') ?></textarea>
                            <small class="text-muted">Configuración de permisos granulares en formato JSON crudo.</small>
                        </div>
                    </div>
                </div>

                <!-- 7. Baja y Desvinculación (Solo Edición) -->
                <?php if ($esEdicion): ?>
                <div id="bajas" class="form-section">
                    <div class="paper-header">
                        <h2 class="section-title"><i class="fas fa-user-slash text-danger"></i> Baja y Desvinculación</h2>
                        <span class="badge bg-danger">Cierre de Expediente</span>
                    </div>
                    
                    <div class="alert alert-warning border small">
                        <i class="fas fa-exclamation-circle"></i> Al registrar una fecha de baja, el estatus del empleado cambiará automáticamente a <strong>BAJA</strong> al guardar.
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Fecha de Cuasa de Baja</label>
                            <input type="date" name="fecha_baja" class="form-control" value="<?= e($empleado['fecha_baja'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tipo de Baja</label>
                            <select name="tipo_baja" class="form-control">
                                <option value="">Seleccione...</option>
                                <?php foreach ($catTiposBaja as $ctb): ?>
                                    <option value="<?= e($ctb['nombre']) ?>" <?= ($empleado['tipo_baja'] ?? '') == $ctb['nombre'] ? 'selected' : '' ?>>
                                        <?= e($ctb['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Documento que Sustenta</label>
                            <select name="documento_sustento_tipo" class="form-control">
                                <option value="">Seleccione...</option>
                                <?php foreach ($catTiposDocBaja as $ctd): ?>
                                    <option value="<?= e($ctd['nombre']) ?>" <?= ($empleado['documento_sustento_tipo'] ?? '') == $ctd['nombre'] ? 'selected' : '' ?>>
                                        <?= e($ctd['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Archivo de Sustento (Digitalizado)</label>
                            <input type="file" name="documento_archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            <?php if (!empty($empleado['documento_sustento_archivo'])): ?>
                                <div class="mt-2">
                                    <a href="<?= e($empleado['documento_sustento_archivo'] ?? '') ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-file-pdf"></i> Ver documento actual
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="action-bar">
                    <a href="<?= url('/modulos/recursos-humanos/empleados.php') ?>" id="btn-cancelar" class="btn btn-light border">
                        <i class="fas fa-arrow-left me-1"></i> Regresar
                    </a>
                    <button type="submit" id="btn-guardar" class="btn btn-primary px-4">
                        <i class="fas fa-save me-2"></i> Guardar Expediente
                    </button>
                </div>
                
            </div>
        </div>
    </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.nav-link[data-target]');
    const sections = document.querySelectorAll('.form-section');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            // Add active to clicked tab
            tab.classList.add('active');
            
            // Hide all sections
            sections.forEach(s => s.classList.remove('active'));
            // Show target section
            const targetId = tab.dataset.target;
            document.getElementById(targetId).classList.add('active');
            
            // Smooth scroll to top of content on mobile
            if (window.innerWidth < 900) {
                document.querySelector('.expediente-content').scrollIntoView({behavior: 'smooth'});
            }
        });
    });

    // --- Lógica de Cambios y Botón de Guardado ---
    const form = document.getElementById('expedienteForm');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const esEdicion = <?= json_encode($esEdicion) ?>;
    let haCambiado = false;

    function marcarComoModificado() {
        if (!haCambiado && esEdicion) {
            haCambiado = true;
            btnGuardar.innerHTML = '<i class="fas fa-save me-2"></i> Guardar Expediente';
            btnGuardar.classList.remove('btn-secondary');
            btnGuardar.classList.add('btn-primary');
            btnCancelar.innerHTML = '<i class="fas fa-undo me-1"></i> Descartar Cambios';
        }
    }

    // Escuchar cambios en todos los inputs
    form.querySelectorAll('input, select, textarea').forEach(el => {
        el.addEventListener('input', marcarComoModificado);
        el.addEventListener('change', marcarComoModificado);
    });

    // Configuración Inicial Modo Edición
    if (esEdicion) {
        btnGuardar.innerHTML = '<i class="fas fa-times me-2"></i> Cerrar';
        btnGuardar.classList.remove('btn-primary');
        btnGuardar.classList.add('btn-secondary');
        
        btnGuardar.addEventListener('click', (e) => {
            if (!haCambiado) {
                e.preventDefault();
                window.location.href = btnCancelar.href;
            }
        });
    }

    // Gestión Dinámica de Hijos
    const addHijoBtn = document.getElementById('addHijoBtn');
    const hijosContainer = document.getElementById('hijosContainer');
    let hijoIdx = hijosContainer.querySelectorAll('tr:not(.no-hijos-row)').length;

    if (addHijoBtn) {
        addHijoBtn.addEventListener('click', () => {
            const noHijosRow = hijosContainer.querySelector('.no-hijos-row');
            if (noHijosRow) noHijosRow.remove();

            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="hijos[${hijoIdx}][nombre]" class="form-control form-control-sm" placeholder="Nombre completo" required></td>
                <td><input type="date" name="hijos[${hijoIdx}][fecha_nacimiento]" class="form-control form-control-sm"></td>
                <td>
                    <select name="hijos[${hijoIdx}][genero]" class="form-select form-select-sm">
                        <option value="HOMBRE">HOMBRE</option>
                        <option value="MUJER">MUJER</option>
                    </select>
                </td>
                <td><button type="button" class="btn btn-outline-danger btn-sm remove-hijo"><i class="fas fa-trash"></i></button></td>
            `;
            hijosContainer.appendChild(row);
            
            // Agregar listeners a los nuevos inputs
            row.querySelectorAll('input, select').forEach(el => {
                el.addEventListener('input', marcarComoModificado);
                el.addEventListener('change', marcarComoModificado);
            });

            hijoIdx++;
            updateHijosCount();
            marcarComoModificado();
        });
    }

    if (hijosContainer) {
        hijosContainer.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-hijo');
            if (btn) {
                btn.closest('tr').remove();
                if (hijosContainer.querySelectorAll('tr').length === 0) {
                    hijosContainer.innerHTML = `<tr class="no-hijos-row"><td colspan="4" class="text-center text-muted py-3 small">No se han registrado hijos</td></tr>`;
                }
                updateHijosCount();
                marcarComoModificado();
            }
        });
    }

    function updateHijosCount() {
        const count = hijosContainer.querySelectorAll('tr:not(.no-hijos-row)').length;
        const switchPM = document.getElementById('padreMadre');
        if (switchPM) switchPM.checked = count > 0;
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
