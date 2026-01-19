<?php
// Configuración principal del sistema

// Definir BASE_URL dinámicamente
if (!defined('BASE_URL')) {
    $script_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $root_fs = str_replace('\\', '/', __DIR__);
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    
    // Calcular ruta web relativa
    $web_path = str_replace($doc_root, '', $root_fs);
    $web_path = rtrim($web_path, '/');
    
    // Asegurar slash final
    define('BASE_URL', $web_path . '/');
}

// Incluir configuración de base de datos y funciones de conexión
require_once __DIR__ . '/config/database.php';
?>
