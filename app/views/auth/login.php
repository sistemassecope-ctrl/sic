<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIS-PAO</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Iconos de Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
</head>

<body class="bg-light min-vh-100 d-flex flex-column align-items-center justify-content-center position-relative">

    <!-- Link al Padrón de Contratistas -->
    <div class="position-absolute top-0 start-0 m-3">
        <a href="<?php echo BASE_URL; ?>/modulos/concursos/padron/index.php" class="btn btn-outline-primary btn-sm fw-bold shadow-sm">
            <i class="bi bi-person-vcard me-1"></i> Padrón de contratistas
        </a>
    </div>

    <div class="text-center mb-4">
        <img src="<?php echo BASE_URL; ?>/img/logo_secope.png" alt="Logo SECOPE" style="max-height:100px;">
    </div>

    <div class="container bg-white rounded-4 shadow p-4 mb-4" style="max-width:400px;">
        <h4 class="text-center text-primary mb-1">Bienvenido</h4>
        <p class="text-muted text-center small mb-4">Sistema Integral de Plan de Obra y Suficiencias</p>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>/index.php?route=login" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Usuario</label>
                <input type="text" class="form-control" name="email" id="email" required>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" name="password" id="password" required>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    Iniciar Sesión
                </button>
            </div>
        </form>

        <div class="text-center mt-3 border-top pt-3">
            <small class="text-muted d-block">&copy; <?php echo date('Y'); ?> SECOPE</small>
            <small class="text-muted fst-italic" style="font-size: 0.75rem;">Powered by GUSATI</small>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>