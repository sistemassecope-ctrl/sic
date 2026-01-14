<?php
$db = (new Database())->getConnection();
$stmt = $db->query("
    SELECT pa.*, 
           (SELECT SUM(monto_federal + monto_estatal + monto_municipal + monto_otros) 
            FROM proyectos_obra po 
            WHERE po.id_programa = pa.id_programa) as inversion_programada
    FROM programas_anuales pa 
    ORDER BY pa.ejercicio DESC
");
$programas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Recursos Financieros</a></li>
                <li class="breadcrumb-item active" aria-current="page">Programas Operativos Anuales</li>
            </ol>
        </nav>
    </div>
    <div class="col-md-4 text-end">
        <a href="/pao/index.php?route=recursos_financieros/programas_operativos/nuevo" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo POA
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
                        <th>Ejercicio</th>
                        <th>Nombre del Programa</th>
                        <th>Monto Autorizado</th>
                        <th>Inv. Programada</th>
                        <th>Estatus</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($programas)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inboxes display-6 d-block mb-3 opacity-50"></i>
                                Sin programas anuales registrados aún.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($programas as $p): ?>
                            <tr>
                                <td class="ps-3 fw-bold">#
                                    <?php echo $p['id_programa']; ?>
                                </td>
                                <td><span class="badge bg-secondary">
                                        <?php echo $p['ejercicio']; ?>
                                    </span></td>
                                <td>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars($p['nombre']); ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($p['descripcion']); ?>
                                    </small>
                                </td>
                                <td class="fw-bold text-success">
                                    $<?php echo number_format($p['monto_autorizado'] ?? 0, 2); ?>
                                </td>
                                <td class="fw-bold text-primary">
                                    $<?php echo number_format($p['inversion_programada'] ?? 0, 2); ?>
                                </td>
                                <td>
                                    <?php
                                    $badgeClass = match ($p['estatus']) {
                                        'Abierto' => 'bg-success',
                                        'Cerrado' => 'bg-danger',
                                        'En Revisión' => 'bg-warning text-dark',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo $p['estatus']; ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="/pao/index.php?route=recursos_financieros/programas_operativos/proyectos&id_programa=<?php echo $p['id_programa']; ?>"
                                        class="btn btn-sm btn-outline-primary" title="Ver Proyectos">
                                        <i class="bi bi-eye"></i> Ver Proyectos
                                    </a>
                                    <a href="/pao/index.php?route=recursos_financieros/programas_operativos/editar&id=<?php echo $p['id_programa']; ?>"
                                        class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="/pao/index.php?route=recursos_financieros/programas_operativos/eliminar&id=<?php echo $p['id_programa']; ?>"
                                        class="btn btn-sm btn-outline-danger" title="Eliminar"
                                        onclick="return confirm('¿Está seguro de eliminar este Programa Anual? Esto borrará también todos sus proyectos asociados.');">
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