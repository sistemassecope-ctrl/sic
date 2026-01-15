<?php
// Configuración de la base de datos basada en comun/temporales.txt

define('DB_HOST', 'localhost');
define('DB_USER', 'sic_test');
define('DB_PASS', 'sic_test.2025');
define('DB_NAME', 'sic'); // Nombre de la base de datos asumido por el contexto del proyecto
define('DB_CHARSET', 'utf8mb4');

// Definir BASE_URL dinámicamente para soportar cambios de nombre de carpeta
if (!defined('BASE_URL')) {
    $script_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Si la ruta contiene 'modulos', asumiendo estructura estándar, cortamos hasta encontrar la raíz web del proyecto
    // Sin embargo, la forma más segura es detectar el directorio del archivo actual y compararlo con DOCUMENT_ROOT

    $root_fs = str_replace('\\', '/', __DIR__); // .../config
    $root_fs = dirname($root_fs); // .../pao (raíz del proyecto)
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);

    $web_path = str_replace($doc_root, '', $root_fs);

    // Asegurar que no haya slash final a menos que sea la raíz
    $web_path = rtrim($web_path, '/');

    // Si estamos en la raíz absoluta
    if (empty($web_path)) {
        $web_path = '';
    }

    define('BASE_URL', $web_path);
}

class Database
{
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    public $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>