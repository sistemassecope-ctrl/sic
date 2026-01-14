<?php
// config_produccion.php - Configuraciones específicas para producción
// Este archivo debe ser incluido en los archivos principales para producción

// Configuración del dominio de producción
define('DOMINIO_PRODUCCION', 'https://padron.gusati.net');
define('ES_PRODUCCION', true);

// Configuración de URLs
function getUrlProduccion($archivo = '') {
    return DOMINIO_PRODUCCION . '/' . $archivo;
}

// Configuración de validación HTTPS
function isHttps() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           $_SERVER['SERVER_PORT'] == 443 ||
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

// Función para generar URLs de validación
function getUrlValidacion($hash) {
    $protocol = isHttps() ? 'https' : 'http';
    return $protocol . '://padron.gusati.net/validar_certificado.php?hash=' . $hash;
}

// Configuración de errores para producción
if (ES_PRODUCCION) {
    // Ocultar errores en producción
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'error.log');
}

// Configuración de headers de seguridad
if (ES_PRODUCCION) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    // Forzar HTTPS en producción
    if (!isHttps()) {
        header('Location: ' . DOMINIO_PRODUCCION . $_SERVER['REQUEST_URI'], true, 301);
        exit();
    }
}
?>
