<div class="sidebar collapse d-md-block" id="sidebarMenu">
    <div class="sidebar-header">
        <img src="<?php echo BASE_URL; ?>img/logo_secope.png" alt="SIS-PAO" class="img-fluid sidebar-logo">
        <h5 class="mt-2 text-white sidebar-title">SIS-PAO</h5>
        <!-- Sidebar Toggle Button -->
        <button class="btn btn-sm btn-outline-light sidebar-toggle-btn" id="sidebarToggleBtn" title="Ocultar menú">
            <i class="bi bi-chevron-double-left"></i>
        </button>
    </div>
    <ul class="nav flex-column sidebar-menu">
        <li class="nav-item">
            <a class="nav-link <?php echo ($route == 'home') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>index.php?route=home">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>

        <!-- Módulo: Recursos Financieros -->
        <li class="nav-item">
            <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#financialSubmenu" role="button" aria-expanded="false" aria-controls="financialSubmenu">
                <span><i class="bi bi-cash-coin"></i> Rec. Financieros</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse <?php echo (strpos($route, 'financieros') !== false) ? 'show' : ''; ?>"
                id="financialSubmenu">
                <ul class="nav flex-column ms-3">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($route == 'recursos_financieros/programas_operativos') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>index.php?route=recursos_financieros/programas_operativos">
                            <i class="bi bi-list-check"></i> Prog. Operativos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($route, 'recursos_financieros/fuas') !== false) ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>index.php?route=recursos_financieros/fuas">
                            <i class="bi bi-file-earmark-text"></i> Formatos únicos de atención
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- Módulo: Recursos Humanos -->
        <li class="nav-item">
            <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#rhSubmenu" role="button" aria-expanded="false" aria-controls="rhSubmenu">
                <span><i class="bi bi-people"></i> Recursos Humanos</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse <?php echo (strpos($_SERVER['REQUEST_URI'], 'recursos_humanos') !== false || strpos($_SERVER['REQUEST_URI'], '/rh/') !== false) ? 'show' : ''; ?>" id="rhSubmenu">
                <ul class="nav flex-column ms-3">
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'empleados.php') !== false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>modulos/rh/empleados.php">
                            <i class="bi bi-person-badge"></i> Empleados
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'organigrama.php') !== false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>modulos/rh/organigrama.php">
                            <i class="bi bi-diagram-3"></i> Organigrama
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'areas.php') !== false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>modulos/rh/areas.php">
                            <i class="bi bi-building"></i> Áreas
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- Módulo: Concursos y Contratos -->
        <li class="nav-item">
            <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#concursosSubmenu" role="button" aria-expanded="false" aria-controls="concursosSubmenu">
                <span><i class="bi bi-briefcase"></i> Concursos y Contratos</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse <?php echo (strpos($route, 'concursos') !== false) ? 'show' : ''; ?>" id="concursosSubmenu">
                <ul class="nav flex-column ms-3">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>modulos/concursos/contratos/admin_certificados.php">
                            <i class="bi bi-award"></i> Certificados
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- Módulo: Configuración Sistema -->
        <li class="nav-item">
            <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#configSubmenu" role="button" aria-expanded="false" aria-controls="configSubmenu">
                <span><i class="bi bi-gear-fill"></i> Config. Sistema</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse <?php echo (strpos($route, 'config') !== false) ? 'show' : ''; ?>" id="configSubmenu">
                <ul class="nav flex-column ms-3">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($route == 'configuracion/roles_usuarios') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>index.php?route=configuracion/roles_usuarios">
                            <i class="bi bi-shield-lock"></i> Roles y Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($route == 'configuracion/permisos_usuarios') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>index.php?route=configuracion/permisos_usuarios">
                            <i class="bi bi-key"></i> Permisos por Usuario
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($route == 'configuracion/areas_pao') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>index.php?route=configuracion/areas_pao">
                            <i class="bi bi-diagram-3"></i> Areas para PAO
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="bi bi-circle"></i> B
            </a>
        </li>
    </ul>
</div>
