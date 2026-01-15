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
        if ($id_proyecto) {
            $newUrl .= "&id_proyecto=$id_proyecto";
        }
        ?>
        <a href="<?php echo $newUrl; ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo FUA <?php echo $id_proyecto ? 'para este Proyecto' : ''; ?>
        </a>
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
                        <th>Tipo</th>
                        <th>Estatus</th>
                        <th>Importe</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fuas)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-file-earmark-text display-6 d-block mb-3 opacity-50"></i>
                                Sin registros de FUA encontrados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fuas as $f): ?>
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
                                        'REFRENDO' => 'bg-purple text-white', // Purple needs custom css or use standard like 'bg-indigo' if avail or 'bg-dark'
                                        'SALDO POR EJERCER' => 'bg-info text-dark',
                                        'CONTROL' => 'bg-secondary'
                                    ];
                                    $tClass = $tipoBadges[$f['tipo_fua']] ?? 'bg-light text-dark border';
                                    ?>
                                    <span class="badge <?php echo $tClass; ?>">
                                        <?php echo $f['tipo_fua']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $badge = match ($f['estatus']) {
                                        'ACTIVO' => 'bg-success',
                                        'CANCELADO' => 'bg-danger',
                                        'CONTROL' => 'bg-warning text-dark',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge; ?>">
                                        <?php echo $f['estatus']; ?>
                                    </span>
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