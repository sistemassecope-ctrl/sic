<?php
// Guardar FUA
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // IDs
    $id_fua = !empty($_POST['id_fua']) ? (int) $_POST['id_fua'] : null;

    // CAMPOS
    $estatus = $_POST['estatus'] ?? null;
    $tipo_suficiencia = $_POST['tipo_suficiencia'] ?? null;
    $folio_fua = $_POST['folio_fua'] ?? null;
    $nombre_proyecto_accion = $_POST['nombre_proyecto_accion'] ?? null;
    $id_proyecto = !empty($_POST['id_proyecto']) ? (int) $_POST['id_proyecto'] : null;

    $fuente_recursos = $_POST['fuente_recursos'] ?? null;
    $importe = !empty($_POST['importe']) ? (float) $_POST['importe'] : 0.00;

    $no_oficio_entrada = $_POST['no_oficio_entrada'] ?? null;
    $oficio_desf_ya = $_POST['oficio_desf_ya'] ?? null;
    $clave_presupuestal = $_POST['clave_presupuestal'] ?? null;

    // Fechas (NULL fix)
    $fecha_ingreso_admvo = !empty($_POST['fecha_ingreso_admvo']) ? $_POST['fecha_ingreso_admvo'] : null;
    $fecha_ingreso_cotrl_ptal = !empty($_POST['fecha_ingreso_cotrl_ptal']) ? $_POST['fecha_ingreso_cotrl_ptal'] : null;
    $fecha_titular = !empty($_POST['fecha_titular']) ? $_POST['fecha_titular'] : null;
    $fecha_firma_regreso = !empty($_POST['fecha_firma_regreso']) ? $_POST['fecha_firma_regreso'] : null;
    $fecha_acuse_antes_fa = !empty($_POST['fecha_acuse_antes_fa']) ? $_POST['fecha_acuse_antes_fa'] : null;
    $fecha_respuesta_sfa = !empty($_POST['fecha_respuesta_sfa']) ? $_POST['fecha_respuesta_sfa'] : null;
    $resultado_tramite = $_POST['resultado_tramite'] ?? 'PENDIENTE';

    $tarea = $_POST['tarea'] ?? null;
    $observaciones = $_POST['observaciones'] ?? null;

    // ARCHIVOS
    $documentos_adjuntos = null;
    // Si es edición, habría que mantener los anteriores si no se suben nuevos, o concatenar.
    // Lógica simple: Si sube, reemplaza/agrega nombre.
    if (isset($_FILES['documentos_adjuntos']) && $_FILES['documentos_adjuntos']['error'][0] === UPLOAD_ERR_OK) {
        $uploaded_files = [];
        $uploadDir = __DIR__ . '/../../../uploads/fuas/';

        foreach ($_FILES['documentos_adjuntos']['name'] as $key => $name) {
            if ($_FILES['documentos_adjuntos']['error'][$key] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['documentos_adjuntos']['tmp_name'][$key];
                $filename = time() . '_' . basename($name);
                if (move_uploaded_file($tmp_name, $uploadDir . $filename)) {
                    $uploaded_files[] = $filename;
                }
            }
        }
        if (!empty($uploaded_files)) {
            $documentos_adjuntos = implode(',', $uploaded_files);
        }
    }

    // Si estamos editando y no subimos nada, mantenemos el valor actual?
    if ($id_fua && $documentos_adjuntos === null) {
        // No actualizamos la columna documentos, o la leemos antes.
        // Mejor en el Query construimos dinámicamente.
    }

    try {
        if ($id_fua) {
            // UPDATE
            $sql = "UPDATE fuas SET 
                estatus = :estatus,
                tipo_suficiencia = :tipo_suficiencia,
                folio_fua = :folio_fua,
                nombre_proyecto_accion = :nombre_proyecto_accion,
                id_proyecto = :id_proyecto,

                fuente_recursos = :fuente_recursos,
                importe = :importe,
                no_oficio_entrada = :no_oficio_entrada,
                oficio_desf_ya = :oficio_desf_ya,
                clave_presupuestal = :clave_presupuestal,
                fecha_ingreso_admvo = :fecha_ingreso_admvo,
                fecha_ingreso_cotrl_ptal = :fecha_ingreso_cotrl_ptal,
                fecha_titular = :fecha_titular,
                fecha_firma_regreso = :fecha_firma_regreso,
                fecha_acuse_antes_fa = :fecha_acuse_antes_fa,
                fecha_respuesta_sfa = :fecha_respuesta_sfa,
                resultado_tramite = :resultado_tramite,
                tarea = :tarea,
                observaciones = :observaciones
                WHERE id_fua = :id_fua";

            // Si hay docs nuevos, agregar al query
            if ($documentos_adjuntos) {
                $sql = str_replace("observaciones = :observaciones", "observaciones = :observaciones, documentos_adjuntos = :docs", $sql);
            }

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_fua', $id_fua);
            if ($documentos_adjuntos)
                $stmt->bindParam(':docs', $documentos_adjuntos);

        } else {
            // INSERT
            $sql = "INSERT INTO fuas (
                estatus, tipo_suficiencia, folio_fua, nombre_proyecto_accion, id_proyecto,
                fuente_recursos, importe, no_oficio_entrada, oficio_desf_ya,
                clave_presupuestal, fecha_ingreso_admvo, fecha_ingreso_cotrl_ptal, fecha_titular, fecha_firma_regreso, fecha_acuse_antes_fa, fecha_respuesta_sfa, resultado_tramite,
                tarea, observaciones, documentos_adjuntos
             ) VALUES (
                :estatus, :tipo_suficiencia, :folio_fua, :nombre_proyecto_accion, :id_proyecto,
                :fuente_recursos, :importe, :no_oficio_entrada, :oficio_desf_ya,
                :clave_presupuestal, :fecha_ingreso_admvo, :fecha_ingreso_cotrl_ptal, :fecha_titular, :fecha_firma_regreso, :fecha_acuse_antes_fa, :fecha_respuesta_sfa, :resultado_tramite,
                :tarea, :observaciones, :docs
             )";
            $stmt = $db->prepare($sql);
            $valDocs = $documentos_adjuntos ?? ''; // Para insert, si es null que sea string vacio o null
            $stmt->bindParam(':docs', $valDocs);
        }

        // Bind comunes
        $stmt->bindParam(':estatus', $estatus);
        $stmt->bindParam(':tipo_suficiencia', $tipo_suficiencia);
        $stmt->bindParam(':folio_fua', $folio_fua);
        $stmt->bindParam(':nombre_proyecto_accion', $nombre_proyecto_accion);
        $stmt->bindParam(':id_proyecto', $id_proyecto);

        $stmt->bindParam(':fuente_recursos', $fuente_recursos);
        $stmt->bindParam(':importe', $importe);
        $stmt->bindParam(':no_oficio_entrada', $no_oficio_entrada);
        $stmt->bindParam(':oficio_desf_ya', $oficio_desf_ya);
        $stmt->bindParam(':clave_presupuestal', $clave_presupuestal);
        $stmt->bindParam(':fecha_ingreso_admvo', $fecha_ingreso_admvo);
        $stmt->bindParam(':fecha_ingreso_cotrl_ptal', $fecha_ingreso_cotrl_ptal);
        $stmt->bindParam(':fecha_titular', $fecha_titular);
        $stmt->bindParam(':fecha_firma_regreso', $fecha_firma_regreso);
        $stmt->bindParam(':fecha_acuse_antes_fa', $fecha_acuse_antes_fa);
        $stmt->bindParam(':fecha_respuesta_sfa', $fecha_respuesta_sfa);
        $stmt->bindParam(':resultado_tramite', $resultado_tramite);
        $stmt->bindParam(':tarea', $tarea);
        $stmt->bindParam(':observaciones', $observaciones);

        $stmt->execute();

        header("Location: /pao/index.php?route=recursos_financieros/fuas");
        exit;

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>