<?php
/**
 * Módulo: Proyectos de Obra (POA)
 * Ubicación: /modulos/recursos-financieros/proyectos.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getConnection();
$user = getCurrentUser();

$id_programa = isset($_GET['id_programa']) ? (int) $_GET['id_programa'] : 0;

if ($id_programa === 0) {
    setFlashMessage('error', 'Programa no especificado.');
    redirect('poas.php');
}

// Información del Programa
$stmt_prog = $pdo->prepare("SELECT * FROM programas_anuales WHERE id_programa = ?");
$stmt_prog->execute([$id_programa]);
$programa = $stmt_prog->fetch();

if (!$programa) {
    setFlashMessage('error', 'El programa solicitado no existe.');
    redirect('poas.php');
}

// Filtro de Áreas
$areaFilter = getAreaFilterSQL('p.id_unidad_responsable');

// Listado de Proyectos
$stmt_proy = $pdo->prepare("
    SELECT p.*, m.nombre_municipio, l.nombre_localidad,
           (SELECT COUNT(*) FROM fuas f WHERE f.id_proyecto = p.id_proyecto) as num_fuas,
           (SELECT SUM(importe) FROM fuas f WHERE f.id_proyecto = p.id_proyecto AND f.estatus != 'CANCELADO') as total_fua
    FROM proyectos_obra p
    LEFT JOIN cat_municipios m ON p.id_municipio = m.id_municipio
    LEFT JOIN cat_localidades l ON p.id_localidad = l.id_localidad
    WHERE p.id_programa = ? AND $areaFilter
    ORDER BY p.id_proyecto DESC
");
$stmt_proy->execute([$id_programa]);
$proyectos = $stmt_proy->fetchAll();

// Totales
$total_inversion = 0;
foreach ($proyectos as $p) {
    $total_inversion += (float) $p['monto_total'];
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="poas.php">Programas Operativos</a></li>
                    <li class="breadcrumb-item active">
                        <?= e($programa['nombre']) ?>
                    </li>
                </ol>
            </nav>
            <h1 class="page-title"><i class="fas fa-project-diagram text-primary"></i> Proyectos de Obra</h1>
            <p class="page-description">Listado de acciones programadas para el ejercicio
                <?= $programa['ejercicio'] ?>
            </p>
        </div>
        <div class="page-actions">
            <a href="proyecto-form.php?id_programa=<?= $id_programa ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Proyecto
            </a>
        </div>
    </div>

    <?= renderFlashMessage() ?>

    <div class="row stats-grid">
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">$
                    <?= number_format($total_inversion, 2) ?>
                </div>
                <div class="stat-label">Inversión Total Programada</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">
                    <?= count($proyectos) ?>
                </div>
                <div class="stat-label">Proyectos Registrados</div>
            </div>
        </div>
    </div>

    <div class="card mb-4"
        style="background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-primary);">
        <div class="card-body">
            <div class="input-group" style="max-width: 400px; position: relative;">
                <span
                    style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 5;">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="searchInput" class="form-control"
                    placeholder="Filtrar por nombre, municipio o ID..." style="padding-left: 2.75rem;">
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover" id="projectsTable">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Proyecto</th>
                            <th>Ubicación</th>
                            <th class="text-end">Monto Total</th>
                            <th class="text-end">Importe FUAs</th>
                            <th class="text-end">Saldo</th>
                            <th class="text-center">Semaforo</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($proyectos)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-box-open fa-3x mb-3 opacity-50"></i>
                                    <p>No hay proyectos registrados en este programa.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($proyectos as $p): ?>
                                <?php
                                $monto_total = (float) $p['monto_total'];
                                $total_fua = (float) ($p['total_fua'] ?? 0);
                                $saldo = $monto_total - $total_fua;

                                if ($total_fua == 0) {
                                    $semaf_class = 'text-muted';
                                    $semaf_title = 'Sin Movimientos';
                                } elseif ($saldo > 0) {
                                    $semaf_class = 'text-warning';
                                    $semaf_title = 'En Proceso';
                                } elseif ($saldo == 0) {
                                    $semaf_class = 'text-success';
                                    $semaf_title = 'Consumido';
                                } else {
                                    $semaf_class = 'text-danger';
                                    $semaf_title = 'Sobregiro';
                                }
                                ?>
                                <tr ondblclick="window.location.href='proyecto-form.php?id=<?= $p['id_proyecto'] ?>'"
                                    style="cursor: pointer;">
                                    <td class="ps-4 fw-bold text-muted">#
                                        <?= $p['id_proyecto'] ?>
                                    </td>
                                    <td style="max-width: 350px;">
                                        <div class="fw-bold text-primary text-truncate" title="<?= e($p['nombre_proyecto']) ?>">
                                            <?= e($p['nombre_proyecto']) ?>
                                        </div>
                                        <small class="text-muted d-block text-truncate">
                                            <?= e($p['breve_descripcion']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="small fw-medium">
                                            <?= e($p['nombre_municipio'] ?? 'N/A') ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?= e($p['nombre_localidad'] ?? '-') ?>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold">$
                                        <?= number_format($monto_total, 2) ?>
                                    </td>
                                    <td class="text-end text-info fw-bold">$
                                        <?= number_format($total_fua, 2) ?>
                                    </td>
                                    <td class="text-end <?= $saldo < 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                                        $
                                        <?= number_format($saldo, 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <i class="fas fa-circle <?= $semaf_class ?>" title="<?= $semaf_title ?>"></i>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="fuas.php?id_proyecto=<?= $p['id_proyecto'] ?>"
                                                class="btn btn-sm btn-secondary" title="Ver FUAs">
                                                <i class="fas fa-file-invoice"></i>
                                            </a>
                                            <a href="proyecto-form.php?id=<?= $p['id_proyecto'] ?>"
                                                class="btn btn-sm btn-secondary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                onclick="confirmDelete(<?= $p['id_proyecto'] ?>)" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
</main>

<script>
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const term = this.value.toLowerCase();
        const rows = document.querySelectorAll('#projectsTable tbody tr');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    });

    function confirmDelete(id) {
        if (confirm('¿Confirma eliminar este proyecto?')) {
            window.location.href = 'proyecto-delete.php?id=' + id + '&id_programa=<?= $id_programa ?>';
        }
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>