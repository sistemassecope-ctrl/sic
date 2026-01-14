<?php
$db = (new Database())->getConnection();

$id_fua = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($id_fua) {
    try {
        $stmt = $db->prepare("DELETE FROM fuas WHERE id_fua = ?");
        $stmt->execute([$id_fua]);
        header("Location: /pao/index.php?route=recursos_financieros/fuas");
        exit;
    } catch (PDOException $e) {
        echo "Error al eliminar: " . $e->getMessage();
    }
} else {
    header("Location: /pao/index.php?route=recursos_financieros/fuas");
    exit;
}
?>