<?php
/**
 * Módulo de Recursos Humanos - Exportar a CSV
 * Demuestra el permiso de exportar con filtrado por áreas
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

define('MODULO_ID', 2);

$permisos = getUserPermissions(MODULO_ID);

// Verificar permiso de exportar
if (!in_array('exportar', $permisos)) {
    setFlashMessage('error', 'No tienes permiso para exportar datos');
    redirect('/recursos-humanos/empleados.php');
}

$pdo = getConnection();
$areasUsuario = getUserAreas();

// Construir consulta con filtrado por áreas
$sql = "
    SELECT 
        e.id as 'ID',
        e.nombre as 'Nombre',
        e.apellido_paterno as 'Apellido Paterno',
        e.apellido_materno as 'Apellido Materno',
        e.email as 'Email',
        e.telefono as 'Teléfono',
        a.nombre_area as 'Área',
        p.nombre_puesto as 'Puesto',
        CASE e.estado WHEN 1 THEN 'Activo' ELSE 'Inactivo' END as 'Estado'
    FROM empleados e
    INNER JOIN areas a ON e.id_area = a.id
    INNER JOIN puestos p ON e.id_puesto = p.id
    WHERE " . getAreaFilterSQL('e.id_area');

$params = [];

// Aplicar filtro de área si se especificó
if (isset($_GET['area']) && in_array((int)$_GET['area'], $areasUsuario)) {
    $sql .= " AND e.id_area = ?";
    $params[] = (int)$_GET['area'];
}

// Aplicar búsqueda si se especificó
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $busqueda = sanitize($_GET['q']);
    $sql .= " AND (e.nombre LIKE ? OR e.apellido_paterno LIKE ? OR e.email LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$sql .= " ORDER BY e.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$empleados = $stmt->fetchAll();

// Generar CSV
$filename = 'empleados_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// BOM para Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Encabezados
if (!empty($empleados)) {
    fputcsv($output, array_keys($empleados[0]));
    
    // Datos
    foreach ($empleados as $empleado) {
        fputcsv($output, $empleado);
    }
}

fclose($output);

// Registrar actividad
logActivity('Exportación', 'Exportó ' . count($empleados) . ' empleados a CSV');

exit;
