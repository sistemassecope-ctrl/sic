<?php
/**
 * Funciones Helper del Sistema
 * Utilidades comunes reutilizables
 */

/**
 * Escapar HTML para prevenir XSS
 * @param string|null $string
 * @return string
 */
function escape(?string $string): string
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Alias corto para escape
 */
function e(?string $string): string
{
    return escape($string);
}

/**
 * Generar token CSRF
 * @return string
 */
function generateCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 * @param string $token
 * @return bool
 */
function verifyCSRFToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtener campo de formulario CSRF
 * @return string HTML del campo hidden
 */
function csrfField(): string
{
    return '<input type="hidden" name="_token" value="' . generateCSRFToken() . '">';
}

/**
 * Verificar si la petición es AJAX
 * @return bool
 */
function isAjax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Responder JSON
 * @param array $data
 * @param int $statusCode
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Responder éxito JSON
 * @param string $message
 * @param array $data
 */
function jsonSuccess(string $message = 'Operación exitosa', array $data = []): void
{
    jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}

/**
 * Responder error JSON
 * @param string $message
 * @param int $statusCode
 */
function jsonError(string $message = 'Error en la operación', int $statusCode = 400): void
{
    jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

/**
 * Sanitizar string para base de datos
 * @param mixed $value
 * @return string
 */
function sanitize($value): string
{
    return trim(strip_tags($value ?? ''));
}

/**
 * Formatear fecha para mostrar
 * @param string|null $date
 * @param string $format
 * @return string
 */
function formatDate(?string $date, string $format = 'd/m/Y'): string
{
    if (empty($date)) {
        return '-';
    }
    return date($format, strtotime($date));
}

/**
 * Formatear fecha y hora para mostrar
 * @param string|null $datetime
 * @return string
 */
function formatDateTime(?string $datetime): string
{
    return formatDate($datetime, 'd/m/Y H:i');
}

/**
 * Obtener nombre completo del empleado
 * @param array $empleado Array con nombre, apellido_paterno, apellido_materno
 * @return string
 */
function getNombreCompleto(array $empleado): string
{
    return trim(sprintf(
        '%s %s %s',
        $empleado['nombre'] ?? '',
        $empleado['apellido_paterno'] ?? '',
        $empleado['apellido_materno'] ?? ''
    ));
}

/**
 * Obtener la ruta base del proyecto
 * @return string
 */
function basePath(): string
{
    return defined('BASE_PATH') ? BASE_PATH : '';
}

/**
 * Generar URL completa con base path
 * @param string $path
 * @return string
 */
function url(string $path = ''): string
{
    return basePath() . $path;
}

/**
 * Obtener la URL base del sitio
 * @return string
 */
function baseUrl(): string
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . basePath();
}

/**
 * Redireccionar a otra página (usa BASE_PATH automáticamente)
 * @param string $path Ruta relativa (ej: '/index.php')
 */
function redirect(string $path): void
{
    $url = basePath() . $path;
    header("Location: $url");
    exit;
}

/**
 * Mostrar mensaje flash (almacenado en sesión)
 * @param string $type success|error|warning|info
 * @param string $message
 */
function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Obtener y eliminar mensaje flash
 * @return array|null
 */
function getFlashMessage(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Renderizar mensaje flash como HTML
 * @return string
 */
function renderFlashMessage(): string
{
    $flash = getFlashMessage();

    if (!$flash) {
        return '';
    }

    $typeClasses = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];

    $class = $typeClasses[$flash['type']] ?? 'alert-info';

    return sprintf(
        '<div class="alert %s alert-dismissible fade show" role="alert">
            %s
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>',
        $class,
        e($flash['message'])
    );
}

/**
 * Obtener SQL para filtrar por ejercicio fiscal
 * @param string $column
 * @return string
 */
function getEjercicioFilterSQL(string $column): string
{
    $ejercicio = isset($_GET['ejercicio']) ? (int) $_GET['ejercicio'] : date('Y');
    return "$column = $ejercicio";
}
