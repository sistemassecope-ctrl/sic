<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso denegado");
}

$db = (new Database())->getConnection();

// Recoger datos
$id = $_POST['id'] ?? null;
$fecha = $_POST['fecha'];
$folio = $_POST['folio'];
$obra_id = !empty($_POST['obra_id']) ? $_POST['obra_id'] : null;
$no_solicitud = $_POST['no_solicitud'];
$beneficiario = $_POST['beneficiario'];
$departamento_id = !empty($_POST['departamento_id']) ? $_POST['departamento_id'] : null;
$usuario = $_POST['usuario'];
$vehiculo_id = !empty($_POST['vehiculo_id']) ? (int)$_POST['vehiculo_id'] : null; // Assuming int if joined, or use string logic if input type is text
$direccion = $_POST['direccion'];
$estatus = $_POST['estatus'];
$estatus_cedula = $_POST['estatus_cedula'];
$surtir_laguna = isset($_POST['surtir_laguna']) ? 1 : 0;
$litros_premium = $_POST['litros_premium'];
$litros_magna = $_POST['litros_magna'];
$litros_diesel = $_POST['litros_diesel'];
$numero_vale = $_POST['numero_vale'];
$km_carretera = $_POST['km_carretera'];
$km_terraceria = $_POST['km_terraceria'];
$km_brecha = $_POST['km_brecha'];
$anio = $_POST['anio'];
$semana = $_POST['semana'];
$importe = $_POST['importe'];
$objetivo = $_POST['objetivo'];
$observaciones = $_POST['observaciones'];
$recibe = $_POST['recibe'];
$vobo = $_POST['vobo'];
$autoriza = $_POST['autoriza'];
$solicita = $_POST['solicita'];

try {
    if ($id) {
        // UPDATE
        $sql = "UPDATE solicitudes_combustible SET 
                fecha=?, folio=?, obra_id=?, no_solicitud=?, beneficiario=?, departamento_id=?, 
                usuario=?, vehiculo_id=?, direccion=?, estatus=?, estatus_cedula=?, surtir_laguna=?,
                litros_premium=?, litros_magna=?, litros_diesel=?, numero_vale=?, 
                km_carretera=?, km_terraceria=?, km_brecha=?, anio=?, semana=?, importe=?,
                objetivo=?, observaciones=?, recibe=?, vobo=?, autoriza=?, solicita=?
                WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $fecha, $folio, $obra_id, $no_solicitud, $beneficiario, $departamento_id,
            $usuario, $vehiculo_id, $direccion, $estatus, $estatus_cedula, $surtir_laguna,
            $litros_premium, $litros_magna, $litros_diesel, $numero_vale,
            $km_carretera, $km_terraceria, $km_brecha, $anio, $semana, $importe,
            $objetivo, $observaciones, $recibe, $vobo, $autoriza, $solicita,
            $id
        ]);
        
    } else {
        // INSERT
        $sql = "INSERT INTO solicitudes_combustible (
                fecha, folio, obra_id, no_solicitud, beneficiario, departamento_id, 
                usuario, vehiculo_id, direccion, estatus, estatus_cedula, surtir_laguna,
                litros_premium, litros_magna, litros_diesel, numero_vale, 
                km_carretera, km_terraceria, km_brecha, anio, semana, importe,
                objetivo, observaciones, recibe, vobo, autoriza, solicita
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $fecha, $folio, $obra_id, $no_solicitud, $beneficiario, $departamento_id,
            $usuario, $vehiculo_id, $direccion, $estatus, $estatus_cedula, $surtir_laguna,
            $litros_premium, $litros_magna, $litros_diesel, $numero_vale,
            $km_carretera, $km_terraceria, $km_brecha, $anio, $semana, $importe,
            $objetivo, $observaciones, $recibe, $vobo, $autoriza, $solicita
        ]);
    }

    header("Location: " . BASE_URL . "/index.php?route=combustible/index");
    exit;

} catch (PDOException $e) {
    echo "Error al guardar: " . $e->getMessage();
}
?>
