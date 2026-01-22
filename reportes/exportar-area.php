<?php
/**
 * Módulo de Reportes - Exportar estadísticas por área a CSV
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAuth();

define('MODULO_ID', 5);

$permisos = getUserPermissions(MODULO_ID);

if (!in_array('exportar', $permisos)) {
    setFlashMessage('error', 'No tienes permiso para exportar reportes');
    redirect('/reportes/index.php');
}

$pdo = getConnection();

// Obtener datos filtrados por áreas del usuario
$datos = $pdo->query("
    SELECT 
        a.nombre_area as 'Área',
        COUNT(e.id) as 'Total Empleados',
        SUM(CASE WHEN e.estado = 1 THEN 1 ELSE 0 END) as 'Activos',
        SUM(CASE WHEN e.estado = 0 THEN 1 ELSE 0 END) as 'Inactivos',
        COUNT(DISTINCT u.id) as 'Usuarios Sistema'
    FROM areas a
    LEFT JOIN empleados e ON a.id = e.id_area
    LEFT JOIN usuarios_sistema u ON e.id = u.id_empleado
    WHERE a.estado = 1 AND " . getAreaFilterSQL('a.id') . "
    GROUP BY a.id, a.nombre_area
    ORDER BY a.nombre_area
")->fetchAll();

$filename = 'reporte_areas_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if (!empty($datos)) {
    fputcsv($output, array_keys($datos[0]));
    foreach ($datos as $row) {
        fputcsv($output, $row);
    }
}

fclose($output);

logActivity('Exportación', 'Exportó reporte de áreas a CSV');

exit;
