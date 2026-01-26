<?php
/**
 * Sidebar con soporte para módulos jerárquicos
 * Muestra menú en árbol basado en permisos del usuario
 */

$pdo = getConnection();
$user = getCurrentUser();
$isAdmin = isAdmin();

/**
 * Obtiene los módulos accesibles para el usuario actual
 * Estructura jerárquica: módulos padres con sus hijos
 */
function getAccessibleModules(): array
{
    $pdo = getConnection();
    $userId = $_SESSION['usuario_id'] ?? 0;
    $isAdmin = isAdmin();

    if ($isAdmin) {
        // Admin ve todos los módulos activos
        $sql = "SELECT DISTINCT m.* FROM modulos m WHERE m.estado = 1 ORDER BY m.orden";
        $stmt = $pdo->query($sql);
    } else {
        // Usuario normal: solo módulos donde tiene al menos un permiso
        $sql = "
            SELECT DISTINCT m.* 
            FROM modulos m
            INNER JOIN usuario_modulo_permisos ump ON m.id = ump.id_modulo
            WHERE ump.id_usuario = ? AND m.estado = 1
            ORDER BY m.orden
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
    }

    $modulos = $stmt->fetchAll();

    // Organizar en estructura jerárquica
    $tree = [];
    $children = [];

    foreach ($modulos as $mod) {
        if ($mod['id_padre'] === null) {
            $tree[$mod['id']] = $mod;
            $tree[$mod['id']]['children'] = [];
        } else {
            $children[$mod['id_padre']][] = $mod;
        }
    }

    // Asignar hijos a padres
    foreach ($children as $parentId => $kids) {
        if (isset($tree[$parentId])) {
            $tree[$parentId]['children'] = $kids;
        } else {
            // Si el padre no está en el árbol pero los hijos sí tienen acceso,
            // buscar el padre y agregarlo
            $stmtP = $pdo->prepare("SELECT * FROM modulos WHERE id = ?");
            $stmtP->execute([$parentId]);
            $parent = $stmtP->fetch();
            if ($parent) {
                $tree[$parentId] = $parent;
                $tree[$parentId]['children'] = $kids;
            }
        }
    }

    // Ordenar por orden
    uasort($tree, function ($a, $b) {
        return $a['orden'] - $b['orden'];
    });

    return $tree;
}

$modulosMenu = getAccessibleModules();

// Manually add Contratos menu (Temporary/Extension)
$modulosMenu[] = [
    'id' => 'custom-contratos',
    'nombre_modulo' => 'Contratos',
    'ruta' => null, // Folder/Parent
    'icono' => 'fa-file-signature',
    'orden' => 999,
    'children' => [
        [
            'nombre_modulo' => 'Gestión de Contratos',
            'ruta' => '/modulos/concursos/contratos/index.php',
            'icono' => 'fa-file-contract'
        ]
    ]
];
$currentPath = $_SERVER['REQUEST_URI'];
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <img src="<?= url('/assets/img/logoSecope.svg') ?>" alt="SECOPE Logo" class="logo-img">
        </div>
        <button class="sidebar-toggle-btn" id="sidebarToggle">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <?php foreach ($modulosMenu as $modulo): ?>
                <?php
                $hasChildren = !empty($modulo['children']);
                $moduleUrl = $modulo['ruta'] ? url($modulo['ruta']) : '#';
                $isActive = $modulo['ruta'] && strpos($currentPath, $modulo['ruta']) !== false;

                // Verificar si algún hijo está activo
                $childActive = false;
                if ($hasChildren) {
                    foreach ($modulo['children'] as $child) {
                        if ($child['ruta'] && strpos($currentPath, $child['ruta']) !== false) {
                            $childActive = true;
                            break;
                        }
                    }
                }
                ?>

                <li
                    class="nav-item <?= $hasChildren ? 'has-submenu' : '' ?> <?= ($isActive || $childActive) ? 'active' : '' ?>">
                    <?php if ($hasChildren): ?>
                        <!-- Módulo con submenú -->
                        <a href="javascript:void(0)" class="nav-link submenu-toggle" data-target="submenu-<?= $modulo['id'] ?>">
                            <i class="fas <?= e($modulo['icono'] ?? 'fa-cube') ?>"></i>
                            <span class="nav-text"><?= e($modulo['nombre_modulo']) ?></span>
                            <i class="fas fa-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= $childActive ? 'open' : '' ?>" id="submenu-<?= $modulo['id'] ?>">
                            <?php foreach ($modulo['children'] as $child):
                                $childUrl = $child['ruta'] ? url($child['ruta']) : '#';
                                $childIsActive = $child['ruta'] && strpos($currentPath, $child['ruta']) !== false;
                                ?>
                                <li class="submenu-item">
                                    <a href="<?= $childUrl ?>" class="submenu-link <?= $childIsActive ? 'active' : '' ?>">
                                        <i class="fas <?= e($child['icono'] ?? 'fa-circle') ?>"></i>
                                        <span><?= e($child['nombre_modulo']) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <!-- Módulo sin submenú -->
                        <a href="<?= $moduleUrl ?>" class="nav-link <?= $isActive ? 'active' : '' ?>">
                            <i class="fas <?= e($modulo['icono'] ?? 'fa-cube') ?>"></i>
                            <span class="nav-text"><?= e($modulo['nombre_modulo']) ?></span>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($user['nombre'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-details">
                <span class="user-name"><?= e($user['nombre'] ?? 'Usuario') ?></span>
                <span class="user-role"><?= e($user['nombre_area'] ?? '') ?></span>
            </div>
        </div>
        <a href="<?= url('/logout.php') ?>" class="logout-btn" title="Cerrar sesión">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</aside>

<script>
    // Toggle sidebar collapse/expand
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const body = document.body;

    // Restaurar estado del sidebar desde localStorage
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (sidebarCollapsed) {
        body.classList.add('sidebar-collapsed');
    }

    // Toggle al hacer click
    sidebarToggle.addEventListener('click', function() {
        body.classList.toggle('sidebar-collapsed');
        const isCollapsed = body.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    });

    // Toggle submenús
    document.querySelectorAll('.submenu-toggle').forEach(toggle => {
        toggle.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const submenu = document.getElementById(targetId);
            const parent = this.closest('.nav-item');

            // Cerrar otros submenús
            document.querySelectorAll('.submenu.open').forEach(sm => {
                if (sm.id !== targetId) {
                    sm.classList.remove('open');
                    sm.closest('.nav-item').classList.remove('expanded');
                }
            });

            // Toggle actual
            submenu.classList.toggle('open');
            parent.classList.toggle('expanded');
        });
    });

    // Abrir submenú activo al cargar la página
    document.querySelectorAll('.submenu-link.active').forEach(link => {
        const submenu = link.closest('.submenu');
        if (submenu) {
            submenu.classList.add('open');
            submenu.closest('.nav-item').classList.add('expanded');
        }
    });
</script>