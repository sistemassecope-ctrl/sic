<?php
/**
 * Módulo de Captura de Firma Autógrafa Digital
 * SOLO ACCESIBLE POR SUPERADMIN
 * 
 * Permite capturar la firma del empleado y establecer su PIN de 4 dígitos
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAuth();

// Verificar que sea SUPERADMIN
$user = getCurrentUser();
if (!isAdmin()) {
    header('Location: ' . url('/index.php'));
    exit;
}

$pdo = getConnection();
$errors = [];
$success = '';

// Obtener ID del empleado (puede venir por GET para seleccionar o POST para guardar)
$empleadoId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$empleado = null;
$firmaExistente = null;

// Cargar empleado si se especificó
if ($empleadoId) {
    $stmt = $pdo->prepare("
        SELECT e.*, 
               a.nombre_area,
               p.nombre as nombre_puesto
        FROM empleados e
        LEFT JOIN areas a ON e.area_id = a.id
        LEFT JOIN puestos_trabajo p ON e.puesto_trabajo_id = p.id
        WHERE e.id = ?
    ");
    $stmt->execute([$empleadoId]);
    $empleado = $stmt->fetch();
    
    if (!$empleado) {
        $errors[] = 'Empleado no encontrado';
        $empleadoId = null;
    } else {
        // Verificar si ya tiene firma
        $stmtFirma = $pdo->prepare("SELECT * FROM empleado_firmas WHERE empleado_id = ? AND estado = 1");
        $stmtFirma->execute([$empleadoId]);
        $firmaExistente = $stmtFirma->fetch();
    }
}

// Cargar lista de empleados para el selector
$stmtEmpleados = $pdo->prepare("
    SELECT e.id, e.nombres, e.apellido_paterno, e.apellido_materno, e.numero_empleado,
           a.nombre_area,
           (SELECT COUNT(*) FROM empleado_firmas ef WHERE ef.empleado_id = e.id AND ef.estado = 1) as tiene_firma
    FROM empleados e
    LEFT JOIN areas a ON e.area_id = a.id
    WHERE e.estado = 'A'
    ORDER BY e.apellido_paterno, e.apellido_materno, e.nombres
");
$stmtEmpleados->execute();
$empleados = $stmtEmpleados->fetchAll();

?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<style>
    :root {
        --signature-bg: #ffffff;
        --signature-border: rgba(88, 166, 255, 0.3);
    }
    
    .firma-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .page-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }
    
    .page-subtitle {
        color: var(--text-secondary);
        margin: 0;
        font-size: 0.95rem;
    }
    
    .selector-card {
        background: var(--bg-card);
        border: 1px solid var(--border-primary);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .empleado-select {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 1px solid var(--border-primary);
        border-radius: 12px;
        background: var(--bg-secondary);
        color: var(--text-primary);
        font-size: 1rem;
        appearance: none;
        cursor: pointer;
    }
    
    .empleado-select:focus {
        outline: none;
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.15);
    }
    
    .empleado-info-card {
        background: linear-gradient(135deg, rgba(88, 166, 255, 0.08) 0%, var(--bg-card) 100%);
        border: 1px solid var(--border-primary);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .empleado-header {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        margin-bottom: 1rem;
    }
    
    .empleado-avatar {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent-primary), #6e42ca);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        font-weight: 700;
        color: white;
        text-transform: uppercase;
    }
    
    .empleado-data h3 {
        margin: 0;
        font-size: 1.35rem;
        color: var(--text-primary);
    }
    
    .empleado-data p {
        margin: 0.25rem 0 0;
        color: var(--text-secondary);
        font-size: 0.95rem;
    }
    
    .firma-status {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .firma-status.has-firma {
        background: rgba(46, 160, 67, 0.15);
        color: #2ea043;
    }
    
    .firma-status.no-firma {
        background: rgba(255, 170, 0, 0.15);
        color: #ffaa00;
    }
    
    .signature-section {
        background: var(--bg-card);
        border: 1px solid var(--border-primary);
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .section-title {
        font-size: 1.15rem;
        color: var(--text-primary);
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .signature-canvas-container {
        position: relative;
        background: var(--signature-bg);
        border: 2px dashed var(--signature-border);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 1rem;
    }
    
    #signatureCanvas {
        display: block;
        width: 100%;
        height: 250px;
        cursor: crosshair;
        touch-action: none;
    }
    
    .signature-placeholder {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #aaa;
        font-size: 1.1rem;
        pointer-events: none;
        text-align: center;
    }
    
    .signature-placeholder i {
        font-size: 2.5rem;
        display: block;
        margin-bottom: 0.5rem;
        opacity: 0.5;
    }
    
    .signature-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .btn-action {
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
    }
    
    .btn-clear {
        background: rgba(248, 81, 73, 0.1);
        color: #f85149;
    }
    
    .btn-clear:hover {
        background: rgba(248, 81, 73, 0.2);
    }
    
    .btn-undo {
        background: rgba(255, 170, 0, 0.1);
        color: #ffaa00;
    }
    
    .btn-undo:hover {
        background: rgba(255, 170, 0, 0.2);
    }
    
    .pin-section {
        background: var(--bg-card);
        border: 1px solid var(--border-primary);
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .pin-input-group {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
        margin: 1.5rem 0;
    }
    
    .pin-digit {
        width: 60px;
        height: 70px;
        text-align: center;
        font-size: 1.75rem;
        font-weight: 700;
        border: 2px solid var(--border-primary);
        border-radius: 12px;
        background: var(--bg-secondary);
        color: var(--text-primary);
        transition: all 0.2s ease;
    }
    
    .pin-digit:focus {
        outline: none;
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.2);
    }
    
    .pin-digit.filled {
        border-color: #2ea043;
        background: rgba(46, 160, 67, 0.1);
    }
    
    .pin-label {
        text-align: center;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
    }
    
    .confirm-section {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-top: 2rem;
    }
    
    .btn-primary-custom {
        background: linear-gradient(135deg, var(--accent-primary), #6e42ca);
        color: white;
        padding: 1rem 2.5rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.1rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(88, 166, 255, 0.3);
    }
    
    .btn-primary-custom:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    
    .btn-secondary-custom {
        background: var(--bg-secondary);
        color: var(--text-primary);
        padding: 1rem 2rem;
        border-radius: 12px;
        font-weight: 600;
        border: 1px solid var(--border-primary);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .btn-secondary-custom:hover {
        background: var(--bg-tertiary);
    }
    
    .existing-firma-preview {
        text-align: center;
        padding: 1.5rem;
        background: rgba(46, 160, 67, 0.05);
        border: 1px solid rgba(46, 160, 67, 0.2);
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }
    
    .existing-firma-preview img {
        max-width: 300px;
        max-height: 150px;
        border: 1px solid var(--border-primary);
        border-radius: 8px;
        background: white;
        padding: 0.5rem;
    }
    
    .existing-firma-preview p {
        margin: 0.75rem 0 0;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    
    .warning-box {
        background: rgba(255, 170, 0, 0.1);
        border: 1px solid rgba(255, 170, 0, 0.3);
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .warning-box i {
        color: #ffaa00;
        font-size: 1.25rem;
        margin-top: 2px;
    }
    
    .warning-box p {
        margin: 0;
        color: var(--text-secondary);
        line-height: 1.5;
    }
    
    .success-box {
        background: rgba(46, 160, 67, 0.1);
        border: 1px solid rgba(46, 160, 67, 0.3);
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .success-box i {
        color: #2ea043;
        font-size: 1.25rem;
    }
    
    .success-box p {
        margin: 0;
        color: var(--text-primary);
    }
    
    .error-box {
        background: rgba(248, 81, 73, 0.1);
        border: 1px solid rgba(248, 81, 73, 0.3);
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
    }
    
    .error-box p {
        margin: 0;
        color: #f85149;
    }
    
    @media (max-width: 768px) {
        .pin-digit {
            width: 50px;
            height: 60px;
            font-size: 1.5rem;
        }
        
        .confirm-section {
            flex-direction: column;
        }
        
        .btn-primary-custom,
        .btn-secondary-custom {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<main class="main-content">
    <div class="firma-container">
        <div class="page-header">
            <a href="<?= url('/modulos/recursos-humanos/empleados.php') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title">
                    <i class="fas fa-signature" style="color: var(--accent-primary);"></i>
                    Captura de Firma Autógrafa
                </h1>
                <p class="page-subtitle">Módulo exclusivo para Superadmin - Registro de firma digital y PIN</p>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <?php foreach ($errors as $error): ?>
                    <p><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-box">
                <i class="fas fa-check-circle"></i>
                <p><?= e($success) ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Selector de Empleado -->
        <div class="selector-card">
            <label style="color: var(--text-secondary); margin-bottom: 0.5rem; display: block; font-weight: 500;">
                <i class="fas fa-user-tie"></i> Seleccionar Empleado
            </label>
            <select id="empleadoSelector" class="empleado-select" onchange="selectEmployee(this.value)">
                <option value="">-- Seleccione un empleado --</option>
                <?php foreach ($empleados as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= $empleadoId == $emp['id'] ? 'selected' : '' ?>>
                        <?= e($emp['apellido_paterno'] . ' ' . $emp['apellido_materno'] . ', ' . $emp['nombres']) ?>
                        (<?= e($emp['numero_empleado'] ?? 'S/N') ?>)
                        <?= $emp['tiene_firma'] ? '✓ Firma registrada' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if ($empleado): ?>
            <!-- Información del Empleado -->
            <div class="empleado-info-card">
                <div class="empleado-header">
                    <div class="empleado-avatar">
                        <?= strtoupper(substr($empleado['nombres'] ?? 'E', 0, 1)) ?>
                    </div>
                    <div class="empleado-data">
                        <h3><?= e($empleado['nombres'] . ' ' . $empleado['apellido_paterno'] . ' ' . $empleado['apellido_materno']) ?></h3>
                        <p>
                            <i class="fas fa-id-badge"></i> <?= e($empleado['numero_empleado'] ?? 'Sin número') ?> &nbsp;|&nbsp;
                            <i class="fas fa-building"></i> <?= e($empleado['nombre_area'] ?? 'Sin área') ?>
                        </p>
                        <p>
                            <i class="fas fa-briefcase"></i> <?= e($empleado['nombre_puesto'] ?? 'Sin puesto') ?>
                        </p>
                    </div>
                    <div style="margin-left: auto;">
                        <?php if ($firmaExistente): ?>
                            <span class="firma-status has-firma">
                                <i class="fas fa-check-circle"></i> Firma Registrada
                            </span>
                        <?php else: ?>
                            <span class="firma-status no-firma">
                                <i class="fas fa-exclamation-triangle"></i> Sin Firma
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($firmaExistente): ?>
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <p><strong>Este empleado ya tiene una firma registrada.</strong></p>
                        <p>Si continúa, se reemplazará la firma y PIN existentes. Esta acción quedará registrada en el sistema.</p>
                        <p style="font-size: 0.85rem; margin-top: 0.5rem;">
                            <i class="fas fa-calendar"></i> Firma capturada: <?= date('d/m/Y H:i', strtotime($firmaExistente['fecha_captura'])) ?>
                        </p>
                    </div>
                </div>
                
                <div class="existing-firma-preview">
                    <p style="margin-bottom: 0.75rem; color: var(--text-primary); font-weight: 600;">
                        <i class="fas fa-signature"></i> Firma Actual
                    </p>
                    <img src="<?= e($firmaExistente['firma_imagen']) ?>" alt="Firma actual">
                    <p>
                        <i class="fas fa-info-circle"></i> Esta firma se reemplazará al guardar una nueva
                    </p>
                </div>
            <?php endif; ?>
            
            <form id="firmaForm" onsubmit="return submitFirma(event)">
                <input type="hidden" id="empleadoId" value="<?= $empleadoId ?>">
                
                <!-- Sección de Firma -->
                <div class="signature-section">
                    <h4 class="section-title">
                        <i class="fas fa-pen-fancy"></i>
                        Área de Firma
                    </h4>
                    <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                        Solicite al empleado que dibuje su firma en el área blanca usando la tableta electrónica o el mouse.
                    </p>
                    
                    <div class="signature-canvas-container">
                        <canvas id="signatureCanvas"></canvas>
                        <div class="signature-placeholder" id="signaturePlaceholder">
                            <i class="fas fa-signature"></i>
                            Firme aquí
                        </div>
                    </div>
                    
                    <div class="signature-actions">
                        <button type="button" class="btn-action btn-clear" onclick="clearSignature()">
                            <i class="fas fa-eraser"></i> Limpiar Todo
                        </button>
                        <button type="button" class="btn-action btn-undo" onclick="undoSignature()">
                            <i class="fas fa-undo"></i> Deshacer
                        </button>
                    </div>
                </div>
                
                <!-- Sección de PIN -->
                <div class="pin-section">
                    <h4 class="section-title">
                        <i class="fas fa-lock"></i>
                        Establecer PIN de Seguridad
                    </h4>
                    <p style="color: var(--text-secondary); margin-bottom: 0.5rem; text-align: center;">
                        El empleado debe establecer un PIN de 4 dígitos para poder firmar documentos.
                    </p>
                    
                    <div style="max-width: 400px; margin: 0 auto;">
                        <p class="pin-label">Ingrese el PIN (4 dígitos)</p>
                        <div class="pin-input-group">
                            <input type="password" class="pin-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="0" autocomplete="off">
                            <input type="password" class="pin-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="1" autocomplete="off">
                            <input type="password" class="pin-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="2" autocomplete="off">
                            <input type="password" class="pin-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="3" autocomplete="off">
                        </div>
                        
                        <p class="pin-label" style="margin-top: 1.5rem;">Confirme el PIN</p>
                        <div class="pin-input-group" id="pinConfirmGroup">
                            <input type="password" class="pin-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="4" autocomplete="off">
                            <input type="password" class="pin-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="5" autocomplete="off">
                            <input type="password" class="pin-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="6" autocomplete="off">
                            <input type="password" class="pin-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="7" autocomplete="off">
                        </div>
                        
                        <div id="pinMatchStatus" style="text-align: center; margin-top: 0.75rem; font-size: 0.9rem;"></div>
                    </div>
                </div>
                
                <!-- Botones de Acción -->
                <div class="confirm-section">
                    <button type="button" class="btn-secondary-custom" onclick="window.location.href='<?= url('/modulos/recursos-humanos/empleados.php') ?>'">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-primary-custom" id="btnGuardar">
                        <i class="fas fa-save"></i> Guardar Firma y PIN
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<script>
// Variables globales para el canvas de firma
let canvas, ctx;
let isDrawing = false;
let lastX = 0;
let lastY = 0;
let strokeHistory = [];
let currentStroke = [];

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    initSignatureCanvas();
    initPinInputs();
});

function selectEmployee(id) {
    if (id) {
        window.location.href = '<?= url('/modulos/recursos-humanos/firma-digital.php') ?>?id=' + id;
    }
}

// ==================== CANVAS DE FIRMA ====================
function initSignatureCanvas() {
    canvas = document.getElementById('signatureCanvas');
    if (!canvas) return;
    
    ctx = canvas.getContext('2d');
    
    // Ajustar tamaño real del canvas
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
    
    // Eventos de mouse
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);
    
    // Eventos táctiles (para tableta)
    canvas.addEventListener('touchstart', handleTouchStart, { passive: false });
    canvas.addEventListener('touchmove', handleTouchMove, { passive: false });
    canvas.addEventListener('touchend', stopDrawing);
}

function resizeCanvas() {
    if (!canvas) return;
    
    const container = canvas.parentElement;
    const rect = container.getBoundingClientRect();
    
    // Preservar el contenido anterior
    const imageData = ctx ? ctx.getImageData(0, 0, canvas.width, canvas.height) : null;
    
    canvas.width = rect.width;
    canvas.height = 250;
    
    // Restaurar contenido
    if (imageData) {
        ctx.putImageData(imageData, 0, 0);
    }
    
    // Configurar estilo de trazo
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
}

function getPosition(e) {
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    
    if (e.touches) {
        return {
            x: (e.touches[0].clientX - rect.left) * scaleX,
            y: (e.touches[0].clientY - rect.top) * scaleY
        };
    }
    return {
        x: (e.clientX - rect.left) * scaleX,
        y: (e.clientY - rect.top) * scaleY
    };
}

function startDrawing(e) {
    isDrawing = true;
    const pos = getPosition(e);
    lastX = pos.x;
    lastY = pos.y;
    currentStroke = [{x: lastX, y: lastY}];
    
    // Ocultar placeholder
    document.getElementById('signaturePlaceholder').style.display = 'none';
}

function draw(e) {
    if (!isDrawing) return;
    e.preventDefault();
    
    const pos = getPosition(e);
    
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    
    currentStroke.push({x: pos.x, y: pos.y});
    
    lastX = pos.x;
    lastY = pos.y;
}

function stopDrawing() {
    if (isDrawing && currentStroke.length > 0) {
        strokeHistory.push([...currentStroke]);
    }
    isDrawing = false;
    currentStroke = [];
}

function handleTouchStart(e) {
    e.preventDefault();
    startDrawing(e);
}

function handleTouchMove(e) {
    e.preventDefault();
    draw(e);
}

function clearSignature() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    strokeHistory = [];
    document.getElementById('signaturePlaceholder').style.display = 'block';
}

function undoSignature() {
    if (strokeHistory.length === 0) return;
    
    strokeHistory.pop();
    redrawSignature();
}

function redrawSignature() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    if (strokeHistory.length === 0) {
        document.getElementById('signaturePlaceholder').style.display = 'block';
        return;
    }
    
    document.getElementById('signaturePlaceholder').style.display = 'none';
    
    strokeHistory.forEach(stroke => {
        if (stroke.length < 2) return;
        
        ctx.beginPath();
        ctx.moveTo(stroke[0].x, stroke[0].y);
        
        for (let i = 1; i < stroke.length; i++) {
            ctx.lineTo(stroke[i].x, stroke[i].y);
        }
        ctx.stroke();
    });
}

function isCanvasEmpty() {
    const pixelData = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
    for (let i = 3; i < pixelData.length; i += 4) {
        if (pixelData[i] > 0) return false;
    }
    return true;
}

function getSignatureData() {
    // Crear un canvas temporal con fondo blanco
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = canvas.width;
    tempCanvas.height = canvas.height;
    const tempCtx = tempCanvas.getContext('2d');
    
    // Fondo blanco
    tempCtx.fillStyle = '#ffffff';
    tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
    
    // Dibujar la firma
    tempCtx.drawImage(canvas, 0, 0);
    
    return tempCanvas.toDataURL('image/png');
}

// ==================== INPUTS DE PIN ====================
function initPinInputs() {
    const pinInputs = document.querySelectorAll('.pin-digit');
    
    pinInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            // Solo permitir números
            this.value = this.value.replace(/[^0-9]/g, '');
            
            if (this.value.length === 1) {
                this.classList.add('filled');
                // Mover al siguiente input
                if (index < pinInputs.length - 1) {
                    pinInputs[index + 1].focus();
                }
            } else {
                this.classList.remove('filled');
            }
            
            checkPinMatch();
        });
        
        input.addEventListener('keydown', function(e) {
            // Permitir borrar y moverse con flechas
            if (e.key === 'Backspace' && this.value === '' && index > 0) {
                pinInputs[index - 1].focus();
            }
        });
        
        input.addEventListener('focus', function() {
            this.select();
        });
    });
}

function getPin() {
    const inputs = document.querySelectorAll('.pin-input-group:first-of-type .pin-digit');
    let pin = '';
    inputs.forEach(input => pin += input.value);
    return pin;
}

function getConfirmPin() {
    const inputs = document.querySelectorAll('#pinConfirmGroup .pin-digit');
    let pin = '';
    inputs.forEach(input => pin += input.value);
    return pin;
}

function checkPinMatch() {
    const pin = getPin();
    const confirmPin = getConfirmPin();
    const statusEl = document.getElementById('pinMatchStatus');
    
    if (pin.length < 4 || confirmPin.length < 4) {
        statusEl.innerHTML = '';
        return false;
    }
    
    if (pin === confirmPin) {
        statusEl.innerHTML = '<span style="color: #2ea043;"><i class="fas fa-check-circle"></i> Los PINs coinciden</span>';
        return true;
    } else {
        statusEl.innerHTML = '<span style="color: #f85149;"><i class="fas fa-times-circle"></i> Los PINs no coinciden</span>';
        return false;
    }
}

// ==================== ENVÍO DEL FORMULARIO ====================
async function submitFirma(e) {
    e.preventDefault();
    
    const empleadoId = document.getElementById('empleadoId').value;
    const pin = getPin();
    const confirmPin = getConfirmPin();
    
    // Validaciones
    if (isCanvasEmpty()) {
        alert('Por favor, capture la firma del empleado.');
        return false;
    }
    
    if (pin.length !== 4) {
        alert('El PIN debe tener exactamente 4 dígitos.');
        return false;
    }
    
    if (pin !== confirmPin) {
        alert('Los PINs no coinciden. Por favor, verifique.');
        return false;
    }
    
    // Confirmar
    if (!confirm('¿Está seguro de guardar esta firma y PIN?\n\nEsta acción quedará registrada en el sistema.')) {
        return false;
    }
    
    const btnGuardar = document.getElementById('btnGuardar');
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    
    try {
        const signatureData = getSignatureData();
        
        const response = await fetch('<?= url('/modulos/recursos-humanos/api/guardar-firma.php') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                empleado_id: empleadoId,
                firma_imagen: signatureData,
                pin: pin
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✓ Firma y PIN guardados exitosamente.');
            window.location.reload();
        } else {
            alert('Error: ' + (result.message || 'No se pudo guardar la firma.'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión. Por favor, intente nuevamente.');
    } finally {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<i class="fas fa-save"></i> Guardar Firma y PIN';
    }
    
    return false;
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
