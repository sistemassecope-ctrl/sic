<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/index.php?route=vehiculos/padron_vehicular");
    exit;
}

$db = (new Database())->getConnection();

// Recoger datos
$id = $_POST['id'] ?? null;
$numero_economico = trim($_POST['numero_economico'] ?? '');
$numero_placas = trim($_POST['numero_placas'] ?? '');
$marca = trim($_POST['marca'] ?? '');
$tipo = trim($_POST['tipo'] ?? '');

// Nuevos campos
$region = $_POST['region'] ?? 'SECOPE';
$con_logotipos = isset($_POST['con_logotipos']) && $_POST['con_logotipos'] == 'SI' ? 'SI' : 'NO';
$en_proceso_baja = isset($_POST['en_proceso_baja']) && $_POST['en_proceso_baja'] == 'SI' ? 'SI' : 'NO';

// Validación básica
if (empty($numero_economico) || empty($marca)) {
    echo "<script>alert('El Número Económico y la Marca son obligatorios.'); window.history.back();</script>";
    exit;
}

try {
    if ($id) {
        // ACTUALIZAR
        $sql = "UPDATE vehiculos SET 
            numero_economico = ?, numero_patrimonio = ?, numero_placas = ?, poliza = ?,
            marca = ?, tipo = ?, modelo = ?, color = ?, numero_serie = ?,
            secretaria_subsecretaria = ?, direccion_departamento = ?,
            resguardo_nombre = ?, factura_nombre = ?,
            observacion_1 = ?, observacion_2 = ?, telefono = ?, kilometraje = ?,
            region = ?, con_logotipos = ?, en_proceso_baja = ?
            WHERE id = ?";
            
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $numero_economico,
            $_POST['numero_patrimonio'] ?? '',
            $numero_placas,
            $_POST['poliza'] ?? '',
            $marca,
            $tipo,
            $_POST['modelo'] ?? '',
            $_POST['color'] ?? '',
            $_POST['numero_serie'] ?? '',
            $_POST['secretaria_subsecretaria'] ?? '',
            $_POST['direccion_departamento'] ?? '',
            $_POST['resguardo_nombre'] ?? '',
            $_POST['factura_nombre'] ?? '',
            $_POST['observacion_1'] ?? '',
            $_POST['observacion_2'] ?? '',
            $_POST['telefono'] ?? '',
            $_POST['kilometraje'] ?? '',
            $region,
            $con_logotipos,
            $en_proceso_baja,
            $id
        ]);
        
        $msg = "Vehículo actualizado correctamente.";
    } else {
        // CREAR
        $sql = "INSERT INTO vehiculos (
            numero_economico, numero_patrimonio, numero_placas, poliza,
            marca, tipo, modelo, color, numero_serie,
            secretaria_subsecretaria, direccion_departamento,
            resguardo_nombre, factura_nombre,
            observacion_1, observacion_2, telefono, kilometraje,
            region, con_logotipos, en_proceso_baja
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $numero_economico,
            $_POST['numero_patrimonio'] ?? '',
            $numero_placas,
            $_POST['poliza'] ?? '',
            $marca,
            $tipo,
            $_POST['modelo'] ?? '',
            $_POST['color'] ?? '',
            $_POST['numero_serie'] ?? '',
            $_POST['secretaria_subsecretaria'] ?? '',
            $_POST['direccion_departamento'] ?? '',
            $_POST['resguardo_nombre'] ?? '',
            $_POST['factura_nombre'] ?? '',
            $_POST['observacion_1'] ?? '',
            $_POST['observacion_2'] ?? '',
            $_POST['telefono'] ?? '',
            $_POST['kilometraje'] ?? '',
            $region,
            $con_logotipos,
            $en_proceso_baja
        ]);
        
        $msg = "Vehículo registrado correctamente.";
    }
    
    // Redireccionar con éxito
    echo "<script>
        window.location.href = '" . BASE_URL . "/index.php?route=vehiculos/padron_vehicular';
        alert('$msg');
    </script>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger m-3'>Error al guardar: " . $e->getMessage() . "</div>";
    echo "<a href='javascript:history.back()' class='btn btn-secondary m-3'>Regresar</a>";
}
?>
