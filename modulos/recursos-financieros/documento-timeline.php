<?php
/**
 * Componente: Timeline del Documento
 * Ubicación: modulos/recursos-financieros/documento-timeline.php
 * Descripción: Visualiza el historial (bitácora) y flujo de firmas de un documento.
 */

namespace SIC\Components;

use PDO;

class DocumentoTimeline
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function render(int $documentoId)
    {
        // 1. Obtener detalles del documento
        $stmtDoc = $this->pdo->prepare("
            SELECT d.*, t.nombre as tipo_nombre
            FROM documentos d
            JOIN cat_tipos_documento t ON d.tipo_documento_id = t.id
            WHERE d.id = ?
        ");
        $stmtDoc->execute([$documentoId]);
        $doc = $stmtDoc->fetch();

        if (!$doc) {
            echo "<div class='alert alert-warning'>Documento no encontrado.</div>";
            return;
        }

        // 2. Obtener flujo de firmas
        $stmtFlujo = $this->pdo->prepare("
            SELECT df.*, u.usuario as username, u.id_empleado, e.nombres as nombre_empleado, e.apellido_paterno
            FROM documento_flujo_firmas df
            JOIN usuarios_sistema u ON df.firmante_id = u.id
            LEFT JOIN empleados e ON u.id_empleado = e.id
            WHERE df.documento_id = ?
            ORDER BY df.orden ASC
        ");
        $stmtFlujo->execute([$documentoId]);
        $firmas = $stmtFlujo->fetchAll();

        // 3. Obtener bitácora
        $stmtBitacora = $this->pdo->prepare("
            SELECT b.*, u.usuario as username
            FROM documento_bitacora b
            JOIN usuarios_sistema u ON b.usuario_id = u.id
            WHERE b.documento_id = ?
            ORDER BY b.timestamp_evento DESC
        ");
        $stmtBitacora->execute([$documentoId]);
        $bitacora = $stmtBitacora->fetchAll();

        ?>
        <div class="documento-timeline-container">
            <div class="timeline-header mb-4">
                <h5 class="fw-bold"><i class="fas fa-history me-2 text-primary"></i>Historial de Vida:
                    <?= e($doc['folio_sistema']) ?>
                </h5>
                <p class="text-muted small">
                    <?= e($doc['tipo_nombre']) ?> -
                    <?= e($doc['titulo']) ?>
                </p>
            </div>

            <!-- Flujo de Firmas (Horizontal/Cards) -->
            <div class="signature-track mb-5">
                <h6 class="text-uppercase x-small text-muted fw-bold mb-3">CADENA DE FIRMAS</h6>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($firmas as $f):
                        $statusClass = $f['estatus'] === 'firmado' ? 'signed' : ($f['estatus'] === 'rechazado' ? 'rejected' : 'pending');
                        $icon = $f['estatus'] === 'firmado' ? 'fa-check-circle' : ($f['estatus'] === 'rechazado' ? 'fa-times-circle' : 'fa-clock');
                        ?>
                        <div class="signature-card <?= $statusClass ?>">
                            <div class="sig-header">
                                <span class="sig-order">#
                                    <?= $f['orden'] ?>
                                </span>
                                <i class="fas <?= $icon ?>"></i>
                            </div>
                            <div class="sig-body">
                                <span class="sig-name">
                                    <?= e($f['nombre_empleado'] . " " . $f['apellido_paterno']) ?>
                                </span>
                                <span class="sig-role">
                                    <?= e($f['rol_firmante']) ?>
                                </span>
                            </div>
                            <?php if ($f['fecha_firma']): ?>
                                <div class="sig-footer">
                                    <span>
                                        <?= date('d/m/Y H:i', strtotime($f['fecha_firma'])) ?>
                                    </span>
                                    <span class="badge bg-<?= $f['tipo_firma'] === 'fiel' ? 'success' : 'info' ?> x-small">
                                        <?= strtoupper($f['tipo_firma']) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Bitácora (Vertical Timeline) -->
            <div class="event-log">
                <h6 class="text-uppercase x-small text-muted fw-bold mb-3">BITÁCORA DE EVENTOS</h6>
                <div class="vertical-timeline">
                    <?php foreach ($bitacora as $b):
                        $actionIcon = 'fa-dot-circle';
                        $actionColor = 'var(--text-muted)';

                        switch ($b['accion']) {
                            case 'CREAR':
                                $actionIcon = 'fa-plus-circle';
                                $actionColor = '#58a6ff';
                                break;
                            case 'FIRMAR':
                                $actionIcon = 'fa-signature';
                                $actionColor = '#2ea043';
                                break;
                            case 'APROBAR':
                                $actionIcon = 'fa-check-double';
                                $actionColor = '#2ea043';
                                break;
                            case 'RECHAZAR':
                                $actionIcon = 'fa-exclamation-triangle';
                                $actionColor = '#ef4444';
                                break;
                            case 'ACTUALIZAR':
                                $actionIcon = 'fa-pen';
                                $actionColor = '#d29922';
                                break;
                        }
                        ?>
                        <div class="timeline-event">
                            <div class="event-icon" style="color: <?= $actionColor ?>; border-color: <?= $actionColor ?>;">
                                <i class="fas <?= $actionIcon ?>"></i>
                            </div>
                            <div class="event-content">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="event-action fw-bold" style="color: <?= $actionColor ?>;">
                                        <?= $b['accion'] ?>
                                    </span>
                                    <span class="event-time mt-0">
                                        <?= date('d/m/Y H:i:s', strtotime($b['timestamp_evento'])) ?>
                                    </span>
                                </div>
                                <p class="event-desc mb-0">
                                    <?= e($b['descripcion']) ?>
                                </p>
                                <?php if ($b['observaciones']): ?>
                                    <div class="event-notes mt-1 italic small text-muted">"
                                        <?= e($b['observaciones']) ?>"
                                    </div>
                                <?php endif; ?>
                                <div class="event-user x-small mt-1">
                                    <i class="fas fa-user-circle me-1"></i>
                                    <?= e($b['username']) ?>
                                    <?php if ($b['firma_tipo'] !== 'ninguna'): ?>
                                        <span class="ms-2 badge bg-dark-subtle text-muted">SELLADO:
                                            <?= strtoupper($b['firma_tipo']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <style>
            .documento-timeline-container {
                background: rgba(255, 255, 255, 0.01);
                border-radius: 12px;
            }

            /* Signature Track */
            .signature-card {
                background: var(--bg-tertiary);
                border: 1px solid var(--border-primary);
                border-radius: 10px;
                padding: 12px;
                min-width: 180px;
                flex: 1;
                display: flex;
                flex-direction: column;
                position: relative;
                transition: all 0.3s;
            }

            .signature-card.signed {
                border-left: 4px solid #2ea043;
            }

            .signature-card.pending {
                border-left: 4px solid #f59e0b;
            }

            .signature-card.rejected {
                border-left: 4px solid #ef4444;
            }

            .sig-header {
                display: flex;
                justify-content: space-between;
                font-size: 0.8rem;
                margin-bottom: 8px;
            }

            .sig-order {
                font-weight: 800;
                color: var(--text-muted);
            }

            .sig-name {
                display: block;
                font-weight: 700;
                font-size: 0.85rem;
                color: var(--text-primary);
            }

            .sig-role {
                display: block;
                font-size: 0.7rem;
                color: var(--text-muted);
                text-transform: uppercase;
            }

            .sig-footer {
                margin-top: 10px;
                padding-top: 5px;
                border-top: 1px dashed var(--border-primary);
                font-size: 0.65rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
                color: var(--text-muted);
            }

            /* Vertical Timeline */
            .vertical-timeline {
                position: relative;
                padding-left: 30px;
            }

            .vertical-timeline::before {
                content: '';
                position: absolute;
                left: 14px;
                top: 5px;
                bottom: 5px;
                width: 2px;
                background: var(--border-primary);
            }

            .timeline-event {
                position: relative;
                margin-bottom: 1.5rem;
            }

            .event-icon {
                position: absolute;
                left: -30px;
                top: 0;
                width: 30px;
                height: 30px;
                background: var(--bg-card);
                border: 2px solid;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.8rem;
                z-index: 1;
            }

            .event-content {
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid var(--border-primary);
                padding: 12px;
                border-radius: 8px;
            }

            .event-time {
                font-size: 0.7rem;
                color: var(--text-muted);
            }

            .event-action {
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            .event-desc {
                font-size: 0.85rem;
                margin-top: 4px;
            }

            .italic {
                font-style: italic;
            }

            .x-small {
                font-size: 0.65rem;
            }
        </style>
        <?php
    }
}
