<?php
// recuperar.php
include("conexion.php");

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rfc = trim($_POST['rfc']);
    if (empty($rfc)) {
        $mensaje = '<div class="alert alert-warning text-center">Por favor, ingresa tu RFC.</div>';
    } else {
        $stmt = $conexion->prepare("SELECT correo FROM usuarios WHERE rfc = ?");
        $stmt->bind_param("s", $rfc);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($resultado->num_rows === 1) {
            $row = $resultado->fetch_assoc();
            $correo = $row['correo'];
            // Generar nueva contraseña segura
            $nueva = generar_password_segura();
            $hash = password_hash($nueva, PASSWORD_BCRYPT);
            $stmt = $conexion->prepare("UPDATE usuarios SET password = ? WHERE rfc = ?");
            $stmt->bind_param("ss", $hash, $rfc);
            if ($stmt->execute()) {
                /*
                // Enviar correo con PHPMailer
                require 'PHPMailer/src/Exception.php';
                require 'PHPMailer/src/PHPMailer.php';
                require 'PHPMailer/src/SMTP.php';
                use PHPMailer\PHPMailer\PHPMailer;
                use PHPMailer\PHPMailer\Exception;
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.hostinger.com'; // Cambia esto por el SMTP de tu hosting
                    $mail->SMTPAuth = true;
                    $mail->Username = 'soporte@gusati.net'; // Cambia esto por tu correo
                    $mail->Password = 'Smettil@subito2s'; // Cambia esto por tu contraseña
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 465; // O el puerto que use tu hosting

                    $mail->setFrom('soporte@gusati.net', 'Soporte');
                    $mail->addAddress($correo);
                    $mail->isHTML(true);
                    $mail->Subject = 'Recuperación de contraseña - SECOPE';
                    $mail->Body    = 'Hola,<br><br>Tu nueva contraseña temporal es: <b>' . htmlspecialchars($nueva) . '</b><br>Por seguridad, cámbiala después de iniciar sesión.<br><br>Saludos.';

                    $mail->send();
                    $mensaje = '<div class="alert alert-success text-center">La nueva contraseña ha sido enviada a tu correo registrado.</div>';
                } catch (Exception $e) {
                    $mensaje = '<div class="alert alert-danger text-center">No se pudo enviar el correo. Contacta al administrador. ' . htmlspecialchars($mail->ErrorInfo) . '</div>';
                }
                */
            } else {
                $mensaje = '<div class="alert alert-danger text-center">Error al actualizar la contraseña. Intenta de nuevo.</div>';
            }
        } else {
            $mensaje = '<div class="alert alert-danger text-center">El RFC no existe en el sistema.</div>';
        }
    }
}

function generar_password_segura($longitud = 10) {
    $mayus = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $minus = 'abcdefghijklmnopqrstuvwxyz';
    $nums = '0123456789';
    $esp = '!@#$%&*?';
    $todos = $mayus . $minus . $nums . $esp;
    $password = $mayus[random_int(0, strlen($mayus)-1)]
              . $minus[random_int(0, strlen($minus)-1)]
              . $nums[random_int(0, strlen($nums)-1)]
              . $esp[random_int(0, strlen($esp)-1)];
    for ($i = 4; $i < $longitud; $i++) {
        $password .= $todos[random_int(0, strlen($todos)-1)];
    }
    return str_shuffle($password);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Recuperar contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light min-vh-100 d-flex flex-column align-items-center justify-content-start">
    <div class="text-center my-4">
        <a href="index.php"><img src="img/logoSecope.svg" alt="Logo SECOPE" style="max-width:90vw;max-height:100px;"></a>
    </div>
    <div class="container bg-white rounded-4 shadow p-4 mb-4" style="max-width:400px;">
        <h2 class="mb-3 text-primary">Recuperar contraseña</h2>
        <?php if ($mensaje) echo $mensaje; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="rfc" class="form-label">RFC</label>
                <input type="text" class="form-control" id="rfc" name="rfc" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Enviar</button>
        </form>
        <div class="text-center mt-3">
            <a href="index.php" class="btn btn-outline-primary">Volver al inicio</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 