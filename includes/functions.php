<?php
require_once __DIR__ . '/../config.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = conectarDB();
}













/**
 * Repara problemas comunes de codificación (CP850 interpretado como Latin1/UTF-8)
 * Corrige principalmente Ñ mostrada como ¥
 */
function reparar_texto($str)
{
    if (!$str)
        return '';

    // Mapeo directo de caracteres problemáticos comunes
    $map = [
        '¥' => 'Ñ',
        '‚' => 'é',
        '¡' => 'í', // A1 (CP850 í) -> A1 (Latin1 ¡)
        '¢' => 'ó', // A2 (CP850 ó) -> A2 (Latin1 ¢)
        '£' => 'ú'  // A3 (CP850 ú) -> A3 (Latin1 £)
    ];

    return str_replace(array_keys($map), array_values($map), $str);
}

/**
 * Obtener estadísticas del dashboard
 */
function getDashboardStats()
{
    global $pdo;

    $stats = [
        'dependencias' => 0,
        'empleados' => 0,
        'puestos' => 0,
        'por_genero' => [],
        'top_dependencias' => []
    ];

    try {
        // Total de dependencias
        $sql = "SELECT COUNT(*) as total FROM area WHERE activo = TRUE";
        $stmt = $pdo->query($sql);
        $stats['dependencias'] = $stmt->fetch()['total'] ?? 0;

        // Total de empleados
        $sql = "SELECT COUNT(*) as total FROM empleados WHERE activo = TRUE";
        $stmt = $pdo->query($sql);
        $stats['empleados'] = $stmt->fetch()['total'] ?? 0;

        // Total de puestos de trabajo
        $sql = "SELECT COUNT(*) as total FROM puestos_trabajo WHERE activo = TRUE";
        $stmt = $pdo->query($sql);
        $stats['puestos'] = $stmt->fetch()['total'] ?? 0;

        // Empleados por género
        $sql = "SELECT genero, COUNT(*) as cantidad FROM empleados WHERE activo = TRUE GROUP BY genero";
        $stmt = $pdo->query($sql);
        $stats['por_genero'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Empleados por dependencia
        $sql = "SELECT d.nombre, COUNT(*) as cantidad 
                FROM empleados e 
                LEFT JOIN area d ON e.dependencia_id = d.id 
                WHERE e.activo = TRUE 
                GROUP BY d.nombre 
                ORDER BY cantidad DESC 
                LIMIT 5";
        $stmt = $pdo->query($sql);
        $stats['top_dependencias'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error obteniendo estadísticas del dashboard: " . $e->getMessage());
        // Mantener valores por defecto en caso de error
    }

    return $stats;
}

/**
 * Obtener areas para el sidebar
 */
function getDependenciasForSidebar()
{
    global $pdo;

    $sql = "SELECT id, nombre, tipo FROM area WHERE activo = TRUE ORDER BY nombre";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener empleados para formularios
 */
function obtenerEmpleados($filtro = '')
{
    global $pdo;

    try {
        $sql = "SELECT e.*, p.nombre as puesto_nombre, d.nombre as dependencia_nombre,
                CONCAT(COALESCE(e.nombres, ''), ' ', COALESCE(e.apellido_paterno, ''), ' ', COALESCE(e.apellido_materno, '')) as nombre_completo
                FROM empleados e 
                LEFT JOIN puestos_trabajo p ON e.puesto_trabajo_id = p.id 
                LEFT JOIN area d ON e.dependencia_id = d.id 
                WHERE e.activo = TRUE $filtro
                ORDER BY e.apellido_paterno, e.apellido_materno, e.nombres";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo empleados: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener niveles de usuario
 */
function obtenerNivelesUsuario()
{
    global $pdo;

    try {
        $sql = "SELECT id, nombre FROM niveles_usuario WHERE activo = TRUE ORDER BY nombre";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo niveles de usuario: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener usuarios del sistema
 */
function obtenerUsuarios()
{
    global $pdo;

    try {
        $sql = "SELECT u.*, 
                       nu.nombre as nivel_nombre,
                       CONCAT(e.nombre, ' ', e.apellido_paterno, ' ', e.apellido_materno) as empleado_nombre
                FROM usuarios_sistema u
                LEFT JOIN niveles_usuario nu ON u.nivel_usuario_id = nu.id
                LEFT JOIN empleados e ON u.empleado_id = e.id
                WHERE u.activo = TRUE
                ORDER BY u.email";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo usuarios: " . $e->getMessage());
        return [];
    }
}

/**
 * Formatear fecha en español mexicano
 */
function formatDate($date, $format = 'd/m/Y')
{
    if (!$date)
        return 'Sin fecha';

    // Configurar locale para español mexicano
    setlocale(LC_TIME, 'es_MX.UTF-8', 'es_MX', 'Spanish_Mexico.1252', 'Spanish_Mexico', 'es');

    $timestamp = strtotime($date);

    // Si el formato incluye nombres de meses o días, usar strftime
    if (
        strpos($format, 'F') !== false || strpos($format, 'M') !== false ||
        strpos($format, 'l') !== false || strpos($format, 'D') !== false
    ) {
        return strftime($format, $timestamp);
    }

    return date($format, $timestamp);
}

/**
 * Formatear fecha completa en español mexicano
 */
function formatDateFull($date)
{
    if (!$date)
        return 'Sin fecha';

    setlocale(LC_TIME, 'es_MX.UTF-8', 'es_MX', 'Spanish_Mexico.1252', 'Spanish_Mexico', 'es');

    $timestamp = strtotime($date);
    return strftime('%A %d de %B de %Y', $timestamp);
}

/**
 * Formatear fecha y hora en español mexicano
 */
function formatDateTime($date)
{
    if (!$date)
        return 'Sin fecha';

    setlocale(LC_TIME, 'es_MX.UTF-8', 'es_MX', 'Spanish_Mexico.1252', 'Spanish_Mexico', 'es');

    $timestamp = strtotime($date);
    return strftime('%d de %B de %Y a las %H:%M', $timestamp);
}

/**
 * Formatear número de teléfono
 */
function formatPhone($phone)
{
    if (!$phone)
        return 'Sin teléfono';

    // Remover caracteres no numéricos
    $phone = preg_replace('/[^0-9]/', '', $phone);

    if (strlen($phone) == 10) {
        return substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6, 4);
    }

    return $phone;
}

/**
 * Formatear salario
 */
function formatSalary($salary)
{
    if (!$salary || $salary == 0)
        return 'Sin especificar';
    return '$' . number_format($salary, 2);
}

/**
 * Obtener nombre del tipo de dependencia
 */
function getTipoDependencia($tipo)
{
    $tipos = [
        'direccion' => 'Dirección',
        'jefatura' => 'Jefatura',
        'departamento' => 'Departamento',
        'area' => 'Área',
        'seccion' => 'Sección'
    ];

    return $tipos[$tipo] ?? $tipo;
}

/**
 * Generar breadcrumb
 */
function generateBreadcrumb($items)
{
    $breadcrumb = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';

    foreach ($items as $index => $item) {
        if ($index == count($items) - 1) {
            $breadcrumb .= '<li class="breadcrumb-item active" aria-current="page">' . $item['text'] . '</li>';
        } else {
            $breadcrumb .= '<li class="breadcrumb-item"><a href="' . $item['url'] . '">' . $item['text'] . '</a></li>';
        }
    }

    $breadcrumb .= '</ol></nav>';
    return $breadcrumb;
}

/**
 * Validar email
 */
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validar RFC
 */
function validateRFC($rfc)
{
    // RFC debe tener 13 caracteres (sin homoclave) o 10-13 caracteres
    $rfc = strtoupper(trim($rfc));
    return preg_match('/^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$/', $rfc);
}

/**
 * Validar CURP
 */
function validateCURP($curp)
{
    // CURP debe tener 18 caracteres
    $curp = strtoupper(trim($curp));
    return preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{2}[A-Z0-9]{5}[0-9A-Z][0-9]$/', $curp);
}

/**
 * Obtener edad desde fecha de nacimiento
 */
function getAge($birthDate)
{
    if (!$birthDate)
        return null;

    $birth = new DateTime($birthDate);
    $today = new DateTime();
    $age = $today->diff($birth);
    return $age->y;
}

/**
 * Obtener antigüedad desde fecha de ingreso
 */
function getSeniority($hireDate)
{
    if (!$hireDate)
        return null;

    $hire = new DateTime($hireDate);
    $today = new DateTime();
    $seniority = $today->diff($hire);

    $years = $seniority->y;
    $months = $seniority->m;

    if ($years > 0) {
        return $years . ' año' . ($years > 1 ? 's' : '') . ($months > 0 ? ' ' . $months . ' mes' . ($months > 1 ? 'es' : '') : '');
    } else {
        return $months . ' mes' . ($months > 1 ? 'es' : '');
    }
}

/**
 * Generar alerta
 */
function generateAlert($type, $message)
{
    $alertClass = 'alert-' . $type;
    return '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
                ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

/**
 * Obtener mensaje de error
 */
function getErrorMessage($error)
{
    $messages = [
        'unauthorized' => 'No tienes permisos para acceder a esta página.',
        'session_expired' => 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.',
        'invalid_credentials' => 'Usuario o contraseña incorrectos.',
        'database_error' => 'Error en la base de datos. Por favor, intenta nuevamente.',
        'validation_error' => 'Por favor, verifica los datos ingresados.',
        'file_upload_error' => 'Error al subir el archivo.',
        'not_found' => 'El recurso solicitado no fue encontrado.'
    ];

    return $messages[$error] ?? 'Ha ocurrido un error inesperado.';
}



/**
 * Verificar si es una petición AJAX
 */
function isAjaxRequest()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Respuesta JSON
 */
function jsonResponse($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Redireccionar con mensaje
 */
function redirectWithMessage($url, $type, $message)
{
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
    header('Location: ' . $url);
    exit;
}

/**
 * Obtener mensaje de alerta de sesión
 */
function getSessionAlert()
{
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return generateAlert($alert['type'], $alert['message']);
    }
    return '';
}

/**
 * Normalizar texto para búsquedas con acentos
 */
function normalizeText($text)
{
    if (!$text)
        return '';

    // Convertir a minúsculas
    $text = mb_strtolower($text, 'UTF-8');

    // Reemplazar caracteres acentuados por sus equivalentes sin acento
    $text = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
        ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
        $text
    );

    return $text;
}

/**
 * Sanitizar texto para entrada de datos
 */
function sanitizeText($text)
{
    if (!$text)
        return '';

    // Convertir a UTF-8 si no lo está
    if (!mb_check_encoding($text, 'UTF-8')) {
        $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
    }

    // Remover caracteres de control excepto saltos de línea y tabulaciones
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

    // Normalizar espacios
    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}

/**
 * Validar texto con caracteres especiales
 */
function validateTextWithAccents($text, $minLength = 1, $maxLength = 255)
{
    if (!$text)
        return false;

    $length = mb_strlen($text, 'UTF-8');

    if ($length < $minLength || $length > $maxLength) {
        return false;
    }

    // Verificar que solo contenga letras, números, espacios y caracteres especiales válidos
    return preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s\-\.\,\;\:\!\?\(\)]+$/u', $text);
}

/**
 * Formatear nombre completo en español mexicano
 */
function formatFullName($nombre, $apellidoPaterno, $apellidoMaterno = '')
{
    $nombre = trim($nombre);
    $apellidoPaterno = trim($apellidoPaterno);
    $apellidoMaterno = trim($apellidoMaterno);

    $fullName = $nombre;

    if ($apellidoPaterno) {
        $fullName .= ' ' . $apellidoPaterno;
    }

    if ($apellidoMaterno) {
        $fullName .= ' ' . $apellidoMaterno;
    }

    return $fullName;
}

/**
 * Capitalizar texto en español mexicano
 */
function capitalizeText($text)
{
    if (!$text)
        return '';

    // Convertir a UTF-8
    $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

    // Capitalizar primera letra de cada palabra
    $words = explode(' ', $text);
    $capitalized = [];

    foreach ($words as $word) {
        if (mb_strlen($word, 'UTF-8') > 0) {
            $firstChar = mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8');
            $rest = mb_strtolower(mb_substr($word, 1, null, 'UTF-8'), 'UTF-8');
            $capitalized[] = $firstChar . $rest;
        }
    }

    return implode(' ', $capitalized);
}

/**
 * Calcular RFC con nueva estructura de nombre separado
 */
function calcularRFC($apellidoPaterno, $apellidoMaterno, $nombres, $fechaNacimiento, $genero)
{
    // Normalizar y limpiar datos
    $apellidoPaterno = limpiarTexto($apellidoPaterno);
    $apellidoMaterno = limpiarTexto($apellidoMaterno);
    $nombres = limpiarTexto($nombres);

    // Obtener primera letra del apellido paterno
    $primerApellido = obtenerPrimeraVocal($apellidoPaterno);
    if (!$primerApellido) {
        $primerApellido = substr($apellidoPaterno, 0, 1);
    }

    // Obtener primera letra del apellido materno
    $segundoApellido = substr($apellidoMaterno, 0, 1);

    // Obtener primera letra del nombre
    $primerNombre = substr($nombres, 0, 1);

    // Formatear fecha (YYMMDD)
    $fecha = date('ymd', strtotime($fechaNacimiento));

    // Generar homoclave (3 caracteres alfanuméricos)
    $homoclave = generarHomoclave($apellidoPaterno, $apellidoMaterno, $nombres, $fechaNacimiento);

    return strtoupper($primerApellido . $segundoApellido . $primerNombre . $fecha . $homoclave);
}

/**
 * Calcular CURP con nueva estructura de nombre separado
 */
function calcularCURP($apellidoPaterno, $apellidoMaterno, $nombres, $fechaNacimiento, $genero, $estadoNacimiento)
{
    // Normalizar y limpiar datos
    $apellidoPaterno = limpiarTexto($apellidoPaterno);
    $apellidoMaterno = limpiarTexto($apellidoMaterno);
    $nombres = limpiarTexto($nombres);

    // Primera letra del apellido paterno
    $curp = substr($apellidoPaterno, 0, 1);

    // Primera vocal del apellido paterno
    $vocal = obtenerPrimeraVocal($apellidoPaterno);
    $curp .= $vocal ? $vocal : 'X';

    // Primera letra del apellido materno
    $curp .= substr($apellidoMaterno, 0, 1);

    // Primera letra del nombre
    $curp .= substr($nombres, 0, 1);

    // Fecha de nacimiento (YYMMDD)
    $curp .= date('ymd', strtotime($fechaNacimiento));

    // Género (H/M)
    $curp .= ($genero == 'Hombre') ? 'H' : 'M';

    // Estado de nacimiento (2 letras)
    $curp .= obtenerClaveEstado($estadoNacimiento);

    // Consonantes internas
    $curp .= obtenerConsonantesInternas($apellidoPaterno);
    $curp .= obtenerConsonantesInternas($apellidoMaterno);
    $curp .= obtenerConsonantesInternas($nombres);

    // Dígito verificador
    $curp .= calcularDigitoVerificador($curp);

    return strtoupper($curp);
}

/**
 * Limpiar texto para RFC/CURP
 */
function limpiarTexto($texto)
{
    $texto = mb_strtoupper(trim($texto), 'UTF-8');
    $texto = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], ['A', 'E', 'I', 'O', 'U', 'X'], $texto);
    $texto = preg_replace('/[^A-Z]/', '', $texto);
    return $texto;
}

/**
 * Obtener primera vocal de una palabra
 */
function obtenerPrimeraVocal($palabra)
{
    $vocales = ['A', 'E', 'I', 'O', 'U'];
    for ($i = 1; $i < strlen($palabra); $i++) {
        if (in_array($palabra[$i], $vocales)) {
            return $palabra[$i];
        }
    }
    return false;
}

/**
 * Obtener consonantes internas (excluyendo la primera)
 */
function obtenerConsonantesInternas($palabra)
{
    $consonantes = [];
    $vocales = ['A', 'E', 'I', 'O', 'U'];

    for ($i = 1; $i < strlen($palabra); $i++) {
        if (!in_array($palabra[$i], $vocales)) {
            $consonantes[] = $palabra[$i];
        }
    }

    return implode('', $consonantes);
}

/**
 * Obtener clave de estado para CURP
 */
function obtenerClaveEstado($estado)
{
    $estados = [
        'AGUASCALIENTES' => 'AS',
        'BAJA CALIFORNIA' => 'BC',
        'BAJA CALIFORNIA SUR' => 'BS',
        'CAMPECHE' => 'CC',
        'COAHUILA' => 'CL',
        'COLIMA' => 'CM',
        'CHIAPAS' => 'CS',
        'CHIHUAHUA' => 'CH',
        'CIUDAD DE MEXICO' => 'DF',
        'DURANGO' => 'DG',
        'GUANAJUATO' => 'GT',
        'GUERRERO' => 'GR',
        'HIDALGO' => 'HG',
        'JALISCO' => 'JC',
        'MEXICO' => 'MC',
        'MICHOACAN' => 'MN',
        'MORELOS' => 'MS',
        'NAYARIT' => 'NT',
        'NUEVO LEON' => 'NL',
        'OAXACA' => 'OC',
        'PUEBLA' => 'PL',
        'QUERETARO' => 'QT',
        'QUINTANA ROO' => 'QR',
        'SAN LUIS POTOSI' => 'SP',
        'SINALOA' => 'SL',
        'SONORA' => 'SR',
        'TABASCO' => 'TC',
        'TAMAULIPAS' => 'TS',
        'TLAXCALA' => 'TL',
        'VERACRUZ' => 'VZ',
        'YUCATAN' => 'YN',
        'ZACATECAS' => 'ZS',
        'EXTRANJERO' => 'NE'
    ];

    $estadoUpper = mb_strtoupper(trim($estado), 'UTF-8');
    return $estados[$estadoUpper] ?? 'NE';
}

/**
 * Generar homoclave para RFC
 */
function generarHomoclave($apellidoPaterno, $apellidoMaterno, $nombres, $fechaNacimiento)
{
    // Algoritmo simplificado para generar homoclave
    $base = $apellidoPaterno . $apellidoMaterno . $nombres . $fechaNacimiento;
    $hash = md5($base);

    // Tomar 3 caracteres del hash y convertir a alfanumérico
    $homoclave = substr($hash, 0, 3);
    $homoclave = preg_replace('/[^A-Z0-9]/', 'A', strtoupper($homoclave));

    return $homoclave;
}

/**
 * Calcular dígito verificador para CURP
 */
function calcularDigitoVerificador($curp)
{
    $valores = [
        '0' => 0,
        '1' => 1,
        '2' => 2,
        '3' => 3,
        '4' => 4,
        '5' => 5,
        '6' => 6,
        '7' => 7,
        '8' => 8,
        '9' => 9,
        'A' => 10,
        'B' => 11,
        'C' => 12,
        'D' => 13,
        'E' => 14,
        'F' => 15,
        'G' => 16,
        'H' => 17,
        'I' => 18,
        'J' => 19,
        'K' => 20,
        'L' => 21,
        'M' => 22,
        'N' => 23,
        'O' => 24,
        'P' => 25,
        'Q' => 26,
        'R' => 27,
        'S' => 28,
        'T' => 29,
        'U' => 30,
        'V' => 31,
        'W' => 32,
        'X' => 33,
        'Y' => 34,
        'Z' => 35
    ];

    $suma = 0;
    for ($i = 0; $i < 17; $i++) {
        $suma += $valores[$curp[$i]] * (18 - $i);
    }

    $residuo = $suma % 10;
    return $residuo == 0 ? '0' : (10 - $residuo);
}

/**
 * Formatear nombre completo con nueva estructura
 */
function formatFullNameNew($apellidoPaterno, $apellidoMaterno, $nombres)
{
    $apellidoPaterno = trim($apellidoPaterno);
    $apellidoMaterno = trim($apellidoMaterno);
    $nombres = trim($nombres);

    $fullName = $nombres;

    if ($apellidoPaterno) {
        $fullName .= ' ' . $apellidoPaterno;
    }

    if ($apellidoMaterno) {
        $fullName .= ' ' . $apellidoMaterno;
    }

    return $fullName;
}

/**
 * Validar estructura de nombre separado
 */
function validateSeparatedName($apellidoPaterno, $apellidoMaterno, $nombres)
{
    $apellidoPaterno = trim($apellidoPaterno);
    $apellidoMaterno = trim($apellidoMaterno);
    $nombres = trim($nombres);

    // Validar que al menos apellido paterno y nombres estén presentes
    if (empty($apellidoPaterno) || empty($nombres)) {
        return false;
    }

    // Validar longitud mínima
    if (mb_strlen($apellidoPaterno, 'UTF-8') < 2 || mb_strlen($nombres, 'UTF-8') < 2) {
        return false;
    }

    // Validar que solo contengan letras, espacios y caracteres especiales válidos
    $pattern = '/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/u';

    return preg_match($pattern, $apellidoPaterno) &&
        preg_match($pattern, $apellidoMaterno) &&
        preg_match($pattern, $nombres);
}

/**
 * Generar contraseña temporal para nuevos usuarios
 */
/**
 * Obtener el ID del nivel de usuario destinado a empleados (nivel 6 por defecto)
 */
function obtenerNivelUsuarioEmpleadoBase()
{
    global $pdo;

    static $nivelEmpleadoId = null;
    if ($nivelEmpleadoId !== null) {
        return $nivelEmpleadoId;
    }

    $consultas = [
        ['SELECT id FROM niveles_usuario WHERE nivel_prioridad = 6 LIMIT 1', []],
        ['SELECT id FROM niveles_usuario WHERE id = 6 LIMIT 1', []],
        ['SELECT id FROM niveles_usuario WHERE nombre LIKE ? ORDER BY nivel_prioridad DESC LIMIT 1', ['%emple%']],
        ['SELECT id FROM niveles_usuario ORDER BY nivel_prioridad DESC LIMIT 1', []],
    ];

    foreach ($consultas as [$sql, $params]) {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($row['id'])) {
                $nivelEmpleadoId = (int) $row['id'];
                return $nivelEmpleadoId;
            }
        } catch (PDOException $e) {
            error_log('Error obteniendo nivel de empleado: ' . $e->getMessage());
        }
    }

    $nivelEmpleadoId = null;
    return $nivelEmpleadoId;
}

/**
 * Generar contraseña temporal segura con caracteres variados
 */
function generarPasswordTemporal($length = 8)
{
    $length = max(12, (int) $length);

    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $special = '!@#$%^&*()-_=+[]{}<>?';

    $passwordChars = [];

    $passwordChars[] = $uppercase[random_int(0, strlen($uppercase) - 1)];
    $passwordChars[] = $lowercase[random_int(0, strlen($lowercase) - 1)];
    $passwordChars[] = $numbers[random_int(0, strlen($numbers) - 1)];
    $passwordChars[] = $special[random_int(0, strlen($special) - 1)];

    $all = $uppercase . $lowercase . $numbers . $special;

    for ($i = count($passwordChars); $i < $length; $i++) {
        $passwordChars[] = $all[random_int(0, strlen($all) - 1)];
    }

    // Mezclar usando Fisher-Yates
    for ($i = count($passwordChars) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        $tmp = $passwordChars[$i];
        $passwordChars[$i] = $passwordChars[$j];
        $passwordChars[$j] = $tmp;
    }

    return implode('', $passwordChars);
}

/**
 * Validar que una contraseña cumpla con los requisitos mínimos de seguridad
 */
function validarPasswordSegura($password, $minLength = 8)
{
    if (!is_string($password)) {
        return false;
    }

    if (strlen($password) < $minLength) {
        return false;
    }

    $requisitos = [
        '/[A-Z]/',
        '/[a-z]/',
        '/[0-9]/',
        '/[^A-Za-z0-9]/',
    ];

    foreach ($requisitos as $regex) {
        if (!preg_match($regex, $password)) {
            return false;
        }
    }

    return true;
}

/**
 * Obtener plantillas de flujo activas
 */
function obtenerPlantillasFlujo()
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, nombre, descripcion, tipo_documento FROM flujo_plantillas WHERE activo = 1 ORDER BY nombre");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener listado de documentos con datos básicos y paso actual
 */
function obtenerDocumentosListado($estado = null)
{
    global $pdo;
    $sql = "SELECT d.id, d.tipo_documento, d.folio, d.titulo, d.estado_actual, d.fecha_creacion, u.email AS creador_email
            FROM documentos d
            LEFT JOIN usuarios_sistema u ON d.creado_por = u.id";
    $params = [];
    if ($estado) {
        $sql .= " WHERE d.estado_actual = ?";
        $params[] = $estado;
    }
    $sql .= " ORDER BY d.fecha_creacion DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($documentos as &$doc) {
        $doc['pasos'] = obtenerFlujoDocumentoResumen($doc['id']);
    }

    return $documentos;
}

/**
 * Obtener resumen del flujo (pasos) para un documento
 */
function obtenerFlujoDocumentoResumen($documentoId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT df.id, df.orden, df.actor_id, df.estatus, df.fecha_asignacion, df.fecha_resolucion, us.email AS actor_email
                           FROM documento_flujos df
                           LEFT JOIN usuarios_sistema us ON df.actor_id = us.id
                           WHERE df.documento_id = ?
                           ORDER BY df.orden");
    $stmt->execute([$documentoId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener detalle completo del documento (datos, flujo, historial, firmas)
 */
function obtenerDocumentoDetalle($documentoId)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT d.*, u.email AS creador_email FROM documentos d LEFT JOIN usuarios_sistema u ON d.creado_por = u.id WHERE d.id = ?");
    $stmt->execute([$documentoId]);
    $documento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$documento) {
        return null;
    }

    $documento['pasos'] = obtenerFlujoDocumentoResumen($documentoId);

    $stmt = $pdo->prepare("SELECT h.*, us.email AS actor_email FROM documento_historial h LEFT JOIN usuarios_sistema us ON h.actor_id = us.id WHERE h.documento_id = ? ORDER BY h.fecha DESC");
    $stmt->execute([$documentoId]);
    $documento['historial'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT f.*, us.email AS actor_email FROM documento_firmas f LEFT JOIN usuarios_sistema us ON f.actor_id = us.id WHERE f.documento_id = ? ORDER BY f.fecha_firma");
    $stmt->execute([$documentoId]);
    $documento['firmas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT id, ruta, nombre_original, subido_por, fecha_subida FROM documento_adjuntos WHERE documento_id = ? ORDER BY fecha_subida DESC");
    $stmt->execute([$documentoId]);
    $documento['adjuntos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $documento['hash_documento'] = hash('sha256', json_encode([
        'datos' => $documento,
        'pasos' => $documento['pasos'],
        'firmas' => $documento['firmas'],
    ]));

    return $documento;
}

