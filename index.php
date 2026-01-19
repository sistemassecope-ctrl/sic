<?php
// Punto de entrada de la aplicación

// Iniciar sesión para persistencia
session_start();

require_once 'config/db.php';
require_once 'app/controllers/AuthController.php';
require_once 'app/controllers/PaoController.php';

// Enrutador básico
$route = $_GET['route'] ?? 'login';

switch ($route) {
    case 'login':
        $controller = new AuthController();
        $controller->login();
        break;

    case 'logout':
        $controller = new AuthController();
        $controller->logout();
        break;

    case 'home':
        // Verificar autenticación
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/index.php?route=login");
            exit;
        }

        $page_title = 'Dashboard';
        $content_view = 'app/views/home.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'pao':
        // Por ahora mantenemos la compatibilidad con el controlador existente
        // Idealmente este controlador debería retornar datos o una vista parcial
        $controller = new PaoController();
        $controller->index();
        break;

    case 'recursos_financieros/programas_operativos':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        $page_title = 'Programas Operativos Anuales';
        $content_view = 'modulos/recursos_financieros/programas_operativos/index.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'recursos_financieros/programas_operativos/nuevo':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");

        // Verificación de Permiso Específico
        $permisos = $_SESSION['user_permisos'] ?? [];
        // Se permite acceso si tiene rol de superadmin (acceso_total) O el permiso específico
        if (!in_array('acceso_total', $permisos) && !in_array('capturar_programas_operativos', $permisos)) {
            echo "<div class='alert alert-danger m-5'>No tienes permiso para acceder a este módulo. Contacta al administrador.</div>";
            exit;
        }

        $page_title = 'Captura de Programa Operativo';
        $content_view = 'modulos/recursos_financieros/programas_operativos/captura.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'recursos_financieros/programas_operativos/guardar':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");

        // Ejecutar lógica de guardado
        require 'modulos/recursos_financieros/programas_operativos/guardar.php';
        break;

    case 'recursos_financieros/programas_operativos/proyectos':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");

        $page_title = 'Proyectos del Programa Operativo';
        $content_view = 'modulos/recursos_financieros/programas_operativos/listar_proyectos.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'recursos_financieros/proyectos/nuevo':
    case 'recursos_financieros/proyectos/editar':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");

        $page_title = 'Captura de Proyecto';
        $content_view = 'modulos/recursos_financieros/programas_operativos/captura_proyecto.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'recursos_financieros/proyectos/guardar':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        require 'modulos/recursos_financieros/programas_operativos/guardar_proyecto.php';
        break;

    case 'recursos_financieros/proyectos/eliminar':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        require 'modulos/recursos_financieros/programas_operativos/eliminar_proyecto.php';
        break;

    case 'recursos_financieros/programas_operativos/editar':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        $page_title = 'Editar Programa Operativo';
        $content_view = 'modulos/recursos_financieros/programas_operativos/captura.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'recursos_financieros/programas_operativos/eliminar':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        require 'modulos/recursos_financieros/programas_operativos/eliminar.php';
        break;

    // --- RUTAS DE FUAS ---
    case 'recursos_financieros/fuas':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        $page_title = 'Gestión de FUAs';
        $content_view = 'modulos/recursos_financieros/fuas/index.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'recursos_financieros/fuas/nuevo':
    case 'recursos_financieros/fuas/editar':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        $page_title = 'Captura de FUA';
        $content_view = 'modulos/recursos_financieros/fuas/captura.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'recursos_financieros/fuas/captura_carpeta':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        $page_title = 'Captura de FUA (Vista Carpeta)';
        $content_view = 'modulos/recursos_financieros/fuas/captura_carpeta.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'recursos_financieros/fuas/guardar':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        require 'modulos/recursos_financieros/fuas/guardar.php';
        break;

    case 'recursos_financieros/fuas/eliminar':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        require 'modulos/recursos_financieros/fuas/eliminar.php';
        break;

    // --- RUTAS DE CATALOGOS DE PROYECTO ---
    case 'recursos_financieros/catalogos':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        $page_title = 'Gestión de Catálogos';
        $content_view = 'modulos/recursos_financieros/catalogos/index.php';
        include 'app/views/layouts/wrapper.php';
        break;

    // --- RUTAS DE CATALOGO DE FUENTES ---
    case 'recursos_financieros/cat_fuentes':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        $page_title = 'Catálogo Fuentes Financiamiento';
        $content_view = 'modulos/recursos_financieros/cat_fuentes/index.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'recursos_financieros/cat_fuentes/captura':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        $page_title = 'Alta/Edición de Fuente';
        $content_view = 'modulos/recursos_financieros/cat_fuentes/captura.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'recursos_financieros/cat_fuentes/guardar':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        require 'modulos/recursos_financieros/cat_fuentes/guardar.php';
        break;


    case 'configuracion/areas_pao':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        $page_title = 'Configuración de Áreas PAO';
        $content_view = 'modulos/configuracion/areas_pao.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'configuracion/roles_usuarios':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        $page_title = 'Gestión de Roles y Usuarios';
        $content_view = 'modulos/configuracion/roles_usuarios.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'configuracion/permisos_usuarios':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        $page_title = 'Permisos Específicos por Usuario';
        $content_view = 'modulos/configuracion/permisos_usuarios.php';
        include 'app/views/layouts/wrapper.php';
        break;

    // --- RUTAS DE FIRMA DIGITAL ---
    case 'perfil/firma':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        $page_title = 'Configuración de Firma Digital';
        $content_view = 'modulos/perfil/firma.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'firmas/bandeja':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        $page_title = 'Bandeja de Firmas';
        $content_view = 'modulos/firmas/bandeja.php';
        include 'app/views/layouts/wrapper.php';
        break;

    // === MÓDULO: SOLICITUD DE COMBUSTIBLE ===
    case 'combustible/index':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        $page_title = 'Solicitudes de Combustible';
        $content_view = 'modulos/combustible/index.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'combustible/nuevo':
    case 'combustible/editar':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        $page_title = isset($_GET['id']) ? 'Editar Solicitud de Combustible' : 'Nueva Solicitud de Combustible';
        $content_view = 'modulos/combustible/create.php';
        include 'app/views/layouts/wrapper.php';
        break;

    case 'combustible/guardar':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        require 'modulos/combustible/save.php';
        exit;
        break;

    case 'combustible/imprimir':
        if (!isset($_SESSION['user_id']))
            header("Location: " . BASE_URL . "/index.php?route=login");
        require 'modulos/combustible/print.php';
        exit;
        break;

    default:
        // 404
        header("HTTP/1.0 404 Not Found");
        echo "Página no encontrada.";
        break;
}
?>