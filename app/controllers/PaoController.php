<?php
require_once 'app/models/Pao.php';

class PaoController
{
    private $paoModel;

    public function __construct()
    {
        // $this->paoModel = new Pao();
    }

    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /pao/index.php?route=login");
            exit;
        }

        // Aquí se cargaría la lógica para el listado de PAO

        include 'app/views/pao/index.php';
    }
}
?>