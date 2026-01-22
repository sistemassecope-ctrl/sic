<?php
/**
 * PAO v2 - Página de Login
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Si ya está autenticado, redirigir al dashboard
if (isAuthenticated()) {
    redirect('/index.php');
}

$error = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = sanitize($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        $error = 'Por favor ingrese usuario y contraseña';
    } else {
        $result = login($usuario, $password);

        if ($result['success']) {
            redirect('/index.php');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SIC Sistema Integral de SECOPE">
    <title>Iniciar Sesión - PAO v2</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>

<body>
    <div class="login-container">
        <div class="login-card fade-in">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-atom"></i>
                </div>
                <h1 class="login-title">PAO v2</h1>
                <p class="login-subtitle">SIC Sistema Integral de SECOPE</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" data-validate>
                <div class="form-group">
                    <label class="form-label" for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" class="form-control" placeholder="Ingrese su usuario"
                        value="<?= e($_POST['usuario'] ?? '') ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Ingrese su contraseña" required>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 1rem;">
                    <i class="fas fa-sign-in-alt"></i>
                    Iniciar Sesión
                </button>
            </form>

            <div style="margin-top: 2rem; text-align: center; color: var(--text-muted); font-size: 0.8rem;">
                <p>Usuarios de prueba:</p>
                <p><strong>admin</strong> / password123</p>
            </div>
        </div>
    </div>

    <script src="<?= url('/assets/js/app.js') ?>"></script>
</body>

</html>