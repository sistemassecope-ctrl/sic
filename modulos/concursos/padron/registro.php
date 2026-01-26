<!-- registro.php -->
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include("conexion.php");

$mensaje = '';
$mostrar_form = true;

function validar_password($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/', $password);
}

function validar_correo($correo) {
    return filter_var($correo, FILTER_VALIDATE_EMAIL);
}

function get_os($user_agent) {
    $os_platform = "Unknown OS";
    $os_array = [
        '/windows nt 10/i'      =>  'Windows 10',
        '/windows nt 6.3/i'     =>  'Windows 8.1',
        '/windows nt 6.2/i'     =>  'Windows 8',
        '/windows nt 6.1/i'     =>  'Windows 7',
        '/windows nt 6.0/i'     =>  'Windows Vista',
        '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     =>  'Windows XP',
        '/windows xp/i'         =>  'Windows XP',
        '/windows nt 5.0/i'     =>  'Windows 2000',
        '/windows me/i'         =>  'Windows ME',
        '/win98/i'              =>  'Windows 98',
        '/win95/i'              =>  'Windows 95',
        '/win16/i'              =>  'Windows 3.11',
        '/macintosh|mac os x/i' =>  'Mac OS X',
        '/mac_powerpc/i'        =>  'Mac OS 9',
        '/linux/i'              =>  'Linux',
        '/ubuntu/i'             =>  'Ubuntu',
        '/iphone/i'             =>  'iPhone',
        '/ipod/i'               =>  'iPod',
        '/ipad/i'               =>  'iPad',
        '/android/i'            =>  'Android',
        '/blackberry/i'         =>  'BlackBerry',
        '/webos/i'              =>  'Mobile'
    ];
    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $os_platform = $value;
        }
    }
    return $os_platform;
}

function get_browser_name($user_agent) {
    $browser = "Unknown Browser";
    $browser_array = [
        '/msie/i'      => 'Internet Explorer',
        '/firefox/i'   => 'Firefox',
        '/safari/i'    => 'Safari',
        '/chrome/i'    => 'Chrome',
        '/edge/i'      => 'Edge',
        '/opera/i'     => 'Opera',
        '/netscape/i'  => 'Netscape',
        '/maxthon/i'   => 'Maxthon',
        '/konqueror/i' => 'Konqueror',
        '/mobile/i'    => 'Handheld Browser'
    ];
    foreach ($browser_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $browser = $value;
        }
    }
    return $browser;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rfc = isset($_POST['rfc']) ? mb_strtoupper(trim($_POST['rfc']), 'UTF-8') : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    
    if (empty($rfc) || empty($password) || empty($confirm_password) || empty($correo)) {
        $mensaje = '<div class="alert alert-warning text-center">Por favor, completa todos los campos.</div>';
    } elseif ($password !== $confirm_password) {
        $mensaje = '<div class="alert alert-warning text-center">Las contraseñas no coinciden.</div>';
    } elseif (!validar_password($password)) {
        $mensaje = '<div class="alert alert-warning text-center">La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.</div>';
    } elseif (!validar_correo($correo)) {
        $mensaje = '<div class="alert alert-warning text-center">El correo electrónico no es válido.</div>';
    } else {
        // Verificar si ya tiene usuario
        $stmt = $conexion->prepare("SELECT * FROM usuarios_padron WHERE rfc = ?");
        $stmt->bind_param("s", $rfc);
        $stmt->execute();
        $resultado_usuario = $stmt->get_result();
        
        if ($resultado_usuario->num_rows > 0) {
            $mensaje = '<div class="alert alert-danger text-center">Ya tienes una cuenta registrada.</div>';
        } else {
            // Datos de auditoría
            $ip = $_SERVER['REMOTE_ADDR'];
            $ua = $_SERVER['HTTP_USER_AGENT'];
            $os = get_os($ua);
            $browser = get_browser_name($ua);

            // Registrar nuevo usuario
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conexion->prepare("INSERT INTO usuarios_padron (rfc, password, correo, ip_registro, so_registro, browser_registro) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $rfc, $password_hash, $correo, $ip, $os, $browser);
            
            if ($stmt->execute()) {
                // Log en bitácora
                $accion = "REGISTRO_NUEVO";
                $detalles = "Registro de nuevo contratista via web";
                $stmt_log = $conexion->prepare("INSERT INTO bitacora_seguridad (rfc, accion, ip_address, user_agent, detalles) VALUES (?, ?, ?, ?, ?)");
                $stmt_log->bind_param("sssss", $rfc, $accion, $ip, $ua, $detalles);
                $stmt_log->execute();
                /*
                // Enviar correo de bienvenida con PHPMailer (Pendiente configuración SMTP)
                */
                $mensaje = '<div class="alert alert-success text-center">Usuario creado exitosamente. <a href="login.php" class="alert-link">Inicia sesión</a></div>';
                $mostrar_form = false;
            } else {
                $mensaje = '<div class="alert alert-danger text-center">Error al registrar usuario: ' . $conexion->error . '</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registro - Padrón de Contratistas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body class="bg-light min-vh-100 d-flex flex-column align-items-center justify-content-start">
    <div class="text-center my-4">
        <!-- Logo from pao project -->
        <a href="index.php"><img src="<?php echo BASE_URL; ?>/img/logo_secope.png" alt="Logo SECOPE" style="max-height:100px;"></a>
    </div>
    <div class="container bg-white rounded-4 shadow p-4 mb-4" style="max-width:400px;">
        <h2 class="mb-3 text-primary">Registrarse en Padrón</h2>
        
        <?php if ($mensaje): ?>
            <?php echo $mensaje; ?>
        <?php endif; ?>
        
        <?php if ($mostrar_form): ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="rfc" class="form-label">RFC</label>
                <input type="text" class="form-control" id="rfc" name="rfc" value="<?php echo isset($_POST['rfc']) ? htmlspecialchars($_POST['rfc']) : ''; ?>" required>
            </div>
            <div class="mb-3">
                <label for="correo" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="correo" name="correo" value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>" required>
                <div class="form-text">Debe ser un correo válido. Ejemplo: usuario@dominio.com</div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="form-text">Mínimo 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.</div>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                <div id="passwordError" class="text-danger small d-none">Las contraseñas no coinciden.</div>
            </div>
            <button type="submit" class="btn btn-success w-100" id="btnSubmit">Crear cuenta</button>
        </form>
        <?php endif; ?>
        
        <div class="text-center mt-3">
            <a href="index.php" class="btn btn-outline-primary">Volver al inicio</a>
        </div>
    </div>

    <?php include("aviso_privacidad.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const textInputs = document.querySelectorAll('input:not([type="email"]):not([type="password"]):not([type="hidden"]):not([type="file"]), textarea');
            textInputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value) {
                        const start = this.selectionStart;
                        const end = this.selectionEnd;
                        this.value = this.value.toUpperCase();
                        this.setSelectionRange(start, end);
                    }
                });
            });

            // Validación de coincidencia de contraseñas en tiempo real
            const password = document.getElementById('password');
            const confirm = document.getElementById('confirm_password');
            const error = document.getElementById('passwordError');
            const btnSubmit = document.getElementById('btnSubmit');

            if (password && confirm) {
                function validarMatch() {
                    if (confirm.value && password.value !== confirm.value) {
                        error.classList.remove('d-none');
                    } else {
                        error.classList.add('d-none');
                    }
                }

                password.addEventListener('input', validarMatch);
                confirm.addEventListener('input', validarMatch);

                const form = document.querySelector('form');
                form.addEventListener('submit', function(e) {
                    if (password.value !== confirm.value) {
                        e.preventDefault();
                        alert('Las contraseñas no coinciden.');
                        return;
                    }
                    
                    // Intercept for privacy check
                    e.preventDefault();
                     if (window.solicitarPrivacidad) {
                        window.solicitarPrivacidad(function() {
                            form.submit();
                        });
                    } else {
                        form.submit();
                    }
                });
            }
        });
    </script>
</body>
</html>
?>
