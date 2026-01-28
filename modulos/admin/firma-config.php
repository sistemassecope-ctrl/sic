<?php
/**
 * Módulo de Configuración de Firmas
 * Permite definir el tipo de firma predeterminado para los documentos.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

// Verificar permisos de administrador (o permiso específico si existiera)
if (!isAdmin()) {
    setFlashMessage('error', 'No tienes permiso para acceder a esta configuración.');
    redirect('/index.php');
}



$pdo = getConnection();

// Obtener Configuración Actual
try {
    $stmt = $pdo->query("SELECT valor FROM configuracion_sistema WHERE clave = 'tipo_firma_global'");
    $currentConfig = $stmt->fetchColumn() ?: 'pin';
} catch (Exception $e) {
    // Fallback in case table doesn't exist yet (though we ran the script)
    $currentConfig = 'pin';
}

// Procesar Formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newConfig = $_POST['tipo_firma_global'] ?? 'pin';
    try {
        $stmt = $pdo->prepare("INSERT INTO configuracion_sistema (clave, valor) VALUES ('tipo_firma_global', ?) ON DUPLICATE KEY UPDATE valor = ?");
        $stmt->execute([$newConfig, $newConfig]);
        $currentConfig = $newConfig;
        setFlashMessage('success', 'Configuración global actualizada correctamente.');
    } catch (Exception $e) {
        setFlashMessage('error', 'Error al guardar configuración: ' . $e->getMessage());
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-cogs text-primary me-2"></i>Configuración Global de Firmas</h1>
            <p class="text-muted">Seleccione el método de firma que se aplicará a <strong>TODOS</strong> los documentos
                del sistema.</p>
        </div>
    </div>

    <div class="card glass-effect border-0 shadow-lg mx-auto" style="max-width: 600px;">
        <div class="card-body p-5">
            <form method="POST">

                <h5 class="mb-4 text-white border-bottom border-secondary pb-2">Método Predeterminado</h5>

                <!-- Custom Radio Buttons styled as Checkboxes (Square) -->
                <!-- Custom Radio Buttons styled as Checkboxes (Square) -->
                <div class="d-flex flex-column gap-3 selection-group">

                    <label
                        class="custom-check-container d-flex align-items-center p-3 border rounded border-secondary bg-dark bg-opacity-25">
                        <input type="radio" name="tipo_firma_global" value="autografa" <?= $currentConfig === 'autografa' ? 'checked' : '' ?>>
                        <span class="checkmark me-3 position-relative"></span>
                        <div>
                            <span class="d-block fw-bold text-white fs-5">Autógrafa</span>
                            <span class="d-block text-muted small mt-1">Impresión inmediata del documento para firma con
                                bolígrafo. Requiere escaneo posterior.</span>
                        </div>
                    </label>

                    <label
                        class="custom-check-container d-flex align-items-center p-3 border rounded border-secondary bg-dark bg-opacity-25">
                        <input type="radio" name="tipo_firma_global" value="pin" <?= $currentConfig === 'pin' ? 'checked' : '' ?>>
                        <span class="checkmark me-3 position-relative"></span>
                        <div>
                            <span class="d-block fw-bold text-white fs-5">Digital (PIN)</span>
                            <span class="d-block text-muted small mt-1">Firma rápida interna utilizando PIN de seguridad
                                y estampa digitalizada.</span>
                        </div>
                    </label>

                    <label
                        class="custom-check-container d-flex align-items-center p-3 border rounded border-secondary bg-dark bg-opacity-25">
                        <input type="radio" name="tipo_firma_global" value="fiel" <?= $currentConfig === 'fiel' ? 'checked' : '' ?>>
                        <span class="checkmark me-3 position-relative"></span>
                        <div>
                            <span class="d-block fw-bold text-white fs-5">FIEL (e.Firma)</span>
                            <span class="d-block text-muted small mt-1">Máxima seguridad legal utilizando certificados
                                del SAT (.cer y .key).</span>
                        </div>
                    </label>

                </div>

                <div class="d-flex justify-content-end mt-4 pt-3 border-top border-secondary">
                    <button type="submit" class="btn btn-primary px-5 fw-bold py-2">
                        <i class="fas fa-save me-2"></i> Guardar Configuración Global
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-info mt-4 glass-vibrant-bg border-0 text-white">
        <h5><i class="fas fa-info-circle me-2"></i> Notas sobre los métodos de firma:</h5>
        <ul class="mb-0 small">
            <li class="mb-1"><strong>Autógrafa:</strong> El sistema generará el documento con espacios para firmar. El
                documento quedará en estado "Pendiente de Carga" hasta que se suba el escaneo firmado.</li>
            <li class="mb-1"><strong>Digital (PIN):</strong> Utiliza la firma digitalizada del empleado (imagen)
                validada mediante un PIN seguro de 6 dígitos. Tiene validez interna.</li>
            <li><strong>FIEL:</strong> Requiere archivos .cer y .key del SAT. Proporciona el nivel más alto de validez
                legal (Non-repudiation).</li>
        </ul>
    </div>

</main>

<style>
    /* Custom CSS to match the user's "Square Checkbox" style for Radio buttons */
    .custom-check-container {
        /* display: block; Handled by bootstrap d-flex */
        position: relative;
        /* padding-left: 35px; REMOVED to avoid overlap, using flexbox now */
        margin-bottom: 5px;
        cursor: pointer;
        font-size: 16px;
        user-select: none;
        color: #e2e8f0;
        transition: all 0.2s ease;
    }
    
    .custom-check-container:hover {
        background-color: rgba(255,255,255,0.05) !important;
        border-color: #64748b !important;
    }

    /* Hide the browser's default radio button */
    .custom-check-container input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    /* Create a custom radio button (square like a checkbox) */
    .checkmark {
        position: relative; /* Changed from absolute */
        /* top: 0; left: 0; Removed */
        height: 24px;
        width: 24px;
        flex-shrink: 0; /* Don't squash me */
        background-color: transparent;
        border: 2px solid #94a3b8;
        border-radius: 4px; /* Slightly rounded square */
        transition: all 0.2s;
    }

    /* On mouse-over, add a grey background color */
    .custom-check-container:hover input~.checkmark {
        border-color: #cbd5e1;
        box-shadow: 0 0 0 4px rgba(255,255,255,0.05);
    }

    /* When the radio button is checked, add a blue background */
    .custom-check-container input:checked~.checkmark {
        background-color: transparent;
        border-color: #fff;
        box-shadow: 0 0 10px rgba(255,255,255,0.2);
    }

    /* Create the checkmark/indicator (hidden when not checked) */
    .checkmark:after {
        content: "";
        position: absolute;
        display: none;
    }

    /* Show the checkmark when checked */
    .custom-check-container input:checked~.checkmark:after {
        display: block;
    }

    /* Style the checkmark icon */
    .custom-check-container .checkmark:after {
        left: 8px;
        top: 3px;
        width: 6px;
        height: 12px;
        border: solid white;
        border-width: 0 2px 2px 0;
        -webkit-transform: rotate(45deg);
        -ms-transform: rotate(45deg);
        transform: rotate(45deg);
    }

    /* Hover effect for row */
    .table-hover tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.02);
    }
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>