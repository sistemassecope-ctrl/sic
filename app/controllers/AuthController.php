<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Logger.php';

class AuthController
{
    private $db;
    private $user;
    private $logger;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
        $this->logger = new Logger($this->db);
    }

    public function login()
    {
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->user->usuario = $_POST['email'] ?? ''; // El campo name bitacora sigue siendo email en el form, lo usaremos como usuario
            $this->user->password = $_POST['password'] ?? '';

            // Ajustar si el input name cambia en la vista, por compatibilidad con el código anterior asumí 'email' 
            // pero el usuario puede ingresar '1100100'.

            if (!empty($this->user->usuario) && !empty($this->user->password)) {
                if ($this->user->login()) {
                    // Login exitoso
                    session_start();
                    $_SESSION['user_id'] = $this->user->id_usuario;
                    $_SESSION['user_name'] = $this->user->nombre_completo;
                    $_SESSION['user_rol'] = $this->user->nombre_rol;
                    $_SESSION['user_nivel'] = $this->user->id_rol; // Mapear rol a nivel para compatibilidad con módulos legacy
                    $_SESSION['user_permisos'] = $this->user->permisos;

                    // Log acceso exitoso
                    $this->logger->logAccess(
                        $this->user->id_usuario,
                        $this->user->usuario,
                        'LOGIN_EXITOSO',
                        'Inicio de sesión correcto. Rol: ' . $this->user->nombre_rol
                    );

                    header("Location: " . BASE_URL . "/index.php?route=home");
                    exit;
                } else {
                    // Login fallido
                    $error = "Credenciales incorrectas o usuario inactivo.";
                    // Log acceso fallido
                    $this->logger->logAccess(
                        null,
                        $this->user->usuario,
                        'LOGIN_FALLIDO',
                        'Credenciales invalidas'
                    );
                }
            } else {
                $error = "Por favor ingrese usuario y contraseña.";
            }
        }

        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function logout()
    {
        session_start();

        // Log logout
        if (isset($_SESSION['user_id'])) {
            // Re-instanciar logger si se perdió contexto (aunque en nuevo request se crea de nuevo)
            // Necesitamos el ID, lo tomamos de la sesión antes de destruir
            $uid = $_SESSION['user_id'];
            $uname = $_SESSION['user_name'] ?? 'Unknown';
            $this->logger->logAccess($uid, $uname, 'LOGOUT', 'Cierre de sesión');
        }

        session_destroy();
        header("Location: " . BASE_URL . "/index.php?route=login");
        exit;
    }
}
?>