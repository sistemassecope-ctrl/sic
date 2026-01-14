<?php
// Saving logic for Projects (proyectos_obra)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = (new Database())->getConnection();
$id_usuario = $_SESSION['user_id'] ?? 1;

// Collect Data
$id_proyecto = !empty($_POST['id_proyecto']) ? (int) $_POST['id_proyecto'] : null;
$id_programa = !empty($_POST['id_programa']) ? (int) $_POST['id_programa'] : null;

if (!$id_programa) {
    die("Error crítico: No se especificó el Programa Anual padre.");
}

$nombre_proyecto = mb_strtoupper(trim($_POST['nombre_proyecto'] ?? ''));
$breve_descripcion = mb_strtoupper(trim($_POST['breve_descripcion'] ?? ''));
$clave_cartera_shcp = $_POST['clave_cartera_shcp'] ?? '';
$id_municipio = !empty($_POST['id_municipio']) ? $_POST['id_municipio'] : null; // is string ID in csv logic commonly
$localidad = mb_strtoupper(trim($_POST['localidad'] ?? ''));
$impacto_proyecto = mb_strtoupper(trim($_POST['impacto_proyecto'] ?? ''));
$num_beneficiarios = (int) ($_POST['num_beneficiarios'] ?? 0);
$monto_federal = (float) ($_POST['monto_federal'] ?? 0);
$monto_estatal = (float) ($_POST['monto_estatal'] ?? 0);
$monto_municipal = (float) ($_POST['monto_municipal'] ?? 0);
$monto_otros = (float) ($_POST['monto_otros'] ?? 0);
$es_multianual = isset($_POST['es_multianual']) ? 1 : 0;

try {
    if ($id_proyecto) {
        // UPDATE
        $sql = "UPDATE proyectos_obra SET
            nombre_proyecto = ?, breve_descripcion = ?, clave_cartera_shcp = ?,
            id_municipio = ?, localidad = ?, impacto_proyecto = ?, num_beneficiarios = ?,
            monto_federal = ?, monto_estatal = ?, monto_municipal = ?, monto_otros = ?,
            es_multianual = ?, fecha_actualizacion = CURRENT_TIMESTAMP
            WHERE id_proyecto = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $nombre_proyecto,
            $breve_descripcion,
            $clave_cartera_shcp,
            $id_municipio,
            $localidad,
            $impacto_proyecto,
            $num_beneficiarios,
            $monto_federal,
            $monto_estatal,
            $monto_municipal,
            $monto_otros,
            $es_multianual,
            $id_proyecto
        ]);
        $success_msg = "Proyecto actualizado correctamente.";

    } else {
        // INSERT
        $sql = "INSERT INTO proyectos_obra (
            id_programa, nombre_proyecto, breve_descripcion, clave_cartera_shcp,
            id_municipio, localidad, impacto_proyecto, num_beneficiarios,
            monto_federal, monto_estatal, monto_municipal, monto_otros,
            es_multianual
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?
        )";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $id_programa,
            $nombre_proyecto,
            $breve_descripcion,
            $clave_cartera_shcp,
            $id_municipio,
            $localidad,
            $impacto_proyecto,
            $num_beneficiarios,
            $monto_federal,
            $monto_estatal,
            $monto_municipal,
            $monto_otros,
            $es_multianual
        ]);
        $success_msg = "Proyecto creado correctamente.";
    }

    header("Location: /pao/index.php?route=recursos_financieros/programas_operativos/proyectos&id_programa=$id_programa&status=success&msg=" . urlencode($success_msg));
    exit;

} catch (PDOException $e) {
    die("Error al guardar el proyecto: " . $e->getMessage());
}
?>