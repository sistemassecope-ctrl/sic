<?php
// Guardar FUA
require_once __DIR__ . '/../../../includes/services/DigitalArchiveService.php';

$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $archiveService = new DigitalArchiveService();

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

    // Fechas
    $fecha_ingreso_admvo = !empty($_POST['fecha_ingreso_admvo']) ? $_POST['fecha_ingreso_admvo'] : null;
    $fecha_ingreso_cotrl_ptal = !empty($_POST['fecha_ingreso_cotrl_ptal']) ? $_POST['fecha_ingreso_cotrl_ptal'] : null;
    $fecha_titular = !empty($_POST['fecha_titular']) ? $_POST['fecha_titular'] : null;
    $fecha_firma_regreso = !empty($_POST['fecha_firma_regreso']) ? $_POST['fecha_firma_regreso'] : null;
    $fecha_acuse_antes_fa = !empty($_POST['fecha_acuse_antes_fa']) ? $_POST['fecha_acuse_antes_fa'] : null;
    $fecha_respuesta_sfa = !empty($_POST['fecha_respuesta_sfa']) ? $_POST['fecha_respuesta_sfa'] : null;
    $resultado_tramite = $_POST['resultado_tramite'] ?? 'PENDIENTE';

    $tarea = $_POST['tarea'] ?? null;
    $observaciones = $_POST['observaciones'] ?? null;

    // --- VALIDACIÓN DE PRESUPUESTO DEL PROYECTO (BACKEND) ---
    if ($id_proyecto > 0) {
        try {
            // 1. Obtener Monto Total del Proyecto
            $stmtP = $db->prepare("
                SELECT (COALESCE(monto_federal,0) + COALESCE(monto_estatal,0) + COALESCE(monto_municipal,0) + COALESCE(monto_otros,0)) as total_proyecto
                FROM proyectos_obra 
                WHERE id_proyecto = ?
            ");
            $stmtP->execute([$id_proyecto]);
            $totProy = (float) ($stmtP->fetchColumn() ?? 0);

            // 2. Obtener Suma de Otros FUAs activos
            $sqlFuas = "SELECT SUM(importe) FROM fuas WHERE id_proyecto = ? AND estatus != 'CANCELADO'";
            $prms = [$id_proyecto];
            if ($id_fua) {
                $sqlFuas .= " AND id_fua != ?";
                $prms[] = $id_fua;
            }
            $stmtF = $db->prepare($sqlFuas);
            $stmtF->execute($prms);
            $totalComprometido = (float) ($stmtF->fetchColumn() ?? 0);

            $saldoDisponible = $totProy - $totalComprometido;

            if ($importe > $saldoDisponible) {
                die("Error de Validación: El importe solicitado ($" . number_format($importe, 2) . ") supera el saldo disponible del proyecto ($" . number_format($saldoDisponible, 2) . ").");
            }
        } catch (Exception $ve) {
            // Error en validación, procedemos o alertamos? Mejor alertar.
            die("Error al validar presupuesto: " . $ve->getMessage());
        }
    }

    try {
        $db->beginTransaction();

        // 1. Guardar/Actualizar Datos Principales (Sin Archivos aún para tener ID)
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

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_fua', $id_fua);
        } else {
            // INSERT
            $sql = "INSERT INTO fuas (
                estatus, tipo_suficiencia, folio_fua, nombre_proyecto_accion, id_proyecto,
                fuente_recursos, importe, no_oficio_entrada, oficio_desf_ya,
                clave_presupuestal, fecha_ingreso_admvo, fecha_ingreso_cotrl_ptal, fecha_titular, fecha_firma_regreso, fecha_acuse_antes_fa, fecha_respuesta_sfa, resultado_tramite,
                tarea, observaciones
             ) VALUES (
                :estatus, :tipo_suficiencia, :folio_fua, :nombre_proyecto_accion, :id_proyecto,
                :fuente_recursos, :importe, :no_oficio_entrada, :oficio_desf_ya,
                :clave_presupuestal, :fecha_ingreso_admvo, :fecha_ingreso_cotrl_ptal, :fecha_titular, :fecha_firma_regreso, :fecha_acuse_antes_fa, :fecha_respuesta_sfa, :resultado_tramite,
                :tarea, :observaciones
             )";
            $stmt = $db->prepare($sql);
        }

        // Bind Comunes
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

        if (!$id_fua) {
            $id_fua = $db->lastInsertId();
        }

        // 2. Procesar Archivos con DigitalArchiveService
        $nuevos_docs_ids = [];

        // Simulación de usuario actual (temporal, ajustar con sesión real)
        $id_usuario_actual = $_SESSION['user_id'] ?? 1; // Default a 1 si no hay session

        if (isset($_FILES['documentos_adjuntos']) && !empty($_FILES['documentos_adjuntos']['name'][0])) {
            // Reorganizar array de $_FILES para iterar más fácil
            $files_array = [];
            foreach ($_FILES['documentos_adjuntos'] as $key => $all) {
                foreach ($all as $i => $val) {
                    $files_array[$i][$key] = $val;
                }
            }

            foreach ($files_array as $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $metadata = [
                        'modulo_origen' => 'FUA',
                        'referencia_id' => $id_fua,
                        'tipo_documento' => 'ANEXO_FUA', // Opcional: podrías hacerlo dinámico
                        'id_usuario' => $id_usuario_actual
                    ];

                    $docId = $archiveService->saveDocument($file, $metadata);
                    if ($docId) {
                        $nuevos_docs_ids[] = $docId;
                    }
                }
            }
        }

        // 3. Actualizar columna documentos_adjuntos (Concatenar JSON IDs)
        if (!empty($nuevos_docs_ids)) {
            // Recuperar existentes
            $stmtDocs = $db->prepare("SELECT documentos_adjuntos FROM fuas WHERE id_fua = ?");
            $stmtDocs->execute([$id_fua]);
            $currentDocsStr = $stmtDocs->fetchColumn();

            // Intentar decodificar JSON, si falla asumir que es string legacy o vacio
            $currentDocs = json_decode($currentDocsStr, true);
            if (!is_array($currentDocs)) {
                $currentDocs = [];
                // Si había legacy (string separado por comas), lo ignoramos o lo convertimos?
                // Por ahora asumimos transición limpia a IDs.
            }

            $finalDocs = array_merge($currentDocs, $nuevos_docs_ids);

            // Guardar como JSON
            $stmtUpdateDocs = $db->prepare("UPDATE fuas SET documentos_adjuntos = ? WHERE id_fua = ?");
            $stmtUpdateDocs->execute([json_encode($finalDocs), $id_fua]);
        }

        $db->commit();
        header("Location: /pao/index.php?route=recursos_financieros/fuas");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>