<?php
// Incluir auth.php para tener acceso a logActivity, sesión y conexión DB (via config.php)
require_once __DIR__ . '/../../includes/auth.php';

// redirectWithMessage eliminado para usar la versión de includes/functions.php

// logActivity eliminado para usar la versión de auth.php

function calcularNivel($pdo, $dependenciaPadreId) {
    if (!$dependenciaPadreId) {
        return 1;
    }
    
    $sql = "SELECT nivel FROM area WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dependenciaPadreId]);
    $resultado = $stmt->fetch();
    
    return $resultado ? $resultado['nivel'] + 1 : 1;
}

function obtenerDependencias($pdo) {
    // Adapted from SIC2
    // Note: SIC2 used 'area d LEFT JOIN area dp ON d.id = dp.id' which seemed wrong or I misread.
    // Standard parent-child is d.area_padre_id = dp.id
    
    $sql = "SELECT d.*, 
                   dp.nombre as padre_nombre,
                   (SELECT COUNT(*) FROM area WHERE area_padre_id = d.id) as num_hijos
            FROM area d
            LEFT JOIN area dp ON d.area_padre_id = dp.id
            WHERE d.activo = 1
            ORDER BY d.id, d.nombre";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}
?>
