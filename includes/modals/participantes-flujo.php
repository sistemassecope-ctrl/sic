<?php
/**
 * Componente: Modal de Selección de Participantes para Flujo de Firmas
 * Ubicación: includes/modals/participantes-flujo.php
 */
?>
<div class="modal fade" id="modalParticipantesFlujo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass-vibrant-bg border-primary shadow-lg"
            style="background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(20px);">
            <div class="modal-header border-bottom border-primary">
                <h5 class="modal-title fw-bold text-white">
                    <i class="fas fa-users-cog me-2 text-primary"></i>Configurar Flujo de Firmas
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body text-white">
                <p class="text-muted small mb-4">Define quiénes participarán en la cadena de firmas y en qué orden.</p>

                <div id="participantes-container" class="mb-4">
                    <!-- Filas dinámicas aquí -->
                </div>

                <div class="text-center">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addParticipante()">
                        <i class="fas fa-plus me-1"></i> Añadir Firmante
                    </button>
                </div>
            </div>
            <div class="modal-footer border-top border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCELAR</button>
                <button type="button" class="btn btn-primary fw-bold" id="btnConfirmarFlujo">
                    <i class="fas fa-paper-plane me-2"></i>INICIAR FLUJO
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .participante-row {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.2s;
    }

    .participante-row:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: var(--accent-primary);
    }

    .order-badge {
        width: 30px;
        height: 30px;
        background: var(--accent-primary);
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        flex-shrink: 0;
    }

    .participante-select {
        flex: 1;
    }

    .participante-rol {
        width: 150px;
    }

    .btn-remove-p {
        color: #ef4444;
        cursor: pointer;
        opacity: 0.6;
        transition: 0.2s;
    }

    .btn-remove-p:hover {
        opacity: 1;
        transform: scale(1.2);
    }
</style>

<script>
    let pCount = 0;

    function addParticipante(userId = '', userName = '', role = '', isFixed = false) {
        pCount++;
        const container = document.getElementById('participantes-container');
        const row = document.createElement('div');
        row.className = 'participante-row';
        row.id = `p-row-${pCount}`;

        row.innerHTML = `
        <div class="order-badge">${pCount}</div>
        <div class="participante-select">
            <select class="form-control select2-participante" name="p_user[]" data-placeholder="Buscar funcionario...">
                ${userId ? `<option value="${userId}" selected>${userName}</option>` : ''}
            </select>
        </div>
        <div class="participante-rol">
            <input type="text" class="form-control form-control-sm text-uppercase" name="p_role[]" placeholder="ROL (Ej: REVISA)" value="${role}">
        </div>
        ${!isFixed ? `<i class="fas fa-times-circle btn-remove-p" onclick="removeParticipante(${pCount})"></i>` : '<div style="width:18px"></div>'}
    `;

        container.appendChild(row);

        // Inicializar Select2 para esta fila
        $(row).find('.select2-participante').select2({
            dropdownParent: $('#modalParticipantesFlujo'),
            width: '100%',
            ajax: {
                url: '<?php echo url("/modulos/gestion-documental/ajax-participantes.php"); ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return { results: data.results };
                },
                cache: true
            },
            minimumInputLength: 2,
            templateResult: formatUserOption,
            templateSelection: formatUserSelection
        });
    }

    function formatUserOption(user) {
        if (user.loading) return user.text;
        return $(`
        <div class="d-flex flex-column">
            <span class="fw-bold">${user.text}</span>
            <span class="x-small text-muted">${user.area || ''} - ${user.puesto || ''}</span>
        </div>
    `);
    }

    function formatUserSelection(user) {
        return user.text || user.element.text;
    }

    function removeParticipante(id) {
        const row = document.getElementById(`p-row-${id}`);
        if (row) {
            row.remove();
            reorderBadges();
        }
    }

    function reorderBadges() {
        const badges = document.querySelectorAll('.order-badge');
        badges.forEach((b, i) => {
            b.textContent = i + 1;
        });
        pCount = badges.length;
    }

    // Lógica de confirmación
    let onConfirmCallback = null;

    function setupParticipantes(defaults, callback) {
        document.getElementById('participantes-container').innerHTML = '';
        pCount = 0;

        if (defaults && defaults.length > 0) {
            defaults.forEach((d, i) => {
                addParticipante(d.id, d.name, d.role, d.fixed);
            });
        } else {
            // Al menos una fila vacía
            addParticipante();
        }

        onConfirmCallback = callback;
        const modal = new bootstrap.Modal(document.getElementById('modalParticipantesFlujo'));
        modal.show();
    }

    document.getElementById('btnConfirmarFlujo').addEventListener('click', function () {
        const data = [];
        const userSelects = document.querySelectorAll('select[name="p_user[]"]');
        const roleInputs = document.querySelectorAll('input[name="p_role[]"]');

        let valid = true;
        userSelects.forEach((select, i) => {
            if (!select.value) {
                valid = false;
                select.classList.add('is-invalid');
            } else {
                data.push({
                    usuario_id: select.value,
                    rol: roleInputs[i].value || 'PARTICIPANTE',
                    orden: i + 1
                });
            }
        });

        if (!valid) {
            alert('Por favor selecciona a todos los participantes.');
            return;
        }

        if (onConfirmCallback) {
            onConfirmCallback(data);
        }

        bootstrap.Modal.getInstance(document.getElementById('modalParticipantesFlujo')).hide();
    });
</script>