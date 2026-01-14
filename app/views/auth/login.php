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
    <link rel="stylesheet" href="/pao/assets/css/styles.css">
    <style>
        body {
            background-color: #f4f6f9;
            /* Color de fondo suave profesional */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background-color: transparent;
            border-bottom: none;
            padding-top: 2rem;
            padding-bottom: 1rem;
            text-align: center;
        }

        .login-logo {
            max-width: 180px;
            height: auto;
        }

        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: 0.75rem;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }

        .form-floating>label {
            color: #6c757d;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card">
                    <div class="card-header">
                        <img src="/pao/img/logo_secope.png" alt="Logo SECOPE" class="login-logo mb-3">
                        <h4 class="card-title text-center text-dark fw-bold">Bienvenido</h4>
                        <p class="text-muted text-center small mb-0">Sistema Integral de Plan de Obra y Suficiencias</p>
                    </div>
                    <div class="card-body p-4">

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="/pao/index.php?route=login" method="POST">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="email" id="email" placeholder="Usuario"
                                    required>
                                <label for="email"><i class="bi bi-person-fill me-1"></i> Usuario</label>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" name="password" id="password"
                                    placeholder="Contraseña" required>
                                <label for="password"><i class="bi bi-lock-fill me-1"></i> Contraseña</label>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Iniciar Sesión <i class="bi bi-box-arrow-in-right ms-1"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center py-3 bg-light border-0 rounded-bottom-1">
                        <small class="text-muted">&copy; <?php echo date('Y'); ?> SECOPE</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>