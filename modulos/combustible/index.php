<?php
$db = (new Database())->getConnection();

// Fetch Requests
$sql = "SELECT * FROM solicitudes_combustible ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Combustible</a></li>
                <li class="breadcrumb-item active" aria-current="page">Solicitudes</li>
            </ol>
        </nav>
        <h2 class="h4">Historial de Solicitudes de Combustible</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="<?php echo BASE_URL; ?>/index.php?route=combustible/nuevo" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nueva Solicitud
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
                        <th>Fecha</th>
                        <th>Folio</th>
                        <th>Beneficiario</th>
                        <th>Vehículo</th>
                        <th>Estatus</th>
                        <th class="text-end">Importe</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-fuel-pump display-6 d-block mb-3 opacity-50"></i>
                                Sin solicitudes registradas.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td class="ps-3 fw-bold">#<?php echo $r['id']; ?></td>
                                <td><?php echo $r['fecha']; ?></td>
                                <td><?php echo htmlspecialchars($r['folio'] ?? 'S/F'); ?></td>
                                <td><?php echo htmlspecialchars($r['beneficiario'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($r['vehiculo_id'] ?? ''); ?></td> <!-- TODO: Join with vehicle table if available -->
                                <td>
                                    <?php
                                    $badge = match ($r['estatus']) {
                                        'Autorizado' => 'bg-success',
                                        'Cancelado' => 'bg-danger',
                                        'Pendiente' => 'bg-warning text-dark',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge; ?>">
                                        <?php echo $r['estatus']; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    $<?php echo number_format($r['importe'] ?? 0, 2); ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="<?php echo BASE_URL; ?>/index.php?route=combustible/imprimir&id=<?php echo $r['id']; ?>"
                                        class="btn btn-sm btn-outline-secondary" title="Imprimir" target="_blank">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/index.php?route=combustible/editar&id=<?php echo $r['id']; ?>"
                                        class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
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
