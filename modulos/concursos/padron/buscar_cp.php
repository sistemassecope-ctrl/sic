<?php
// buscar_cp.php - Maneja las consultas AJAX para códigos postales
include("conexion.php");

header('Content-Type: application/json');

if (isset($_GET['cp'])) {
    $cp = trim($_GET['cp']);
    
    if (strlen($cp) == 5 && is_numeric($cp)) {
        // Buscar información del código postal
        $stmt = $conexion->prepare("SELECT DISTINCT estado, municipio FROM codigos_postales WHERE cp = ? LIMIT 1");
        $stmt->bind_param("s", $cp);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            $info = $resultado->fetch_assoc();
            
            // Buscar colonias para este código postal
            $stmt = $conexion->prepare("SELECT DISTINCT colonia FROM codigos_postales WHERE cp = ? ORDER BY colonia");
            $stmt->bind_param("s", $cp);
            $stmt->execute();
            $colonias_result = $stmt->get_result();
            
            $colonias = [];
            while ($row = $colonias_result->fetch_assoc()) {
                $colonias[] = $row['colonia'];
            }
            
            echo json_encode([
                'success' => true,
                'estado' => $info['estado'],
                'municipio' => $info['municipio'],
                'colonias' => $colonias
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Código postal no encontrado'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Código postal inválido'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Parámetro CP requerido'
    ]);
}
?> 