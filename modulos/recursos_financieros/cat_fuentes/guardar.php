<?php
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_fuente = !empty($_POST['id_fuente']) ? (int) $_POST['id_fuente'] : null;

    $anio = $_POST['anio'] ?? date('Y');
    $abreviatura = strtoupper(trim($_POST['abreviatura']));
    $nombre_fuente = strtoupper(trim($_POST['nombre_fuente']));
    $activo = isset($_POST['activo']) ? 1 : 0;

    try {
        if ($id_fuente) {
            $sql = "UPDATE cat_fuentes_financiamiento SET 
                    anio = :anio, 
                    abreviatura = :abbr, 
                    nombre_fuente = :nom, 
                    activo = :act 
                    WHERE id_fuente = :id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id_fuente);
        } else {
            $sql = "INSERT INTO cat_fuentes_financiamiento (anio, abreviatura, nombre_fuente, activo) 
                    VALUES (:anio, :abbr, :nom, :act)";
            $stmt = $db->prepare($sql);
        }

        $stmt->bindParam(':anio', $anio);
        $stmt->bindParam(':abbr', $abreviatura);
        $stmt->bindParam(':nom', $nombre_fuente);
        $stmt->bindParam(':act', $activo);

        $stmt->execute();

        header("Location: /pao/index.php?route=recursos_financieros/cat_fuentes");
        exit;

    } catch (PDOException $e) {
        die("Error al guardar: " . $e->getMessage());
    }
}
?>