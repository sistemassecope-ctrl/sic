<?php
$db = (new Database())->getConnection();

// Fetch FUAs
// Filter by Project
$id_proyecto = isset($_GET['id_proyecto']) ? (int) $_GET['id_proyecto'] : null;

$sql = "
    SELECT f.*, po.nombre_proyecto as proyecto_origen
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

// Fetch Project Name for Display if filtering
$project_info = null;
if ($id_proyecto) {
    $stmtP = $db->prepare("SELECT nombre_proyecto FROM proyectos_obra WHERE id_proyecto = ?");
    $stmtP->execute([$id_proyecto]);
    $project_info = $stmtP->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Recursos Financieros</a></li>
                <li class="breadcrumb-item active" aria-current="page">Gestión de FUAs
                    <?php echo $project_info ? ' - ' . htmlspecialchars($project_info['nombre_proyecto']) : ''; ?>
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
                <i class="bi bi-plus-circle"></i> Nuevo FUA <?php echo $id_proyecto ? '(Normal)' : ''; ?>
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

<div class="card shadow border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Folio</th>
                        <th>Proyecto / Acción</th>
                        <th>Tipo de Suficiencia</th>
                        <th>Estatus</th>
                        <th>Etapa Actual</th>
                        <th>Importe</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fuas)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="bi bi-inbox display-4 mb-3 opacity-25"></i>
                                    <h5 class="fw-bold">No hay información de momento</h5>
                                    <p class="mb-0">No se han registrado Formatos Únicos de Atención (FUA) asociados.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fuas as $f): ?>
                            <?php
                            // Logic to determine progress based on current stage
                            $progress = 5;
                            $progressLabel = 'Captura';
                            $progressColor = 'secondary'; // Gris neutral para inicio
                    
                            if (!empty($f['fecha_respuesta_sfa'])) {
                                $progress = 100;
                                $progressLabel = 'Concluido';
                                $progressColor = 'success'; // Verde final
                            } elseif (!empty($f['fecha_acuse_antes_fa'])) {
                                $progress = 85;
                                $progressLabel = 'Trámite SFyA';
                                $progressColor = 'primary'; // Azul fuerte
                            } elseif (!empty($f['fecha_firma_regreso'])) {
                                $progress = 70;
                                $progressLabel = 'Firmas Listas';
                                $progressColor = 'info'; // Celeste
                            } elseif (!empty($f['fecha_titular'])) {
                                $progress = 50;
                                $progressLabel = 'Firma Titular';
                                $progressColor = 'warning'; // Amarillo
                            } elseif (!empty($f['fecha_ingreso_cotrl_ptal'])) {
                                $progress = 30;
                                $progressLabel = 'Control Ptal.';
                                $progressColor = 'warning'; // Naranja/Amarillo (Bootstrap no tiene orange, usamos warning)
                            } elseif (!empty($f['fecha_ingreso_admvo'])) {
                                $progress = 15;
                                $progressLabel = 'Admvo.';
                                $progressColor = 'danger'; // Rojo para indicar inicio formal
                            }
                            ?>
                            <tr>
                                <td class="ps-3 fw-bold">#
                                    <?php echo $f['id_fua']; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($f['folio_fua'] ?? 'S/F'); ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-truncate" style="max-width: 300px;">
                                        <?php echo htmlspecialchars($f['nombre_proyecto_accion'] ?? $f['proyecto_origen'] ?? 'Sin nombre'); ?>
                                    </div>
                                    <?php if ($f['proyecto_origen']): ?>
                                        <small class="text-muted d-block">PROY:
                                            <?php echo htmlspecialchars($f['proyecto_origen']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $tipoBadges = [
                                        'NUEVA' => 'bg-primary',
                                        'REFRENDO' => 'bg-indigo text-white',
                                        'SALDO POR EJERCER' => 'bg-info text-dark',
                                        'CONTROL' => 'bg-secondary'
                                    ];
                                    $tClass = $tipoBadges[$f['tipo_suficiencia']] ?? 'bg-light text-dark border';
                                    ?>
                                    <span class="badge <?php echo $tClass; ?>">
                                        <?php echo $f['tipo_suficiencia']; ?>
                                    </span>
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
                                    <div class="d-flex flex-column justify-content-center" style="min-width: 140px;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-muted small" style="font-size: 0.65rem;">
                                                <?php echo $progressLabel; ?>
                                            </span>
                                            <span class="small fw-bold text-<?php echo $progressColor; ?>"
                                                style="font-size: 0.7rem;">
                                                <?php echo $progress; ?>%
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-<?php echo $progressColor; ?>" role="progressbar"
                                                style="width: <?php echo $progress; ?>%;"
                                                aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-bold text-end">
                                    $
                                    <?php echo number_format($f['importe'] ?? 0, 2); ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="/pao/index.php?route=recursos_financieros/fuas/editar&id=<?php echo $f['id_fua']; ?>"
                                        class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="/pao/index.php?route=recursos_financieros/fuas/eliminar&id=<?php echo $f['id_fua']; ?>"
                                        class="btn btn-sm btn-outline-danger" title="Eliminar"
                                        onclick="return confirm('¿Está seguro de eliminar este registro?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>