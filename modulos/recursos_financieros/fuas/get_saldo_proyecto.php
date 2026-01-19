<?php
require_once __DIR__ . '/../../../../config/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id_proyecto'])) {
    echo json_encode(['error' => 'No se proporcionó ID de proyecto']);
    exit;
}

$id_proyecto = (int) $_GET['id_proyecto'];
$id_fua_actual = isset($_GET['id_fua']) ? (int) $_GET['id_fua'] : 0;

try {
    $db = (new Database())->getConnection();

    // 1. Obtener Monto Total del Proyecto
    $stmt = $db->prepare("
        SELECT (COALESCE(monto_federal,0) + COALESCE(monto_estatal,0) + COALESCE(monto_municipal,0) + COALESCE(monto_otros,0)) as total_proyecto,
               nombre_proyecto
        FROM proyectos_obra 
        WHERE id_proyecto = ?
    ");
    $stmt->execute([$id_proyecto]);
    $proyecto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$proyecto) {
        echo json_encode(['error' => 'Proyecto no encontrado']);
        exit;
    }

    $total_proyecto = (float) $proyecto['total_proyecto'];

    // 2. Obtener Suma de FUAs existentes (activos)
    // Excluyendo el FUA actual si estamos editando
    $sqlFuas = "SELECT SUM(importe) as total_comprometido FROM fuas WHERE id_proyecto = ? AND estatus != 'CANCELADO'";
    $params = [$id_proyecto];

    if ($id_fua_actual > 0) {
        $sqlFuas .= " AND id_fua != ?";
        $params[] = $id_fua_actual;
    }

    $stmtFuas = $db->prepare($sqlFuas);
    $stmtFuas->execute($params);
    $infoFuas = $stmtFuas->fetch(PDO::FETCH_ASSOC);
    $total_comprometido = (float) ($infoFuas['total_comprometido'] ?? 0);

    // 3. Calcular Saldo
    $saldo_disponible = $total_proyecto - $total_comprometido;

    echo json_encode([
        'total_proyecto' => $total_proyecto,
        'total_comprometido' => $total_comprometido,
        'saldo_disponible' => $saldo_disponible,
        'nombre_proyecto' => $proyecto['nombre_proyecto']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>