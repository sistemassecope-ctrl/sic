<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIS-PAO</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
</head>

<body>

    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white mb-4">
            <div class="container-fluid">
                <button class="btn btn-outline-secondary d-md-none me-2" type="button" data-bs-toggle="collapse"
                    data-bs-target="#sidebarMenu">
                    <i class="bi bi-list"></i>
                </button>

                <h4 class="mb-0 text-primary">
                    <?php echo $page_title ?? 'SIS-PAO'; ?>
                </h4>

                <div class="ms-auto">
                    <ul class="navbar-nav flex-row">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i>
                                <span
                                    class="d-none d-sm-inline ms-1"><?php echo $_SESSION['user_name'] ?? 'Usuario'; ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end position-absolute">
                                <li><a class="dropdown-item"
                                        href="<?php echo BASE_URL; ?>/index.php?route=perfil/firma"><i
                                            class="bi bi-pen"></i> Configurar Firma</a></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Perfil</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger"
                                        href="<?php echo BASE_URL; ?>/index.php?route=logout"><i
                                            class="bi bi-box-arrow-right"></i> Salir</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid">
            <?php
            // Renderizar la vista solicitada
            if (isset($content_view) && file_exists($content_view)) {
                include $content_view;
            } else {
                echo "<div class='alert alert-warning'>Vista no encontrada.</div>";
            }
            ?>
        </div>

        <footer class="mt-5 text-center text-muted small pb-3">
            &copy;
            <?php echo date('Y'); ?> Secretaría de Comunicaciones y Obras Públicas del Estado
        </footer>
    </div>

    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sidebar Toggle Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebarMenu');
        const mainContent = document.querySelector('.main-content');
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        
        // Restore sidebar state from localStorage
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('sidebar-collapsed');
        }
        
        // Toggle sidebar
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('sidebar-collapsed');
                
                // Save state
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            });
        }
    });
    </script>
</body>

</html>