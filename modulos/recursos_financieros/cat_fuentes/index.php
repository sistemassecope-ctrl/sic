<?php
$db = (new Database())->getConnection();

// Query simple
$sql = "SELECT * FROM cat_fuentes_financiamiento ORDER BY anio DESC, abreviatura ASC";
$fuentes = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h2 class="text-primary fw-bold"><i class="bi bi-bank"></i> Catálogo de Fuentes de Recursos</h2>
        <p class="text-muted">Administración de fuentes de financiamiento disponibles.</p>
    </div>
    <div class="col-md-4 text-end">
        <button onclick="window.close();" class="btn btn-secondary shadow-sm me-2">
            <i class="bi bi-x-circle"></i> Cerrar
        </button>
        <a href="/pao/index.php?route=recursos_financieros/cat_fuentes/captura" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg"></i> Nueva Fuente
        </a>
    </div>
</div>

<div class="card shadow border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Año</th>
                        <th>Abreviatura</th>
                        <th>Nombre Completo</th>
                        <th class="text-center">Estatus</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fuentes)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">No hay fuentes registradas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fuentes as $row): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">
                                    <?php echo $row['anio']; ?>
                                </td>
                                <td><span class="badge bg-light text-primary border border-primary">
                                        <?php echo $row['abreviatura']; ?>
                                    </span></td>
                                <td>
                                    <?php echo $row['nombre_fuente']; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['activo']): ?>
                                        <span class="badge bg-success rounded-pill"><i class="bi bi-check-circle"></i> Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary rounded-pill">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="/pao/index.php?route=recursos_financieros/cat_fuentes/captura&id=<?php echo $row['id_fuente']; ?>"
                                        class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>