<?php
class User
{
    private $conn;
    private $table_name = "usuarios";

    public $id_usuario;
    public $usuario;
    public $password;
    public $nombre_completo;
    public $id_rol;
    public $nombre_rol;
    public $activo;

    // Lista de permisos cargados
    public $permisos = [];

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function login()
    {
        // Query actualizado para la nueva estructura
        // Unimos con roles para obtener nombre del rol
        $query = "SELECT u.id_usuario, u.usuario, u.password, u.nombre_completo, u.id_rol, u.activo, r.nombre_rol 
                  FROM " . $this->table_name . " u
                  JOIN roles r ON u.id_rol = r.id_rol
                  WHERE u.usuario = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $this->usuario = htmlspecialchars(strip_tags($this->usuario));
        $stmt->bindParam(1, $this->usuario);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar si el usuario está activo
            if ($row['activo'] != 1) {
                return false; // Usuario inactivo
            }

            // Verificar contraseña con hash
            if (password_verify($this->password, $row['password'])) {
                $this->id_usuario = $row['id_usuario'];
                $this->nombre_completo = $row['nombre_completo'];
                $this->id_rol = $row['id_rol'];
                $this->nombre_rol = $row['nombre_rol'];

                // Cargar permisos
                $this->loadPermisos();

                return true;
            }
        }
        return false;
    }

    private function loadPermisos()
    {
        // Unimos los permisos del ROL + los permisos ESPECÍFICOS del USUARIO
        $query = "SELECT p.clave_permiso 
                  FROM permisos p
                  JOIN roles_permisos rp ON p.id_permiso = rp.id_permiso
                  WHERE rp.id_rol = ?
                  UNION
                  SELECT p.clave_permiso
                  FROM permisos p
                  JOIN usuarios_permisos up ON p.id_permiso = up.id_permiso
                  WHERE up.id_usuario = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_rol);
        $stmt->bindParam(2, $this->id_usuario);
        $stmt->execute();

        $this->permisos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->permisos[] = $row['clave_permiso'];
        }
    }

    public function hasPermission($clave_permiso)
    {
        // SuperAdmin (rol 1) suele tener acceso total, pero validemos por lista
        // O si preferimos hardcodeo: if ($this->id_rol == 1) return true;

        // Mejor usamos la lista cargada
        return in_array($clave_permiso, $this->permisos) || in_array('acceso_total', $this->permisos);
    }
}
?>