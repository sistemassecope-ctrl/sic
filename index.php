<?php
/**
 * PAO v2 - Dashboard Principal
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

requireAuth();

$user = getCurrentUser();
$pdo = getConnection();

// Estadísticas - Solo para administradores
$stats = [];
$ultimosAccesos = [];

if (isAdmin()) {
    // Total empleados
    $stmt = $pdo->query("SELECT COUNT(*) FROM empleados WHERE activo = 1");
    $stats['empleados'] = $stmt->fetchColumn();

    // Total usuarios del sistema
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios_sistema WHERE estado = 1");
    $stats['usuarios'] = $stmt->fetchColumn();

    // Total áreas
    $stmt = $pdo->query("SELECT COUNT(*) FROM areas WHERE estado = 1");
    $stats['areas'] = $stmt->fetchColumn();

    // Total módulos
    $stmt = $pdo->query("SELECT COUNT(*) FROM modulos WHERE estado = 1");
    $stats['modulos'] = $stmt->fetchColumn();

    // Obtener últimos accesos (solo admins)
    $stmt = $pdo->query("
        SELECT 
            u.usuario, u.ultimo_acceso,
            CONCAT(e.nombres, ' ', e.apellido_paterno) as nombre_completo,
            a.nombre_area
        FROM usuarios_sistema u
        INNER JOIN empleados e ON u.id_empleado = e.id
        INNER JOIN areas a ON e.area_id = a.id
        WHERE u.ultimo_acceso IS NOT NULL
        ORDER BY u.ultimo_acceso DESC
        LIMIT 5
    ");
    $ultimosAccesos = $stmt->fetchAll();
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-description">Bienvenido, <?= e($user['nombre']) ?>.</p>
        </div>
        <div>
            <span class="badge badge-info" style="font-size: 0.9rem;">
                <i class="fas fa-calendar"></i>
                <?= date('d/m/Y H:i') ?>
            </span>
        </div>
    </div>
    
    <?= renderFlashMessage() ?>
    
    <?php if (isAdmin()): ?>
    <!-- Stats Cards - Solo para Administradores -->
    <div class="stats-grid fade-in">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($stats['empleados'] ?? 0) ?></div>
                <div class="stat-label">Empleados Activos</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($stats['usuarios'] ?? 0) ?></div>
                <div class="stat-label">Usuarios del Sistema</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($stats['areas'] ?? 0) ?></div>
                <div class="stat-label">Áreas Organizacionales</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-cubes"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($stats['modulos'] ?? 0) ?></div>
                <div class="stat-label">Módulos Activos</div>
            </div>
        </div>
    </div>
    
    <!-- Content Grid - Admin -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
        <!-- Recent Activity -->
        <div class="card fade-in">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clock" style="color: var(--accent-primary); margin-right: 0.5rem;"></i>
                    Últimos Accesos
                </h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Área</th>
                                <th>Último Acceso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ultimosAccesos)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: var(--text-muted);">
                                        No hay registros de acceso
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ultimosAccesos as $acceso): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="width: 32px; height: 32px; background: var(--gradient-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: white;">
                                                <?= strtoupper(substr($acceso['nombre_completo'], 0, 2)) ?>
                                            </span>
                                            <div>
                                                <div style="font-weight: 500;"><?= e($acceso['nombre_completo']) ?></div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted);">@<?= e($acceso['usuario']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?= e($acceso['nombre_area']) ?></span>
                                    </td>
                                    <td style="color: var(--text-secondary); font-size: 0.875rem;">
                                        <?= formatDateTime($acceso['ultimo_acceso']) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card fade-in">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt" style="color: var(--accent-warning); margin-right: 0.5rem;"></i>
                    Acciones Rápidas
                </h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <a href="<?= url('/admin/usuarios.php') ?>" class="btn btn-secondary" style="justify-content: flex-start; padding: 1rem;">
                        <i class="fas fa-user-plus"></i>
                        Nuevo Usuario
                    </a>
                    <a href="<?= url('/admin/permisos.php') ?>" class="btn btn-secondary" style="justify-content: flex-start; padding: 1rem;">
                        <i class="fas fa-key"></i>
                        Gestionar Permisos
                    </a>
                    <a href="<?= url('/modulos/recursos-humanos/empleados.php') ?>" class="btn btn-secondary" style="justify-content: flex-start; padding: 1rem;">
                        <i class="fas fa-id-card"></i>
                        Ver Empleados
                    </a>
                    <a href="<?= url('/reportes/index.php') ?>" class="btn btn-secondary" style="justify-content: flex-start; padding: 1rem;">
                        <i class="fas fa-file-alt"></i>
                        Generar Reportes
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Vista para Usuarios Normales (sin permisos de admin) -->
    <div class="fade-in" style="max-width: 800px; margin: 0 auto;">
        <!-- Card de Bienvenida -->
        <div class="card" style="text-align: center; padding: 2rem;">
            <div style="width: 80px; height: 80px; background: var(--gradient-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white; margin: 0 auto 1.5rem;">
                <?= strtoupper(substr($user['nombre'] ?? 'U', 0, 1)) ?>
            </div>
            <h2 style="margin-bottom: 0.5rem; color: var(--text-primary);">
                ¡Hola, <?= e($user['nombre']) ?>!
            </h2>
            <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                Bienvenido al Sistema Integral de Control
            </p>
            
            <!-- Tu Información -->
            <div style="background: rgba(88, 166, 255, 0.1); border-radius: var(--radius-md); border: 1px solid rgba(88, 166, 255, 0.2); padding: 1.5rem; text-align: left; margin-bottom: 2rem;">
                <h4 style="font-size: 0.9rem; margin-bottom: 1rem; color: var(--accent-primary);">
                    <i class="fas fa-info-circle"></i> Tu Información
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">Área</div>
                        <div style="font-weight: 500; color: var(--text-primary);"><?= e($user['nombre_area'] ?? 'No asignada') ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">Puesto</div>
                        <div style="font-weight: 500; color: var(--text-primary);"><?= e($user['nombre_puesto'] ?? 'No asignado') ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Acciones Disponibles -->
            <h4 style="font-size: 0.9rem; margin-bottom: 1rem; color: var(--text-secondary); text-align: left;">
                <i class="fas fa-hand-pointer"></i> Acciones Disponibles
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <?php if (!empty($user['empleado_id'])): ?>
                <a href="<?= url('/modulos/recursos-humanos/mi-expediente.php') ?>" class="btn btn-primary" style="padding: 1rem;">
                    <i class="fas fa-user-circle"></i>
                    Mi Expediente
                </a>
                <?php endif; ?>
                <a href="<?= url('/logout.php') ?>" class="btn btn-secondary" style="padding: 1rem;">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </a>
            </div>
            
            <p style="margin-top: 2rem; font-size: 0.85rem; color: var(--text-muted);">
                <i class="fas fa-lock"></i> 
                Si necesitas acceso a módulos adicionales, contacta a tu administrador.
            </p>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
