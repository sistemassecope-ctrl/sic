<?php
/**
 * Módulo de Reportes
 * Genera reportes filtrados por las áreas del usuario
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

define('MODULO_ID', 5); // ID del módulo Reportes

$permisos = getUserPermissions(MODULO_ID);
$puedeVer = in_array('ver', $permisos);
$puedeExportar = in_array('exportar', $permisos);

if (!$puedeVer) {
    setFlashMessage('error', 'No tienes permiso para acceder a Reportes');
    redirect('/index.php');
}

$pdo = getConnection();
$user = getCurrentUser();
$areasUsuario = getUserAreas();

// Estadísticas filtradas por áreas del usuario
$areaFilterSQL = getAreaFilterSQL('e.id_area');

// Total empleados en áreas visibles
$stmt = $pdo->query("SELECT COUNT(*) FROM empleados e WHERE estado = 1 AND $areaFilterSQL");
$totalEmpleados = $stmt->fetchColumn();

// Empleados por área (solo áreas visibles)
$empleadosPorArea = $pdo->query("
    SELECT a.nombre_area, COUNT(e.id) as total
    FROM areas a
    LEFT JOIN empleados e ON a.id = e.id_area AND e.estado = 1
    WHERE a.estado = 1 AND " . getAreaFilterSQL('a.id') . "
    GROUP BY a.id, a.nombre_area
    ORDER BY total DESC
")->fetchAll();

// Empleados por puesto (solo de áreas visibles)
$empleadosPorPuesto = $pdo->query("
    SELECT p.nombre_puesto, COUNT(e.id) as total
    FROM puestos p
    LEFT JOIN empleados e ON p.id = e.id_puesto AND e.estado = 1 AND $areaFilterSQL
    WHERE p.estado = 1
    GROUP BY p.id, p.nombre_puesto
    HAVING total > 0
    ORDER BY total DESC
")->fetchAll();

// Usuarios del sistema por área (solo áreas visibles)
$usuariosPorArea = $pdo->query("
    SELECT a.nombre_area, COUNT(u.id) as total
    FROM areas a
    LEFT JOIN empleados e ON a.id = e.id_area
    LEFT JOIN usuarios_sistema u ON e.id = u.id_empleado AND u.estado = 1
    WHERE a.estado = 1 AND " . getAreaFilterSQL('a.id') . "
    GROUP BY a.id, a.nombre_area
    ORDER BY a.nombre_area
")->fetchAll();

// Obtener áreas visibles para mostrar
$areasVisibles = $pdo->query("
    SELECT * FROM areas WHERE estado = 1 AND " . getAreaFilterSQL('id') . " ORDER BY nombre_area
")->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fas fa-chart-bar" style="color: var(--accent-warning);"></i>
                Reportes del Sistema
            </h1>
            <p class="page-description">
                Estadísticas y reportes de tu organización
                <?php if (count($areasUsuario) < 5): ?>
                    <span class="badge badge-warning" style="margin-left: 0.5rem;">
                        <i class="fas fa-filter"></i> 
                        Datos filtrados: <?= count($areasUsuario) ?> área(s)
                    </span>
                <?php else: ?>
                    <span class="badge badge-success" style="margin-left: 0.5rem;">
                        <i class="fas fa-globe"></i> 
                        Vista completa
                    </span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    
    <?= renderFlashMessage() ?>
    
    <!-- Información de áreas visibles -->
    <div class="card" style="margin-bottom: 1.5rem; background: linear-gradient(135deg, rgba(240, 136, 62, 0.1) 0%, rgba(248, 81, 73, 0.1) 100%);">
        <div class="card-body" style="padding: 1rem 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <div>
                    <strong><i class="fas fa-eye"></i> Áreas en este reporte:</strong>
                </div>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php foreach ($areasVisibles as $area): ?>
                        <span class="badge badge-info"><?= e($area['nombre_area']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($totalEmpleados) ?></div>
                <div class="stat-label">Empleados en tus áreas</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= count($areasVisibles) ?></div>
                <div class="stat-label">Áreas visibles</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-briefcase"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= count($empleadosPorPuesto) ?></div>
                <div class="stat-label">Puestos ocupados</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= array_sum(array_column($usuariosPorArea, 'total')) ?></div>
                <div class="stat-label">Usuarios del sistema</div>
            </div>
        </div>
    </div>
    
    <!-- Gráficas/Tablas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
        
        <!-- Empleados por Área -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-building" style="color: var(--accent-primary);"></i>
                    Empleados por Área
                </h3>
                <?php if ($puedeExportar): ?>
                <a href="<?= url('/reportes/exportar-area.php') ?>" class="btn btn-sm btn-success">
                    <i class="fas fa-download"></i> CSV
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Área</th>
                            <th style="text-align: center;">Empleados</th>
                            <th style="width: 40%;">Distribución</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $maxEmpleados = max(array_column($empleadosPorArea, 'total') ?: [1]);
                        foreach ($empleadosPorArea as $item): 
                            $porcentaje = $maxEmpleados > 0 ? ($item['total'] / $maxEmpleados) * 100 : 0;
                        ?>
                        <tr>
                            <td style="font-weight: 500;"><?= e($item['nombre_area']) ?></td>
                            <td style="text-align: center;">
                                <span class="badge badge-info"><?= $item['total'] ?></span>
                            </td>
                            <td>
                                <div style="background: var(--bg-tertiary); border-radius: 4px; height: 20px; overflow: hidden;">
                                    <div style="background: var(--gradient-primary); width: <?= $porcentaje ?>%; height: 100%; transition: width 0.5s ease;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Empleados por Puesto -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-briefcase" style="color: var(--accent-secondary);"></i>
                    Empleados por Puesto
                </h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Puesto</th>
                            <th style="text-align: center;">Total</th>
                            <th style="width: 40%;">Distribución</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $maxPuesto = max(array_column($empleadosPorPuesto, 'total') ?: [1]);
                        foreach ($empleadosPorPuesto as $item): 
                            $porcentaje = $maxPuesto > 0 ? ($item['total'] / $maxPuesto) * 100 : 0;
                        ?>
                        <tr>
                            <td style="font-weight: 500;"><?= e($item['nombre_puesto']) ?></td>
                            <td style="text-align: center;">
                                <span class="badge badge-success"><?= $item['total'] ?></span>
                            </td>
                            <td>
                                <div style="background: var(--bg-tertiary); border-radius: 4px; height: 20px; overflow: hidden;">
                                    <div style="background: var(--gradient-success); width: <?= $porcentaje ?>%; height: 100%; transition: width 0.5s ease;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    
    <!-- Nota informativa -->
    <div class="card" style="margin-top: 1.5rem;">
        <div class="card-body" style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 48px; height: 48px; background: rgba(88, 166, 255, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-info-circle" style="font-size: 1.5rem; color: var(--accent-primary);"></i>
            </div>
            <div>
                <strong style="color: var(--accent-primary);">Nota sobre los datos mostrados</strong>
                <p style="margin: 0.25rem 0 0; color: var(--text-secondary); font-size: 0.9rem;">
                    Los reportes solo incluyen información de las áreas a las que tienes acceso asignado.
                    <?php if (count($areasUsuario) < 5): ?>
                        Contacta a un administrador si necesitas ver información de otras áreas.
                    <?php else: ?>
                        Tienes acceso completo a todas las áreas de la organización.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
