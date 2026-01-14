<?php
/**
 * Configuración de la Aplicación
 * Define constantes y configuraciones generales del sistema
 */

// Detección de entorno
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = in_array($host, ['localhost:44024', '127.0.0.1:44024'], true);

// Configuración del sistema
define('SITE_NAME', 'SIC - Sistema Integral SECOPE');
define('SITE_URL', $isLocal ? 'http://localhost:44024' : 'https://secope.gusati.net');
// Usuario reporta acceso vía /sic/ (minúsculas). Ajustamos para coincidir.
define('BASE_URL', '/sic/');
define('ADMIN_EMAIL', 'admin@secope.gob.mx');

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Configuración regional para español mexicano
setlocale(LC_ALL, 'es_MX.UTF-8', 'es_MX', 'Spanish_Mexico.1252', 'Spanish_Mexico', 'es');
ini_set('intl.default_locale', 'es_MX');

// Configuración de caracteres UTF-8
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');

// Configuración de errores según entorno
if ($isLocal) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}
error_reporting(E_ALL);
ini_set('log_errors', '1');

// Crear directorio de logs si no existe
$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}
ini_set('error_log', $log_dir . '/error.log');
