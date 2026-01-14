
/**
 * Obtener lista simple de dependencias (ahora Areas)
 */
function obtenerDependenciasSimple() {
    global $pdo;
    try {
        // Table renamed from dependencias to area
        $sql = "SELECT id, nombre FROM area WHERE activo = TRUE ORDER BY nombre";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo areas (dependencias): " . $e->getMessage());
        return [];
    }
}
