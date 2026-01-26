<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Validar sesión, el requireAuth en auth.php ya permite esta página
requireAuth();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validaciones
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Las nuevas contraseñas no coinciden.';
    } elseif (strlen($new_password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $error = 'La contraseña debe incluir al menos una letra mayúscula.';
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $error = 'La contraseña debe incluir al menos un número.';
    } elseif (!preg_match('/[\W]/', $new_password)) {
        $error = 'La contraseña debe incluir al menos un símbolo especial (!@#$%^&*).';
    } else {
        $pdo = getConnection();
        $userId = getCurrentUserId();
        
        // Verificar contraseña actual
        $stmt = $pdo->prepare("SELECT contrasena FROM usuarios_sistema WHERE id = ?");
        $stmt->execute([$userId]);
        $storedHash = $stmt->fetchColumn();
        
        if (!password_verify($current_password, $storedHash)) {
            $error = 'La contraseña actual es incorrecta.';
        } else {
            // Actualizar contraseña
            $newHash = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE usuarios_sistema SET contrasena = ?, cambiar_password = 0 WHERE id = ?");
            
            if ($update->execute([$newHash, $userId])) {
                // Actualizar sesión
                $_SESSION['cambiar_password'] = false;
                
                // Mensaje y redirección
                // Asumiendo que setFlashMessage existe en helpers.php
                if (function_exists('setFlashMessage')) {
                    setFlashMessage('success', 'Contraseña actualizada correctamente.');
                }
                
                redirect('/index.php');
            } else {
                $error = 'Ocurrió un error al actualizar la contraseña. Intente nuevamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - SIC</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    
    <style>
        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .password-card {
            background: var(--bg-card);
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            padding: 2.5rem;
            width: 100%;
            max-width: 500px;
            border: 1px solid var(--border-primary);
        }
        .password-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .password-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        .password-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        .requirements-list {
            background: var(--bg-secondary);
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
            border: 1px solid var(--border-primary);
        }
        .requirements-list ul {
            padding-left: 1.2rem;
            margin: 0;
        }
        .requirements-list li {
            margin-bottom: 0.25rem;
        }
        .valid {
            color: var(--accent-secondary);
        }
        .invalid {
            color: var(--text-muted);
        }
        .input-group-text {
            background-color: var(--bg-tertiary);
            border-color: var(--border-primary);
            color: var(--text-secondary);
        }
        .form-control {
            background-color: var(--bg-secondary);
            border-color: var(--border-primary);
            color: var(--text-primary);
        }
        .form-control:focus {
            background-color: var(--bg-secondary);
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 0.25rem rgba(88, 166, 255, 0.25);
            color: var(--text-primary);
        }
    </style>
</head>
<body>

    <div class="password-card fade-in">
        <div class="password-header">
            <div style="margin-bottom: 1rem;">
                <i class="fas fa-lock fa-3x" style="color: var(--primary-color);"></i>
            </div>
            <h1 class="password-title">Cambio de Contraseña Requerido</h1>
            <p class="password-subtitle">Por seguridad, debes actualizar tu contraseña temporal antes de continuar.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="requirements-list">
            <strong>Tu nueva contraseña debe contener:</strong>
            <ul id="password-requirements">
                <li id="req-length">Mínimo 8 caracteres</li>
                <li id="req-upper">Al menos una letra mayúscula</li>
                <li id="req-number">Al menos un número</li>
                <li id="req-symbol">Al menos un símbolo (!@#$%^&*)</li>
            </ul>
        </div>

        <form method="POST" action="">
            <div class="form-group mb-3">
                <label class="form-label" for="current_password">Contraseña Actual (Temporal)</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="form-label" for="new_password">Nueva Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="form-label" for="confirm_password">Confirmar Nueva Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-check-double"></i></span>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="fas fa-save me-2"></i> Actualizar Contraseña
            </button>
            
            <div class="text-center mt-3">
                 <a href="<?= url('/logout.php') ?>" class="text-secondary small">Cancelar y Cerrar Sesión</a>
            </div>
        </form>
    </div>

    <script>
        // Validación en vivo simple
        const newPassInput = document.getElementById('new_password');
        const reqLength = document.getElementById('req-length');
        const reqUpper = document.getElementById('req-upper');
        const reqNumber = document.getElementById('req-number');
        const reqSymbol = document.getElementById('req-symbol');

        newPassInput.addEventListener('input', function() {
            const val = this.value;
            
            updateReq(reqLength, val.length >= 8);
            updateReq(reqUpper, /[A-Z]/.test(val));
            updateReq(reqNumber, /[0-9]/.test(val));
            updateReq(reqSymbol, /[\W]/.test(val)); // Validates non-word characters (symbols)
        });

        function updateReq(el, isValid) {
            if (isValid) {
                el.classList.add('valid');
                el.classList.remove('invalid');
                el.innerHTML = '<i class="fas fa-check-circle me-1"></i> ' + el.textContent.replace(/^.*?\s/, ''); 
            } else {
                el.classList.remove('valid');
                el.classList.add('invalid');
                // Restore text if needed or just keep simplistic
            }
        }
    </script>
</body>
</html>
