<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/services/SignatureService.php';
require_once __DIR__ . '/../../includes/services/StampingService.php';

$db = (new Database())->getConnection();
$signatureService = new SignatureService();
$stampingService = new StampingService();

// Mock User (en prod usar $_SESSION['user_id'])
$id_usuario = $_SESSION['user_id'] ?? 1;

// --- ACCIONES POST ---
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'firmar') {
        try {
            $id_doc = $_POST['id_documento'];
            $pin = $_POST['pin'];
            $rol = 'REVISOR'; // Esto idealmente viene del permiso del usuario o input

            $res = $signatureService->signDocument($id_doc, $id_usuario, $pin, $rol);

            if ($res['success']) {
                $mensaje = $res['message'];
                $tipo_mensaje = 'success';

                // Generar Hoja de Firmas actualizada
                // OJO: Esto podría ser asíncrono o solo al final. Lo hacemos aquí para demostración inmediata.
                try {
                    $stampingService->generateSignatureSheet($id_doc);
                } catch (Exception $e) {
                    $mensaje .= " (Nota: Error generando PDF visual: " . $e->getMessage() . ")";
                }

            } else {
                throw new Exception($res['message']);
            }
        } catch (Exception $e) {
            $mensaje = $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
}

// --- FETCH DOCUMENTOS PENDIENTES ---
// Por simplicidad, traemos todos los documentos EN_FIRMA que este usuario NO haya firmado aun.
// En un sistema real, filtrarías por documentos asignados a su área/rol.
$sql = "
    SELECT d.*, 
        (SELECT COUNT(*) FROM archivo_firmas f WHERE f.id_documento = d.id_documento AND f.id_usuario = :idu) as ya_firme
    FROM archivo_documentos d
    WHERE d.estado = 'EN_FIRMA'
    HAVING ya_firme = 0
    ORDER BY d.fecha_creacion DESC
";
$stmt = $db->prepare($sql);
$stmt->execute([':idu' => $id_usuario]);
$pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="row justify-content-center">
    <!-- Header Móvil -->
    <div class="col-12 d-md-none mb-3">
        <div class="d-flex justify-content-between align-items-center bg-white p-3 shadow-sm rounded">
            <h5 class="m-0 fw-bold">Mis Firmas</h5>
            <span class="badge bg-danger rounded-pill">
                <?php echo count($pendientes); ?>
            </span>
        </div>
    </div>

    <!-- Lista Desktop/Movil -->
    <div class="col-lg-8">

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show shadow-sm" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($pendientes)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-patch-check display-1 text-light"></i>
                <p class="mt-3 fs-5">¡Todo al día! No tienes documentos pendientes de firma.</p>
            </div>
        <?php else: ?>
            <h5 class="d-none d-md-block text-secondary mb-3">Documentos Pendientes (
                <?php echo count($pendientes); ?>)
            </h5>

            <div class="list-group shadow-sm">
                <?php foreach ($pendientes as $doc): ?>
                    <div class="list-group-item list-group-item-action p-3 mb-2 border rounded-3 document-item">
                        <div class="d-flex w-100 justify-content-between align-items-start">
                            <div>
                                <div class="d-flex align-items-center mb-1">
                                    <span class="badge bg-light text-dark border me-2">
                                        <?php echo $doc['modulo_origen']; ?>
                                    </span>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($doc['fecha_creacion'])); ?>
                                    </small>
                                </div>
                                <h6 class="mb-1 fw-bold text-primary text-truncate-2">
                                    <?php echo htmlspecialchars($doc['nombre_archivo_original']); ?>
                                </h6>
                                <p class="mb-1 small text-muted">
                                    <?php echo $doc['tipo_documento']; ?> (Ref: #
                                    <?php echo $doc['referencia_id']; ?>)
                                </p>
                            </div>
                            <div class="ms-2">
                                <a href="/pao/ver_archivo.php?uuid=<?php echo $doc['uuid']; ?>" target="_blank"
                                    class="btn btn-outline-primary btn-sm rounded-circle"
                                    style="width: 32px; height: 32px; padding: 0; line-height: 30px;">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </div>

                        <div class="mt-3 pt-2 border-top d-grid">
                            <button type="button" class="btn btn-dark btn-sm fw-bold"
                                onclick="openSignModal(<?php echo $doc['id_documento']; ?>, '<?php echo htmlspecialchars($doc['nombre_archivo_original']); ?>')">
                                <i class="bi bi-pen-fill me-2"></i> FIRMAR DOCUMENTO
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Firma -->
<div class="modal fade" id="signModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm"> <!-- Small modal for mobile feel -->
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white border-0">
                <h6 class="modal-title fw-bold">Autorizar Documento</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <p class="small text-muted mb-2 text-center">Estás por firmar:</p>
                <p class="fw-bold text-center text-break" id="modalDocName">Doc...</p>

                <form method="POST" id="formSign">
                    <input type="hidden" name="action" value="firmar">
                    <input type="hidden" name="id_documento" id="modalDocId">

                    <div class="mb-3 mt-4">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Ingrese su PIN de
                            Seguridad</label>
                        <input type="password" name="pin"
                            class="form-control form-control-lg text-center letter-spacing-2" maxlength="6"
                            inputmode="numeric" placeholder="******" required autocomplete="off">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg shadow-sm">
                            <i class="bi bi-check-circle-fill me-2"></i> CONFIRMAR
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openSignModal(id, name) {
        document.getElementById('modalDocId').value = id;
        document.getElementById('modalDocName').innerText = name;
        var myModal = new bootstrap.Modal(document.getElementById('signModal'));
        myModal.show();
    }
</script>

<style>
    .letter-spacing-2 {
        letter-spacing: 5px;
    }

    /* Mobile optimization */
    @media (max-width: 768px) {
        .document-item {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, .075) !important;
            border: none !important;
        }
    }
</style>