<!-- login.php -->
<?php
session_start();
include("conexion.php");

// Si no es una petición POST, redirigir al index (donde está el formulario)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$rfc = isset($_POST['rfc']) ? mb_strtoupper($_POST['rfc'], 'UTF-8') : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (!empty($rfc) && !empty($password)) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];

    $stmt = $conexion->prepare("SELECT * FROM usuarios_padron WHERE rfc = ?");
    $stmt->bind_param("s", $rfc);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        if (password_verify($password, $usuario['password'])) {
            // Log de éxito
            $stmt_log = $conexion->prepare("INSERT INTO bitacora_seguridad (rfc, accion, ip_address, user_agent, detalles) VALUES (?, 'LOGIN_EXITOSO', ?, ?, 'Inicio de sesión correcto')");
            $stmt_log->bind_param("sss", $rfc, $ip, $ua);
            $stmt_log->execute();

            $_SESSION['rfc'] = $usuario['rfc'];
            header("Location: dashboard.php");
            exit;
        } else {
            // Log de error de contraseña
            $stmt_log = $conexion->prepare("INSERT INTO bitacora_seguridad (rfc, accion, ip_address, user_agent, detalles) VALUES (?, 'LOGIN_FALLIDO_PASSWORD', ?, ?, 'Contraseña incorrecta')");
            $stmt_log->bind_param("sss", $rfc, $ip, $ua);
            $stmt_log->execute();
            $error = "Contraseña incorrecta.";
        }
    } else {
        // Log de usuario no encontrado
        $stmt_log = $conexion->prepare("INSERT INTO bitacora_seguridad (rfc, accion, ip_address, user_agent, detalles) VALUES (?, 'LOGIN_FALLIDO_USUARIO', ?, ?, 'Usuario no encontrado')");
        $stmt_log->bind_param("sss", $rfc, $ip, $ua);
        $stmt_log->execute();
        $error = "Usuario no encontrado.";
    }
}

if (isset($error)) {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Error de Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body class="bg-light min-vh-100 d-flex flex-column align-items-center justify-content-start">
    <div class="text-center my-4">
        <a href="index.php"><img src="<?php echo BASE_URL; ?>/img/logo_secope.png" alt="Logo SECOPE" style="max-height:100px;"></a>
    </div>
    <div class="container bg-white rounded-4 shadow p-4 mb-4" style="max-width:400px;">
        <h2 class="mb-3 text-danger">Error de Autenticación</h2>
        <div class="alert alert-danger text-center">
            <?php echo $error; ?>
        </div>
        <a href="index.php" class="btn btn-primary w-100">Volver al inicio</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
?>
