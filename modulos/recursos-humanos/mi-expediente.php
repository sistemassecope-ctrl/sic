<?php
/**
 * Mi Expediente - Vista personal del empleado
 * Permite a cualquier usuario ver y editar parcialmente sus propios datos
 * 
 * REGLAS:
 * - NO puede ver: Sueldos/Salarios, Baja y Desvinculación, Sistema y Accesos
 * - SOLO LECTURA: Datos Personales (identidad), Laboral, Académico
 * - PUEDE EDITAR: Datos de Contacto (excepto email institucional), Familiar/Social
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getConnection();
$user = getCurrentUser();

// Verificar que el usuario tenga un empleado_id asignado
if (empty($user['empleado_id'])) {
    setFlashMessage('error', 'No tienes un expediente de empleado asociado a tu cuenta.');
    redirect('/index.php');
}

$empleadoId = (int)$user['empleado_id'];
$errors = [];
$success = false;

// Cargar datos del empleado
$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id = ?");
$stmt->execute([$empleadoId]);
$empleado = $stmt->fetch();

if (!$empleado) {
    setFlashMessage('error', 'No se pudo cargar tu expediente.');
    redirect('/index.php');
}

// Cargar hijos
$stmtH = $pdo->prepare("SELECT * FROM empleado_hijos WHERE empleado_id = ?");
$stmtH->execute([$empleadoId]);
$hijos = $stmtH->fetchAll();

// Cargar datos adicionales para mostrar (solo lectura)
$areaStmt = $pdo->prepare("SELECT nombre_area FROM areas WHERE id = ?");
$areaStmt->execute([$empleado['area_id']]);
$areaNombre = $areaStmt->fetchColumn() ?: 'Sin asignar';

$puestoStmt = $pdo->prepare("SELECT nombre FROM puestos_trabajo WHERE id = ?");
$puestoStmt->execute([$empleado['puesto_trabajo_id']]);
$puestoNombre = $puestoStmt->fetchColumn() ?: 'Sin asignar';

// Procesar formulario - SOLO campos permitidos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Campos de Contacto (excepto email institucional)
    $telefonoCelular = sanitize($_POST['telefono_celular'] ?? '');
    $telefonoParticular = sanitize($_POST['telefono_particular'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    // email_institucional NO se puede editar
    
    // Dirección Fragmentada
    $calle = sanitize($_POST['calle'] ?? '');
    $numExterior = sanitize($_POST['num_exterior'] ?? '');
    $numInterior = sanitize($_POST['num_interior'] ?? '');
    $codigoPostal = sanitize($_POST['codigo_postal'] ?? '');
    $colonia = sanitize($_POST['colonia'] ?? '');
    $ciudad = sanitize($_POST['ciudad'] ?? '');
    $municipio = sanitize($_POST['municipio'] ?? '');
    $estadoDir = sanitize($_POST['estado_dir'] ?? '');
    
    // Familia - Cónyuge
    $conyugeNombre = sanitize($_POST['conyuge_nombre'] ?? '');
    $conyugeFechaNac = !empty($_POST['conyuge_fecha_nacimiento']) ? $_POST['conyuge_fecha_nacimiento'] : null;
    $conyugeGenero = sanitize($_POST['conyuge_genero'] ?? '');
    $padreMadre = isset($_POST['padre_madre']) ? 1 : 0;
    $hijosPost = $_POST['hijos'] ?? [];
    
    if (empty($errors)) {
        try {
            // Solo actualizar campos permitidos
            $sql = "UPDATE empleados SET 
                telefono_celular = ?,
                telefono_particular = ?,
                email = ?,
                calle = ?,
                num_exterior = ?,
                num_interior = ?,
                codigo_postal = ?,
                colonia = ?,
                ciudad = ?,
                municipio = ?,
                estado_dir = ?,
                conyuge_nombre = ?,
                conyuge_fecha_nacimiento = ?,
                conyuge_genero = ?,
                padre_madre = ?,
                fecha_actualizacion = NOW()
                WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $telefonoCelular,
                $telefonoParticular,
                $email,
                $calle,
                $numExterior,
                $numInterior,
                $codigoPostal,
                $colonia,
                $ciudad,
                $municipio,
                $estadoDir,
                $conyugeNombre,
                $conyugeFechaNac,
                $conyugeGenero,
                $padreMadre,
                $empleadoId
            ]);
            
            // Sincronizar Hijos
            $pdo->prepare("DELETE FROM empleado_hijos WHERE empleado_id = ?")->execute([$empleadoId]);
            foreach ($hijosPost as $h) {
                if (!empty($h['nombre'])) {
                    $stmtI = $pdo->prepare("INSERT INTO empleado_hijos (empleado_id, nombre_completo, fecha_nacimiento, genero) VALUES (?, ?, ?, ?)");
                    $stmtI->execute([$empleadoId, sanitize($h['nombre']), $h['fecha_nacimiento'] ?: null, $h['genero'] ?: null]);
                }
            }
            
            $success = true;
            setFlashMessage('success', 'Tus datos han sido actualizados correctamente.');
            
            // Recargar datos
            $stmt = $pdo->prepare("SELECT * FROM empleados WHERE id = ?");
            $stmt->execute([$empleadoId]);
            $empleado = $stmt->fetch();
            
            $stmtH = $pdo->prepare("SELECT * FROM empleado_hijos WHERE empleado_id = ?");
            $stmtH->execute([$empleadoId]);
            $hijos = $stmtH->fetchAll();
            
        } catch (Exception $e) {
            $errors[] = 'Error al guardar: ' . $e->getMessage();
        }
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<style>
    :root {
        --paper-bg: var(--bg-card);
        --section-header-color: var(--text-primary);
        --tab-active-border: var(--accent-primary);
    }
    
    .expediente-container {
        max-width: 1100px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: 2rem;
        align-items: start;
    }
    
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
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: rgba(255,255,255,0.05);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 2.5rem;
        border: 3px solid rgba(88, 166, 255, 0.3);
        color: var(--accent-primary);
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
        padding: 0.85rem 1.25rem;
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
        border-left: 3px solid var(--accent-primary) !important;
    }
    
    .nav-link .badge {
        margin-left: auto;
        font-size: 0.65rem;
    }
    
    .expediente-content {
        background: var(--paper-bg);
        border: 1px solid var(--border-primary);
        border-radius: 12px;
        box-shadow: var(--shadow-sm);
        min-height: 500px;
        padding: 2rem;
    }
    
    .paper-header {
        border-bottom: 1px solid var(--border-primary);
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .section-title {
        font-size: 1.15rem;
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
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 1.25rem;
    }
    
    .form-label {
        color: #c9d1d9;
        margin-bottom: 0.5rem;
        display: block;
        font-weight: 500;
        font-size: 0.9rem;
    }
    
    .form-control, .form-select {
        background-color: var(--bg-tertiary);
        border: 1px solid var(--border-primary);
        color: var(--text-primary);
    }
    
    .form-control:focus, .form-select:focus {
        background-color: var(--bg-primary);
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.15);
        color: var(--text-primary);
    }
    
    .form-control:disabled, .form-control[readonly] {
        background-color: rgba(33, 38, 45, 0.6);
        opacity: 0.75;
        cursor: not-allowed;
    }
    
    .readonly-field {
        background: rgba(88, 166, 255, 0.05);
        border: 1px dashed var(--border-primary);
        padding: 0.75rem 1rem;
        border-radius: 8px;
        color: var(--text-primary);
        font-weight: 500;
    }
    
    .readonly-label {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-bottom: 0.25rem;
    }
    
    .info-card {
        background: linear-gradient(135deg, rgba(88, 166, 255, 0.05) 0%, rgba(33, 38, 45, 0.5) 100%);
        border: 1px solid rgba(88, 166, 255, 0.2);
        border-radius: 10px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }
    
    .info-card h5 {
        color: var(--accent-primary);
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    
    .action-bar {
        position: sticky;
        bottom: 0;
        background: var(--bg-card);
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
    <div class="d-flex align-items-center mb-4">
        <a href="<?= url('/index.php') ?>" class="btn btn-sm btn-outline-secondary me-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="h3 mb-0" style="color: var(--text-primary);">
                <i class="fas fa-user-circle text-primary me-2"></i>Mi Expediente
            </h1>
            <p class="text-muted mb-0 small">Consulta y actualiza tu información personal</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="margin-bottom: 2rem;">
            <i class="fas fa-exclamation-triangle"></i> Error al guardar:
            <ul style="margin: 0.5rem 0 0 1.5rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" id="miExpedienteForm">
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
                    <h3 style="margin:0; font-size:1rem;"><?= e($empleado['nombres']) ?></h3>
                    <p style="margin:0.25rem 0 0; opacity:0.8; font-size:0.85rem;"><?= e($empleado['apellido_paterno']) ?> <?= e($empleado['apellido_materno'] ?? '') ?></p>
                    <span class="badge bg-<?= $empleado['estatus'] == 'ACTIVO' ? 'success' : 'warning' ?> mt-2" style="font-weight:normal;">
                        <?= e($empleado['estatus'] ?? 'ACTIVO') ?>
                    </span>
                </div>
                
                <div class="nav-tabs" id="formTabs" role="tablist">
                    <button type="button" class="nav-link active" data-target="personal">
                        <i class="fas fa-id-card fa-fw"></i> Datos Personales
                        <span class="badge bg-secondary">Solo lectura</span>
                    </button>
                    <button type="button" class="nav-link" data-target="contacto">
                        <i class="fas fa-address-book fa-fw"></i> Contacto
                        <span class="badge bg-success">Editable</span>
                    </button>
                    <button type="button" class="nav-link" data-target="laboral">
                        <i class="fas fa-briefcase fa-fw"></i> Info. Laboral
                        <span class="badge bg-secondary">Solo lectura</span>
                    </button>
                    <button type="button" class="nav-link" data-target="academico">
                        <i class="fas fa-graduation-cap fa-fw"></i> Académico
                        <span class="badge bg-secondary">Solo lectura</span>
                    </button>
                    <button type="button" class="nav-link" data-target="familiar">
                        <i class="fas fa-users fa-fw"></i> Familiar / Social
                        <span class="badge bg-success">Editable</span>
                    </button>
                </div>
            </aside>
            
            <!-- Contenido Principal -->
            <div class="expediente-content">
                
                <!-- 1. Datos Personales (SOLO LECTURA) -->
                <div id="personal" class="form-section active">
                    <div class="paper-header">
                        <h2 class="section-title"><i class="fas fa-id-card text-primary"></i> Datos de Identidad</h2>
                        <span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Solo lectura</span>
                    </div>
                    
                    <div class="info-card">
                        <h5><i class="fas fa-info-circle me-1"></i> Información</h5>
                        <p class="mb-0 small" style="color: var(--text-secondary);">
                            Estos datos son gestionados por Recursos Humanos. Si detectas algún error, contacta a tu área de RH.
                        </p>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <div class="readonly-label">Nombres</div>
                            <div class="readonly-field"><?= e($empleado['nombres'] ?? '-') ?></div>
                        </div>
                        <div class="form-group">
                            <div class="readonly-label">Apellido Paterno</div>
                            <div class="readonly-field"><?= e($empleado['apellido_paterno'] ?? '-') ?></div>
                        </div>
                        <div class="form-group">
                            <div class="readonly-label">Apellido Materno</div>
                            <div class="readonly-field"><?= e($empleado['apellido_materno'] ?? '-') ?></div>
                        </div>
                        <div class="form-group">
                            <div class="readonly-label">Fecha de Nacimiento</div>
                            <div class="readonly-field"><?= $empleado['fecha_nacimiento'] ? date('d/m/Y', strtotime($empleado['fecha_nacimiento'])) : '-' ?></div>
                        </div>
                        <div class="form-group">
                            <div class="readonly-label">Género</div>
                            <div class="readonly-field"><?= e($empleado['genero'] ?? '-') ?></div>
                        </div>
                        <div class="form-group">
                            <div class="readonly-label">Lugar de Nacimiento</div>
                            <div class="readonly-field"><?= e($empleado['lugar_nacimiento'] ?? '-') ?></div>
                        </div>
                        <div class="form-group">
                            <div class="readonly-label">RFC</div>
                            <div class="readonly-field font-monospace"><?= e($empleado['rfc'] ?? '-') ?></div>
                        </div>
                        <div class="form-group">
                            <div class="readonly-label">CURP</div>
                            <div class="readonly-field font-monospace"><?= e($empleado['curp'] ?? '-') ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- 2. Información de Contacto (EDITABLE excepto email institucional) -->
                <div id="contacto" class="form-section">
                    <div class="paper-header">
                        <h2 class="section-title"><i class="fas fa-address-book text-success"></i> Medios de Contacto</h2>
                        <span class="badge bg-success"><i class="fas fa-edit me-1"></i>Editable</span>
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
                            <label class="form-label">Email Institucional <span class="badge bg-secondary">Solo lectura</span></label>
                            <input type="email" class="form-control" value="<?= e($empleado['email_institucional'] ?? '') ?>" disabled readonly>
                            <small class="text-muted">El correo institucional es asignado por RH</small>
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
                
                <!-- 3. Información Laboral (SOLO LECTURA) -->
                <div id="laboral" class="form-section">
                    <div class="paper-header">
                        <h2 class="section-title"><i class="fas fa-briefcase text-warning"></i> Adscripción y Puesto</h2>
                        <span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Solo lectura</span>
                    </div>
                    
                    <div class="info-card">
                        <h5><i class="fas fa-info-circle me-1"></i> Información</h5>
                        <p class="mb-0 small" style="color: var(--text-secondary);">
                            La información laboral es gestionada por el área de Recursos Humanos.
                        </p>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <div class="readonly-label">Número de Empleado</div>
                            <div class="readonly-field font-monospace fw-bold"><?= e($empleado['numero_empleado'] ?? '-') ?></div>
                        </div>
                        <div class="form-group">
                            <div class="readonly-label">Estatus</div>
                            <div class="readonly-field">
                                <?php 
                                $estatusIcon = match($empleado['estatus'] ?? 'ACTIVO') {
                                    'ACTIVO' => '🟢',
                                    'BAJA' => '🔴',
                                    'LICENCIA' => '🟡',
                                    default => '⚪'
                                };
                                echo $estatusIcon . ' ' . e($empleado['estatus'] ?? 'ACTIVO');
                                ?>
                            </div>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <div class="readonly-label">Área de Adscripción</div>
                            <div class="readonly-field"><?= e($areaNombre) ?></div>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <div class="readonly-label">Puesto Oficial</div>
                            <div class="readonly-field"><?= e($puestoNombre) ?></div>
                        </div>
                        <div class="form-group">
                            <div class="readonly-label">Tipo de Nombramiento</div>
                            <div class="readonly-field"><?= e($empleado['nombramiento'] ?? '-') ?></div>
                        </div>
                        <div class="form-group">
                            <div class="readonly-label">Fecha de Ingreso</div>
                            <div class="readonly-field"><?= $empleado['fecha_ingreso'] ? date('d/m/Y', strtotime($empleado['fecha_ingreso'])) : '-' ?></div>
                        </div>
                        <div class="form-group">
                            <div class="readonly-label">Horario</div>
                            <div class="readonly-field"><?= e($empleado['horario'] ?? '-') ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- 4. Académico (SOLO LECTURA) -->
                <div id="academico" class="form-section">
                    <div class="paper-header">
                        <h2 class="section-title"><i class="fas fa-graduation-cap text-info"></i> Perfil Académico</h2>
                        <span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Solo lectura</span>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <div class="readonly-label">Último Grado de Estudios</div>
                            <div class="readonly-field"><?= e($empleado['ultimo_grado_estudios'] ?? '-') ?></div>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <div class="readonly-label">Profesión / Título</div>
                            <div class="readonly-field"><?= e($empleado['profesion'] ?? '-') ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- 5. Familiar (EDITABLE) -->
                <div id="familiar" class="form-section">
                    <div class="paper-header">
                        <h2 class="section-title"><i class="fas fa-users text-danger"></i> Datos Familiares y Sociales</h2>
                        <span class="badge bg-success"><i class="fas fa-edit me-1"></i>Editable</span>
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
                            <div class="form-check form-switch mt-2">
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
                
                <div class="action-bar">
                    <a href="<?= url('/index.php') ?>" class="btn btn-light border">
                        <i class="fas fa-arrow-left me-1"></i> Volver al Inicio
                    </a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-2"></i> Guardar Cambios
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
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            sections.forEach(s => s.classList.remove('active'));
            const targetId = tab.dataset.target;
            document.getElementById(targetId).classList.add('active');
            
            if (window.innerWidth < 900) {
                document.querySelector('.expediente-content').scrollIntoView({behavior: 'smooth'});
            }
        });
    });

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
            hijoIdx++;
            updateHijosCount();
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
