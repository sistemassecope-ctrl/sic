<?php
/**
 * Acción: Eliminar FUA
 * Ubicación: /modulos/recursos-financieros/fua-delete.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getConnection();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    try {
        $pdo->prepare("DELETE FROM fuas WHERE id_fua = ?")->execute([$id]);
        setFlashMessage('success', 'Suficiencia eliminada correctamente.');
    } catch (Exception $e) {
        setFlashMessage('error', 'Error: ' . $e->getMessage());
    }
}

redirect('modulos/recursos-financieros/fuas.php');
