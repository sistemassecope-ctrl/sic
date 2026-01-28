<?php
// Primero las inclusiones
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

// Iniciar protección de salida
ob_start();

// Configurar respuesta JSON por defecto
function send_json_response($data)
{
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    ob_end_flush();
    exit;
}

if (!isAdmin()) {
    send_json_response(['success' => false, 'error' => 'Sesión administrativa no válida o caducada.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'error' => 'Petición inválida.']);
}

$empleadoId = (int) ($_POST['empleado_id'] ?? 0);
$password = $_POST['fiel_pass'] ?? '';
$pin = $_POST['fiel_pin'] ?? '';

if (!$empleadoId || empty($password) || empty($pin)) {
    send_json_response(['success' => false, 'error' => 'Faltan datos obligatorios (ID, Password o PIN).']);
}

if (!preg_match('/^[0-9]{4}$/', $pin)) {
    send_json_response(['success' => false, 'error' => 'El PIN debe ser de exactamente 4 dígitos numéricos.']);
}

if (!isset($_FILES['fiel_cer']) || !isset($_FILES['fiel_key'])) {
    send_json_response(['success' => false, 'error' => 'No se recibieron los archivos de certificado o llave.']);
}

if ($_FILES['fiel_cer']['error'] !== UPLOAD_ERR_OK || $_FILES['fiel_key']['error'] !== UPLOAD_ERR_OK) {
    send_json_response(['success' => false, 'error' => 'Error al subir los archivos. Verifique que pesen menos de 2MB.']);
}

try {
    $cerFile = $_FILES['fiel_cer']['tmp_name'];
    $keyFile = $_FILES['fiel_key']['tmp_name'];

    // 1. Leer Certificado (.cer) - El SAT usa formato DER, PHP requiere PEM
    $cerContent = file_get_contents($cerFile);
    // Convertir DER a PEM si es necesario
    if (strpos($cerContent, '-----BEGIN CERTIFICATE-----') === false) {
        $cerPem = "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($cerContent), 64) . "-----END CERTIFICATE-----\n";
    } else {
        $cerPem = $cerContent;
    }

    $x509 = openssl_x509_parse($cerPem);
    if (!$x509) {
        throw new Exception('No se pudo leer el certificado. Verifique que sea un archivo .cer válido.');
    }

    // 2. Extraer metadatos
    $subject = $x509['subject'];
    $commonName = $subject['CN'] ?? 'Desconocido';
    $rfc = '';

    // DEBUG: Ver qué trae el subject exactamente
    // throw new Exception('DEBUG SUBJECT: ' . print_r($subject, true));

    // Los certificados del SAT guardan el RFC en el campo 'serialNumber' o 'x509name'
    // Pero a veces estos campos contienen CURP + RFC concatenados.

    // Prioridad 1: Buscar campo específico del SAT si existe (en algunas versiones de OpenSSL)
    // El OID para RFC es 2.5.4.5 pero PHP lo mapea a 'serialNumber'

    // Vamos a buscar en TODO el subject cualquier cosa que parezca un RFC de 13 caracteres (Persona Física)
    // O 12 (Persona Moral), pero priorizando el que NO sea la CURP (18 chars)
    $allMatches = [];
    foreach ($subject as $key => $value) {
        if (!is_string($value))
            continue;

        // Regex específica para RFC (12 o 13 chars)
        if (preg_match_all('/([A-Z&Ñ]{3,4}[0-9]{2}[0-1][0-9][0-3][0-9][A-Z0-9]{3})/', $value, $m)) {
            foreach ($m[1] as $match)
                $allMatches[] = $match;
        }
    }

    // Filtrar duplicados
    $allMatches = array_unique($allMatches);

    if (!empty($allMatches)) {
        // Si hay varios, el RFC suele ser el que aparece al final o el que coincide con el patrón de homoclave
        $rfc = $allMatches[0];
    }

    // Si aún no hay RFC, buscar por patrones conocidos del SAT
    if (empty($rfc)) {
        $possibleRfc = $subject['serialNumber'] ?? $subject['x509name'] ?? $commonName ?? '';
        if (preg_match('/([A-Z&Ñ]{3,4}[0-9]{2}[0-1][0-9][0-3][0-9][A-Z0-9]{3})/', $possibleRfc, $matches)) {
            $rfc = $matches[1];
        }
    }

    if (empty($rfc)) {
        throw new Exception('No se pudo identificar un RFC válido. Subject data: ' . json_encode($subject));
    }

    $validoHasta = date('Y-m-d H:i:s', $x509['validTo_time_t']);
    $serie = $x509['serialNumber'] ?? 'S/N';

    if (time() > $x509['validTo_time_t']) {
        throw new Exception('El certificado ha expirado el ' . date('d/m/Y', $x509['validTo_time_t']));
    }

    // Para depuración del usuario: lanzar error con los datos encontrados antes de validar contra DB
    // DESCOMENTA LA LÍNEA DE ABAJO PARA VER QUE ESTÁ LEYENDO REALMENTE
    // throw new Exception("DEBUG: RFC Detectado: [$rfc] | CommonName: [$commonName] | CURP/Serial: " . ($subject['serialNumber'] ?? 'N/A'));

    // 5. Validar Llave Privada con el Password
    $keyContent = file_get_contents($keyFile);

    while (openssl_error_string())
        ;

    $privateKey = openssl_pkey_get_private($keyContent, $password);

    if (!$privateKey) {
        if (strpos($keyContent, '-----BEGIN') === false) {
            $keyPem = "-----BEGIN ENCRYPTED PRIVATE KEY-----\n" . chunk_split(base64_encode($keyContent), 64) . "-----END ENCRYPTED PRIVATE KEY-----\n";
            $privateKey = openssl_pkey_get_private($keyPem, $password);
        }
    }

    if (!$privateKey) {
        throw new Exception('La contraseña de la clave privada es incorrecta o el archivo .key es inválido.');
    }

    // 6. Verificar correspondencia entre Certificado y Llave mediante prueba de firma real
    $dataToSign = "PAO-VALIDATION-" . time() . "-" . $empleadoId;
    $signature = '';

    if (!openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
        throw new Exception('Error crítico de validación: La llave no pudo generar una firma digital.');
    }

    if (openssl_verify($dataToSign, $signature, $cerPem, OPENSSL_ALGO_SHA256) !== 1) {
        throw new Exception('Inconsistencia detectada: El certificado y la clave privada no forman un par válido.');
    }

    // 7. Validar contra el RFC registrado del empleado
    $db = getConnection();
    $stmtEmp = $db->prepare("SELECT rfc FROM empleados WHERE id = ?");
    $stmtEmp->execute([$empleadoId]);
    $emp = $stmtEmp->fetch();

    if (!$emp) {
        throw new Exception('Empleado no encontrado en el sistema.');
    }

    $empRfc = strtoupper(trim($emp['rfc'] ?? ''));
    $certRfc = strtoupper(trim($rfc));

    if (!empty($empRfc) && !empty($certRfc) && $certRfc !== $empRfc) {
        throw new Exception("Seguridad: El certificado subido pertenece al RFC <strong>$certRfc</strong>, pero este expediente pertenece a <strong>$empRfc</strong>.");
    }

    // 8. Guardar en Base de Datos
    $passHash = password_hash($password, PASSWORD_DEFAULT);
    $pinHash = password_hash($pin, PASSWORD_DEFAULT);

    $sql = "INSERT INTO empleado_firmas 
            (empleado_id, fiel_certificado_base64, fiel_password_hash, fiel_pin_hash, fiel_vencimiento, fiel_rfc, fiel_nombre, fiel_serie, fiel_estado, capturado_por, fecha_captura)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            fiel_certificado_base64 = VALUES(fiel_certificado_base64),
            fiel_password_hash = VALUES(fiel_password_hash),
            fiel_pin_hash = VALUES(fiel_pin_hash),
            fiel_vencimiento = VALUES(fiel_vencimiento),
            fiel_rfc = VALUES(fiel_rfc),
            fiel_nombre = VALUES(fiel_nombre),
            fiel_serie = VALUES(fiel_serie),
            fiel_estado = 1,
            fecha_captura = NOW()";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        $empleadoId,
        base64_encode($cerContent),
        $passHash,
        $pinHash,
        date('Y-m-d', $x509['validTo_time_t']),
        $rfc,
        $commonName,
        $serie,
        getCurrentUser()['id'] ?? 0
    ]);

    send_json_response([
        'success' => true,
        'message' => 'e.firma validada y registrada exitosamente.',
        'data' => [
            'rfc' => $rfc,
            'nombre' => $commonName,
            'vencimiento' => date('d/m/Y', $x509['validTo_time_t'])
        ]
    ]);

} catch (Exception $e) {
    send_json_response(['success' => false, 'error' => $e->getMessage()]);
}