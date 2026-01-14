<?php
ob_start(); // Start buffering to catch any stray whitespace
require_once 'auth.php';
require_once 'functions.php';

// Verificar autenticación
requireAuth();

// Verificar expiración de sesión
if (!checkSessionExpiry()) {
    redirectWithMessage('login.php', 'warning', 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
}

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Limpiar cualquier salida previa (espacios en blanco, newlines de includes)
ob_clean();

// Configurar headers para UTF-8
header('Content-Type: text/html; charset=UTF-8');
header('Content-Language: es-MX');

// SALIDA HTML CONTROLADA BYTE POR BYTE
echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . ($pageTitle ?? 'SIC - Sistema Integral SECOPE') . '</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="' . BASE_URL . 'assets/css/styles.css" rel="stylesheet">';
    
    if (isset($extraCSS)) {
        foreach ($extraCSS as $css) {
            echo '<link href="' . (strpos($css, 'http') === 0 ? $css : BASE_URL . $css) . '" rel="stylesheet">';
        }
    }
echo '</head>
<body>';
?>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo BASE_URL; ?>img/logo_secope.png" alt="SECOPE Logo" class="img-fluid">
            <h5 class="mb-0">SIC</h5>
            <small>Sistema Integral SECOPE</small>
        </div>
        
        <nav class="sidebar-menu">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'index' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>index.php">
                        <i class="fas fa-home"></i>Inicio
                    </a>
                </li>
                
                <?php
                // Obtener módulos permitidos dinámicamente
                $modulosPermitidos = getModulosDisponibles();
                
                foreach ($modulosPermitidos as $modulo) {
                    // Determinar si el módulo actual está activo (basado en la URL)
                    $isActive = (strpos($_SERVER['PHP_SELF'], $modulo['url']) !== false) || 
                                ($currentPage . '.php' == basename($modulo['url']));
                    
                    // Icono por defecto si no viene en DB (aunque debería)
                    $icono = $modulo['icono'] ?? 'fas fa-circle';
                ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $isActive ? 'active' : ''; ?>" href="<?php echo BASE_URL . $modulo['url']; ?>">
                            <i class="<?php echo htmlspecialchars($icono); ?>"></i><?php echo htmlspecialchars($modulo['nombre']); ?>
                        </a>
                    </li>
                <?php
                }
                ?>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content"><nav class="navbar navbar-expand-lg navbar-light bg-white mb-0 shadow-sm py-1" style="min-height: 50px;">
            <div class="container-fluid">
                <button class="btn btn-link d-lg-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <a class="navbar-brand d-none d-lg-block p-0 m-0" href="<?php echo BASE_URL; ?>modulos/rh/empleados.php">
                    <img src="<?php echo BASE_URL; ?>img/logo_secope.png" alt="SECOPE" height="35" class="d-inline-block align-text-top">
                    <span class="ms-2 align-middle" style="font-size: 1.1rem;">Sistema Integral SECOPE</span>
                </a>
                
                <div class="navbar-nav ms-auto">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <?php 
                            if (!empty($currentUser['empleado_foto'])) {
                                $headerFoto = BASE_URL . 'uploads/empleados/' . $currentUser['empleado_foto']; 
                            ?>
                                <img src="<?php echo htmlspecialchars($headerFoto); ?>" 
                                     alt="User" 
                                     class="rounded-circle me-2" 
                                     width="32" height="32"
                                     style="object-fit: cover;"
                                     onerror="this.src='<?php echo BASE_URL; ?>img/user-placeholder.svg'">
                            <?php 
                            }
                            // Mostrar email institucional, si no existe, mostrar el usuario (evitar email personal si así se prefiere, o fallback mínimo)
                            echo htmlspecialchars($currentUser['email_institucional'] ?: ($currentUser['email'] ?: $currentUser['username'])); 
                            ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modulos/rh/mi_perfil.php">
                                <i class="fas fa-user me-2"></i>Mi Perfil
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                            </a></li>
                        </ul>
                    </div>
                </div>
        </nav>
        <div class="container-fluid p-0">

            <!-- Breadcrumb -->
            <?php if (isset($breadcrumb)): ?>
                <div class="mb-4">
                    <?php echo generateBreadcrumb($breadcrumb); ?>
                </div>
            <?php endif; ?>

            <!-- Alert Messages -->
            <?php echo getSessionAlert(); ?>
