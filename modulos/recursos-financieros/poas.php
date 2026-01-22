<?php
/**
 * Módulo: Programas Operativos Anuales (POA)
 * Ubicación: /modulos/recursos-financieros/poas.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();
// requirePermission('ver', 5); // ID 5 para Recursos Financieros según el plan

$pdo = getConnection();
$user = getCurrentUser();

// Obtener programas anuales con su inversión programada total
$stmt = $pdo->query("
    SELECT pa.*, 
           (SELECT SUM(monto_federal + monto_estatal + monto_municipal + monto_otros) 
            FROM proyectos_obra po 
            WHERE po.id_programa = pa.id_programa) as inversion_programada,
           (SELECT COUNT(*) FROM proyectos_obra po WHERE po.id_programa = pa.id_programa) as num_proyectos
    FROM programas_anuales pa 
    ORDER BY pa.ejercicio DESC
");
$programas = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-file-invoice-dollar text-primary"></i> Programas Operativos Anuales
            </h1>
            <p class="page-description">Gestión de techos financieros por ejercicio fiscal</p>
        </div>
        <div class="page-actions">
            <a href="poa-form.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Nuevo POA
            </a>
        </div>
    </div>

    <?= renderFlashMessage() ?>

    <div class="row stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">
                    <?= count($programas) ?>
                </div>
                <div class="stat-label">Programas Registrados</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">
                    <?= count(array_filter($programas, fn($p) => $p['estatus'] == 'Abierto')) ?>
                </div>
                <div class="stat-label">POAs Abiertos</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="ps-4">Ejercicio</th>
                            <th>Nombre del Programa</th>
                            <th class="text-end">Monto Autorizado</th>
                            <th class="text-end">Inv. Programada</th>
                            <th class="text-center">Proyectos</th>
                            <th>Estatus</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($programas)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-folder-open fa-3x mb-3 text-muted"></i>
                                    <p class="text-muted">No hay programas anuales registrados aún.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($programas as $p): ?>
                                <tr ondblclick="window.location.href='proyectos.php?id_programa=<?= $p['id_programa'] ?>'"
                                    style="cursor: pointer;">
                                    <td class="ps-4">
                                        <span class="badge bg-secondary">
                                            <?= e($p['ejercicio']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary">
                                            <?= e($p['nombre']) ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= e($p['descripcion']) ?>
                                        </small>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        $
                                        <?= number_format($p['monto_autorizado'] ?? 0, 2) ?>
                                    </td>
                                    <td class="text-end fw-bold text-info">
                                        $
                                        <?= number_format($p['inversion_programada'] ?? 0, 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-dark border">
                                            <?= $p['num_proyectos'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match ($p['estatus']) {
                                            'Abierto' => 'badge-success',
                                            'Cerrado' => 'badge-danger',
                                            'En Revisión' => 'badge-warning',
                                            default => 'badge-info'
                                        };
                                        ?>
                                        <span class="badge <?= $statusClass ?>">
                                            <?= e($p['estatus']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="proyectos.php?id_programa=<?= $p['id_programa'] ?>"
                                                class="btn btn-sm btn-secondary" title="Ver Proyectos">
                                                <i class="fas fa-list"></i>
                                            </a>
                                            <a href="poa-form.php?id=<?= $p['id_programa'] ?>" class="btn btn-sm btn-secondary"
                                                title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                onclick="confirmDelete(<?= $p['id_programa'] ?>)" title="Eliminar">
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
    function confirmDelete(id) {
        if (confirm('¿Está seguro de eliminar este Programa Anual? Esto borrará también todos sus proyectos asociados.')) {
            window.location.href = 'poa-delete.php?id=' + id;
        }
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>