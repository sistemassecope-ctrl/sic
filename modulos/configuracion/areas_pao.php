<?php
// Lógica de Backend
$db = (new Database())->getConnection();

// Procesar Guardado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_areas_pao') {
    try {
        $selected_ids = isset($_POST['area_ids']) ? $_POST['area_ids'] : [];

        // 1. Obtener IDs actualmente activos
        $stmt = $db->query("SELECT area_id FROM area_pao WHERE deleted_at IS NULL");
        $current_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 2. Calcular a eliminar (están en current pero no en selected)
        $to_delete = array_diff($current_ids, $selected_ids);

        // 3. Calcular a agregar (están en selected pero no en current)
        $to_add = array_diff($selected_ids, $current_ids);

        $db->beginTransaction();

        // Soft Delete
        if (!empty($to_delete)) {
            $in = str_repeat('?,', count($to_delete) - 1) . '?';
            $sql = "UPDATE area_pao SET deleted_at = NOW() WHERE area_id IN ($in)";
            $stmt = $db->prepare($sql);
            $stmt->execute(array_values($to_delete));
        }

        // Insert or Restore
        foreach ($to_add as $area_id) {
            // Check if exists (including deleted)
            $check = $db->prepare("SELECT id FROM area_pao WHERE area_id = ?");
            $check->execute([$area_id]);
            if ($check->rowCount() > 0) {
                // Restore
                $restore = $db->prepare("UPDATE area_pao SET deleted_at = NULL, updated_at = NOW() WHERE area_id = ?");
                $restore->execute([$area_id]);
            } else {
                // Insert
                $insert = $db->prepare("INSERT INTO area_pao (area_id, created_at) VALUES (?, NOW())");
                $insert->execute([$area_id]);
            }
        }

        $db->commit();
        $success_msg = "Áreas actualizadas correctamente.";

    } catch (Exception $e) {
        $db->rollBack();
        $error_msg = "Error al guardar: " . $e->getMessage();
    }
}

// Obtener todas las áreas
$stmt = $db->query("SELECT * FROM area WHERE activo = 1 ORDER BY nivel, nombre");
$all_areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener áreas seleccionadas (PAO)
$stmt = $db->query("SELECT p.area_id, a.nombre, a.tipo FROM area_pao p JOIN area a ON p.area_id = a.id WHERE p.deleted_at IS NULL");
$pao_areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$pao_area_ids = array_column($pao_areas, 'area_id');

// Función recursiva para construir el árbol
function buildTree(array &$elements, $parentId = null)
{
    $branch = array();
    foreach ($elements as $element) {
        if ($element['area_padre_id'] == $parentId) {
            $children = buildTree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[$element['id']] = $element;
            // unset($elements[$element['id']]); // Performance optimization but modifies parsing
        }
    }
    return $branch;
}

$area_tree = buildTree($all_areas);

function renderTree($tree, $pao_area_ids)
{
    echo '<ul class="list-group list-group-flush ms-4 border-start border-2">';
    foreach ($tree as $node) {
        $is_selected = in_array($node['id'], $pao_area_ids);
        // Si está seleccionada, la mostramos deshabilitada o marcada en el árbol, 
        // o permitimos duplicados visuales pero validamos en backend. 
        // Lo común es: si está en la derecha, no draggable desde la izquierda o visualmente opaco.
        $opacity = $is_selected ? 'opacity-50' : '';
        $draggable = $is_selected ? 'false' : 'true';

        echo '<li class="list-group-item bg-transparent border-0 py-1">';
        echo '<div class="d-flex align-items-center">';
        echo '<i class="bi bi-caret-right-fill text-muted me-1 small"></i>';
        echo '<span class="draggable-item badge bg-white text-dark border shadow-sm p-2 ' . $opacity . '" draggable="' . $draggable . '" data-id="' . $node['id'] . '" data-name="' . htmlspecialchars($node['nombre']) . '">';
        echo '<i class="bi bi-grip-vertical text-secondary me-2"></i>';
        echo htmlspecialchars($node['nombre']) . ' <small class="text-muted">(' . $node['tipo'] . ')</small>';
        echo '</span>';
        echo '</div>';

        if (isset($node['children'])) {
            renderTree($node['children'], $pao_area_ids);
        }
        echo '</li>';
    }
    echo '</ul>';
}
?>

<style>
    .draggable-item {
        cursor: grab;
    }

    .draggable-item:active {
        cursor: grabbing;
    }

    .drop-zone {
        min-height: 400px;
        border: 2px dashed #dee2e6;
        background-color: #f8f9fa;
        transition: all 0.3s;
    }

    .drop-zone.drag-over {
        background-color: #e9ecef;
        border-color: #0d6efd;
    }
</style>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="text-primary"><i class="bi bi-diagram-3-fill"></i> Configuración de Áreas PAO</h4>
        <p class="text-muted">Arrastre las áreas desde el árbol de la izquierda hacia la lista de la derecha para
            habilitarlas en el PAO.</p>
    </div>
</div>

<?php if (isset($success_msg)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php echo $success_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_msg)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php echo $error_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" id="paoAreasForm">
    <input type="hidden" name="action" value="save_areas_pao">

    <div class="row">
        <!-- Árbol de Áreas (Origen) -->
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-light fw-bold">
                    <i class="bi bi-building"></i> Estructura Organizacional
                </div>
                <div class="card-body overflow-auto" style="max-height: 600px;">
                    <?php renderTree($area_tree, $pao_area_ids); ?>
                </div>
            </div>
        </div>

        <!-- Lista de Selección (Destino) -->
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div
                    class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-check-square-fill"></i> Áreas Seleccionadas para PAO</span>
                    <span class="badge bg-white text-primary" id="count-badge">
                        <?php echo count($pao_areas); ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush p-3 drop-zone" id="selected-list">
                        <?php foreach ($pao_areas as $area): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center mb-2 border rounded shadow-sm bg-white"
                                data-id="<?php echo $area['area_id']; ?>">
                                <div>
                                    <i class="bi bi-bookmark-check-fill text-success me-2"></i>
                                    <strong>
                                        <?php echo htmlspecialchars($area['nombre']); ?>
                                    </strong>
                                    <br><small class="text-muted">
                                        <?php echo $area['tipo']; ?>
                                    </small>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <input type="hidden" name="area_ids[]" value="<?php echo $area['area_id']; ?>">
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($pao_areas)): ?>
                            <p class="text-center text-muted mt-5 empty-msg">Arrastre áreas aquí</p>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="card-footer bg-white text-end">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const draggables = document.querySelectorAll('.draggable-item');
        const dropZone = document.getElementById('selected-list');
        const emptyMsg = dropZone.querySelector('.empty-msg');

        // Drag Start
        draggables.forEach(draggable => {
            draggable.addEventListener('dragstart', (e) => {
                if (draggable.classList.contains('opacity-50')) {
                    e.preventDefault();
                    return;
                }
                e.dataTransfer.setData('text/plain', JSON.stringify({
                    id: draggable.getAttribute('data-id'),
                    name: draggable.getAttribute('data-name')
                }));
                e.dataTransfer.effectAllowed = 'copy';
                draggable.classList.add('dragging');
            });

            draggable.addEventListener('dragend', () => {
                draggable.classList.remove('dragging');
            });
        });

        // Drop Zone Events
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault(); // Necessary to allow dropping
            e.dataTransfer.dropEffect = 'copy';
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');

            try {
                const data = JSON.parse(e.dataTransfer.getData('text/plain'));

                // Check if already exists
                const existing = dropZone.querySelector(`li[data-id="${data.id}"]`);
                if (existing) return;

                // Remove empty message
                if (emptyMsg) emptyMsg.style.display = 'none';

                // Create new item
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center mb-2 border rounded shadow-sm bg-white animate__animated animate__fadeIn';
                li.setAttribute('data-id', data.id);
                li.innerHTML = `
                <div>
                    <i class="bi bi-bookmark-check-fill text-success me-2"></i>
                    <strong>${data.name}</strong>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                    <i class="bi bi-trash"></i>
                </button>
                <input type="hidden" name="area_ids[]" value="${data.id}">
            `;

                dropZone.appendChild(li);
                updateCount();

                // Disable original
                const original = document.querySelector(`.draggable-item[data-id="${data.id}"]`);
                if (original) {
                    original.classList.add('opacity-50');
                    original.setAttribute('draggable', 'false');
                }

            } catch (err) {
                console.error('Error parsing drag data', err);
            }
        });

        // Remove Item Logic (Delegation)
        dropZone.addEventListener('click', (e) => {
            if (e.target.closest('.remove-item')) {
                const li = e.target.closest('li');
                const id = li.getAttribute('data-id');
                li.remove();

                // Re-enable original
                const original = document.querySelector(`.draggable-item[data-id="${id}"]`);
                if (original) {
                    original.classList.remove('opacity-50');
                    original.setAttribute('draggable', 'true');
                }

                updateCount();

                if (dropZone.querySelectorAll('li').length === 0 && emptyMsg) {
                    emptyMsg.style.display = 'block';
                }
            }
        });

        function updateCount() {
            const count = dropZone.querySelectorAll('li').length;
            document.getElementById('count-badge').textContent = count;
        }
    });
</script>