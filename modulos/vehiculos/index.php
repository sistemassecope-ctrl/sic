<?php
/**
 * Módulo: Vehículos
 * Descripción: Padrón Vehicular con filtros detallados
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// ID del módulo de Padrón Vehicular
define('MODULO_ID', 45);

$pdo = getConnection();

// Obtener permisos del usuario para este módulo
$permisos_user = getUserPermissions(MODULO_ID);
$puedeVer = in_array('ver', $permisos_user);
$puedeCrear = in_array('crear', $permisos_user);
$puedeEditar = in_array('editar', $permisos_user);
$puedeEliminar = in_array('eliminar', $permisos_user);

if (!$puedeVer) {
    setFlashMessage('error', 'No tienes permiso para acceder al padrón vehicular.');
    redirect('/index.php');
}

// 2. Obtener opciones para filtros (Dynamic Dropdowns)
$regiones = $pdo->query("SELECT DISTINCT region FROM vehiculos WHERE region != '' ORDER BY region")->fetchAll(PDO::FETCH_COLUMN);
$marcas = $pdo->query("SELECT DISTINCT marca FROM vehiculos WHERE marca != '' ORDER BY marca")->fetchAll(PDO::FETCH_COLUMN);
$tipos = $pdo->query("SELECT DISTINCT tipo FROM vehiculos WHERE tipo != '' ORDER BY tipo")->fetchAll(PDO::FETCH_COLUMN);
$colores = $pdo->query("SELECT DISTINCT color FROM vehiculos WHERE color != '' ORDER BY color")->fetchAll(PDO::FETCH_COLUMN);
$modelos = $pdo->query("SELECT DISTINCT modelo FROM vehiculos WHERE modelo != '' ORDER BY modelo")->fetchAll(PDO::FETCH_COLUMN);

// 3. Procesar Filtros
$f_region = $_GET['region'] ?? '';
$f_logotipos = $_GET['logotipos'] ?? '';
$f_baja = $_GET['baja'] ?? '';
$f_color = $_GET['color'] ?? '';
$f_marca = $_GET['marca'] ?? '';
$f_modelo = $_GET['modelo'] ?? '';
$f_tipo = $_GET['tipo'] ?? '';
$f_resguardo = $_GET['resguardo'] ?? '';
// En el nuevo sistema, Secretaria/Dirección se maneja via Areas. 
// Permitiremos buscar por nombre de area si el usuario quiere filtrar.
$f_area_nombre = $_GET['area_nombre'] ?? '';

$where = ['1=1']; // Mostrar todo sin restricción de área
$params = [];

if ($f_region) {
    $where[] = "v.region = ?";
    $params[] = $f_region;
}
if ($f_logotipos && $f_logotipos != 'Todos') {
    $where[] = "v.con_logotipos = ?";
    $params[] = $f_logotipos;
}
if ($f_baja && $f_baja != 'Todos') {
    $where[] = "v.en_proceso_baja = ?";
    $params[] = $f_baja;
}
if ($f_color && $f_color != 'Todos') {
    $where[] = "v.color = ?";
    $params[] = $f_color;
}
if ($f_marca && $f_marca != 'Todas') {
    $where[] = "v.marca = ?";
    $params[] = $f_marca;
}
if ($f_modelo && $f_modelo != 'Todos') {
    $where[] = "v.modelo = ?";
    $params[] = $f_modelo;
}
if ($f_tipo && $f_tipo != 'Todos') {
    $where[] = "v.tipo = ?";
    $params[] = $f_tipo;
}
if ($f_resguardo) {
    $where[] = "v.resguardo_nombre LIKE ?";
    $params[] = "%$f_resguardo%";
}
if ($f_area_nombre) {
    $where[] = "a.nombre_area LIKE ?";
    $params[] = "%$f_area_nombre%";
}

// Filtro implícito: Solo activos en padrón (Histórico está en otra vista)
$where[] = "v.activo = 1";

$whereSQL = implode(' AND ', $where);

// 4. Paginación
$perPage = isset($_GET['per_page']) && $_GET['per_page'] === 'all' ? null : 30;
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

// Contar total de registros
$countSQL = "SELECT COUNT(*) FROM vehiculos v LEFT JOIN areas a ON v.area_id = a.id WHERE $whereSQL";
try {
    $stmtCount = $pdo->prepare($countSQL);
    $stmtCount->execute($params);
    $totalRecords = $stmtCount->fetchColumn();
} catch (PDOException $e) {
    $totalRecords = 0;
}

$totalPages = $perPage ? ceil($totalRecords / $perPage) : 1;
$currentPage = min($currentPage, max(1, $totalPages));

// 5. Query Principal con paginación
$sql = "SELECT 
            v.*,
            a.nombre_area
        FROM vehiculos v
        LEFT JOIN areas a ON v.area_id = a.id
        WHERE $whereSQL
        ORDER BY v.numero_economico ASC";

if ($perPage) {
    $offset = ($currentPage - 1) * $perPage;
    $sql .= " LIMIT $perPage OFFSET $offset";
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vehiculos = $stmt->fetchAll();
} catch (PDOException $e) {
    $vehiculos = [];
    setFlashMessage('error', 'Error: ' . $e->getMessage());
}

?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fas fa-truck-pickup" style="color: var(--accent-blue);"></i>
                Padrón Vehicular
                <span class="badge bg-primary ms-2"><?= $totalRecords ?> registros</span>
            </h1>
            <p class="page-description">Unidades activas</p>
        </div>
        <div class="d-flex gap-2">
            <a href="bandeja_bajas.php" class="btn btn-outline-warning text-dark">
                <i class="fas fa-inbox me-1"></i> Bandeja de Bajas
            </a>
            <?php if ($puedeCrear): ?>
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Vehículo
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?= renderFlashMessage() ?>

    <!-- Panel de Filtros (Estilo Legacy) -->
    <div class="card mb-4 bg-dark text-white border-secondary">
        <div class="card-header bg-transparent border-secondary d-flex justify-content-between align-items-center py-2">
            <h6 class="mb-0 text-white"><i class="fas fa-filter"></i> Filtros de Búsqueda</h6>
            <button class="btn btn-link btn-sm text-decoration-none p-0 text-white" type="button"
                data-bs-toggle="collapse" data-bs-target="#filtrosCollapse" aria-expanded="true">
                <i class="fas fa-chevron-up"></i> Mostrar / Ocultar
            </button>
        </div>
        <div class="collapse show" id="filtrosCollapse">
            <div class="card-body bg-dark text-white pt-3 pb-2">
                <form method="GET" id="searchForm">
                    <!-- Row 1: Region, Logos, Baja, Color -->
                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1">Región</label>
                            <select name="region" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Todas</option>
                                <?php foreach ($regiones as $r): ?>
                                    <option value="<?= e($r) ?>" <?= $f_region == $r ? 'selected' : '' ?>><?= e($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1">Logotipos</label>
                            <select name="logotipos" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="Todos">Todos</option>
                                <option value="SI" <?= $f_logotipos == 'SI' ? 'selected' : '' ?>>SI</option>
                                <option value="NO" <?= $f_logotipos == 'NO' ? 'selected' : '' ?>>NO</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1">Estatus Baja</label>
                            <select name="baja" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="Todos">Todos</option>
                                <option value="SI" <?= $f_baja == 'SI' ? 'selected' : '' ?>>SI</option>
                                <option value="NO" <?= $f_baja == 'NO' ? 'selected' : '' ?>>NO</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1">Color</label>
                            <select name="color" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="Todos">Todos</option>
                                <?php foreach ($colores as $c): ?>
                                    <option value="<?= e($c) ?>" <?= $f_color == $c ? 'selected' : '' ?>><?= e($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Row 2: Marca, Modelo, Tipo -->
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Marca</label>
                            <select name="marca" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="Todas">Todas</option>
                                <?php foreach ($marcas as $m): ?>
                                    <option value="<?= e($m) ?>" <?= $f_marca == $m ? 'selected' : '' ?>><?= e($m) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Modelo</label>
                            <select name="modelo" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="Todos">Todos</option>
                                <?php foreach ($modelos as $m): ?>
                                    <option value="<?= e($m) ?>" <?= $f_modelo == $m ? 'selected' : '' ?>><?= e($m) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Tipo</label>
                            <select name="tipo" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="Todos">Todos</option>
                                <?php foreach ($tipos as $t): ?>
                                    <option value="<?= e($t) ?>" <?= $f_tipo == $t ? 'selected' : '' ?>><?= e($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Row 3: Resguardante, Secretaria -->
                    <div class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold mb-1">Resguardante</label>
                            <input type="text" name="resguardo" class="form-control form-control-sm"
                                placeholder="Nombre..." value="<?= e($f_resguardo) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold mb-1">Secretaria / Depto</label>
                            <input type="text" name="area_nombre" class="form-control form-control-sm"
                                placeholder="Buscar área..." value="<?= e($f_area_nombre) ?>">
                        </div>
                    </div>

                    <!-- Hidden Submit for Enter Key -->
                    <button type="submit" class="d-none"></button>
                </form>
            </div>
        </div>
        </div>

        <!-- Tabla Resultados -->
        <div class="card" style="border:none; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-sm">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3" style="width: 50px;">#</th>
                            <th>Económico</th>
                            <th>Placas</th>
                            <th>Marca/Modelo</th>
                            <th>Tipo/Color</th>
                            <th>Región</th>
                            <th>Resguardo</th>
                            <th>Logos</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vehiculos)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">No se encontraron vehículos.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vehiculos as $index => $v): ?>
                                <tr>
                                    <td class="ps-3 text-muted"><?= $index + 1 ?></td>
                                    <td class="fw-bold"><?= e($v['numero_economico']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($v['numero_placas']) ?></span></td>
                                    <td><?= e($v['marca']) ?> <small class="text-muted"><?= e($v['modelo']) ?></small></td>
                                    <td><?= e($v['tipo']) ?> / <?= e($v['color']) ?></td>
                                    <td><?= e($v['region']) ?></td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;"
                                            title="<?= e($v['resguardo_nombre']) ?>">
                                            <?= e($v['resguardo_nombre']) ?>
                                        </div>
                                        <small class="text-secondary d-block text-truncate" style="max-width: 200px;">
                                            <?= e($v['nombre_area']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if (($v['con_logotipos'] ?? 'SI') == 'SI'): ?>
                                            <span class="badge bg-success-soft text-success">Sí</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-soft text-secondary">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group">
                                            <?php if ($puedeEditar): ?>
                                                <a href="edit.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary"
                                                    title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <!-- Notas Trigger -->
                                                <button type="button" class="btn btn-sm btn-outline-info" title="Bitácora de Notas"
                                                    onclick="verNotas(<?= $v['id'] ?>, '<?= e($v['numero_economico']) ?>')">
                                                    <i class="fas fa-clipboard-list"></i>
                                                </button>
                                                <!-- Baja Trigger -->
                                                <button type="button" class="btn btn-sm btn-outline-warning" title="Dar de Baja"
                                                    onclick="confirmarBaja(<?= $v['id'] ?>, '<?= e($v['numero_economico']) ?>')">
                                                    <i class="fas fa-arrow-down"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($puedeEliminar): ?>
                                                <!-- Eliminar Trigger -->
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    title="Eliminar Permanentemente"
                                                    onclick="confirmarEliminacion(<?= $v['id'] ?>, '<?= e($v['numero_economico']) ?>')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
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
                                        <a class="page-link"
                                            href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $currentPage - 1)])) ?>">
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
                                            <a class="page-link"
                                                href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor;

                                    if ($endPage < $totalPages): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>

                                    <!-- Siguiente -->
                                    <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                            href="?<?= http_build_query(array_merge($_GET, ['page' => min($totalPages, $currentPage + 1)])) ?>">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                    </li>

                                    <!-- Última -->
                                    <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                            href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">
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

<!-- Modal Confirmar Baja -->
<div class="modal fade" id="modalBaja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">Solicitar Baja Vehicular</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Deseas iniciar el trámite de baja para el vehículo <strong id="bajaEco"></strong>?</p>
                <div class="alert alert-info py-2">
                    <small>Esta acción enviará una solicitud a la Dirección Administrativa para su aprobación.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Motivo de Baja:</label>
                    <select id="motivoBaja" class="form-select">
                        <option value="Fin de Vida Útil">Fin de Vida Útil</option>
                        <option value="Siniestro">Siniestro</option>
                        <option value="Robo">Robo</option>
                        <option value="Venta / Subasta">Venta / Subasta</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="mb-3">
                     <label class="form-label">Detalles Adicionales:</label>
                     <textarea id="motivoAdicional" class="form-control" rows="2" placeholder="Describe brevemente..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="ejecutarSolicitudBaja()">Enviar Solicitud</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Visualizar Notas -->
<div class="modal fade" id="modalNotas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header bg-dark text-white border-secondary">
                <h5 class="modal-title"><i class="fas fa-clipboard-list"></i> Bitácora: <span
                        id="modalNotasTitulo"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body bg-dark text-white" id="modalNotasBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-info" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary bg-dark">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php include 'notas/modal_edit.php'; ?>

<style>
    /* Estilos extra para badges suaves */
    .bg-success-soft {
        background-color: #d1e7dd;
    }

    .bg-secondary-soft {
        background-color: #e2e3e5;
    }

    .text-sm {
        font-size: 0.9rem;
    }
</style>

<script>
    let bajaId = null;
    let bajaModal = null;

    document.addEventListener('DOMContentLoaded', function () {
        // Init Bootstrap Modal
        const modalEl = document.getElementById('modalBaja');
        if (modalEl && window.bootstrap) {
            bajaModal = new bootstrap.Modal(modalEl);
        }

        // Toggle logic for filters
        const toggleBtn = document.querySelector('[data-bs-toggle="collapse"]');
        const targetId = toggleBtn ? toggleBtn.getAttribute('data-bs-target') : null;
        const targetEl = targetId ? document.querySelector(targetId) : null;

        if (toggleBtn && targetEl) {
            toggleBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const isShown = targetEl.classList.contains('show');
                if (isShown) {
                    targetEl.classList.remove('show');
                    targetEl.style.display = 'none';
                } else {
                    targetEl.classList.add('show');
                    targetEl.style.display = 'block';
                }
            });
        }
    });

    function confirmarBaja(id, economico) {
        bajaId = id;
        document.getElementById('bajaEco').textContent = economico;

        if (bajaModal) {
            bajaModal.show();
        } else {
            alert("Bootstrap Modal no cargó correctamente.");
        }
    }

    async function ejecutarSolicitudBaja() {
        if (!bajaId) return;

        const motivoSelect = document.getElementById('motivoBaja').value;
        const motivoText = document.getElementById('motivoAdicional').value;
        const finalMotivo = motivoSelect + (motivoText ? ": " + motivoText : "");

        try {
            const formData = new FormData();
            formData.append('action', 'solicitar_baja');
            formData.append('vehiculo_id', bajaId);
            formData.append('motivo', finalMotivo);

            const response = await fetch('acciones_baja.php', {
                method: 'POST',
                body: formData
            });

            // Check if response is ok
            if (!response.ok) {
                const text = await response.text();
                throw new Error(`Server Error ${response.status}: ${text.substring(0, 100)}`);
            }

            const data = await response.json();
            
            if (data.success) {
                if (bajaModal) bajaModal.hide();
                // Swal would be nicer but using alert for consistency with existing code unless swal is available
                alert('Solicitud enviada: ' + data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            console.error(e);
            alert('Error: ' + e.message);
        }
    }

    async function confirmarEliminacion(id, economico) {
        const mensaje = `⚠️ ADVERTENCIA: ELIMINACIÓN PERMANENTE\n\n` +
            `Está a punto de ELIMINAR PERMANENTEMENTE el vehículo ${economico}.\n\n` +
            `• Este registro será ELIMINADO del sistema\n` +
            `• NO se moverá al Histórico de Bajas\n` +
            `• Esta acción NO se puede deshacer\n\n` +
            `Si desea conservar el registro histórico, use "Dar de Baja" en su lugar.\n\n` +
            `¿Está COMPLETAMENTE seguro de continuar?`;

        if (confirm(mensaje)) {
            try {
                const response = await fetch('api/delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Vehículo eliminado permanentemente del sistema.');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (e) {
                console.error(e);
                alert('Error de conexión');
            }
        }
    }

    // Modal de Notas
    let notasModalObj = null;

    function verNotas(id, economico) {
        document.getElementById('modalNotasTitulo').textContent = economico;
        const body = document.getElementById('modalNotasBody');
        body.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-info" role="status"><span class="visually-hidden">Cargando...</span></div></div>';

        if (!notasModalObj) {
            notasModalObj = new bootstrap.Modal(document.getElementById('modalNotas'));
        }
        notasModalObj.show();

        // Cargar contenido via AJAX
        fetch(`notas/view_async.php?vehiculo_id=${id}&from=/modulos/vehiculos/index.php`)
            .then(response => response.text())
            .then(html => {
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = '<div class="alert alert-danger">Error al cargar notas.</div>';
                console.error(err);
            });
    }
</script>


<?php include __DIR__ . '/../../includes/footer.php'; ?>