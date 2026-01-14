<?php
require_once 'config/db.php';

class Pao
{
    private $conn;
    private $table_name = 'pao_obras'; // Asumiendo nombre de tabla, ajustar según esquema

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Funciones CRUD aquí
}
?>