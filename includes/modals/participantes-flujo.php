<?php
/**
 * Componente: Modal de Selección de Participantes para Flujo de Firmas (Versión Drag & Drop Premium)
 * Ubicación: includes/modals/participantes-flujo.php
 */
?>
<!-- SortableJS para Drag & Drop Capability -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<div class="modal fade" id="modalParticipantesFlujo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content glass-vibrant-bg border-primary shadow-lg"
            style="background: rgba(15, 23, 42, 0.98); backdrop-filter: blur(25px); border: 1px solid rgba(59, 130, 246, 0.3);">

            <div class="modal-header border-bottom border-primary/20 pb-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-primary-subtle p-2 rounded-3 me-3">
                        <i class="fas fa-users-cog fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0">Configuración Avanzada del Flujo</h5>
                        <p class="text-muted small mb-0">Gestione participantes y roles mediante arrastrar y soltar</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- PANEL IZQUIERDO: Búsqueda y Selección -->
                    <div class="col-lg-5">
                        <div class="card h-100 bg-black/20 border-secondary/30 rounded-4 overflow-hidden shadow-inner">
                            <div class="card-header bg-black/40 border-secondary/20 py-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label text-white-50 small fw-bold text-uppercase mb-0">Origen de
                                        Participantes</label>
                                    <button
                                        class="btn btn-xs btn-outline-info border-info/30 rounded-pill px-2 py-0 text-uppercase fw-bold"
                                        style="font-size: 9px;" onclick="toggleExternalForm()">
                                        <i class="fas fa-user-tag me-1"></i> Externo / Manual
                                    </button>
                                </div>

                                <!-- Formulario de Búsqueda Normal -->
                                <div id="search-mode-container">
                                    <div class="input-group input-group-grow">
                                        <span class="input-group-text bg-transparent border-secondary/40 text-muted">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input type="text" id="search-participantes"
                                            class="form-control bg-transparent border-secondary/40 text-white shadow-none"
                                            placeholder="Nombre, puesto o área...">
                                    </div>
                                </div>

                                <!-- Formulario Manual (Oculto por defecto) -->
                                <div id="external-mode-container" style="display: none;"
                                    class="animate__animated animate__fadeIn">
                                    <div class="bg-primary/5 p-3 rounded-3 border border-primary/20">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <input type="text" id="ext-nombre"
                                                    class="form-control form-control-sm bg-black/30 border-secondary/40 text-white"
                                                    placeholder="Nombre completo">
                                            </div>
                                            <div class="col-6">
                                                <input type="text" id="ext-area"
                                                    class="form-control form-control-sm bg-black/30 border-secondary/40 text-white"
                                                    placeholder="Área / Unidad">
                                            </div>
                                            <div class="col-6">
                                                <input type="text" id="ext-puesto"
                                                    class="form-control form-control-sm bg-black/30 border-secondary/40 text-white"
                                                    placeholder="Puesto">
                                            </div>
                                            <div class="col-12 mt-2">
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-primary btn-sm flex-grow-1 fw-bold"
                                                        onclick="addExternalParticipant()">
                                                        <i class="fas fa-plus me-1"></i> CREAR TARJETA
                                                    </button>
                                                    <button class="btn btn-outline-secondary btn-sm"
                                                        onclick="toggleExternalForm()">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div id="available-list"
                                    class="list-group list-group-flush overflow-auto custom-scrollbar"
                                    style="max-height: 500px; min-height: 400px;">
                                    <!-- Los resultados de búsqueda aparecerán aquí -->
                                    <div class="p-5 text-center text-muted opacity-50">
                                        <i class="fas fa-user-plus fs-1 mb-3"></i>
                                        <p>Escribe al menos 2 caracteres para buscar...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BOTONES DE ACCIÓN (Mobile: Row, Desktop: Column center) -->
                    <div class="col-lg-1 d-none d-lg-flex flex-column justify-content-center align-items-center gap-3">
                        <div class="action-hint text-center text-muted small mb-2">
                            <i class="fas fa-mouse-pointer d-block mb-1"></i>
                            Arrastre <br> o use
                        </div>
                        <button type="button"
                            class="btn btn-outline-primary rounded-circle p-3 shadow-sm btn-action-move"
                            onclick="moveToDefaultSection()" title="Mover a destinatario">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button type="button"
                            class="btn btn-outline-secondary rounded-circle p-3 shadow-sm btn-action-move"
                            onclick="clearSelections()" title="Quitar seleccionados">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>

                    <!-- PANEL DERECHO: Configuración del Flujo -->
                    <div class="col-lg-6">
                        <div class="flow-canvas custom-scrollbar overflow-auto pe-2" style="max-height: 600px;">

                            <!-- SECCIÓN: DESTINATARIO -->
                            <div class="role-section mb-4" data-role="DESTINATARIO">
                                <div class="section-header d-flex justify-content-between align-items-center mb-2">
                                    <span
                                        class="badge bg-primary-subtle text-primary border border-primary/20 px-3 py-2 fw-bold">
                                        <i class="fas fa-envelope me-2"></i>DESTINATARIO / A QUIÉN VA DIRIGIDO
                                    </span>
                                </div>
                                <div class="drop-zone rounded-4 border border-dashed border-primary/30 bg-primary/5 p-3 min-h-60"
                                    id="dz-destinatario">
                                    <div class="empty-placeholder text-center text-muted py-2 small">
                                        <i class="fas fa-plus-circle me-1"></i> Arrastre aquí para asignar
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN: FIRMANTES -->
                            <div class="role-section mb-4" data-role="FIRMANTE">
                                <div class="section-header d-flex justify-content-between align-items-center mb-2">
                                    <span
                                        class="badge bg-success-subtle text-success border border-success/20 px-3 py-2 fw-bold">
                                        <i class="fas fa-file-signature me-2"></i>FIRMANTES (FLUJO CRONOLÓGICO)
                                    </span>
                                    <small class="text-white-50 x-small"><i class="fas fa-info-circle me-1"></i> Se
                                        firmará en el orden mostrado</small>
                                </div>
                                <div class="drop-zone rounded-4 border border-dashed border-success/30 bg-success/5 p-3 min-h-120"
                                    id="dz-firmante">
                                    <div class="empty-placeholder text-center text-muted py-4 small">
                                        <i class="fas fa-list-ol me-1"></i> Arrastre aquí a quienes deben firmar
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <!-- SECCIÓN: CON COPIA -->
                                <div class="col-md-6">
                                    <div class="role-section mb-4" data-role="COPIA">
                                        <div class="section-header mb-2">
                                            <span
                                                class="badge bg-info-subtle text-info border border-info/20 px-3 py-2 fw-bold">
                                                <i class="fas fa-copy me-2"></i>CON COPIA (CCP)
                                            </span>
                                        </div>
                                        <div class="drop-zone rounded-4 border border-dashed border-info/30 bg-info/5 p-3 min-h-100"
                                            id="dz-copia">
                                            <div class="empty-placeholder text-center text-muted py-3 small">
                                                Compartir copia
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SECCIÓN: CON ATENCIÓN -->
                                <div class="col-md-6">
                                    <div class="role-section mb-4" data-role="ATENCION">
                                        <div class="section-header mb-2">
                                            <span
                                                class="badge bg-warning-subtle text-warning border border-warning/20 px-3 py-2 fw-bold">
                                                <i class="fas fa-eye me-2"></i>CON ATENCIÓN (CCA)
                                            </span>
                                        </div>
                                        <div class="drop-zone rounded-4 border border-dashed border-warning/30 bg-warning/5 p-3 min-h-100"
                                            id="dz-atencion">
                                            <div class="empty-placeholder text-center text-muted py-3 small">
                                                Para conocimiento
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top border-white/10 p-3 bg-black/20">
                <div class="me-auto text-muted small">
                    <span id="p-count-total">0</span> participantes en el flujo
                </div>
                <button type="button" class="btn btn-link text-white-50 text-decoration-none fw-semibold"
                    data-bs-dismiss="modal">CANCELAR</button>
                <button type="button" class="btn btn-primary px-4 fw-bold rounded-3 shadow-lg" id="btnConfirmarFlujo">
                    <i class="fas fa-paper-plane me-2"></i>INICIAR FLUJO DINÁMICO
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos Premium para el Modal */
    .glass-vibrant-bg {
        background: radial-gradient(circle at top right, rgba(29, 78, 216, 0.1), transparent),
            radial-gradient(circle at bottom left, rgba(88, 28, 135, 0.1), transparent);
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.02);
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .drop-zone {
        transition: all 0.3s ease;
        position: relative;
    }

    .drop-zone.active {
        border-color: #3b82f6 !important;
        background: rgba(59, 130, 246, 0.15) !important;
        transform: scale(1.01);
    }

    .participant-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 10px 15px;
        margin-bottom: 8px;
        border-radius: 12px;
        cursor: grab;
        display: flex;
        align-items: center;
        transition: all 0.2s;
    }

    .participant-card:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(59, 130, 246, 0.5);
        transform: translateY(-1px);
    }

    .participant-card:active {
        cursor: grabbing;
    }

    .participant-card.sortable-ghost {
        opacity: 0.4;
        background: #3b82f6;
    }

    .participant-card.is-external {
        border: 1px dashed rgba(13, 202, 240, 0.5);
        background: rgba(13, 202, 240, 0.05);
    }

    .participant-card.is-external .p-avatar {
        background: #0dcaf0;
    }

    .external-badge {
        font-size: 8px;
        background: #0dcaf0;
        color: #000;
        padding: 1px 4px;
        border-radius: 4px;
        text-transform: uppercase;
        font-weight: 900;
        margin-left: 5px;
    }

    .p-avatar {
        width: 32px;
        height: 32px;
        background: var(--bs-primary);
        color: white;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 12px;
        margin-right: 12px;
        flex-shrink: 0;
    }

    .p-info {
        line-height: 1.2;
        flex-grow: 1;
        overflow: hidden;
    }

    .p-name {
        display: block;
        font-weight: 600;
        font-size: 13px;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .p-meta {
        display: block;
        font-size: 11px;
        color: rgba(255, 255, 255, 0.5);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .btn-remove-p {
        color: rgba(255, 255, 255, 0.3);
        cursor: pointer;
        padding: 5px;
        transition: 0.2s;
    }

    .btn-remove-p:hover {
        color: #ef4444;
    }

    .min-h-60 {
        min-height: 60px;
    }

    .min-h-100 {
        min-height: 100px;
    }

    .min-h-120 {
        min-height: 120px;
    }

    .badge-order {
        background: rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.8);
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 10px;
        margin-right: 8px;
    }

    /* Input focus premium */
    .input-group-grow .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 15px rgba(59, 130, 246, 0.2);
    }
</style>

<script>
    let onConfirmCallback = null;
    let sortables = {};
    let searchTimeout = null;

    document.addEventListener('DOMContentLoaded', function () {
        initSortables();
        setupSearch();
    });

    function initSortables() {
        const zones = ['available-list', 'dz-destinatario', 'dz-firmante', 'dz-copia', 'dz-atencion'];
        const groupName = "participants";

        zones.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;

            sortables[id] = new Sortable(el, {
                group: groupName,
                animation: 150,
                ghostClass: 'sortable-ghost',
                handle: '.participant-card',
                onAdd: function (evt) {
                    updateEmptyPlaceholders();
                    updateCounts();
                    // Al mover a una zona de destino, asegurarse de agregar el botón de remover si no lo tiene
                    ensureActionButtons(evt.item, id !== 'available-list');
                },
                onRemove: function () {
                    updateEmptyPlaceholders();
                    updateCounts();
                },
                onUpdate: function () {
                    updateCounts();
                }
            });
        });
    }

    function ensureActionButtons(item, shouldHave) {
        let removeBtn = item.querySelector('.btn-remove-p');
        if (shouldHave && !removeBtn) {
            removeBtn = document.createElement('i');
            removeBtn.className = 'fas fa-times-circle btn-remove-p ms-2';
            removeBtn.onclick = function () {
                item.remove();
                updateEmptyPlaceholders();
                updateCounts();
            };
            item.appendChild(removeBtn);
        } else if (!shouldHave && removeBtn) {
            removeBtn.remove();
        }
    }

    function setupSearch() {
        const input = document.getElementById('search-participantes');
        input.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            if (query.length < 2) return;

            searchTimeout = setTimeout(() => {
                fetchUsers(query);
            }, 300);
        });
    }

    async function fetchUsers(q) {
        const list = document.getElementById('available-list');
        list.innerHTML = `<div class="p-4 text-center text-muted small"><i class="fas fa-spinner fa-spin me-2"></i>Buscando...</div>`;

        try {
            const resp = await fetch(`<?php echo url("/modulos/gestion-documental/ajax-participantes.php"); ?>?q=${encodeURIComponent(q)}`);
            const data = await resp.json();

            if (data.success && data.results && data.results.length > 0) {
                list.innerHTML = '';
                data.results.forEach(user => {
                    // Evitar duplicados si ya están en alguna zona de la derecha
                    if (isUserAlreadySelected(user.id)) return;
                    list.appendChild(createParticipantCard(user));
                });
            } else {
                list.innerHTML = `<div class="p-5 text-center text-muted opacity-50">No se encontraron resultados</div>`;
            }
        } catch (err) {
            list.innerHTML = `<div class="p-4 text-center text-danger small">Error en la búsqueda</div>`;
        }
    }

    function toggleExternalForm() {
        const searchMode = document.getElementById('search-mode-container');
        const extMode = document.getElementById('external-mode-container');
        const isSearchVisible = searchMode.style.display !== 'none';

        if (isSearchVisible) {
            searchMode.style.display = 'none';
            extMode.style.display = 'block';
            document.getElementById('ext-nombre').focus();
        } else {
            searchMode.style.display = 'block';
            extMode.style.display = 'none';
        }
    }

    function addExternalParticipant() {
        const nombre = document.getElementById('ext-nombre').value.trim();
        const area = document.getElementById('ext-area').value.trim();
        const puesto = document.getElementById('ext-puesto').value.trim();

        if (!nombre) {
            alert('El nombre es obligatorio');
            return;
        }

        const user = {
            id: 'EXT-' + Date.now(),
            text: nombre,
            area: area || 'Externo',
            puesto: puesto || 'Persona Física/Moral',
            isExternal: true
        };

        const card = createParticipantCard(user);
        const list = document.getElementById('available-list');

        // Limpiar mensaje de "Escribe para buscar" si existe
        if (list.querySelector('.p-5')) list.innerHTML = '';

        list.prepend(card);

        // Limpiar campos
        document.getElementById('ext-nombre').value = '';
        document.getElementById('ext-area').value = '';
        document.getElementById('ext-puesto').value = '';

        toggleExternalForm();
    }

    function createParticipantCard(user) {
        const initials = user.text.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        const div = document.createElement('div');
        div.className = 'participant-card' + (user.isExternal ? ' is-external' : '');
        div.dataset.id = user.id;
        div.dataset.name = user.text;
        div.dataset.puesto = user.puesto;
        div.dataset.area = user.area;
        div.dataset.isExternal = user.isExternal ? '1' : '0';

        div.innerHTML = `
            <div class="p-avatar">${initials}</div>
            <div class="p-info">
                <span class="p-name">
                    ${user.text}
                    ${user.isExternal ? '<span class="external-badge">MANUAL</span>' : ''}
                </span>
                <span class="p-meta">${user.area} ${user.puesto ? ' - ' + user.puesto : ''}</span>
            </div>
        `;

        div.ondblclick = () => {
            // Mover a la primera sección disponible (Destinatario si está vacío, si no Firmante)
            const target = document.getElementById('dz-destinatario').children.length <= 1 ? 'dz-destinatario' : 'dz-firmante';
            moveToZone(div, target);
        };

        return div;
    }

    function moveToZone(card, zoneId) {
        const zone = document.getElementById(zoneId);
        ensureActionButtons(card, zoneId !== 'available-list');
        zone.appendChild(card);
        updateEmptyPlaceholders();
        updateCounts();
    }

    function isUserAlreadySelected(userId) {
        return !!document.querySelector(`.flow-canvas div[data-id="${userId}"]`);
    }

    function updateEmptyPlaceholders() {
        const zones = ['dz-destinatario', 'dz-firmante', 'dz-copia', 'dz-atencion'];
        zones.forEach(id => {
            const dz = document.getElementById(id);
            const placeholder = dz.querySelector('.empty-placeholder');
            const cards = dz.querySelectorAll('.participant-card');

            if (cards.length > 0) {
                if (placeholder) placeholder.style.display = 'none';
            } else {
                if (placeholder) placeholder.style.display = 'block';
            }

            // Actualizar badges de orden en Firmantes
            if (id === 'dz-firmante') {
                cards.forEach((card, idx) => {
                    let badge = card.querySelector('.badge-order');
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'badge-order';
                        card.prepend(badge);
                    }
                    badge.textContent = idx + 1;
                });
            } else {
                cards.forEach(card => {
                    const badge = card.querySelector('.badge-order');
                    if (badge) badge.remove();
                });
            }
        });
    }

    function updateCounts() {
        const total = document.querySelectorAll('.flow-canvas .participant-card').length;
        document.getElementById('p-count-total').textContent = total;
    }

    function clearSelections() {
        if (!confirm('¿Quitar todos los participantes configurados?')) return;
        const zones = ['dz-destinatario', 'dz-firmante', 'dz-copia', 'dz-atencion'];
        zones.forEach(id => {
            const dz = document.getElementById(id);
            dz.querySelectorAll('.participant-card').forEach(c => c.remove());
        });
        updateEmptyPlaceholders();
        updateCounts();
    }

    function moveToDefaultSection() {
        const available = document.querySelectorAll('#available-list .participant-card');
        if (available.length === 0) return;
        // Solo mover el primero de la lista disponible por simplicidad del botón
        moveToZone(available[0], 'dz-destinatario');
    }

    // API COMPATIBILITY
    function setupParticipantes(defaults, callback) {
        // Limpiar
        clearZones();
        onConfirmCallback = callback;

        if (defaults && defaults.length > 0) {
            defaults.forEach(d => {
                const zoneId = getZoneByRole(d.rol_oficio);
                const card = createParticipantCard({
                    id: d.id,
                    text: d.name,
                    puesto: d.puesto || '',
                    area: d.area || ''
                });
                moveToZone(card, zoneId);
            });
        }

        updateEmptyPlaceholders();
        updateCounts();

        const modal = new bootstrap.Modal(document.getElementById('modalParticipantesFlujo'));
        modal.show();
    }

    function clearZones() {
        const zones = ['dz-destinatario', 'dz-firmante', 'dz-copia', 'dz-atencion', 'available-list'];
        zones.forEach(id => {
            const dz = document.getElementById(id);
            if (!dz) return;
            dz.querySelectorAll('.participant-card').forEach(c => c.remove());
        });
    }

    function getZoneByRole(role) {
        switch (role) {
            case 'DESTINATARIO': return 'dz-destinatario';
            case 'FIRMANTE': return 'dz-firmante';
            case 'COPIA': return 'dz-copia';
            case 'ATENCION': return 'dz-atencion';
            default: return 'dz-firmante';
        }
    }

    document.getElementById('btnConfirmarFlujo').addEventListener('click', function () {
        const data = [];
        const zones = [
            { id: 'dz-destinatario', role: 'DESTINATARIO' },
            { id: 'dz-firmante', role: 'FIRMANTE' },
            { id: 'dz-copia', role: 'COPIA' },
            { id: 'dz-atencion', role: 'ATENCION' }
        ];

        let firmanteOrder = 1;
        zones.forEach(zone => {
            const cards = document.getElementById(zone.id).querySelectorAll('.participant-card');
            cards.forEach(card => {
                data.push({
                    usuario_id: card.dataset.id.startsWith('EXT-') ? null : card.dataset.id,
                    rol: zone.role === 'FIRMANTE' ? 'FIRMA' : 'REVISA', // Rol técnico
                    rol_oficio: zone.role,
                    orden: zone.role === 'FIRMANTE' ? firmanteOrder++ : 0,
                    // Datos adicionales para manuales
                    nombre_manual: card.dataset.isExternal === '1' ? card.dataset.name : null,
                    area_manual: card.dataset.isExternal === '1' ? card.dataset.area : null,
                    puesto_manual: card.dataset.isExternal === '1' ? card.dataset.puesto : null
                });
            });
        });

        if (data.length === 0) {
            alert('Debe seleccionar al menos un participante para el flujo.');
            return;
        }

        // Validar que haya al menos un firmante
        if (!data.some(d => d.rol_oficio === 'FIRMANTE')) {
            alert('Debe haber al menos un FIRMANTE en el flujo.');
            return;
        }

        if (onConfirmCallback) {
            onConfirmCallback(data);
        }

        bootstrap.Modal.getInstance(document.getElementById('modalParticipantesFlujo')).hide();
    });
</script>