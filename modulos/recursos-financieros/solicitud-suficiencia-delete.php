<?php
/**
 * Acción: Eliminar FUA
 * Ubicación: /modulos/recursos-financieros/solicitud-suficiencia-delete.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// ID del módulo de Suficiencias (FUAs)
define('MODULO_ID', 54);

// Obtener permisos del usuario para este módulo
$permisos_user = getUserPermissions(MODULO_ID);
$puedeEliminar = in_array('eliminar', $permisos_user);

if (!$puedeEliminar) {
    setFlashMessage('error', 'No tienes permiso para eliminar suficiencias.');
    redirect('modulos/recursos-financieros/solicitudes-suficiencia.php');
}

$pdo = getConnection();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM solicitudes_suficiencia WHERE id_fua = ?");
        $stmt->execute([$id]);

        setFlashMessage('success', 'Solicitud eliminada correctamente');
    } catch (Exception $e) {
        setFlashMessage('error', 'Error al eliminar: ' . $e->getMessage());
    }
}

redirect('modulos/recursos-financieros/solicitudes-suficiencia.php');
