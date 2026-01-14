<?php
require_once '../../config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$pageTitle = 'Puestos de Trabajo - SIC';
$breadcrumb = [
    ['url' => '../../dashboard.php', 'text' => 'Dashboard'],
    ['url' => 'puestos_trabajo.php', 'text' => 'Puestos de Trabajo']
];
require_once '../../includes/header.php';

$pdo = conectarDB();

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'agregar':
                $nombre = trim($_POST['nombre']);
                $descripcion = trim($_POST['descripcion']);
                
                if (!empty($nombre)) {
                    $stmt = $pdo->prepare("INSERT INTO puestos_trabajo (nombre, descripcion) VALUES (?, ?)");
                    if ($stmt->execute([$nombre, $descripcion])) {
                        // Registrar auditoría
                        if (function_exists('logActivity') && isset($_SESSION['user_id'])) {
                            logActivity('puesto_agregado', "Puesto agregado: $nombre", $_SESSION['user_id']);
                        }
                        $mensaje = "Puesto de trabajo agregado exitosamente.";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al agregar el puesto de trabajo.";
                        $tipo_mensaje = "danger";
                    }
                } else {
                    $mensaje = "Por favor complete todos los campos obligatorios.";
                    $tipo_mensaje = "warning";
                }
                break;
                
            case 'editar':
                $id = $_POST['id'];
                $nombre = trim($_POST['nombre']);
                $descripcion = trim($_POST['descripcion']);
                
                if (!empty($nombre)) {
                    $stmt = $pdo->prepare("UPDATE puestos_trabajo SET nombre = ?, descripcion = ? WHERE id = ?");
                    if ($stmt->execute([$nombre, $descripcion, $id])) {
                        // Registrar auditoría
                        if (function_exists('logActivity') && isset($_SESSION['user_id'])) {
                            logActivity('puesto_editado', "Puesto editado: $nombre (ID: $id)", $_SESSION['user_id']);
                        }
                        $mensaje = "Puesto de trabajo actualizado exitosamente.";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al actualizar el puesto de trabajo.";
                        $tipo_mensaje = "danger";
                    }
                } else {
                    $mensaje = "Por favor complete todos los campos obligatorios.";
                    $tipo_mensaje = "warning";
                }
                break;
                
            case 'eliminar':
                $id = $_POST['id'];
                $stmt = $pdo->prepare("UPDATE puestos_trabajo SET activo = FALSE WHERE id = ?");
                if ($stmt->execute([$id])) {
                    // Registrar auditoría
                    if (function_exists('logActivity') && isset($_SESSION['user_id'])) {
                        logActivity('puesto_eliminado', "Puesto eliminado: ID $id", $_SESSION['user_id']);
                    }
                    $mensaje = "Puesto de trabajo eliminado exitosamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al eliminar el puesto de trabajo.";
                    $tipo_mensaje = "danger";
                }
                break;
        }
    }
}



// Obtener dependencias para el formulario (usando la función de config.php)

$puestos_trabajo = obtenerPuestosTrabajo($pdo);

// Obtener estadísticas
$total_puestos = count($puestos_trabajo);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Puestos de Trabajo - Sistema de Dependencias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container-fluid {
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
            padding: 15px 20px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .stats-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-weight: 600;
        }
        
        .table td {
            border-color: #e9ecef;
            vertical-align: middle;
        }
        
        .badge {
            border-radius: 20px;
            padding: 8px 12px;
            font-size: 0.8em;
        }
        
        .badge-directivo {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .badge-ejecutivo {
            background: linear-gradient(135deg, #fd7e14, #e55a00);
            color: white;
        }
        
        .badge-operativo {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }
        
        .badge-tecnico {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
        }
        
        .badge-administrativo {
            background: linear-gradient(135deg, #6f42c1, #5a32a3);
            color: white;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        
        .text-muted {
            color: #6c757d !important;
        }
        
                 .alert {
             border-radius: 10px;
             border: none;
         }
         
         .input-group-text {
             background: linear-gradient(135deg, #667eea, #764ba2);
             color: white;
             border: none;
             border-radius: 10px 0 0 10px;
         }
         
         .input-group .form-control {
             border-radius: 0 10px 10px 0;
             border: 2px solid #e9ecef;
         }
         
         .input-group .form-control:focus {
             border-color: #667eea;
             box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
         }
         
         .btn-outline-secondary {
             border-color: #e9ecef;
             color: #6c757d;
             border-radius: 0 10px 10px 0;
         }
         
         .btn-outline-secondary:hover {
             background-color: #6c757d;
             border-color: #6c757d;
             color: white;
         }
         
         #contador-resultados {
             background: rgba(102, 126, 234, 0.1);
             border-color: rgba(102, 126, 234, 0.2);
             color: #667eea;
         }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="fas fa-briefcase text-primary"></i>
                        Gestión de Puestos de Trabajo
                    </h1>
                    <p class="text-muted mb-0">Administra los puestos de trabajo de las dependencias</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="../../dashboard.php" class="btn btn-primary">
                        <i class="fas fa-home"></i>
                        Inicio
                    </a>
                </div>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if (isset($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle"></i>
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row">
            <div class="col-md-12">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_puestos; ?></div>
                    <div class="stats-label">Total Puestos de Trabajo</div>
                </div>
            </div>
        </div>

        <!-- Botón Agregar y Buscador -->
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                            <i class="fas fa-plus"></i>
                            Agregar Nuevo Puesto de Trabajo
                        </button>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="buscador" placeholder="Buscar puestos de trabajo..." onkeyup="filtrarPuestos()">
                            <button class="btn btn-outline-secondary" type="button" onclick="limpiarBusqueda()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Puestos de Trabajo -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i>
                    Lista de Puestos de Trabajo
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($puestos_trabajo)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                        <h3 class="text-muted">No hay puestos de trabajo registrados</h3>
                        <p class="text-muted">Comienza agregando el primer puesto de trabajo</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Puesto</th>
                                    <th>Descripción</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($puestos_trabajo as $puesto): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($puesto['nombre']); ?></strong>
                                        </td>
                                        <td>
                                            <?php if (!empty($puesto['descripcion'])): ?>
                                                <small><?php echo htmlspecialchars(substr($puesto['descripcion'], 0, 50)) . (strlen($puesto['descripcion']) > 50 ? '...' : ''); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Sin descripción</small>
                                            <?php endif; ?>
                                        </td>
                                        <!-- Columnas requisitos y responsabilidades eliminadas -->
                                        <td>
                                            <button type="button" class="btn btn-warning btn-sm" 
                                                    onclick="editarPuesto(<?php echo htmlspecialchars(json_encode($puesto)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" 
                                                    onclick="eliminarPuesto(<?php echo $puesto['id']; ?>, '<?php echo htmlspecialchars($puesto['nombre']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Agregar -->
    <div class="modal fade" id="modalAgregar" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus"></i>
                        Agregar Nuevo Puesto de Trabajo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="accion" value="agregar">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Puesto *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="requisitos" class="form-label">Requisitos</label>
                            <textarea class="form-control" id="requisitos" name="requisitos" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="responsabilidades" class="form-label">Responsabilidades</label>
                            <textarea class="form-control" id="responsabilidades" name="responsabilidades" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i>
                        Editar Puesto de Trabajo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre del Puesto *</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <!-- Campos requisitos y responsabilidades eliminados -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i>
                            Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar -->
    <div class="modal fade" id="modalEliminar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" id="delete_id">
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas eliminar el puesto de trabajo <strong id="delete_nombre"></strong>?</p>
                        <p class="text-muted">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i>
                            Eliminar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
         <script>
         function editarPuesto(puesto) {
             document.getElementById('edit_id').value = puesto.id;
             document.getElementById('edit_nombre').value = puesto.nombre;
             document.getElementById('edit_descripcion').value = puesto.descripcion || '';
             // Campos requisitos y responsabilidades eliminados
             
             new bootstrap.Modal(document.getElementById('modalEditar')).show();
         }
         
         function eliminarPuesto(id, nombre) {
             document.getElementById('delete_id').value = id;
             document.getElementById('delete_nombre').textContent = nombre;
             
             new bootstrap.Modal(document.getElementById('modalEliminar')).show();
         }
         
         // Función para filtrar puestos de trabajo
         function filtrarPuestos() {
             const busqueda = document.getElementById('buscador').value.toLowerCase();
             const tabla = document.querySelector('.table tbody');
             const filas = tabla.querySelectorAll('tr');
             let puestosEncontrados = 0;
             
             filas.forEach(fila => {
                 const nombre = fila.querySelector('td:first-child strong').textContent.toLowerCase();
                 const descripcion = fila.querySelector('td:nth-child(2) small').textContent.toLowerCase();
                 
                 // Buscar en nombre y descripción
                 if (nombre.includes(busqueda) || 
                     descripcion.includes(busqueda)) {
                     fila.style.display = '';
                     puestosEncontrados++;
                 } else {
                     fila.style.display = 'none';
                 }
             });
             
             // Mostrar mensaje si no hay resultados
             const mensajeNoResultados = document.getElementById('mensaje-no-resultados');
             if (puestosEncontrados === 0 && busqueda !== '') {
                 if (!mensajeNoResultados) {
                     const mensaje = document.createElement('tr');
                     mensaje.id = 'mensaje-no-resultados';
                     mensaje.innerHTML = `
                         <td colspan="5" class="text-center py-4">
                             <i class="fas fa-search fa-2x text-muted mb-2"></i>
                             <h5 class="text-muted">No se encontraron puestos que coincidan con "${busqueda}"</h5>
                             <p class="text-muted">Intenta con otros términos de búsqueda</p>
                         </td>
                     `;
                     tabla.appendChild(mensaje);
                 }
             } else if (mensajeNoResultados) {
                 mensajeNoResultados.remove();
             }
             
             // Actualizar contador de resultados
             actualizarContadorResultados(puestosEncontrados, busqueda);
         }
         
         // Función para limpiar búsqueda
         function limpiarBusqueda() {
             document.getElementById('buscador').value = '';
             filtrarPuestos();
         }
         
         // Función para actualizar contador de resultados
         function actualizarContadorResultados(encontrados, busqueda) {
             const totalPuestos = <?php echo $total_puestos; ?>;
             const contadorElement = document.getElementById('contador-resultados');
             
             if (busqueda !== '') {
                 if (!contadorElement) {
                     const contador = document.createElement('div');
                     contador.id = 'contador-resultados';
                     contador.className = 'alert alert-info mt-3';
                     contador.innerHTML = `
                         <i class="fas fa-info-circle"></i>
                         Se encontraron <strong>${encontrados}</strong> puestos de ${totalPuestos} totales
                     `;
                     document.querySelector('.card-body').appendChild(contador);
                 } else {
                     contadorElement.innerHTML = `
                         <i class="fas fa-info-circle"></i>
                         Se encontraron <strong>${encontrados}</strong> puestos de ${totalPuestos} totales
                     `;
                 }
             } else if (contadorElement) {
                 contadorElement.remove();
             }
         }
         
         // Limpiar búsqueda al cargar la página
         document.addEventListener('DOMContentLoaded', function() {
             document.getElementById('buscador').value = '';
         });
     </script>
<?php require_once '../../includes/footer.php'; ?>
