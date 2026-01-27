<?php
/**
 * Ajax Helper: Obtener saldo disponible de un proyecto
 * Ubicación: /modulos/recursos-financieros/get-saldo-proyecto.php
 */

require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

if (!isset($_GET['id_proyecto'])) {
    echo json_encode(['error' => 'No se proporcionó ID de proyecto']);
    exit;
}

$id_proyecto = (int) $_GET['id_proyecto'];
$id_fua_actual = isset($_GET['id_fua']) ? (int) $_GET['id_fua'] : 0;

try {
    $pdo = getConnection();

    // 1. Obtener Monto Total del Proyecto
    $stmt = $pdo->prepare("SELECT monto_total, nombre_proyecto FROM proyectos_obra WHERE id_proyecto = ?");
    $stmt->execute([$id_proyecto]);
    $proyecto = $stmt->fetch();

    if (!$proyecto) {
        echo json_encode(['error' => 'Proyecto no encontrado']);
        exit;
    }

    $total_proyecto = (float) $proyecto['monto_total'];

    // 2. Obtener Suma de Solicitudes existentes (activas), excluyendo el actual
    $sqlFuas = "SELECT SUM(monto_total_solicitado) as total_comprometido FROM solicitudes_suficiencia WHERE id_proyecto = ? AND estatus = 'ACTIVO'";
    $params = [$id_proyecto];
    if ($id_fua_actual > 0) {
        $sqlFuas .= " AND id_fua != ?";
        $params[] = $id_fua_actual;
    }

    $stmtFuas = $pdo->prepare($sqlFuas);
    $stmtFuas->execute($params);
    $total_comprometido = (float) $stmtFuas->fetchColumn();

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
