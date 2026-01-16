<?php
// Configuración de Firma Digital
$db = (new Database())->getConnection();

// Mock Session for dev
// Session user check
$id_usuario = $_SESSION['user_id'] ?? 1; // Default to 1 if not set but should be blocked by router

// Handle POST
$mensaje = '';
$tipo_mensaje = '';

// Helper to get OS
function getOS()
{
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $os_platform = "Unknown OS Platform";
    $os_array = [
        '/windows nt 10/i' => 'Windows 10/11',
        '/windows nt 6.3/i' => 'Windows 8.1',
        '/windows nt 6.2/i' => 'Windows 8',
        '/windows nt 6.1/i' => 'Windows 7',
        '/windows nt 6.0/i' => 'Windows Vista',
        '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
        '/windows nt 5.1/i' => 'Windows XP',
        '/macintosh|mac os x/i' => 'Mac OS X',
        '/mac_powerpc/i' => 'Mac OS 9',
        '/linux/i' => 'Linux',
        '/ubuntu/i' => 'Ubuntu',
        '/iphone/i' => 'iPhone',
        '/ipod/i' => 'iPod',
        '/ipad/i' => 'iPad',
        '/android/i' => 'Android',
        '/blackberry/i' => 'BlackBerry',
        '/webos/i' => 'Mobile'
    ];

    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $os_platform = $value;
        }
    }
    return $os_platform;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Audit Data
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $os_info = getOS();
    $updated_by = $id_usuario;

    try {
        // 1. PIN Update
        if (!empty($_POST['pin']) && !empty($_POST['pin_confirm'])) {
            $pin = $_POST['pin'];
            $pin_confirm = $_POST['pin_confirm'];

            if ($pin !== $pin_confirm) {
                throw new Exception("Los PINs no coinciden.");
            }
            if (!preg_match('/^\d{6}$/', $pin)) {
                throw new Exception("El PIN debe ser de 6 dígitos numéricos.");
            }

            $pinHash = password_hash($pin, PASSWORD_DEFAULT);

            // Upsert PIN with Audit
            $sql = "INSERT INTO usuarios_config_firma 
                    (id_usuario, pin_firma, updated_by, ip_address, user_agent, os_info) 
                    VALUES (:id_u, :pin, :upd_by, :ip, :ua, :os) 
                    ON DUPLICATE KEY UPDATE 
                        pin_firma = :pin_upd,
                        updated_by = :upd_by_u,
                        ip_address = :ip_u,
                        user_agent = :ua_u,
                        os_info = :os_u";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':id_u' => $id_usuario,
                ':pin' => $pinHash,
                ':upd_by' => $updated_by,
                ':ip' => $ip_address,
                ':ua' => $user_agent,
                ':os' => $os_info,
                ':pin_upd' => $pinHash,
                ':upd_by_u' => $updated_by,
                ':ip_u' => $ip_address,
                ':ua_u' => $user_agent,
                ':os_u' => $os_info
            ]);

            $mensaje = "PIN actualizado correctamente.";
            $tipo_mensaje = "success";
        }

        // 2. Signature Pad (Base64)
        if (!empty($_POST['firma_base64'])) {
            $data = $_POST['firma_base64'];

            // Basic validation
            if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                $data = substr($data, strpos($data, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif

                if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                    throw new Exception('Tipo de imagen inválido.');
                }

                $data = base64_decode($data);

                if ($data === false) {
                    throw new Exception('Fallo al decodificar la imagen.');
                }
            } else {
                throw new Exception('Formato de datos de firma inválido.');
            }

            // Save BINARY to DB (Secure, no file vestige)

            // Remove old file if exists (cleanup legacy)
            $stmt = $db->prepare("SELECT ruta_firma_imagen FROM usuarios_config_firma WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);
            $currentPath = $stmt->fetchColumn();

            if ($currentPath) {
                $oldPath = __DIR__ . '/../../' . $currentPath;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            // Insert into blob with Audit
            $sql = "INSERT INTO usuarios_config_firma 
                    (id_usuario, firma_blob, ruta_firma_imagen, updated_by, ip_address, user_agent, os_info) 
                    VALUES (:id_u, :blob, NULL, :upd_by, :ip, :ua, :os) 
                    ON DUPLICATE KEY UPDATE 
                        firma_blob = :blob_upd, 
                        ruta_firma_imagen = NULL,
                        updated_by = :upd_by_u,
                        ip_address = :ip_u,
                        user_agent = :ua_u,
                        os_info = :os_u";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':id_u' => $id_usuario,
                ':blob' => $data,
                ':upd_by' => $updated_by,
                ':ip' => $ip_address,
                ':ua' => $user_agent,
                ':os' => $os_info,
                ':blob_upd' => $data,
                ':upd_by_u' => $updated_by,
                ':ip_u' => $ip_address,
                ':ua_u' => $user_agent,
                ':os_u' => $os_info
            ]);

            $mensaje = "Rúbrica actualizada correctamente (Almacenamiento Binario Seguro).";
            $tipo_mensaje = "success";
        }

        // 3. Delete Signature
        if (isset($_POST['action']) && $_POST['action'] === 'delete_firma') {
            // Remove legacy file if any
            $stmt = $db->prepare("SELECT ruta_firma_imagen FROM usuarios_config_firma WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);
            $currentPath = $stmt->fetchColumn();

            if ($currentPath) {
                // Remove file physically
                $absPath = __DIR__ . '/../../' . $currentPath;
                if (file_exists($absPath)) {
                    unlink($absPath);
                }
            }

            // Update DB
            $stmt = $db->prepare("UPDATE usuarios_config_firma SET firma_blob = NULL, ruta_firma_imagen = NULL WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);

            $mensaje = "Firma eliminada correctamente.";
            $tipo_mensaje = "warning";
        }

        // 4. Delete PIN
        if (isset($_POST['action']) && $_POST['action'] === 'delete_pin') {
            $stmt = $db->prepare("UPDATE usuarios_config_firma SET pin_firma = NULL WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);

            $mensaje = "PIN de seguridad eliminado.";
            $tipo_mensaje = "warning";
        }

    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Fetch Current Config
$stmt = $db->prepare("SELECT * FROM usuarios_config_firma WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$config = $stmt->fetch(PDO::FETCH_ASSOC);

$hasPin = !empty($config['pin_firma']);

// Prepare Image Source
$firmaImgSrc = null;
if (!empty($config['firma_blob'])) {
    // Serve from BLOB
    $firmaImgSrc = 'data:image/png;base64,' . base64_encode($config['firma_blob']);
} elseif (!empty($config['ruta_firma_imagen'])) {
    // Legacy fallback
    $firmaImgSrc = '/pao/' . $config['ruta_firma_imagen'];
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Mi Firma Digital</h4>
            <a href="/pao/index.php" class="btn btn-outline-secondary btn-sm">Volver</a>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- TARJETA: CONFIGURACIÓN DE FIRMA -->
        <div class="card shadow border-0 mb-4">
            <div class="card-body p-4">

                <form action="" method="POST" id="signatureForm">

                    <!-- SECCIÓN 1: RÚBRICA VISUAL -->
                    <h5 class="text-primary border-bottom pb-2 mb-3">1. Rúbrica Visual</h5>

                    <div class="mb-3 text-center">
                        <p class="mb-2">Dibuje su firma en el recuadro de abajo:</p>

                        <div class="border rounded bg-white mx-auto position-relative" id="signature-container"
                            style="width: 100%; max-width: 400px; height: 200px; touch-action: none;">
                            <canvas id="signature-pad" class="w-100 h-100"></canvas>

                            <!-- Controls for Fullscreen -->
                            <div id="fullscreen-controls">
                                <button type="button" class="btn btn-primary rounded-circle shadow-lg"
                                    id="close-fullscreen" style="width: 50px; height: 50px;">
                                    <i class="bi bi-check-lg fs-4"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-danger btn-sm" id="clear-signature">
                                <i class="bi bi-eraser"></i> Borrar
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm ms-2" id="toggle-fullscreen">
                                <i class="bi bi-arrows-fullscreen"></i> Ampliar / Pantalla Completa
                            </button>
                        </div>

                        <!-- Hidden input to store generated implementation -->
                        <input type="hidden" name="firma_base64" id="firma_base64">

                        <div class="mt-4">
                            <label class="form-label fw-bold">Firma Actual Guardada:</label>

                            <?php if ($firmaImgSrc): ?>
                                <div class="border rounded bg-light d-flex align-items-center justify-content-center mx-auto mb-2"
                                    style="width: 200px; height: 100px; overflow: hidden;">
                                    <img src="<?php echo $firmaImgSrc; ?>" alt="Firma Actual"
                                        style="max-width: 100%; max-height: 100%;">
                                </div>
                                <div class="text-center">
                                    <button type="submit" name="action" value="delete_firma"
                                        class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('¿Está seguro de eliminar su firma? Deberá dibujar una nueva para firmar documentos.')">
                                        <i class="bi bi-trash"></i> Eliminar Firma
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="border rounded bg-light d-flex align-items-center justify-content-center mx-auto"
                                    style="width: 200px; height: 100px;">
                                    <span class="text-muted small">Sin firma configurada</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- SECCIÓN 2: PIN DE SEGURIDAD -->
                    <h5 class="text-primary border-bottom pb-2 mb-3 mt-4">2. PIN de Seguridad</h5>

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="alert alert-light border">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3 fs-3 text-warning"><i class="bi bi-lock-fill"></i></div>
                                        <div>
                                            <strong>Estado del PIN:</strong>
                                            <?php if ($hasPin): ?>
                                                <span class="text-success fw-bold">CONFIGURADO</span>
                                            <?php else: ?>
                                                <span class="text-danger fw-bold">NO CONFIGURADO</span>
                                            <?php endif; ?>
                                            <p class="mb-0 small text-muted">Este PIN de 6 dígitos será solicitado cada
                                                vez
                                                que necesite firmar un documento oficial.</p>
                                        </div>
                                    </div>

                                    <?php if ($hasPin): ?>
                                        <div>
                                            <button type="submit" name="action" value="delete_pin"
                                                class="btn btn-outline-danger btn-sm"
                                                onclick="return confirm('¿Seguro que desea eliminar su PIN? No podrá firmar hasta configurar uno nuevo.')">
                                                <i class="bi bi-trash"></i> Eliminar PIN
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <label for="pin" class="form-label">Nuevo PIN (6 dígitos)</label>
                            <input type="password" class="form-control text-center fs-5 letter-spacing-2" id="pin"
                                name="pin" maxlength="6" pattern="\d{6}" inputmode="numeric" placeholder="******">
                        </div>
                        <div class="col-6">
                            <label for="pin_confirm" class="form-label">Confirmar PIN</label>
                            <input type="password" class="form-control text-center fs-5 letter-spacing-2"
                                id="pin_confirm" name="pin_confirm" maxlength="6" pattern="\d{6}" inputmode="numeric"
                                placeholder="******">
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-5">
                        <button type="submit" name="action" value="save" class="btn btn-primary btn-lg" id="saveBtn">
                            <i class="bi bi-save2"></i> Guardar Cambios
                        </button>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

<style>
    .letter-spacing-2 {
        letter-spacing: 5px;
    }

    #signature-pad {
        cursor: crosshair;
        background-color: #fff;
    }

    /* Fullscreen specific styles */
    #signature-container.fullscreen {
        position: fixed !important;
        top: 0;
        left: 0;
        width: 100% !important;
        height: 100vh !important;
        max-width: none !important;
        z-index: 9999;
        border-radius: 0 !important;
        background-color: #fff;
    }

    #signature-container.fullscreen #signature-pad {
        height: 100% !important;
    }

    /* Controls overlays for fullscreen */
    #fullscreen-controls {
        display: none;
    }

    #signature-container.fullscreen #fullscreen-controls {
        display: block;
        position: absolute;
        bottom: 20px;
        right: 20px;
        z-index: 10000;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('signature-container');
        const canvas = document.getElementById('signature-pad');
        const signatureForm = document.getElementById('signatureForm');
        const clearButton = document.getElementById('clear-signature');
        const toggleFsButton = document.getElementById('toggle-fullscreen');
        const closeFsButton = document.getElementById('close-fullscreen');
        const firmaInput = document.getElementById('firma_base64');

        // Adjust canvas resolution 
        function updateCanvasSize(preserve = false) {
            // Save content if requested
            let tempCanvas = null;
            if (preserve && hasSignature && canvas.width > 0 && canvas.height > 0) {
                tempCanvas = document.createElement('canvas');
                tempCanvas.width = canvas.width;
                tempCanvas.height = canvas.height;
                tempCanvas.getContext('2d').drawImage(canvas, 0, 0);
            }

            const ratio = Math.max(window.devicePixelRatio || 1, 1);

            // Set physical dimensions
            canvas.width = container.offsetWidth * ratio;
            canvas.height = container.offsetHeight * ratio;

            // Scale context for logic coordinates
            const ctx = canvas.getContext('2d');
            ctx.scale(ratio, ratio);

            // Re-apply context settings (reset by width change)
            ctx.strokeStyle = "#000000";
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            // Restore image
            if (tempCanvas) {
                // Draw temp canvas stretched to new logical dimensions
                ctx.drawImage(tempCanvas, 0, 0, container.offsetWidth, container.offsetHeight);
            }
        }

        // Initial resize w/o preserve
        updateCanvasSize(false);
        // DISABLED window resize listener to prevent mobile glitches

        const ctx = canvas.getContext('2d');

        // --- DRAWING LOGIC ---
        let isDrawing = false;
        let hasSignature = false;

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            // Support both touch and mouse
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        }

        function startPosition(e) {
            isDrawing = true;
            hasSignature = true;
            // Re-assert context state in case of reset
            ctx.strokeStyle = "#000000";
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';

            const pos = getPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
            // Prevent scrolling on touch ONLY when drawing
            if (e.type === 'touchstart') document.body.style.overflow = 'hidden';
        }

        function finishPosition(e) {
            isDrawing = false;
            ctx.beginPath();
            if (e.type === 'touchend') document.body.style.overflow = '';
        }

        function draw(e) {
            if (!isDrawing) return;
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            e.preventDefault(); // Stop scrolling while drawing
        }

        // Event Listeners
        canvas.addEventListener('mousedown', startPosition);
        canvas.addEventListener('mouseup', finishPosition);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseleave', finishPosition);

        canvas.addEventListener('touchstart', startPosition, { passive: false });
        canvas.addEventListener('touchend', finishPosition);
        canvas.addEventListener('touchmove', draw, { passive: false });


        // --- CONTROLS ---

        function clearCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height); // Use actual pixel width
            hasSignature = false;
            firmaInput.value = '';
        }

        clearButton.addEventListener('click', clearCanvas);

        // Fullscreen Logic
        function toggleFullscreen() {
            container.classList.toggle('fullscreen');

            // Force resize after transition
            setTimeout(function () {
                updateCanvasSize(true);
            }, 50);

            // Show/Hide body scroll
            if (container.classList.contains('fullscreen')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        if (toggleFsButton) toggleFsButton.addEventListener('click', toggleFullscreen);
        if (closeFsButton) closeFsButton.addEventListener('click', toggleFullscreen);

        // Close fullscreen on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && container.classList.contains('fullscreen')) {
                toggleFullscreen();
            }
        });


        // --- FORM SUBMIT ---
        signatureForm.addEventListener('submit', function (e) {
            if (hasSignature) {
                const dataURL = canvas.toDataURL('image/png');
                firmaInput.value = dataURL;
            }
        });
    });
</script>