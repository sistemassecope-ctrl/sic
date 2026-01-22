<?php
/**
 * Acción: Eliminar Proyecto
 * Ubicación: /modulos/recursos-financieros/proyecto-delete.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getConnection();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$id_programa = isset($_GET['id_programa']) ? (int) $_GET['id_programa'] : 0;

if ($id > 0) {
    try {
        // Check if has FUAs
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM fuas WHERE id_proyecto = ?");
        $stmt_check->execute([$id]);
        if ($stmt_check->fetchColumn() > 0) {
            setFlashMessage('error', 'No se puede eliminar un proyecto que tiene suficiencias (FUAs) vinculadas.');
        } else {
            $pdo->prepare("DELETE FROM proyectos_obra WHERE id_proyecto = ?")->execute([$id]);
            setFlashMessage('success', 'Proyecto eliminado correctamente.');
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'Error: ' . $e->getMessage());
    }
}

redirect("modulos/recursos-financieros/proyectos.php?id_programa=$id_programa");
