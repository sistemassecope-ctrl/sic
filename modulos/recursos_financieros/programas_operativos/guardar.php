<?php
// Saving Logic for Programas Operativos Anuales (Parent)

// 1. Validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso no permitido.");
}

// 2. DB Connection
$db = (new Database())->getConnection();

// 3. User ID from Session
$id_usuario = $_SESSION['user_id'] ?? 1;

// 4. Collect Data
$id_programa = !empty($_POST['id_programa']) ? (int) $_POST['id_programa'] : null;
$ejercicio = $_POST['ejercicio'] ?? date('Y');
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
$estatus = $_POST['estatus'] ?? 'Abierto';
$monto_autorizado = (float) ($_POST['monto_autorizado'] ?? 0);

try {
    if ($id_programa) {
        // --- UPDATE ---
        $sql = "UPDATE programas_anuales SET 
            ejercicio = ?, nombre = ?, descripcion = ?, estatus = ?, monto_autorizado = ?, fecha_registro = CURRENT_TIMESTAMP
            WHERE id_programa = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$ejercicio, $nombre, $descripcion, $estatus, $monto_autorizado, $id_programa]);

        $success_msg = "Programa Anual actualizado correctamente.";
    } else {
        // --- INSERT ---
        // Duplicate key check on 'ejercicio'
        $stmt_check = $db->prepare("SELECT id_programa FROM programas_anuales WHERE ejercicio = ?");
        $stmt_check->execute([$ejercicio]);
        if ($stmt_check->fetch()) {
            die("Error: Ya existe un Programa Anual registrado para el ejercicio $ejercicio.");
        }

        $sql = "INSERT INTO programas_anuales (
            ejercicio, nombre, descripcion, estatus, monto_autorizado
        ) VALUES (
            ?, ?, ?, ?, ?
        )";
        $stmt = $db->prepare($sql);
        $stmt->execute([$ejercicio, $nombre, $descripcion, $estatus, $monto_autorizado]);

        $success_msg = "Programa Anual creado correctamente.";
    }

    // 6. Success
    $success_msg = "Programa Operativo Anual guardado/actualizado correctamente.";
    header("Location: /pao/index.php?route=recursos_financieros/programas_operativos&status=success&msg=" . urlencode($success_msg));
    exit;

} catch (PDOException $e) {
    die("Error al guardar Programa Anual: " . $e->getMessage());
}
?>