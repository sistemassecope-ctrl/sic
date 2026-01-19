<?php
/**
 * Organigrama - Visual Organizational Chart
 */
$pageTitle = 'Organigrama - SIC';
$breadcrumb = [
    ['url' => '../../modulos/rh/empleados.php', 'text' => 'Inicio'],
    ['url' => 'organigrama.php', 'text' => 'Organigrama']
];
require_once '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-0"><i class="fas fa-sitemap text-primary me-2"></i>Organigrama</h1>
                <p class="text-muted mb-0">Estructura organizacional de SECOPE</p>
            </div>
            <div id="navigation-controls" class="d-none">
                <button id="btnVolver" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Volver a vista completa
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-building me-2"></i>
                    <span id="chart-title">Estructura Completa</span>
                </h5>
                <small class="text-muted" id="chart-hint">
                    <i class="fas fa-info-circle me-1"></i>
                    Doble clic en un área para ver su estructura
                </small>
                <div class="zoom-controls">
                    <button class="btn btn-sm btn-outline-secondary" id="zoomOut" title="Alejar">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <span class="zoom-level mx-2" id="zoomLevel">100%</span>
                    <button class="btn btn-sm btn-outline-secondary" id="zoomIn" title="Acercar">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary ms-2" id="zoomReset" title="Restablecer">
                        <i class="fas fa-compress-arrows-alt"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-success ms-3" id="btnPrint" title="Imprimir">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2 text-muted">Cargando organigrama...</p>
                </div>
                <div id="orgchart-container" class="d-none">
                    <div id="orgchart" class="orgchart-wrapper"></div>
                </div>
                <div id="error-container" class="d-none text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <p class="text-muted">Error al cargar el organigrama</p>
                    <button class="btn btn-primary" onclick="loadOrgChart()">
                        <i class="fas fa-refresh me-1"></i> Reintentar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Preview Modal -->
<div class="modal fade" id="printPreviewModal" tabindex="-1" aria-labelledby="printPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="printPreviewModalLabel">
                    <i class="fas fa-print me-2"></i>Configuración de Impresión
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Settings Panel -->
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Ajustes</h6>
                            </div>
                            <div class="card-body">
                                <!-- Orientation -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Orientación</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="printOrientation" id="orientLandscape" value="landscape" checked>
                                        <label class="btn btn-outline-primary" for="orientLandscape">
                                            <i class="fas fa-arrows-alt-h me-1"></i> Horizontal
                                        </label>
                                        <input type="radio" class="btn-check" name="printOrientation" id="orientPortrait" value="portrait">
                                        <label class="btn btn-outline-primary" for="orientPortrait">
                                            <i class="fas fa-arrows-alt-v me-1"></i> Vertical
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Scale -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Escala: <span id="printScaleValue">55%</span></label>
                                    <input type="range" class="form-range" id="printScale" min="20" max="100" value="55" step="5">
                                    <div class="d-flex justify-content-between text-muted small">
                                        <span>20%</span>
                                        <span>100%</span>
                                    </div>
                                </div>
                                
                                <!-- Quick presets -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Preajustes</label>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-secondary btn-sm preset-btn" data-scale="40" data-orient="landscape">
                                            <i class="fas fa-expand me-1"></i> Organigrama completo
                                        </button>
                                        <button class="btn btn-outline-secondary btn-sm preset-btn" data-scale="70" data-orient="landscape">
                                            <i class="fas fa-sitemap me-1"></i> Sección mediana
                                        </button>
                                        <button class="btn btn-outline-secondary btn-sm preset-btn" data-scale="90" data-orient="portrait">
                                            <i class="fas fa-building me-1"></i> Sección pequeña
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview Panel -->
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Vista Previa</h6>
                                <small class="text-muted" id="previewPageInfo"></small>
                            </div>
                            <div class="card-body p-2" style="background: #e0e0e0; min-height: 400px;">
                                <div id="printPreviewContainer" class="print-preview-page">
                                    <!-- Cloned org chart will go here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnConfirmPrint">
                    <i class="fas fa-print me-1"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Orgchart Container - contained within main-content */
.orgchart-wrapper {
    overflow: auto;
    padding: 30px;
    min-height: 500px;
    max-height: calc(100vh - 250px);
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    position: relative;
}

/* Zoomable content */
.orgchart-content {
    transform-origin: top center;
    transition: transform 0.2s ease;
    display: inline-block;
    min-width: 100%;
}

/* Zoom controls */
.zoom-controls {
    display: flex;
    align-items: center;
}

.zoom-level {
    font-size: 0.85rem;
    font-weight: 600;
    color: #495057;
    min-width: 50px;
    text-align: center;
}

/* Org Chart Tree Structure */
.org-tree {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.org-level {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

/* Node Styles */
.org-node {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.org-card {
    background: white;
    border-radius: 12px;
    padding: 15px 20px;
    min-width: 180px;
    max-width: 250px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    position: relative;
}

.org-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.org-card.has-children:hover {
    border-color: #667eea;
}

.org-card.is-root {
    border-color: #667eea;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.org-card.is-root .org-type {
    background: rgba(255,255,255,0.2);
    color: white;
}

.org-card.is-root .org-name {
    color: white;
}

/* Type Badge */
.org-type {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.type-secretaria { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }
.type-subsecretaria { background: linear-gradient(135deg, #fd7e14, #e55a00); color: white; }
.type-secretaria-tecnica { background: linear-gradient(135deg, #e83e8c, #d63384); color: white; }
.type-direccion { background: linear-gradient(135deg, #ffc107, #e0a800); color: #212529; }
.type-subdireccion { background: linear-gradient(135deg, #20c997, #1ea085); color: white; }
.type-area { background: linear-gradient(135deg, #17a2b8, #138496); color: white; }
.type-jefatura { background: linear-gradient(135deg, #6f42c1, #5a32a3); color: white; }
.type-default { background: linear-gradient(135deg, #6c757d, #5a6268); color: white; }

/* Name */
.org-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: #2d3748;
    line-height: 1.3;
    word-wrap: break-word;
}

/* Children indicator */
.children-indicator {
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 24px;
    height: 24px;
    background: #667eea;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: bold;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
}

/* Children container */
.org-children {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 50px;
    flex-wrap: nowrap;
    position: relative;
    padding-top: 25px;
}

/* Vertical connector from parent card down */
.org-node > .org-children::before {
    content: '';
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    width: 3px;
    height: 25px;
    background: #667eea;
    border-radius: 2px;
    z-index: 1;
}

/* Horizontal connector spanning all children */
.org-children::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: #667eea;
    border-radius: 2px;
}

/* For single child, hide horizontal line */
.org-children:has(> .org-node:only-child)::after {
    display: none;
}

/* Vertical connector from horizontal line down to each child */
.org-children > .org-node::before {
    content: '';
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    width: 3px;
    height: 25px;
    background: #667eea;
    border-radius: 2px;
    z-index: 1;
}

/* Drill indicator */
.drill-hint {
    font-size: 0.7rem;
    color: #a0aec0;
    margin-top: 5px;
}

.org-card.has-children .drill-hint {
    color: #667eea;
}

/* Print Preview Modal Styles */
.print-preview-page {
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    margin: auto;
    overflow: auto;
    padding: 15px;
    transition: all 0.3s ease;
}

.print-preview-page.landscape {
    width: 100%;
    max-width: 800px;
    min-height: 400px;
    aspect-ratio: 297 / 210;
}

.print-preview-page.portrait {
    width: 100%;
    max-width: 500px;
    min-height: 500px;
    aspect-ratio: 210 / 297;
}

.print-preview-page .org-tree {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.print-preview-page .org-card {
    padding: 4px 8px !important;
    min-width: 80px !important;
}

.print-preview-page .org-type {
    font-size: 0.5rem !important;
    padding: 1px 4px !important;
}

.print-preview-page .org-name {
    font-size: 0.6rem !important;
}

.print-preview-page .org-children {
    margin-top: 20px !important;
    gap: 6px !important;
}

.preset-btn.active {
    background-color: var(--bs-primary);
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .org-card {
        min-width: 140px;
        padding: 10px 15px;
    }
    
    .org-name {
        font-size: 0.8rem;
    }
    
    .orgchart-wrapper {
        padding: 15px;
    }
    
    .org-children {
        gap: 10px;
    }
}

/* Print Styles */
@media print {
    /* Hide UI elements */
    .sidebar,
    .main-content > .navbar,
    .card-header,
    #navigation-controls,
    .zoom-controls,
    #chart-hint,
    .children-indicator,
    .drill-hint,
    footer,
    .breadcrumb,
    .row.mb-4 {
        display: none !important;
    }
    
    /* Reset layout */
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    
    body {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .card-body {
        padding: 0 !important;
    }
    
    /* Orgchart wrapper - centered */
    .orgchart-wrapper {
        overflow: visible !important;
        max-height: none !important;
        min-height: auto !important;
        padding: 20px !important;
        background: white !important;
        display: flex !important;
        justify-content: center !important;
    }
    
    .orgchart-content {
        /* Scale set dynamically via JavaScript */
        transform-origin: top center !important;
    }
    
    /* Tree centered */
    .org-tree {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
    }
    
    .org-level {
        display: flex !important;
        justify-content: center !important;
        flex-wrap: nowrap !important;
    }
    
    /* Each main branch - page break after */
    .org-level > .org-node {
        page-break-inside: avoid;
        break-inside: avoid;
    }
    
    /* Children layout */
    .org-children {
        flex-wrap: nowrap !important;
        justify-content: center !important;
        gap: 8px !important;
        margin-top: 25px !important;
        page-break-inside: avoid;
    }
    
    /* Connector lines for print */
    .org-children::after,
    .org-children > .org-node::before,
    .org-node > .org-children::before {
        background: #333 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    /* Compact cards for print */
    .org-card {
        box-shadow: none !important;
        border: 1px solid #333 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        padding: 6px 10px !important;
        min-width: 100px !important;
    }
    
    .org-type {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        font-size: 0.5rem !important;
        padding: 2px 5px !important;
        margin-bottom: 4px !important;
    }
    
    .org-name {
        font-size: 0.65rem !important;
        line-height: 1.2 !important;
    }
    
    /* Page setup - set dynamically via JavaScript */
    @page {
        /* size set dynamically */
        margin: 0.5cm;
    }
    
    /* Prevent blank pages */
    html, body {
        height: auto !important;
        min-height: 0 !important;
        overflow: visible !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .row, .col-12, .container-fluid, .main-content, .card, .card-body {
        height: auto !important;
        min-height: 0 !important;
        max-height: none !important;
        overflow: visible !important;
        page-break-inside: avoid;
    }
    
    /* Hide modal when printing */
    .modal, .modal-backdrop {
        display: none !important;
    }
    
    /* Hide footer completely */
    footer, .footer {
        display: none !important;
        height: 0 !important;
    }
}
</style>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let currentRootId = null;
let navigationHistory = [];

// Load org chart data
async function loadOrgChart(rootId = null) {
    const loading = document.getElementById('loading');
    const container = document.getElementById('orgchart-container');
    const errorContainer = document.getElementById('error-container');
    const chartTitle = document.getElementById('chart-title');
    const navControls = document.getElementById('navigation-controls');
    
    loading.classList.remove('d-none');
    container.classList.add('d-none');
    errorContainer.classList.add('d-none');
    
    try {
        let url = BASE_URL + 'api/organigrama.php';
        if (rootId) {
            url += '?root=' + rootId;
        }
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Error desconocido');
        }
        
        currentRootId = rootId;
        
        // Update title and controls
        if (rootId) {
            const rootNode = result.data[0];
            chartTitle.textContent = rootNode ? rootNode.name : 'Vista parcial';
            navControls.classList.remove('d-none');
        } else {
            chartTitle.textContent = 'Estructura Completa';
            navControls.classList.add('d-none');
            navigationHistory = [];
        }
        
        renderOrgChart(result.data);
        
        loading.classList.add('d-none');
        container.classList.remove('d-none');
        
    } catch (error) {
        console.error('Error loading org chart:', error);
        loading.classList.add('d-none');
        errorContainer.classList.remove('d-none');
    }
}

// Render org chart
function renderOrgChart(data) {
    const chart = document.getElementById('orgchart');
    chart.innerHTML = '';
    
    // Reset zoom when rendering new chart
    currentZoom = 1;
    document.getElementById('zoomLevel').textContent = '100%';
    
    if (!data || data.length === 0) {
        chart.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-folder-open fa-3x mb-3"></i><p>No hay áreas para mostrar</p></div>';
        return;
    }
    
    // Create zoomable content wrapper
    const zoomWrapper = document.createElement('div');
    zoomWrapper.id = 'orgchart-content';
    zoomWrapper.className = 'orgchart-content';
    
    const tree = document.createElement('div');
    tree.className = 'org-tree';
    
    // Render root level
    const rootLevel = document.createElement('div');
    rootLevel.className = 'org-level';
    
    data.forEach(node => {
        rootLevel.appendChild(createNodeElement(node));
    });
    
    tree.appendChild(rootLevel);
    zoomWrapper.appendChild(tree);
    chart.appendChild(zoomWrapper);
}

// Create node element
function createNodeElement(node) {
    const nodeEl = document.createElement('div');
    nodeEl.className = 'org-node';
    
    const card = document.createElement('div');
    card.className = 'org-card';
    if (node.hasChildren) card.classList.add('has-children');
    if (node.isRoot) card.classList.add('is-root');
    
    // Type badge
    const typeClass = 'type-' + (node.type || 'default').toLowerCase()
        .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
        .replace(/\s+/g, '-');
    
    const typeBadge = document.createElement('span');
    typeBadge.className = 'org-type ' + typeClass;
    typeBadge.textContent = node.type || 'Área';
    
    // Name
    const name = document.createElement('div');
    name.className = 'org-name';
    name.textContent = node.name;
    
    card.appendChild(typeBadge);
    card.appendChild(name);
    
    // Children indicator
    if (node.hasChildren && node.children) {
        const indicator = document.createElement('div');
        indicator.className = 'children-indicator';
        indicator.textContent = node.children.length;
        indicator.title = node.children.length + ' subareas';
        card.appendChild(indicator);
        
        const hint = document.createElement('div');
        hint.className = 'drill-hint';
        hint.innerHTML = '<i class="fas fa-mouse-pointer"></i> Doble clic para expandir';
        card.appendChild(hint);
    }
    
    // Double click handler for drill-down
    card.addEventListener('dblclick', (e) => {
        e.stopPropagation();
        if (node.hasChildren) {
            navigationHistory.push(currentRootId);
            loadOrgChart(node.id);
        }
    });
    
    // Single click for info (optional)
    card.addEventListener('click', (e) => {
        // Remove active from all
        document.querySelectorAll('.org-card.active').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
    });
    
    nodeEl.appendChild(card);
    
    // Render children
    if (node.children && node.children.length > 0) {
        const childrenContainer = document.createElement('div');
        childrenContainer.className = 'org-children';
        
        node.children.forEach(child => {
            childrenContainer.appendChild(createNodeElement(child));
        });
        
        nodeEl.appendChild(childrenContainer);
    }
    
    return nodeEl;
}

// Back button handler
document.getElementById('btnVolver').addEventListener('click', () => {
    if (navigationHistory.length > 0) {
        const prevRoot = navigationHistory.pop();
        loadOrgChart(prevRoot);
    } else {
        loadOrgChart(null);
    }
});

// Zoom functionality
let currentZoom = 1;
const ZOOM_STEP = 0.1;
const MIN_ZOOM = 0.3;
const MAX_ZOOM = 1.5;

function updateZoom() {
    const content = document.getElementById('orgchart-content');
    if (content) {
        content.style.transform = `scale(${currentZoom})`;
    }
    document.getElementById('zoomLevel').textContent = Math.round(currentZoom * 100) + '%';
}

document.getElementById('zoomIn').addEventListener('click', () => {
    if (currentZoom < MAX_ZOOM) {
        currentZoom = Math.min(MAX_ZOOM, currentZoom + ZOOM_STEP);
        updateZoom();
    }
});

document.getElementById('zoomOut').addEventListener('click', () => {
    if (currentZoom > MIN_ZOOM) {
        currentZoom = Math.max(MIN_ZOOM, currentZoom - ZOOM_STEP);
        updateZoom();
    }
});

document.getElementById('zoomReset').addEventListener('click', () => {
    currentZoom = 1;
    updateZoom();
});

// Print Preview System
let printScale = 55;
let printOrientation = 'landscape';

function updatePrintPreview() {
    const previewContainer = document.getElementById('printPreviewContainer');
    const scaleSlider = document.getElementById('printScale');
    const scaleValue = document.getElementById('printScaleValue');
    const orientLandscape = document.getElementById('orientLandscape');
    
    printScale = parseInt(scaleSlider.value);
    printOrientation = orientLandscape.checked ? 'landscape' : 'portrait';
    
    scaleValue.textContent = printScale + '%';
    
    // Update preview container orientation
    previewContainer.className = 'print-preview-page ' + printOrientation;
    
    // Clone and scale the org chart for preview
    const orgContent = document.getElementById('orgchart-content');
    if (orgContent) {
        const clone = orgContent.cloneNode(true);
        clone.id = 'preview-clone';
        clone.style.transform = `scale(${printScale / 100})`;
        clone.style.transformOrigin = 'top center';
        
        // Remove interactive elements from clone
        clone.querySelectorAll('.children-indicator, .drill-hint').forEach(el => el.remove());
        
        previewContainer.innerHTML = '';
        previewContainer.appendChild(clone);
    }
}

function openPrintPreview() {
    // Reset to defaults
    document.getElementById('printScale').value = 55;
    document.getElementById('orientLandscape').checked = true;
    
    updatePrintPreview();
    
    const modal = new bootstrap.Modal(document.getElementById('printPreviewModal'));
    modal.show();
}

// Print button opens modal
document.getElementById('btnPrint').addEventListener('click', openPrintPreview);

// Scale slider change
document.getElementById('printScale').addEventListener('input', updatePrintPreview);

// Orientation change
document.querySelectorAll('input[name="printOrientation"]').forEach(radio => {
    radio.addEventListener('change', updatePrintPreview);
});

// Preset buttons
document.querySelectorAll('.preset-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const scale = this.dataset.scale;
        const orient = this.dataset.orient;
        
        document.getElementById('printScale').value = scale;
        if (orient === 'landscape') {
            document.getElementById('orientLandscape').checked = true;
        } else {
            document.getElementById('orientPortrait').checked = true;
        }
        
        // Highlight active preset
        document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        updatePrintPreview();
    });
});

// Confirm print button
document.getElementById('btnConfirmPrint').addEventListener('click', () => {
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('printPreviewModal')).hide();
    
    // Apply print settings via CSS custom properties
    const content = document.getElementById('orgchart-content');
    if (content) {
        content.style.setProperty('--print-scale', printScale / 100);
    }
    
    // Update @page orientation dynamically
    const styleSheet = document.createElement('style');
    styleSheet.id = 'dynamic-print-style';
    styleSheet.textContent = `
        @media print {
            .orgchart-content {
                transform: scale(${printScale / 100}) !important;
                transform-origin: top center !important;
            }
            @page {
                size: ${printOrientation};
                margin: 0.5cm;
            }
        }
    `;
    
    // Remove old dynamic style if exists
    const oldStyle = document.getElementById('dynamic-print-style');
    if (oldStyle) oldStyle.remove();
    
    document.head.appendChild(styleSheet);
    
    // Small delay then print
    setTimeout(() => {
        window.print();
    }, 100);
});

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    loadOrgChart();
});
</script>

<?php require_once '../../includes/footer.php'; ?>
