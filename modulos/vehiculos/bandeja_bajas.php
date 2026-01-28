<?php
/**
 * Módulo: Bandeja de Gestión de Bajas Vehiculares
 * Ubicación: /modulos/vehiculos/bandeja_bajas.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// Mismo ID de Módulo que Vehículos (general) o uno específico si se crea
define('MODULO_Vehiculos', 4); // Asumiendo ID 4 para Vehículos basado en conversaciones previas, o usar DB query dinamica si preferimos.

$pdo = getConnection();
$user = getCurrentUser();
$userId = getCurrentUserId();

// Determinar rol para la vista
$esAdmin = isAdmin();
// Aquí podríamos validar permiso específico 'autorizar_bajas' si existiera
$puedeAutorizar = $esAdmin; 

// --- Lógica de Filtros por Tab ---
$tab = $_GET['tab'] ?? ($puedeAutorizar ? 'por_autorizar' : 'mis_solicitudes');

// Consultas según Tab
$solicitudes = [];

if ($tab === 'mis_solicitudes') {
    // 1. Marcar como vistas las notificaciones (finalizado/rechazado)
    $pdo->prepare("UPDATE solicitudes_baja SET visto = 1 WHERE solicitante_id = ? AND estado IN ('finalizado', 'rechazado')")->execute([$userId]);

    $stmt = $pdo->prepare("
        SELECT sb.*, v.marca, v.modelo, v.numero_economico, v.numero_placas, a.nombre_area
        FROM solicitudes_baja sb
        JOIN vehiculos v ON sb.vehiculo_id = v.id
        LEFT JOIN areas a ON sb.area_solicitante_id = a.id
        WHERE sb.solicitante_id = ?
        ORDER BY sb.fecha_solicitud DESC
    ");
    $stmt->execute([$userId]);
    $solicitudes = $stmt->fetchAll();

} elseif ($tab === 'por_autorizar') {
    if (!$puedeAutorizar) {
        setFlashMessage('error', 'No tienes permiso para ver esta sección.');
        redirect('bandeja_bajas.php?tab=mis_solicitudes');
    }
    
    // Admin ve todo lo pendiente
    $stmt = $pdo->query("
        SELECT sb.*, v.marca, v.modelo, v.numero_economico, v.numero_placas, a.nombre_area, 
               emp.nombres as solicitante_nombre, emp.apellido_paterno as solicitante_apellido
        FROM solicitudes_baja sb
        JOIN vehiculos v ON sb.vehiculo_id = v.id
        LEFT JOIN areas a ON sb.area_solicitante_id = a.id
        LEFT JOIN usuarios_sistema us ON sb.solicitante_id = us.id
        LEFT JOIN empleados emp ON us.id_empleado = emp.id
        WHERE sb.estado = 'pendiente'
        ORDER BY sb.fecha_solicitud ASC
    ");
    $solicitudes = $stmt->fetchAll();
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-tasks text-primary me-2"></i>Gestión de Bajas Vehiculares</h1>
            <p class="text-muted">Administra las solicitudes de baja del padrón vehicular.</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver al Padrón
            </a>
        </div>
    </div>

    <!-- Tabs -->
    <div class="custom-tabs mb-4">
        <?php if (!$puedeAutorizar): ?>
        <a href="?tab=mis_solicitudes" class="tab-item <?= $tab === 'mis_solicitudes' ? 'active' : '' ?>">
            <i class="fas fa-user-clock me-2"></i>Mis Solicitudes
        </a>
        <?php endif; ?>
        <?php if ($puedeAutorizar): ?>
        <a href="?tab=por_autorizar" class="tab-item <?= $tab === 'por_autorizar' ? 'active' : '' ?>">
            <i class="fas fa-stamp me-2"></i>Por Autorizar
            <?php 
                // Contador rápido
                $count = $pdo->query("SELECT COUNT(*) FROM solicitudes_baja WHERE estado = 'pendiente'")->fetchColumn();
                if ($count > 0) echo "<span class='badge bg-danger ms-2'>$count</span>"; 
            ?>
        </a>
        <?php endif; ?>
    </div>

    <!-- Listado -->
    <div class="management-grid">
        <?php if (empty($solicitudes)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle fa-3x mb-3 text-muted"></i>
                <h3>¡Todo limpio!</h3>
                <p>No hay solicitudes en esta sección.</p>
            </div>
        <?php else: ?>
            <?php foreach ($solicitudes as $s): 
                $statusColor = 'secondary';
                $statusIcon = 'fa-clock';
                if ($s['estado'] === 'pendiente') { $statusColor = 'warning'; $statusIcon = 'fa-hourglass-half'; }
                if ($s['estado'] === 'autorizado') { $statusColor = 'success'; $statusIcon = 'fa-check-circle'; }
                if ($s['estado'] === 'rechazado') { $statusColor = 'danger'; $statusIcon = 'fa-times-circle'; }
                if ($s['estado'] === 'finalizado') { $statusColor = 'dark'; $statusIcon = 'fa-archive'; }
            ?>
                <div class="management-row">
                    <!-- Status Indicator -->
                    <div class="row-step step-status" style="border-left: 5px solid var(--bs-<?= $statusColor ?>);">
                        <div class="text-<?= $statusColor ?> text-center">
                            <i class="fas <?= $statusIcon ?> fa-2x mb-2"></i>
                            <div class="small fw-bold text-uppercase"><?= $s['estado'] ?></div>
                        </div>
                    </div>

                    <!-- Detalles del Vehículo -->
                    <div class="row-step step-details flex-grow-1">
                        <h4 class="mb-1">
                            <?= e($s['marca']) ?> <?= e($s['modelo']) ?> 
                            <span class="text-muted fs-6">(Eco: <?= e($s['numero_economico']) ?>)</span>
                        </h4>
                        <div class="text-muted small mb-2">
                            <i class="fas fa-grip-lines me-1"></i> Placas: <?= e($s['numero_placas']) ?>
                        </div>
                        
                        <div class="alert alert-light border mb-0 p-2 small">
                            <strong>Motivo:</strong> <?= e($s['motivo']) ?>
                            <?php if($tab === 'por_autorizar'): ?>
                                <div class="mt-1 text-primary">
                                    <i class="fas fa-user-circle me-1"></i> 
                                    Solicitado por: <?= e($s['solicitante_nombre'] ?? '') ?> <?= e($s['solicitante_apellido'] ?? '') ?>
                                    (<?= e($s['nombre_area'] ?? 'Área desconocida') ?>)
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($s['comentarios_respuesta']): ?>
                            <div class="mt-2 text-<?= $s['estado'] === 'rechazado' ? 'danger' : 'success' ?> small">
                                <i class="fas fa-comment-dots me-1"></i>
                                <strong>Respuesta:</strong> <?= e($s['comentarios_respuesta']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Acciones -->
                    <div class="row-step step-actions d-flex flex-column justify-content-center gap-2 p-3 bg-light" style="min-width: 180px;">
                        
                        <!-- Acciones para Autorizador -->
                        <?php if ($tab === 'por_autorizar' && $s['estado'] === 'pendiente'): ?>
                            <button class="btn btn-sm btn-success w-100" onclick="responder(<?= $s['id'] ?>, 'aprobada')">
                                <i class="fas fa-check me-1"></i> Aprobar
                            </button>
                            <button class="btn btn-sm btn-danger w-100" onclick="responder(<?= $s['id'] ?>, 'rechazada')">
                                <i class="fas fa-times me-1"></i> Rechazar
                            </button>
                        <?php endif; ?>

                        <!-- Acciones para Solicitante (Finalizar) -->
                        <?php if ($tab === 'mis_solicitudes' && $s['estado'] === 'finalizado'): ?>
                            <div class="small text-muted text-center mt-1">
                                <i class="fas fa-check-double"></i> Proceso Completado
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Modales para acciones -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function responder(id, decision) {
        Swal.fire({
            title: decision === 'aprobada' ? '¿Aprobar solicitud?' : '¿Rechazar solicitud?',
            input: 'textarea',
            inputLabel: 'Comentarios',
            inputPlaceholder: 'Escribe un comentario o motivo...',
            inputAttributes: {
                'aria-label': 'Comentarios'
            },
            showCancelButton: true,
            confirmButtonText: decision === 'aprobada' ? 'Sí, Aprobar' : 'Sí, Rechazar',
            confirmButtonColor: decision === 'aprobada' ? '#198754' : '#dc3545',
            inputValidator: (value) => {
                if (decision === 'rechazada' && !value) {
                    return 'Debes escribir el motivo del rechazo';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const comentarios = result.value;
                
                const formData = new FormData();
                formData.append('action', 'responder_solicitud');
                formData.append('solicitud_id', id);
                formData.append('decision', decision);
                formData.append('comentarios', comentarios);

                fetch('acciones_baja.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('Listo', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }

    function finalizarBaja(id) {
        Swal.fire({
            title: '¿Confirmar Baja Definitiva?',
            text: "El vehículo pasará al histórico y desaparecerá del padrón activo.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#212529',
            confirmButtonText: 'Sí, Finalizar Baja'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'finalizar_baja');
                formData.append('solicitud_id', id);

                fetch('acciones_baja.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                     if(data.success) {
                        Swal.fire('Finalizado', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }
</script>

<style>
    /* Estilos reutilizados y adaptados para Bandeja */
    .custom-tabs {
        display: flex;
        background: #f8f9fa;
        padding: 5px;
        border-radius: 8px;
        gap: 5px;
        border: 1px solid #dee2e6;
        width: fit-content;
    }
    .tab-item {
        padding: 10px 20px;
        border-radius: 6px;
        text-decoration: none;
        color: #6c757d;
        font-weight: 600;
        transition: all 0.2s;
        display: flex;
        align-items: center;
    }
    .tab-item:hover { background: #e9ecef; color: #495057; }
    .tab-item.active { background: #fff; color: #0d6efd; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

    .management-grid { display: flex; flex-direction: column; gap: 15px; }
    .management-row {
        display: flex;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        overflow: hidden;
        transition: transform 0.2s;
    }
    .management-row:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    
    .row-step { padding: 15px; }
    .step-status { display: flex; align-items: center; justify-content: center; width: 100px; background: #f8f9fa; }
    
    .empty-state { text-align: center; padding: 40px; color: #adb5bd; border: 2px dashed #dee2e6; border-radius: 10px; }
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
