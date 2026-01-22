<?php
/**
 * Módulo: Vehículos - Editar Baja Histórica
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requireAuth();

// 1. Identificación Módulo Padre (Vehículos) para permisos
$pdo = getConnection();
$stmtMod = $pdo->prepare("SELECT id FROM modulos WHERE nombre_modulo = ?");
$stmtMod->execute(['Vehículos']);
$modulo = $stmtMod->fetch();
$MODULO_ID = $modulo ? $modulo['id'] : 0;

requirePermission('editar', $MODULO_ID);

$id = $_GET['id'] ?? 0;
if (!$id) {
    header("Location: bajas.php");
    exit;
}

// 2. Obtener Datos de la Baja
$stmt = $pdo->prepare("SELECT * FROM vehiculos_bajas WHERE id = ?");
$stmt->execute([$id]);
$baja = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$baja) {
    die("Registro de baja no encontrado.");
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fas fa-edit"></i> Editar Baja Histórica
            </h1>
            <p class="page-description">Corrección de datos en archivo muerto</p>
        </div>
        <div>
            <button type="button" class="btn btn-success me-2" onclick="restaurarVehiculo()">
                <i class="fas fa-sync-alt"></i> Restaurar al Padrón
            </button>
            <a href="bajas.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Cancelar
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="formEditBaja" onsubmit="event.preventDefault(); updateBaja();">
                <input type="hidden" name="id" value="<?= $baja['id'] ?>">
                
                <h5 class="mb-3 text-muted">Información de la Baja</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Fecha de Baja</label>
                        <input type="datetime-local" name="fecha_baja" class="form-control" 
                               value="<?= date('Y-m-d\TH:i', strtotime($baja['fecha_baja'])) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Motivo de Baja</label>
                        <select name="motivo_baja" class="form-select" required>
                            <?php 
                            $motivos = ['Baja Definitiva', 'Siniestro', 'Robo', 'Venta', 'Chatarra', 'Otro'];
                            foreach($motivos as $m): 
                            ?>
                                <option value="<?= $m ?>" <?= ($baja['motivo_baja'] == $m) ? 'selected' : '' ?>>
                                    <?= $m ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <h5 class="mb-3 text-muted">Datos del Vehículo</h5>

                <!-- Fila 1 -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">No. Económico</label>
                        <input type="text" name="numero_economico" class="form-control fw-bold" 
                               value="<?= htmlspecialchars($baja['numero_economico']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">No. Patrimonio</label>
                        <input type="text" name="numero_patrimonio" class="form-control" 
                               value="<?= htmlspecialchars($baja['numero_patrimonio'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Placas</label>
                        <input type="text" name="numero_placas" class="form-control" 
                               value="<?= htmlspecialchars($baja['numero_placas']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Póliza</label>
                        <input type="text" name="poliza" class="form-control" 
                               value="<?= htmlspecialchars($baja['poliza'] ?? '') ?>">
                    </div>
                </div>

                <!-- Fila 2 -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Marca</label>
                        <input type="text" name="marca" class="form-control" 
                               value="<?= htmlspecialchars($baja['marca']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo</label>
                        <input type="text" name="tipo" class="form-control" 
                               value="<?= htmlspecialchars($baja['tipo'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Modelo</label>
                        <input type="text" name="modelo" class="form-control" 
                               value="<?= htmlspecialchars($baja['modelo']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Color</label>
                        <input type="text" name="color" class="form-control" 
                               value="<?= htmlspecialchars($baja['color'] ?? '') ?>">
                    </div>
                </div>

                <!-- Fila 3 -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">No. Serie</label>
                        <input type="text" name="numero_serie" class="form-control" 
                               value="<?= htmlspecialchars($baja['numero_serie'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Resguardo a Nombre De</label>
                        <input type="text" name="resguardo_nombre" class="form-control" 
                               value="<?= htmlspecialchars($baja['resguardo_nombre'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Región</label>
                        <select name="region" class="form-select">
                            <option value="SECOPE" <?= ($baja['region'] == 'SECOPE') ? 'selected' : '' ?>>SECOPE</option>
                            <option value="REGION LAGUNA" <?= ($baja['region'] == 'REGION LAGUNA') ? 'selected' : '' ?>>REGION LAGUNA</option>
                        </select>
                    </div>
                </div>

                <!-- Fila 4: Observaciones -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Observación 1</label>
                        <textarea name="observacion_1" class="form-control" rows="2"><?= htmlspecialchars($baja['observacion_1'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Observación 2</label>
                        <textarea name="observacion_2" class="form-control" rows="2"><?= htmlspecialchars($baja['observacion_2'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Fila 5: Otros -->
                 <div class="row g-3 mb-3 align-items-center">
                    <div class="col-md-3">
                         <label class="form-label">Kilometraje</label>
                         <input type="text" name="kilometraje" class="form-control" value="<?= htmlspecialchars($baja['kilometraje'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                         <label class="form-label">Teléfono</label>
                         <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($baja['telefono'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch pt-4">
                            <input class="form-check-input" type="checkbox" name="con_logotipos" value="SI" id="switchLogos" 
                                   <?= ($baja['con_logotipos'] ?? 'NO') == 'SI' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="switchLogos">¿Tenía Logotipos?</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
function updateBaja() {
    const form = document.getElementById('formEditBaja');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    fetch('api/update_baja.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Registro de baja actualizado correctamente.');
            window.location.href = 'bajas.php';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al guardar cambios.');
    });
}

function restaurarVehiculo() {
    const bajaId = document.querySelector('input[name="id"]').value;
    const economico = document.querySelector('input[name="numero_economico"]').value;
    
    if (confirm(`¿Estás seguro de restaurar el vehículo ${economico} al padrón activo?`)) {
        fetch('api/restaurar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: bajaId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Vehículo restaurado exitosamente.');
                window.location.href = 'index.php';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión');
        });
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
