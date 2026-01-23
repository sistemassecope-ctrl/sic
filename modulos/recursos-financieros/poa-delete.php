<?php
/**
 * Acción: Eliminar POA
 * Ubicación: /modulos/recursos-financieros/poa-delete.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// ID del módulo de Programas Operativos (Listado de Programas)
define('MODULO_ID', 56);

// Obtener permisos del usuario para este módulo
$permisos_user = getUserPermissions(MODULO_ID);
$puedeEliminar = in_array('eliminar', $permisos_user);

if (!$puedeEliminar) {
    setFlashMessage('error', 'No tienes permiso para eliminar Programas Anuales.');
    redirect('modulos/recursos-financieros/poas.php');
}

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

redirect('modulos/recursos-financieros/poas.php');
