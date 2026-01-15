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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rfc = isset($_POST['rfc']) ? mb_strtoupper(trim($_POST['rfc']), 'UTF-8') : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    
    if (empty($rfc) || empty($password) || empty($correo)) {
        $mensaje = '<div class="alert alert-warning text-center">Por favor, completa todos los campos.</div>';
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
            // Registrar nuevo usuario
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conexion->prepare("INSERT INTO usuarios_padron (rfc, password, correo) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $rfc, $password_hash, $correo);
            
            if ($stmt->execute()) {
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
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
            <button type="submit" class="btn btn-success w-100">Crear cuenta</button>
        </form>
        <?php endif; ?>
        
        <div class="text-center mt-3">
            <a href="index.php" class="btn btn-outline-primary">Volver al inicio</a>
        </div>
    </div>
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
        });
    </script>
</body>
</html>
?>
