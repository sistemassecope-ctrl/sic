<?php
/**
 * API endpoint for organizational chart data
 * Returns areas hierarchy as JSON
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$pdo = conectarDB();

// Parámetro opcional para filtrar por área padre
$rootId = isset($_GET['root']) && $_GET['root'] !== '' ? intval($_GET['root']) : null;

try {
    // Obtener todas las áreas activas
    $sql = "SELECT id, nombre, tipo, area_padre_id, nivel, descripcion
            FROM area 
            WHERE activo = 1 
            ORDER BY nivel, nombre";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Función para construir árbol jerárquico
    function buildTree($areas, $parentId = null) {
        $branch = [];
        foreach ($areas as $area) {
            $areaParent = $area['area_padre_id'];
            // Normalizar valores nulos/vacíos
            $isRoot = ($parentId === null && (empty($areaParent) || $areaParent === null || $areaParent === 0));
            $isChild = ($parentId !== null && $areaParent == $parentId);
            
            if ($isRoot || $isChild) {
                $children = buildTree($areas, $area['id']);
                $node = [
                    'id' => (int)$area['id'],
                    'name' => $area['nombre'],
                    'type' => $area['tipo'],
                    'level' => (int)$area['nivel'],
                    'description' => $area['descripcion'] ?? '',
                    'children' => $children,
                    'hasChildren' => count($children) > 0
                ];
                $branch[] = $node;
            }
        }
        return $branch;
    }
    
    // Si se especifica un rootId, buscar solo esa rama
    if ($rootId !== null) {
        // Encontrar el área raíz solicitada
        $rootArea = null;
        foreach ($areas as $area) {
            if ((int)$area['id'] === $rootId) {
                $rootArea = $area;
                break;
            }
        }
        
        if (!$rootArea) {
            http_response_code(404);
            echo json_encode(['error' => 'Área no encontrada']);
            exit;
        }
        
        $children = buildTree($areas, $rootId);
        $tree = [[
            'id' => (int)$rootArea['id'],
            'name' => $rootArea['nombre'],
            'type' => $rootArea['tipo'],
            'level' => (int)$rootArea['nivel'],
            'description' => $rootArea['descripcion'] ?? '',
            'children' => $children,
            'hasChildren' => count($children) > 0,
            'isRoot' => true
        ]];
    } else {
        $tree = buildTree($areas, null);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $tree,
        'rootId' => $rootId,
        'total' => count($areas)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
