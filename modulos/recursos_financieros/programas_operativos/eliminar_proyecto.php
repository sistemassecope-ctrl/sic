<?php
// Deletion Logic for Projects

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: ID de proyecto inválido.");
}
$id_proyecto = (int) $_GET['id'];
$id_programa_redirect = isset($_GET['id_programa']) ? (int) $_GET['id_programa'] : 0;

$db = (new Database())->getConnection();

try {
    // If we didn't get the parent ID, fetch it first so we can redirect back correctly
    if ($id_programa_redirect === 0) {
        $stmt_get = $db->prepare("SELECT id_programa FROM proyectos_obra WHERE id_proyecto = ?");
        $stmt_get->execute([$id_proyecto]);
        $id_programa_redirect = $stmt_get->fetchColumn();
    }

    $stmt = $db->prepare("DELETE FROM proyectos_obra WHERE id_proyecto = ?");
    $stmt->execute([$id_proyecto]);

    $success_msg = "Proyecto eliminado correctamente.";
    header("Location: /pao/index.php?route=recursos_financieros/programas_operativos/proyectos&id_programa=$id_programa_redirect&status=success&msg=" . urlencode($success_msg));
    exit;

} catch (PDOException $e) {
    die("Error al eliminar proyecto: " . $e->getMessage());
}
?>