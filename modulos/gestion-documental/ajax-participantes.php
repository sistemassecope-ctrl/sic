<?php
/**
 * AJAX: Buscar candidatos para firmantes
 * Archivo: modulos/gestion-documental/ajax-participantes.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getConnection();
$query = trim($_GET['q'] ?? '');

try {
    if (strlen($query) < 2) {
        // Por defecto mostrar algunos (ej: los de la misma área) o vacio
        $stmt = $pdo->prepare("
            SELECT u.id, u.usuario, e.nombres, e.apellido_paterno, e.apellido_materno, a.nombre_area, p.nombre as puesto
            FROM usuarios_sistema u
            JOIN empleados e ON u.id_empleado = e.id
            LEFT JOIN areas a ON e.area_id = a.id
            LEFT JOIN puestos_trabajo p ON e.puesto_trabajo_id = p.id
            WHERE u.estado = 1
            LIMIT 10
        ");
        $stmt->execute();
    } else {
        $search = "%$query%";
        $stmt = $pdo->prepare("
            SELECT u.id, u.usuario, e.nombres, e.apellido_paterno, e.apellido_materno, a.nombre_area, p.nombre as puesto
            FROM usuarios_sistema u
            JOIN empleados e ON u.id_empleado = e.id
            LEFT JOIN areas a ON e.area_id = a.id
            LEFT JOIN puestos_trabajo p ON e.puesto_trabajo_id = p.id
            WHERE u.estado = 1 AND (
                e.nombres LIKE ? OR 
                e.apellido_paterno LIKE ? OR 
                e.apellido_materno LIKE ? OR 
                u.usuario LIKE ? OR
                a.nombre_area LIKE ?
            )
            LIMIT 20
        ");
        $stmt->execute([$search, $search, $search, $search, $search]);
    }

    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear para Select2 o vista personalizada
    $results = array_map(function ($u) {
        return [
            'id' => $u['id'],
            'text' => $u['nombres'] . ' ' . $u['apellido_paterno'] . ' ' . $u['apellido_materno'],
            'usuario' => $u['usuario'],
            'area' => $u['nombre_area'],
            'puesto' => $u['puesto']
        ];
    }, $usuarios);

    jsonSuccess('Candidatos encontrados', ['results' => $results]);

} catch (Exception $e) {
    jsonError($e->getMessage());
}
