<?php
// Obtener conexión y datos
$db = (new Database())->getConnection();

// --- 1. Obtener Opciones para Listas Desplegables ---
$marcas = $db->query("SELECT DISTINCT marca FROM vehiculos WHERE marca != '' ORDER BY marca")->fetchAll(PDO::FETCH_COLUMN);
$tipos = $db->query("SELECT DISTINCT tipo FROM vehiculos WHERE tipo != '' ORDER BY tipo")->fetchAll(PDO::FETCH_COLUMN);
$modelos = $db->query("SELECT DISTINCT modelo FROM vehiculos WHERE modelo != '' ORDER BY modelo")->fetchAll(PDO::FETCH_COLUMN);
$colores = $db->query("SELECT DISTINCT color FROM vehiculos WHERE color != '' ORDER BY color")->fetchAll(PDO::FETCH_COLUMN);
$secretarias = $db->query("SELECT DISTINCT secretaria_subsecretaria FROM vehiculos WHERE secretaria_subsecretaria != '' ORDER BY secretaria_subsecretaria")->fetchAll(PDO::FETCH_COLUMN);
$direcciones = $db->query("SELECT DISTINCT direccion_departamento FROM vehiculos WHERE direccion_departamento != '' ORDER BY direccion_departamento")->fetchAll(PDO::FETCH_COLUMN);

// --- 2. Construcción de Filtros ---
$where = "1=1";
$params = [];

// Filtros Texto (LIKE)
$textFilters = [
    'numero_economico' => 'numero_economico',
    'numero_placas' => 'numero_placas',
    'resguardo_nombre' => 'resguardo_nombre',
    'numero_serie' => 'numero_serie',
    'poliza' => 'poliza',
    'factura_nombre' => 'factura_nombre'
];

foreach ($textFilters as $key => $col) {
    if (!empty($_GET[$key])) {
        $where .= " AND $col LIKE ?";
        $params[] = "%" . trim($_GET[$key]) . "%";
    }
}

// Filtros Exactos (Selects)
$exactFilters = [
    'region' => 'region',
    'con_logotipos' => 'con_logotipos',
    'en_proceso_baja' => 'en_proceso_baja',
    'marca' => 'marca',
    'tipo' => 'tipo',
    'modelo' => 'modelo',
    'color' => 'color',
    'secretaria_subsecretaria' => 'secretaria_subsecretaria',
    'direccion_departamento' => 'direccion_departamento'
];

foreach ($exactFilters as $key => $col) {
    if (!empty($_GET[$key])) {
        $where .= " AND $col = ?";
        $params[] = $_GET[$key];
    }
}

// Búsqueda General (Backup)
if (!empty($_GET['search'])) {
    $term = "%" . trim($_GET['search']) . "%";
    $where .= " AND (numero_economico LIKE ? OR numero_placas LIKE ? OR resguardo_nombre LIKE ?)";
    array_push($params, $term, $term, $term);
}

// --- 3. Paginación ---
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$queryParams = $_GET;
unset($queryParams['route']);
unset($queryParams['page']);
$queryString = http_build_query($queryParams);

// Contar Total
$sqlCount = "SELECT COUNT(*) FROM vehiculos WHERE $where";
$stmtCount = $db->prepare($sqlCount);
$stmtCount->execute($params);
$total = $stmtCount->fetchColumn();
$pages = ceil($total / $limit);

// Obtener Registros
$sql = "SELECT * FROM vehiculos WHERE $where ORDER BY numero_economico ASC LIMIT $start, $limit";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helpers de Vista
function isSelected($field, $value) {
    return (isset($_GET[$field]) && $_GET[$field] == $value) ? 'selected' : '';
}
function getVal($field) {
    return isset($_GET[$field]) ? htmlspecialchars($_GET[$field]) : '';
}
// Helper para estilos de filtros activos
function getSelectClass($field) {
    $base = "form-select form-select-sm cursor-pointer";
    if (!empty($_GET[$field])) {
        // ID 404: Solicitud de alto contraste (Gris oscuro, texto blanco, negritas)
        return $base . " bg-secondary text-white fw-bold shadow-sm border-secondary";
    }
    return $base;
}
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="h4 mb-0 text-primary">
            <i class="bi bi-truck me-2"></i>Padrón Vehicular
        </h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="#">Vehículos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Padrón</li>
            </ol>
        </nav>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo BASE_URL; ?>/index.php?route=vehiculos/padron_vehicular/nuevo" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Vehículo
        </a>
    </div>
</div>

<!-- Panel de Filtros -->
<div class="card shadow-sm mb-4 border-0">
    <div class="card-header bg-white py-3 border-bottom-0">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-secondary border px-3 py-1 bg-light rounded"><i class="bi bi-funnel me-2"></i>Filtros de Búsqueda</h5>
            <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filtrosCollapse" aria-expanded="true" aria-controls="filtrosCollapse">
                <i class="bi bi-chevron-expand me-1"></i> Mostrar / Ocultar
            </button>
        </div>
    </div>
    <div class="collapse show" id="filtrosCollapse">
        <div class="card-body pt-0">
            <form action="" method="GET" class="border opacity-75 p-3 rounded bg-light bg-opacity-10">
                <input type="hidden" name="route" value="vehiculos/padron_vehicular">
                
                <!-- Grid de Filtros -->
                <div class="row g-2 mb-3">
                    <!-- Fila 1: Clasificación Principal -->
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Región</label>
                        <select name="region" class="<?php echo getSelectClass('region'); ?>" onchange="this.form.submit()">
                            <option value="">Todas</option>
                            <option value="SECOPE" <?php echo isSelected('region', 'SECOPE'); ?>>SECOPE</option>
                            <option value="REGION LAGUNA" <?php echo isSelected('region', 'REGION LAGUNA'); ?>>REGION LAGUNA</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Logotipos</label>
                        <select name="con_logotipos" class="<?php echo getSelectClass('con_logotipos'); ?>" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="SI" <?php echo isSelected('con_logotipos', 'SI'); ?>>SÍ</option>
                            <option value="NO" <?php echo isSelected('con_logotipos', 'NO'); ?>>NO</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Estatus Baja</label>
                        <select name="en_proceso_baja" class="<?php echo getSelectClass('en_proceso_baja'); ?>" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="NO" <?php echo isSelected('en_proceso_baja', 'NO'); ?>>Activos</option>
                            <option value="SI" <?php echo isSelected('en_proceso_baja', 'SI'); ?>>Para Baja</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Color</label>
                        <select name="color" class="<?php echo getSelectClass('color'); ?>" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <?php foreach($colores as $c): ?>
                                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo isSelected('color', $c); ?>><?php echo htmlspecialchars($c); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Fila 2: Datos del Vehículo -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Marca</label>
                        <select name="marca" class="<?php echo getSelectClass('marca'); ?>" onchange="this.form.submit()">
                            <option value="">Todas</option>
                            <?php foreach($marcas as $m): ?>
                                <option value="<?php echo htmlspecialchars($m); ?>" <?php echo isSelected('marca', $m); ?>><?php echo htmlspecialchars($m); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Modelo</label>
                        <select name="modelo" class="<?php echo getSelectClass('modelo'); ?>" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <?php foreach($modelos as $m): ?>
                                <option value="<?php echo htmlspecialchars($m); ?>" <?php echo isSelected('modelo', $m); ?>><?php echo htmlspecialchars($m); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Tipo</label>
                        <select name="tipo" class="<?php echo getSelectClass('tipo'); ?>" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <?php foreach($tipos as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>" <?php echo isSelected('tipo', $t); ?>><?php echo htmlspecialchars($t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Fila 3: Identificadores (Texto - Requiere Enter) -->
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">No. Económico</label>
                        <input type="text" name="numero_economico" class="form-control form-control-sm" value="<?php echo getVal('numero_economico'); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Placas</label>
                        <input type="text" name="numero_placas" class="form-control form-control-sm" value="<?php echo getVal('numero_placas'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">No. Serie</label>
                        <input type="text" name="numero_serie" class="form-control form-control-sm" value="<?php echo getVal('numero_serie'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Póliza</label>
                        <input type="text" name="poliza" class="form-control form-control-sm" value="<?php echo getVal('poliza'); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Factura</label>
                        <input type="text" name="factura_nombre" class="form-control form-control-sm" value="<?php echo getVal('factura_nombre'); ?>">
                    </div>
                    
                    <!-- Fila 4: Ubicación y Resguardo -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Resguardante</label>
                        <input type="text" name="resguardo_nombre" class="form-control form-control-sm" placeholder="Nombre..." value="<?php echo getVal('resguardo_nombre'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Secretaría / Depto</label>
                        <select name="secretaria_subsecretaria" class="<?php echo getSelectClass('secretaria_subsecretaria'); ?>" onchange="this.form.submit()">
                            <option value="">Todas</option>
                            <?php foreach($secretarias as $s): ?>
                                <option value="<?php echo htmlspecialchars($s); ?>" <?php echo isSelected('secretaria_subsecretaria', $s); ?>><?php echo htmlspecialchars($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Dirección</label>
                        <select name="direccion_departamento" class="<?php echo getSelectClass('direccion_departamento'); ?>" onchange="this.form.submit()">
                            <option value="">Todas</option>
                            <?php foreach($direcciones as $d): ?>
                                <option value="<?php echo htmlspecialchars($d); ?>" <?php echo isSelected('direccion_departamento', $d); ?>><?php echo htmlspecialchars($d); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="text-end">
                    <?php if(!empty($queryParams)): ?>
                        <a href="<?php echo BASE_URL; ?>/index.php?route=vehiculos/padron_vehicular" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-x-circle me-1"></i> Borrar Filtros
                        </a>
                    <?php endif; ?>
                    <!-- Botón filtrar eliminado, ahora es automático para dropdowns -->
                    <button type="submit" class="btn btn-sm btn-primary d-none">Filtrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabla de Resultados -->
<div class="card shadow border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase small text-secondary">
                    <tr>
                        <th class="ps-4 py-3" style="width: 50px;">#</th>
                        <th>N° Eco</th>
                        <th>Placas</th>
                        <th>Marca / Modelo</th>
                        <th>Tipo</th>
                        <th>Región / Estatus</th>
                        <th>Resguardante</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if (empty($vehiculos)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-truck display-6 d-block mb-3 opacity-50"></i>
                                No se encontraron vehículos que coincidan con los filtros.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vehiculos as $index => $v): ?>
                            <?php $contador = ($page - 1) * $limit + $index + 1; ?>
                            <tr>
                                <td class="ps-4 text-muted fw-light small">
                                    <?php echo $contador; ?>
                                </td>
                                <td class="fw-bold text-primary">
                                    <?php echo htmlspecialchars($v['numero_economico']); ?>
                                </td>
                                <td>
                                    <?php if($v['numero_placas']): ?>
                                        <span class="badge bg-light text-dark border font-monospace">
                                            <?php echo htmlspecialchars($v['numero_placas']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">S/P</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?php echo htmlspecialchars($v['marca']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($v['modelo'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <span class="small text-secondary"><?php echo htmlspecialchars($v['tipo']); ?></span>
                                </td>
                                <td>
                                    <!-- Región -->
                                    <?php if ($v['region'] == 'SECOPE'): ?>
                                        <span class="fw-bold text-secondary small">SECOPE</span>
                                    <?php else: ?>
                                        <span class="badge bg-info bg-opacity-10 text-info mb-1">
                                            <?php echo htmlspecialchars($v['region']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <!-- Estatus Baja -->
                                    <?php if ($v['en_proceso_baja'] == 'SI'): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-danger rounded-pill">
                                                <i class="bi bi-exclamation-circle me-1"></i> Para Baja
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-dark small text-truncate" style="max-width: 180px;" title="<?php echo htmlspecialchars($v['resguardo_nombre']); ?>">
                                        <?php echo htmlspecialchars($v['resguardo_nombre']); ?>
                                    </div>
                                    <div class="text-muted small text-truncate" style="max-width: 180px;">
                                        <?php echo htmlspecialchars($v['direccion_departamento']); ?>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="<?php echo BASE_URL; ?>/index.php?route=vehiculos/padron_vehicular/editar&id=<?php echo $v['id']; ?>" 
                                           class="btn btn-sm btn-light text-primary border" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" 
                                           class="btn btn-sm btn-light text-danger border" 
                                           title="Eliminar"
                                           data-bs-toggle="modal" 
                                           data-bs-target="#deleteModal<?php echo $v['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Modal de Eliminación -->
                                    <div class="modal fade text-start" id="deleteModal<?php echo $v['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title fs-6">Confirmar Eliminación</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="mb-0">¿Estás seguro de que deseas eliminar el vehículo?</p>
                                                    <p class="mt-1 small">Resguardo: <strong><?php echo htmlspecialchars($v['resguardo_nombre']); ?></strong></p>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <a href="<?php echo BASE_URL; ?>/index.php?route=vehiculos/padron_vehicular/eliminar&id=<?php echo $v['id']; ?>" class="btn btn-sm btn-danger">Eliminar</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <?php if ($pages > 1): ?>
        <div class="p-3 border-top">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?route=vehiculos/padron_vehicular&page=<?php echo $page - 1; ?>&<?php echo $queryString; ?>">Anterior</a>
                    </li>
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?route=vehiculos/padron_vehicular&page=<?php echo $i; ?>&<?php echo $queryString; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?route=vehiculos/padron_vehicular&page=<?php echo $page + 1; ?>&<?php echo $queryString; ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
        
        <div class="card-footer bg-white text-end text-muted small p-2">
            Total de registros: <?php echo $total; ?>
        </div>
    </div>
</div>
