<?php
/**
 * Módulo: Vehículos - Histórico de Bajas
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// 1. Identificación Módulo Padre (Vehículos)
$pdo = getConnection();
$stmtMod = $pdo->prepare("SELECT id FROM modulos WHERE nombre_modulo = ?");
$stmtMod->execute(['Vehículos']);
$modulo = $stmtMod->fetch();
$MODULO_ID = $modulo ? $modulo['id'] : 0;

requirePermission('ver', $MODULO_ID);

// 3. Paginación
$perPage = isset($_GET['per_page']) && $_GET['per_page'] === 'all' ? null : 30;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// También aplicamos filtro de área por seguridad
$filtroAreas = getAreaFilterSQL('area_id');

// Contar total de registros
$countSQL = "SELECT COUNT(*) FROM vehiculos_bajas WHERE $filtroAreas";
try {
    $totalRecords = $pdo->query($countSQL)->fetchColumn();
} catch (PDOException $e) {
    $totalRecords = 0;
}

$totalPages = $perPage ? ceil($totalRecords / $perPage) : 1;
$currentPage = min($currentPage, max(1, $totalPages));

// 4. Consulta de Bajas con paginación
$sql = "SELECT * FROM vehiculos_bajas 
        WHERE $filtroAreas 
        ORDER BY fecha_baja DESC";

if ($perPage) {
    $offset = ($currentPage - 1) * $perPage;
    $sql .= " LIMIT $perPage OFFSET $offset";
}

$bajas = $pdo->query($sql)->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fas fa-history" style="color: var(--text-muted);"></i>
                Histórico de Bajas
                <span class="badge bg-danger ms-2"><?= count($bajas) ?> registros</span>
            </h1>
            <p class="page-description">Archivo muerto del padrón vehicular</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Padrón
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4" style="width: 50px;">#</th>
                        <th>Unidad</th>
                        <th>Baja</th>
                        <th>Detalles</th>
                        <th>Motivo</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bajas)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                No hay registros históricos de bajas.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bajas as $index => $v): ?>
                        <tr>
                            <td class="ps-4 text-muted"><?= $index + 1 ?></td>
                            <td>
                                <div class="fw-bold text-dark">ECO-<?= e($v['numero_economico']) ?></div>
                                <div class="small text-muted"><?= e($v['numero_placas']) ?></div>
                            </td>
                            <td>
                                <div class="text-danger fw-bold"><?= e($v['fecha_baja']) ?></div>
                                <div class="small text-muted">Año: <?= e($v['anio_baja']) ?></div>
                            </td>
                            <td>
                                <div><?= e($v['marca']) ?> <?= e($v['modelo']) ?></div>
                                <div class="small text-muted">
                                    Color <?= e($v['color']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= e($v['motivo_baja']) ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <?php if (hasPermission('editar', $MODULO_ID)): ?>
                                    <div class="btn-group">
                                        <!-- Restaurar - Verde -->
                                        <button type="button" class="btn btn-sm btn-outline-success" title="Restaurar al Padrón" 
                                                onclick="restaurarBaja(<?= $v['id'] ?>, '<?= e($v['numero_economico']) ?>')">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <!-- Editar - Azul -->
                                        <a href="edit_baja.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar Datos de Baja">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <?php if ($totalRecords > 0): ?>
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-3">
            <div class="text-muted small">
                Mostrando 
                <?php if ($perPage): ?>
                    <?= min(($currentPage - 1) * $perPage + 1, $totalRecords) ?> - 
                    <?= min($currentPage * $perPage, $totalRecords) ?> de 
                <?php endif; ?>
                <?= $totalRecords ?> registros
            </div>
            
            <div class="d-flex gap-2 align-items-center">
                <!-- Botón Mostrar Todo -->
                <?php if ($perPage): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['per_page' => 'all', 'page' => 1])) ?>" 
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-list"></i> Mostrar Todo
                    </a>
                <?php else: ?>
                    <a href="?<?= http_build_query(array_diff_key($_GET, ['per_page' => '', 'page' => ''])) ?>" 
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-th-list"></i> Paginar
                    </a>
                <?php endif; ?>
                
                <!-- Controles de Página -->
                <?php if ($totalPages > 1 && $perPage): ?>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <!-- Primera -->
                        <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        
                        <!-- Anterior -->
                        <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $currentPage - 1)])) ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        
                        <!-- Números de Página -->
                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        if ($startPage > 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif;
                        
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor;
                        
                        if ($endPage < $totalPages): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        
                        <!-- Siguiente -->
                        <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => min($totalPages, $currentPage + 1)])) ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        
                        <!-- Última -->
                        <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
function restaurarBaja(id, economico) {
    if (confirm(`¿Estás seguro de restaurar el vehículo ${economico} al padrón activo?`)) {
        fetch('api/restaurar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Vehículo restaurado exitosamente.');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión');
        });
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
