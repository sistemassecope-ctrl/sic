<?php
// Deletion Logic for Programas Operativos Anuales

// 1. Validation
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: ID inválido.");
}
$id_programa = (int) $_GET['id'];

// 2. DB Connection
$db = (new Database())->getConnection();

try {
    // 3. Check for dependencies
    $stmt_check = $db->prepare("SELECT COUNT(*) FROM proyectos_obra WHERE id_programa = ?");
    $stmt_check->execute([$id_programa]);
    $num_proyectos = $stmt_check->fetchColumn();

    if ($num_proyectos > 0) {
        $error_msg = "No se puede eliminar este Programa Operativo Anual porque tiene $num_proyectos proyectos asociados. Por favor, elimine los proyectos manualmente antes de continuar.";
        header("Location: /pao/index.php?route=recursos_financieros/programas_operativos&status=error&msg=" . urlencode($error_msg));
        exit;
    }

    // 4. Delete Query
    $stmt = $db->prepare("DELETE FROM programas_anuales WHERE id_programa = ?");
    $stmt->execute([$id_programa]);

    $success_msg = "Programa Anual eliminado correctamente.";
    header("Location: /pao/index.php?route=recursos_financieros/programas_operativos&status=success&msg=" . urlencode($success_msg));
    exit;

} catch (PDOException $e) {
    die("Error al eliminar: " . $e->getMessage());
}
?>