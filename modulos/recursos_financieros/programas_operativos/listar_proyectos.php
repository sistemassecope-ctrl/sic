<?php
// List Projects for a specific POA
$db = (new Database())->getConnection();

$id_programa = isset($_GET['id_programa']) ? (int) $_GET['id_programa'] : 0;

if ($id_programa === 0) {
    echo "<div class='alert alert-danger'>Programa no especificado.</div>";
    return;
}

// Fetch Parent Program Info
$stmt_prog = $db->prepare("SELECT * FROM programas_anuales WHERE id_programa = ?");
$stmt_prog->execute([$id_programa]);
$programa = $stmt_prog->fetch(PDO::FETCH_ASSOC);

if (!$programa) {
    echo "<div class='alert alert-danger'>El programa solicitado no existe.</div>";
    return;
}

// Fetch Projects
$stmt_proy = $db->prepare("SELECT p.*, m.nombre_municipio,
                           (SELECT COUNT(*) FROM fuas f WHERE f.id_proyecto = p.id_proyecto) as num_fuas
                           FROM proyectos_obra p
                           LEFT JOIN cat_municipios m ON p.id_municipio = m.id_municipio
                           WHERE p.id_programa = ?
                           ORDER BY p.id_proyecto DESC");
$stmt_proy->execute([$id_programa]);
$proyectos = $stmt_proy->fetchAll(PDO::FETCH_ASSOC);

// Calcular Gran Total de Inversión
$gran_total_inversion = 0;
foreach ($proyectos as $p) {
    $gran_total_inversion += ($p['monto_federal'] + $p['monto_estatal'] + $p['monto_municipal'] + $p['monto_otros']);
}
?>

<style>
    /* Sticky Header - Window Level */
    .sticky-header-th th {
        position: sticky;
        top: 0;
        z-index: 1020;
        background-color: #f8f9fa !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="row mb-3">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a
                        href="/pao/index.php?route=recursos_financieros/programas_operativos">Programas Operativos</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($programa['nombre']); ?>
                </li>
            </ol>
        </nav>
        <div class="d-flex align-items-center flex-wrap gap-3">
            <h4 class="fw-bold text-primary mb-0">Listado de Proyectos de Obra</h4>
            <span class="badge bg-success fs-6 shadow-sm">
                Inversión Total: $<?php echo number_format($gran_total_inversion, 2); ?>
            </span>
        </div>
        <p class="text-muted small mt-2">
            Ejercicio: <strong>
                <?php echo $programa['ejercicio']; ?>
            </strong> |
            Estatus: <span class="badge bg-secondary">
                <?php echo $programa['estatus']; ?>
            </span>
        </p>
    </div>
</div>

<!-- Toolbar: Buscador (Izq) y Boton (Der) -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="input-group" style="max-width: 300px;">
        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
        <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Buscar proyecto..."
            autocomplete="off">
    </div>

    <a href="/pao/index.php?route=recursos_financieros/proyectos/nuevo&id_programa=<?php echo $id_programa; ?>"
        class="btn btn-success shadow-sm">
        <i class="bi bi-plus-lg"></i> Agregar Proyecto
    </a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const table = document.querySelector('table tbody');

        searchInput.addEventListener('keyup', function () {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
</script>

<div class="card shadow border-0">
    <div class="card-body p-0">
        <!-- Removed table-responsive to allow window scroll adhesion -->
        <div>
            <table class="table table-hover align-middle mb-0 sticky-header-th">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Nombre del Proyecto</th>
                        <th>Municipio / Localidad</th>
                        <th class="text-end">Monto Total</th>
                        <th class="text-center">Semáforo</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proyectos)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-bricks display-6 d-block mb-3 opacity-50"></i>
                                No hay proyectos registrados en este programa.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($proyectos as $p): ?>
                            <?php
                            $monto_total = $p['monto_federal'] + $p['monto_estatal'] + $p['monto_municipal'] + $p['monto_otros'];
                            ?>
                            <tr style="cursor: pointer;" onclick="if(event.target.closest('.btn-group')) return;"
                                ondblclick="window.location.href='/pao/index.php?route=recursos_financieros/proyectos/editar&id=<?php echo $p['id_proyecto']; ?>'">
                                <td class="ps-3 fw-bold">#
                                    <?php echo $p['id_proyecto']; ?>
                                </td>
                                <td style="max-width: 300px;">
                                    <div class="text-truncate fw-bold"
                                        title="<?php echo htmlspecialchars($p['nombre_proyecto']); ?>">
                                        <?php echo htmlspecialchars($p['nombre_proyecto']); ?>
                                    </div>
                                    <small class="text-muted text-truncate d-block">
                                        <?php echo htmlspecialchars($p['breve_descripcion']); ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="small fw-bold">
                                        <?php echo htmlspecialchars($p['nombre_municipio'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="small text-muted">
                                        <?php echo htmlspecialchars($p['localidad']); ?>
                                    </div>
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    $<?php echo number_format($monto_total, 2); ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    // Semáforo: Rojo si tiene FUAs asociados (num_fuas > 0)
                                    // Ignoramos FUAs cancelados en el conteo (ver QUERY)
                                    $tiene_movimientos = ($p['num_fuas'] > 0);

                                    $semaforo_color = $tiene_movimientos ? 'text-danger' : 'text-secondary';
                                    $semaforo_titulo = $tiene_movimientos ? 'Tiene Movimientos (FUAs)' : 'Sin Movimientos';
                                    ?>
                                    <i class="bi bi-circle-fill <?php echo $semaforo_color; ?> fs-5"
                                        title="<?php echo $semaforo_titulo; ?>" data-bs-toggle="tooltip"></i>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group">
                                        <a href="/pao/index.php?route=recursos_financieros/fuas&id_proyecto=<?php echo $p['id_proyecto']; ?>"
                                            class="btn btn-sm btn-outline-info" title="Ver FUAs"
                                            onclick="event.stopPropagation();"><i class="bi bi-file-earmark-text"></i></a>
                                        <a href="/pao/index.php?route=recursos_financieros/proyectos/editar&id=<?php echo $p['id_proyecto']; ?>"
                                            class="btn btn-sm btn-outline-secondary" title="Editar Proyecto"
                                            onclick="event.stopPropagation();"><i class="bi bi-pencil"></i></a>
                                        <a href="/pao/index.php?route=recursos_financieros/proyectos/eliminar&id=<?php echo $p['id_proyecto']; ?>&id_programa=<?php echo $id_programa; ?>"
                                            class="btn btn-sm btn-outline-danger" title="Eliminar Proyecto"
                                            onclick="event.stopPropagation(); return confirm('¿Confirma eliminar este proyecto?');"><i
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