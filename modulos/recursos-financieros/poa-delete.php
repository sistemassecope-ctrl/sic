<?php
/**
 * Acción: Eliminar POA
 * Ubicación: /modulos/recursos-financieros/poa-delete.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getConnection();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    try {
        // Check if has projects
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM proyectos_obra WHERE id_programa = ?");
        $stmt_check->execute([$id]);
        if ($stmt_check->fetchColumn() > 0) {
            setFlashMessage('error', 'No se puede eliminar un programa que ya tiene proyectos vinculados.');
        } else {
            $pdo->prepare("DELETE FROM programas_anuales WHERE id_programa = ?")->execute([$id]);
            setFlashMessage('success', 'Programa eliminado correctamente.');
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'Error: ' . $e->getMessage());
    }
}

redirect('poas.php');
