<?php
$db = (new Database())->getConnection();
$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID no especificado");
}

$stmt = $db->prepare("
    SELECT sc.*, po.nombre_proyecto 
    FROM solicitudes_combustible sc
    LEFT JOIN proyectos_obra po ON sc.obra_id = po.id_proyecto
    WHERE sc.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Solicitud no encontrada");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Combustible #<?php echo $data['id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 12px; font-family: Arial, sans-serif; background: #fff; }
        .label-bold { font-weight: bold; color: #333; }
        .box-input {
            border: 1px solid #ccc;
            padding: 2px 5px;
            background-color: #f8f9fa; /* Simulando gris suave de inputs read-only */
            min-height: 20px;
        }
        .header-icons i { font-size: 20px; margin: 0 10px; color: #555; }
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; }
            .box-input { border: 1px solid #999 !important; }
        }
        .table-qty td, .table-qty th { padding: 2px; font-size: 11px; }
        .signature-line { border-top: 1px solid #000; margin-top: 30px; width: 90%; margin-left: auto; margin-right: auto; }
    </style>
</head>
<body onload="window.print()">

<div class="container mt-3">
    <!-- Toolbar -->
    <div class="d-flex justify-content-between no-print mb-4">
        <a href="javascript:window.history.back()" class="btn btn-secondary btn-sm">Regresar</a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">Imprimir</button>
    </div>

    <!-- Header Simulado de la App Desktop (Opcional, pero da contexto) -->
    <div class="row align-items-center mb-3 text-center border-bottom pb-2">
        <div class="col-12">
            <h4 class="m-0">Solicitud Combustible [CR-P7]</h4>
        </div>
    </div>

    <!-- Fila 1 -->
    <div class="row mb-2">
        <div class="col-2 text-end"><span class="label-bold">Fecha:</span></div>
        <div class="col-4">
            <div class="box-input"><?php echo $data['fecha']; ?></div>
        </div>
        <div class="col-2 text-end"><span class="label-bold">Folio:</span></div>
        <div class="col-4">
            <div class="box-input"><?php echo $data['folio']; ?></div>
        </div>
    </div>

    <!-- Fila 2 -->
    <div class="row mb-2">
        <div class="col-2 text-end"><span class="label-bold">Obras:</span></div>
        <div class="col-10">
            <div class="box-input"><?php echo $data['obra_id'] . ' | ' . htmlspecialchars($data['nombre_proyecto'] ?? 'Sin Nombre'); ?></div>
        </div>
    </div>

    <!-- Fila 3 -->
    <div class="row mb-2">
        <div class="col-2 text-end"><span class="label-bold">No. Solicitud:</span></div>
        <div class="col-2">
            <div class="box-input"><?php echo $data['no_solicitud']; ?></div>
        </div>
        <div class="col-1 text-end"><span class="label-bold">Beneficiario:</span></div>
        <div class="col-3">
            <div class="box-input"><?php echo htmlspecialchars($data['beneficiario']); ?></div>
        </div>
        <div class="col-1 text-end"><span class="label-bold">Depto:</span></div>
        <div class="col-3">
            <div class="box-input"><?php echo htmlspecialchars($data['departamento_id']); ?></div>
        </div>
    </div>

    <!-- Fila 4 -->
    <div class="row mb-2">
        <div class="col-2 text-end"><span class="label-bold">Usuario:</span></div>
        <div class="col-4">
            <div class="box-input"><?php echo htmlspecialchars($data['usuario']); ?></div>
        </div>
        <div class="col-2 text-end"><span class="label-bold">Estatus:</span></div>
        <div class="col-4">
            <div class="box-input"><?php echo $data['estatus']; ?></div>
        </div>
    </div>

    <!-- Fila 5 -->
    <div class="row mb-2">
        <div class="col-2 text-end"><span class="label-bold">Vehículo:</span></div>
        <div class="col-4">
            <div class="box-input"><?php echo htmlspecialchars($data['vehiculo_id']); ?></div>
        </div>
        <div class="col-2 text-end"><span class="label-bold">Est. Cédula:</span></div>
        <div class="col-2">
            <div class="box-input"><?php echo $data['estatus_cedula']; ?></div>
        </div>
        <div class="col-2">
             <div class="form-check">
                <input class="form-check-input" type="checkbox" <?php echo $data['surtir_laguna'] ? 'checked' : ''; ?> disabled>
                <label class="form-check-label">Surtir Laguna</label>
            </div>
        </div>
    </div>

     <!-- Fila 6: Dirección -->
    <div class="row mb-3">
        <div class="col-2 text-end"><span class="label-bold">Dirección:</span></div>
        <div class="col-10">
            <div class="box-input"><?php echo htmlspecialchars($data['direccion']); ?></div>
        </div>
    </div>

    <!-- Bloque Central -->
    <div class="row mb-3">
        <div class="col-6">
            <table class="table table-bordered table-qty text-center mb-0">
                <thead class="table-light">
                    <tr><th>CANTIDAD</th><th>CONCEPTO</th></tr>
                </thead>
                <tbody>
                    <?php if($data['litros_premium'] > 0): ?>
                    <tr><td><?php echo $data['litros_premium']; ?></td><td>LITROS PREMIUM</td></tr>
                    <?php endif; ?>
                    <?php if($data['litros_magna'] > 0): ?>
                    <tr><td><?php echo $data['litros_magna']; ?></td><td>LITROS MAGNA</td></tr>
                    <?php endif; ?>
                    <?php if($data['litros_diesel'] > 0): ?>
                    <tr><td><?php echo $data['litros_diesel']; ?></td><td>LITROS DIESEL</td></tr>
                    <?php endif; ?>
                     <!-- Rellenar filas vacías si no hay nada, para mantener altura -->
                     <?php if($data['litros_premium'] == 0 && $data['litros_magna'] == 0 && $data['litros_diesel'] == 0): ?>
                        <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                        <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                        <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                     <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="col-6">
             <div class="border p-4 text-center">
                 <strong>NUMERO DE VALE:</strong><br>
                 <span class="fs-4"><?php echo htmlspecialchars($data['numero_vale']); ?></span>
             </div>
        </div>
    </div>

    <!-- KM y Financiero -->
    <div class="row mb-3">
        <div class="col-6">
            <div class="border p-2">
                 <div class="text-center bg-light border-bottom mb-2 fw-bold">KILOMETRAJE POR RECORRER:</div>
                 <div class="row mb-1">
                     <div class="col-4">A CARRETERA:</div>
                     <div class="col-6 border-bottom text-end"><?php echo $data['km_carretera']; ?></div>
                     <div class="col-2">KM</div>
                 </div>
                 <div class="row mb-1">
                     <div class="col-4">B TERRACERIA:</div>
                     <div class="col-6 border-bottom text-end"><?php echo $data['km_terraceria']; ?></div>
                     <div class="col-2">KM</div>
                 </div>
                 <div class="row">
                     <div class="col-4">C BRECHA:</div>
                     <div class="col-6 border-bottom text-end"><?php echo $data['km_brecha']; ?></div>
                     <div class="col-2">KM</div>
                 </div>
            </div>
        </div>
        <div class="col-6">
            <div class="row mb-2">
                <div class="col-6">
                    <span class="label-bold">Año:</span>
                    <div class="box-input d-inline-block w-50"><?php echo $data['anio']; ?></div>
                </div>
                <div class="col-6">
                    <span class="label-bold">Semana:</span>
                    <div class="box-input d-inline-block w-50"><?php echo $data['semana']; ?></div>
                </div>
            </div>
            <div class="row">
                <div class="col-3 text-end pt-1"><span class="label-bold">Importe:</span></div>
                <div class="col-9">
                    <div class="box-input text-end">$ <?php echo number_format($data['importe'], 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Objetivo y Observaciones -->
    <div class="row mb-2">
        <div class="col-2 text-end"><span class="label-bold">OBJETIVO:</span></div>
        <div class="col-10">
            <div class="box-input" style="min-height: 40px;"><?php echo nl2br(htmlspecialchars($data['objetivo'])); ?></div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-2 text-end"><span class="label-bold">OBSERVACIONES:</span></div>
        <div class="col-10">
            <div class="box-input" style="min-height: 40px;"><?php echo nl2br(htmlspecialchars($data['observaciones'])); ?></div>
        </div>
    </div>

    <!-- Firmas -->
    <div class="row mt-5 text-center">
        <div class="col-6 mb-4">
            <div class="signature-line"></div>
            <div>RECIBE</div>
            <div class="small fw-bold"><?php echo htmlspecialchars($data['recibe']); ?></div>
        </div>
        <div class="col-6 mb-4">
            <div class="signature-line"></div>
            <div>VO.BO.</div>
            <div class="small fw-bold"><?php echo htmlspecialchars($data['vobo']); ?></div>
        </div>
        <div class="col-6 offset-6 mb-4">
            <div class="signature-line"></div>
            <div>AUTORIZA</div>
            <div class="small fw-bold"><?php echo htmlspecialchars($data['autoriza']); ?></div>
        </div>
        <div class="col-6 offset-6 mb-4">
            <div class="signature-line"></div>
            <div>SOLICITA</div>
             <div class="small fw-bold"><?php echo htmlspecialchars($data['solicita']); ?></div>
        </div>
    </div>

</div>

</body>
</html>
