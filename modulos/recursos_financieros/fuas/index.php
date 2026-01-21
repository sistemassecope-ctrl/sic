<?php
$db = (new Database())->getConnection();

// Fetch FUAs
// Filter by Project
$id_proyecto = isset($_GET['id_proyecto']) ? (int) $_GET['id_proyecto'] : null;

$sql = "
    SELECT f.*, po.nombre_proyecto as proyecto_origen,
           (COALESCE(po.monto_federal,0) + COALESCE(po.monto_estatal,0) + COALESCE(po.monto_municipal,0) + COALESCE(po.monto_otros,0)) as total_proyecto,
           (SELECT SUM(importe) FROM fuas f2 WHERE f2.id_proyecto = f.id_proyecto AND f2.estatus != 'CANCELADO') as total_comprometido_proy
    FROM fuas f
    LEFT JOIN proyectos_obra po ON f.id_proyecto = po.id_proyecto
    WHERE 1=1
";

$params = [];
if ($id_proyecto) {
    $sql .= " AND f.id_proyecto = :id_proyecto";
    $params[':id_proyecto'] = $id_proyecto;
}

$sql .= " ORDER BY f.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$fuas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Project Budget if filtering
$budget_info = null;
if ($id_proyecto) {
    $stmtB = $db->prepare("
            SELECT 
                (COALESCE(monto_federal,0) + COALESCE(monto_estatal,0) + COALESCE(monto_municipal,0) + COALESCE(monto_otros,0)) as total_proyecto,
                nombre_proyecto
            FROM proyectos_obra 
            WHERE id_proyecto = ?
        ");
    $stmtB->execute([$id_proyecto]);
    $budget_info = $stmtB->fetch(PDO::FETCH_ASSOC);

    if ($budget_info) {
        // Calculate total committed in this project
        $total_fua = 0;
        foreach ($fuas as $f) {
            if ($f['estatus'] !== 'CANCELADO') {
                $total_fua += (float) $f['importe'];
            }
        }
        $budget_info['total_comprometido'] = $total_fua;
        $budget_info['saldo_disponible'] = $budget_info['total_proyecto'] - $total_fua;
    }
}

// Fetch Employees for Oficio "al vuelo"
$empleados = $db->query("
    SELECT e.*, p.nombre as puesto_nombre,
           CONCAT(COALESCE(e.nombres, ''), ' ', COALESCE(e.apellido_paterno, ''), ' ', COALESCE(e.apellido_materno, '')) as nombre_completo
    FROM empleados e 
    LEFT JOIN puestos_trabajo p ON e.puesto_trabajo_id = p.id 
    WHERE e.activo = 1
    ORDER BY e.apellido_paterno, e.nombres
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Recursos Financieros</a></li>
                <li class="breadcrumb-item active" aria-current="page">Suficiencias Presupuestales
                    <?php echo $budget_info ? ' - ' . htmlspecialchars($budget_info['nombre_proyecto']) : ''; ?>
                </li>
            </ol>
        </nav>
    </div>
    <div class="col-md-4 text-end">
        <?php
        $newUrl = "/pao/index.php?route=recursos_financieros/fuas/nuevo";
        $newUrlCarpeta = "/pao/index.php?route=recursos_financieros/fuas/captura_carpeta";
        if ($id_proyecto) {
            $newUrl .= "&id_proyecto=$id_proyecto";
            $newUrlCarpeta .= "&id_proyecto=$id_proyecto";
        }
        ?>
        <div class="btn-group">
            <a href="<?php echo $newUrl; ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nueva Suficiencia <?php echo $id_proyecto ? '(Normal)' : ''; ?>
            </a>
            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Opciones</span>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo $newUrlCarpeta; ?>"><i
                            class="bi bi-folder2-open me-2"></i>Nueva Captura (Vista Carpeta)</a></li>
            </ul>
        </div>
    </div>
</div>

<?php if ($budget_info): ?>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body py-3">
                    <h6 class="text-uppercase small fw-bold opacity-75">Presupuesto del Proyecto</h6>
                    <h3 class="mb-0">$<?php echo number_format($budget_info['total_proyecto'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-info text-dark">
                <div class="card-body py-3">
                    <h6 class="text-uppercase small fw-bold opacity-75">Total Comprometido (FUAs)</h6>
                    <h3 class="mb-0">$<?php echo number_format($budget_info['total_comprometido'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <?php
            $saldoClass = $budget_info['saldo_disponible'] < 0 ? 'bg-danger' : 'bg-success';
            ?>
            <div class="card shadow-sm border-0 <?php echo $saldoClass; ?> text-white">
                <div class="card-body py-3">
                    <h6 class="text-uppercase small fw-bold opacity-75">Saldo Disponible</h6>
                    <h3 class="mb-0">$<?php echo number_format($budget_info['saldo_disponible'], 2); ?></h3>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Folio</th>
                        <th>Proyecto / Acción</th>
                        <th>Estatus</th>
                        <th>Etapa Actual</th>
                        <th class="text-end">Importe Suficiencia</th>
                        <th class="text-end">Ppto. Proyecto</th>
                        <th class="text-end">Saldo Proy.</th>
                        <th class="text-center">Financiero</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fuas)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="bi bi-inbox display-4 mb-3 opacity-25"></i>
                                    <h5 class="fw-bold">No hay información de momento</h5>
                                    <p class="mb-0">No se han registrado Suficiencias Presupuestales asociadas.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fuas as $f): ?>
                            <?php
                            // Progress bar calculation
                            $progress = 0;
                            $progressLabel = 'Inicial';
                            $progressColor = 'secondary';

                            if (!empty($f['fecha_respuesta_sfa'])) {
                                $progress = 100;
                                $progressLabel = 'Finalizado';
                                $progressColor = 'success';
                            } elseif (!empty($f['fecha_acuse_antes_fa'])) {
                                $progress = 85;
                                $progressLabel = 'Tramite SFyA';
                                $progressColor = 'primary';
                            } elseif (!empty($f['fecha_firma_regreso'])) {
                                $progress = 70;
                                $progressLabel = 'Firmas Listas';
                                $progressColor = 'info';
                            } elseif (!empty($f['fecha_titular'])) {
                                $progress = 50;
                                $progressLabel = 'Firma Titular';
                                $progressColor = 'warning';
                            } elseif (!empty($f['fecha_ingreso_cotrl_ptal'])) {
                                $progress = 30;
                                $progressLabel = 'Control Ptal.';
                                $progressColor = 'warning';
                            } elseif (!empty($f['fecha_ingreso_admvo'])) {
                                $progress = 15;
                                $progressLabel = 'Admvo.';
                                $progressColor = 'danger';
                            }

                            // Financial Status (Semaforo) for the project
                            $monto_total_proy = (float) ($f['total_proyecto'] ?? 0);
                            $total_fua_proy = (float) ($f['total_comprometido_proy'] ?? 0);
                            $saldo_proy = $monto_total_proy - $total_fua_proy;

                            if ($monto_total_proy == 0) {
                                $semaforo_color = 'text-secondary';
                                $semaforo_titulo = 'Sin presupuesto definido';
                            } elseif ($total_fua_proy == 0) {
                                $semaforo_color = 'text-secondary';
                                $semaforo_titulo = 'Sin movimientos';
                            } elseif ($saldo_proy > 0) {
                                $semaforo_color = 'text-warning';
                                $semaforo_titulo = 'En proceso (Con saldo)';
                            } elseif ($saldo_proy == 0) {
                                $semaforo_color = 'text-success';
                                $semaforo_titulo = 'Totalmente comprometido';
                            } else {
                                $semaforo_color = 'text-danger';
                                $semaforo_titulo = 'SOBREGIRO';
                            }
                            ?>
                            <tr>
                                <td class="ps-3 fw-bold">#<?php echo $f['id_fua']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($f['folio_fua'] ?? 'S/F'); ?>
                                    <div class="small text-muted"><?php echo $f['tipo_suficiencia']; ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-truncate" style="max-width: 250px;"
                                        title="<?php echo htmlspecialchars($f['nombre_proyecto_accion'] ?? $f['proyecto_origen'] ?? 'Sin nombre'); ?>">
                                        <?php echo htmlspecialchars($f['nombre_proyecto_accion'] ?? $f['proyecto_origen'] ?? 'Sin nombre'); ?>
                                    </div>
                                    <?php if ($f['proyecto_origen']): ?>
                                        <small class="text-muted d-block text-truncate" style="max-width: 250px;">PROY:
                                            <?php echo htmlspecialchars($f['proyecto_origen']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $badge = match ($f['estatus']) {
                                        'ACTIVO' => 'bg-success',
                                        'CANCELADO' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge; ?>">
                                        <?php echo $f['estatus']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column justify-content-center" style="min-width: 120px;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-muted small"
                                                style="font-size: 0.65rem;"><?php echo $progressLabel; ?></span>
                                            <span class="small fw-bold text-<?php echo $progressColor; ?>"
                                                style="font-size: 0.7rem;"><?php echo $progress; ?>%</span>
                                        </div>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar bg-<?php echo $progressColor; ?>" role="progressbar"
                                                style="width: <?php echo $progress; ?>%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-bold text-end text-primary">
                                    $<?php echo number_format($f['importe'] ?? 0, 2); ?>
                                </td>
                                <td class="text-end text-muted small">
                                    $<?php echo number_format($monto_total_proy, 2); ?>
                                </td>
                                <td
                                    class="text-end <?php echo $saldo_proy < 0 ? 'text-danger fw-bold' : 'text-success'; ?> small">
                                    $<?php echo number_format($saldo_proy, 2); ?>
                                </td>
                                <td class="text-center">
                                    <i class="bi bi-circle-fill <?php echo $semaforo_color; ?>"
                                        title="<?php echo $semaforo_titulo; ?>" data-bs-toggle="tooltip"></i>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            title="Generar Oficio al vuelo"
                                            onclick="prepararOficio(<?php echo $f['id_fua']; ?>)">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </button>
                                        <a href="/pao/index.php?route=recursos_financieros/fuas/editar&id=<?php echo $f['id_fua']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Editar"><i
                                                class="bi bi-pencil"></i></a>
                                        <a href="/pao/index.php?route=recursos_financieros/fuas/eliminar&id=<?php echo $f['id_fua']; ?>"
                                            class="btn btn-sm btn-outline-danger" title="Eliminar"
                                            onclick="return confirm('¿Está seguro de eliminar este registro?');"><i
                                                class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Generar Oficio Al Vuelo -->
<div class="modal fade" id="modalOficio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Personalizar Oficio "Al Vuelo"</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formOficio" target="_blank" action="/pao/modulos/recursos_financieros/fuas/generar_oficio.php"
                    method="GET">
                    <input type="hidden" name="id" id="modal_id_fua">

                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold"><i
                                    class="bi bi-person-fill-down me-2"></i>DATOS DEL DESTINATARIO</h6>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase">Buscar Empleado</label>
                                <select class="form-select select2-empleados" onchange="autoFillOficio(this, 'dest')">
                                    <option value="">-- Seleccionar de la tabla --</option>
                                    <?php foreach ($empleados as $emp): ?>
                                        <option value="<?php echo htmlspecialchars($emp['nombre_completo']); ?>"
                                            data-cargo="<?php echo htmlspecialchars($emp['puesto_nombre'] ?: ''); ?>">
                                            <?php echo htmlspecialchars($emp['nombre_completo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase text-muted">Nombre y Título
                                    (Manual)</label>
                                <input type="text" name="dest_nom" id="oficio_dest_nom" class="form-control"
                                    value="C.P. MARLEN SÁNCHEZ GARCÍA">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase text-muted">Cargo</label>
                                <input type="text" name="dest_car" id="oficio_dest_car" class="form-control"
                                    value="DIRECTORA DE ADMINISTRACIÓN">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold"><i
                                    class="bi bi-person-fill-up me-2"></i>DATOS DEL REMITENTE</h6>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase">Buscar Empleado</label>
                                <select class="form-select select2-empleados" onchange="autoFillOficio(this, 'rem')">
                                    <option value="">-- Seleccionar de la tabla --</option>
                                    <?php foreach ($empleados as $emp): ?>
                                        <option value="<?php echo htmlspecialchars($emp['nombre_completo']); ?>"
                                            data-cargo="<?php echo htmlspecialchars($emp['puesto_nombre'] ?: ''); ?>">
                                            <?php echo htmlspecialchars($emp['nombre_completo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase text-muted">Nombre y Título
                                    (Manual)</label>
                                <input type="text" name="rem_nom" id="oficio_rem_nom" class="form-control"
                                    value="ING. CÉSAR OTHÓN RODRÍGUEZ GÓMEZ">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-uppercase text-muted">Cargo</label>
                                <input type="text" name="rem_car" id="oficio_rem_car" class="form-control"
                                    value="SUBSECRETARIO DE INFRAESTRUCTURA CARRETERA">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 d-flex align-items-center border-0"
                        style="background-color: #f0f7ff; color: #055160;">
                        <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                        <small>Puedes seleccionar un empleado de la lista para autocompletar o escribir directamente en
                            los campos inferiores si no se encuentra en el registro.</small>
                    </div>

                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-light px-4 me-2 border"
                            data-bs-dismiss="modal">CERRAR</button>
                        <button type="submit" class="btn btn-dark px-5 shadow-sm"
                            onclick="bootstrap.Modal.getInstance(document.getElementById('modalOficio')).hide();">
                            <i class="bi bi-file-pdf me-2"></i>GENERAR PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="fixed-bottom bg-white shadow-lg border-top p-3" style="z-index: 1050;">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-search text-primary"></i></span>
                    <input type="text" class="form-control bg-light border-0 ps-2" id="searchFUA"
                        placeholder="Buscar FUA (Folio, Proyecto...)" onkeyup="filterTable()">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function PreparingModal() {
        // Init Select2 if available inside modal
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            $('.select2-empleados').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('#modalOficio')
            });
        }
    }

    function autoFillOficio(select, type) {
        const selectedOption = select.options[select.selectedIndex];
        if (!selectedOption.value) return;

        const nameInput = document.getElementById(`oficio_${type}_nom`);
        const cargoInput = document.getElementById(`oficio_${type}_car`);

        nameInput.value = selectedOption.value;
        cargoInput.value = selectedOption.getAttribute('data-cargo') || '';

        // Add subtle highlight effect
        [nameInput, cargoInput].forEach(inp => {
            inp.style.backgroundColor = '#fff9c4';
            setTimeout(() => inp.style.backgroundColor = 'white', 1000);
        });
    }

    function prepararOficio(id) {
        document.getElementById('modal_id_fua').value = id;
        var modalEl = document.getElementById('modalOficio');
        var myModal = new bootstrap.Modal(modalEl);
        PreparingModal();
        myModal.show();
    }

    function filterTable() {
        var input = document.getElementById("searchFUA");
        var filter = input.value.toUpperCase();
        var table = document.querySelector(".table");
        var tr = table.getElementsByTagName("tr");

        for (var i = 1; i < tr.length; i++) { // Start at 1 to skip header
            var found = false;
            // Search in ID (0), Folio (1), and Project (2) columns
            var columns = [0, 1, 2];

            for (var j = 0; j < columns.length; j++) {
                var td = tr[i].getElementsByTagName("td")[columns[j]];
                if (td) {
                    var txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }

            if (found) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
</script>