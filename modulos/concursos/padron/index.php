<!-- index.php -->
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("conexion.php");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Padrón de Contratistas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body class="bg-light min-vh-100 d-flex flex-column align-items-center justify-content-start">
    <div class="text-center my-4">
        <a href="index.php"><img src="img/logoSecope.svg" alt="Logo SECOPE" style="max-height:100px;"></a>
    </div>
    <div class="container bg-white rounded-4 shadow p-4 mb-4" style="max-width:400px;">
        <h2 class="mb-3 text-primary">Iniciar Sesión</h2>
        <form method="POST" action="login.php" class="mb-4">
            <div class="mb-3">
                <label for="rfc" class="form-label">RFC</label>
                <input type="text" class="form-control" id="rfc" name="rfc" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>
        <div class="mb-4 text-center">
            <a href="recuperar.php" class="link-secondary">¿Olvidaste tu contraseña?</a>
        </div>
        <h2 class="mb-3 text-primary">Registrarse</h2>
        <form method="POST" action="registro.php">
            <div class="mb-3">
                <label for="rfc_reg" class="form-label">RFC</label>
                <input type="text" class="form-control" id="rfc_reg" name="rfc" required>
            </div>
            <div class="mb-3">
                <label for="correo_reg" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="correo_reg" name="correo" required>
                <div class="form-text">Debe ser un correo válido. Ejemplo: usuario@dominio.com</div>
            </div>
            <div class="mb-3">
                <label for="password_reg" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password_reg" name="password" required>
                <div class="form-text">Mínimo 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.</div>
            </div>
            <button type="submit" class="btn btn-success w-100">Crear cuenta</button>
        </form>
        <div class="text-center mt-3 border-top pt-3">
            <small class="text-muted d-block">&copy; <?php echo date('Y'); ?> SECOPE</small>
            <small class="text-muted fst-italic" style="font-size: 0.75rem;">Powered by GUSATI</small>
        </div>
    </div>
    <?php include("aviso_privacidad.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ... existing uppercase logic ...
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

            // Intercept form submission
            const formRegistro = document.querySelector('form[action="registro.php"]');
            if (formRegistro) {
                formRegistro.addEventListener('submit', function(e) {
                    e.preventDefault();
                    // Basic validation check (HTML5 does this but preventing default might skip it if triggered programmatically, 
                    // but here we are in the submit event so it's valid)
                    
                    if (window.solicitarPrivacidad) {
                        window.solicitarPrivacidad(function() {
                            formRegistro.submit();
                        });
                    } else {
                        // Fallback if modal script failed
                        formRegistro.submit();
                    }
                });
            }
        });
    </script>
</body>
</html>