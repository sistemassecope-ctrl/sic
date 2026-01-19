<?php
// Debug: Verificar que el archivo se está ejecutando
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - empleados.php se está ejecutando\n", FILE_APPEND);

require_once '../../config.php';
require_once '../../includes/functions.php';
require_once '../../includes/user_sync.php';
require_once '../../includes/auth.php';

requireModuleAccess('empleados.php', 'leer');

$pageTitle = 'Empleados - SIC';
$breadcrumb = [
    ['url' => 'empleados.php', 'text' => 'Inicio'],
    ['url' => 'empleados.php', 'text' => 'Empleados']
];

$pdo = conectarDB();

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'agregar':
                file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Caso agregar ejecutándose\n", FILE_APPEND);
                $numero_empleado = $_POST['numero_empleado'];
                $apellido_paterno = $_POST['apellido_paterno'];
                $apellido_materno = $_POST['apellido_materno'];
                $nombres = $_POST['nombres'];
                $email = $_POST['email'];
                $fecha_nacimiento = $_POST['fecha_nacimiento'];
                $genero = $_POST['genero'];
                $direccion = $_POST['direccion'];
                $telefono_celular = $_POST['telefono_celular'];
                $telefono_particular = $_POST['telefono_particular'];
                $estado_nacimiento = $_POST['estado_nacimiento'];
                $puesto_trabajo_id = $_POST['puesto_trabajo_id'] ?: null;
                $dependencia_id = $_POST['dependencia_id'] ?: null;
                $fecha_ingreso = $_POST['fecha_ingreso'] ?: null;
                $salario = $_POST['salario'] ?: 0.00;
                
                // Obtener RFC y CURP del formulario
                $rfc = $_POST['rfc'] ?? '';
                $curp = $_POST['curp'] ?? '';
                
                // Nuevos campos agregados
                $foto = $_POST['foto'] ?? null;
                $categoria = $_POST['categoria'] ?? null;
                $ingreso_oficial = $_POST['ingreso_oficial'] ?? null;
                $sueldo_bruto = $_POST['sueldo_bruto'] ?? 0.00;
                $sueldo_neto = $_POST['sueldo_neto'] ?? 0.00;
                $estatus = $_POST['estatus'] ?? 'Activo';
                $vencimiento = $_POST['vencimiento'] ?? null;
                $horario = $_POST['horario'] ?? null;
                $checador = $_POST['checador'] ?? null;
                $lugar_nacimiento = $_POST['lugar_nacimiento'] ?? null;
                $email_institucional = $_POST['email_institucional'] ?? null;
                $ultimo_grado_estudios = $_POST['ultimo_grado_estudios'] ?? null;
                $profesion = $_POST['profesion'] ?? null;
                $sexo = $_POST['sexo'] ?? null;
                $p01 = $_POST['p01'] ?? null;
                $bono_desempeno = $_POST['bono_desempeno'] ?? 0.00;
                $renta_transporte = $_POST['renta_transporte'] ?? 0.00;
                $otros_ingresos = $_POST['otros_ingresos'] ?? 0.00;
                $isr = $_POST['isr'] ?? 0.00;
                $fondo_pensiones = $_POST['fondo_pensiones'] ?? 0.00;
                $issste = $_POST['issste'] ?? 0.00;
                $seguro_colectivo = $_POST['seguro_colectivo'] ?? 0.00;
                $otros_descuentos = $_POST['otros_descuentos'] ?? 0.00;
                $padre_madre = $_POST['padre_madre'] ?? null;
                $numero_hijos = $_POST['numero_hijos'] ?? 0;
                $edad_hijos = $_POST['edad_hijos'] ?? null;
                $vulnerabilidad = $_POST['vulnerabilidad'] ?? null;
                $motivo = $_POST['motivo'] ?? null;
                $discapacidad = $_POST['discapacidad'] ?? null;
                $identificacion_etnia = $_POST['identificacion_etnia'] ?? null;
                $obligacion_civil_mercantil = $_POST['obligacion_civil_mercantil'] ?? null;
                $nombramiento = $_POST['nombramiento'] ?? null;
                $puesto_finanzas = $_POST['puesto_finanzas'] ?? null;
                $adscripcion_finanzas = $_POST['adscripcion_finanzas'] ?? null;
                
                // Mapear genero al campo sexo para compatibilidad con DB
                $sexo = null;
                if ($genero == 'Hombre') $sexo = 'Masculino';
                if ($genero == 'Mujer') $sexo = 'Femenino';
                
                try {
                    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Iniciando transacción para agregar empleado\n", FILE_APPEND);
                    $pdo->beginTransaction();
                    
                    // Concatenar apellidos para la columna apellido
                    $apellido = trim($apellido_paterno . ' ' . $apellido_materno);
                    
                    // Insert dinámico según columnas existentes en producción
                    $colsStmt = $pdo->query("SHOW COLUMNS FROM empleados");
                    $colsArr = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
                    $colsSet = array_flip($colsArr);

                    // Mapa de posibles columnas -> variables
                    $inputMap = [
                        'numero_empleado' => $numero_empleado,
                        'nombres' => $nombres,
                        'apellido_paterno' => $apellido_paterno,
                        'apellido_materno' => $apellido_materno,
                        'apellido' => $apellido,
                        'email' => $email,
                        'fecha_nacimiento' => $fecha_nacimiento,
                        'genero' => $genero,
                        'sexo' => $sexo,
                        'p01' => $p01,
                        'bono_desempeno' => $bono_desempeno,
                        'renta_transporte' => $renta_transporte,
                        'otros_ingresos' => $otros_ingresos,
                        'isr' => $isr,
                        'fondo_pensiones' => $fondo_pensiones,
                        'issste' => $issste,
                        'seguro_colectivo' => $seguro_colectivo,
                        'otros_descuentos' => $otros_descuentos,
                        'padre_madre' => $padre_madre,
                        'numero_hijos' => $numero_hijos,
                        'edad_hijos' => $edad_hijos,
                        'vulnerabilidad' => $vulnerabilidad,
                        'motivo' => $motivo,
                        'discapacidad' => $discapacidad,
                        'identificacion_etnia' => $identificacion_etnia,
                        'obligacion_civil_mercantil' => $obligacion_civil_mercantil,
                        'nombramiento' => $nombramiento,
                        'puesto_finanzas' => $puesto_finanzas,
                        'adscripcion_finanzas' => $adscripcion_finanzas,
                        'direccion' => $direccion,
                        'telefono_celular' => $telefono_celular,
                        'telefono_particular' => $telefono_particular,
                        'estado_nacimiento' => $estado_nacimiento,
                        'lugar_nacimiento' => $lugar_nacimiento,
                        'rfc' => $rfc,
                        'curp' => $curp,
                        'email_institucional' => $email_institucional,
                        'ultimo_grado_estudios' => $ultimo_grado_estudios,
                        'profesion' => $profesion,
                        'foto' => $foto,
                        'categoria' => $categoria,
                        'puesto_trabajo_id' => $puesto_trabajo_id,
                        'fecha_ingreso' => $fecha_ingreso,
                        'ingreso_oficial' => $ingreso_oficial,
                        'salario' => $salario,
                        'sueldo_bruto' => $sueldo_bruto,
                        'sueldo_neto' => $sueldo_neto,
                        'estatus' => $estatus,
                        'vencimiento' => $vencimiento,
                        'horario' => $horario,
                        'checador' => $checador,
                        'dependencia_id' => $dependencia_id,
                    ];

                    // Orden sugerido para mantener consistencia
                    $preferredOrder = array_keys($inputMap);
                    $insertCols = [];
                    $placeholders = [];
                    $values = [];
                    foreach ($preferredOrder as $col) {
                        if (isset($colsSet[$col])) {
                            $insertCols[] = $col;
                            $placeholders[] = '?';
                            $values[] = $inputMap[$col];
                        }
                    }

                    if (empty($insertCols)) {
                        throw new Exception('No hay columnas coincidentes para insertar en empleados');
                    }

                    $sql = 'INSERT INTO empleados (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $placeholders) . ')';
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($values);
                    
                    $empleado_id = $pdo->lastInsertId();
                    
                    // Confirmar empleado creado
                    $pdo->commit();

                    // Crear usuario automáticamente (no bloquear alta de empleado si falla)
                    try {
                        $datosEmpleado = [
                            'id' => $empleado_id,
                            'numero_empleado' => $numero_empleado,
                            'email' => $email,
                            'email_institucional' => $email_institucional,
                            'nombres' => $nombres,
                            'apellido_paterno' => $apellido_paterno,
                            'apellido_materno' => $apellido_materno,
                        ];

                        $sync = crearUsuarioSistemaParaEmpleado($pdo, $datosEmpleado, [
                            'autor_id' => $_SESSION['user_id'] ?? null,
                        ]);

                        if ($sync['status'] === 'created') {
                            $_SESSION['password_temp'] = $sync['password'];
                            $_SESSION['email_temp'] = $sync['email'];
                            $_SESSION['username_temp'] = $sync['username'];
                        }
                    } catch (Throwable $eUsr) {
                        file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Error creando usuario (no crítico): " . $eUsr->getMessage() . "\n", FILE_APPEND);
                    }
                    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Transacción completada exitosamente\n", FILE_APPEND);
                    
                    // Registrar auditoría
                    logActivity('empleado_agregado', "Empleado agregado: $nombres $apellido_paterno $apellido_materno (ID: $empleado_id)", $_SESSION['user_id']);
                    
                } catch (Exception $e) {
                    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Error en transacción: " . $e->getMessage() . "\n", FILE_APPEND);
                    $pdo->rollBack();
                    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Error al crear empleado y usuario: " . $e->getMessage() . "\n", FILE_APPEND);
                }
                break;
                
            case 'editar':
                $id = $_POST['id'];
                $numero_empleado = $_POST['numero_empleado'];
                $apellido_paterno = $_POST['apellido_paterno'];
                $apellido_materno = $_POST['apellido_materno'];
                $nombres = $_POST['nombres'];
                $email = $_POST['email'];
                $fecha_nacimiento = $_POST['fecha_nacimiento'];
                $genero = $_POST['genero'];
                $direccion = $_POST['direccion'];
                $telefono_celular = $_POST['telefono_celular'];
                $telefono_particular = $_POST['telefono_particular'];
                $estado_nacimiento = $_POST['estado_nacimiento'];
                $puesto_trabajo_id = $_POST['puesto_trabajo_id'] ?: null;
                $dependencia_id = $_POST['dependencia_id'] ?: null;
                $fecha_ingreso = $_POST['fecha_ingreso'] ?: null;
                $salario = $_POST['salario'] ?: 0.00;
                
                // Obtener RFC y CURP del formulario
                $rfc = $_POST['rfc'] ?? '';
                $curp = $_POST['curp'] ?? '';
                
                // Nuevos campos agregados
                $foto = $_POST['foto'] ?? null;
                $categoria = $_POST['categoria'] ?? null;
                $ingreso_oficial = $_POST['ingreso_oficial'] ?? null;
                $sueldo_bruto = $_POST['sueldo_bruto'] ?? 0.00;
                $sueldo_neto = $_POST['sueldo_neto'] ?? 0.00;
                $estatus = $_POST['estatus'] ?? 'Activo';
                $vencimiento = $_POST['vencimiento'] ?? null;
                $horario = $_POST['horario'] ?? null;
                $checador = $_POST['checador'] ?? null;
                $lugar_nacimiento = $_POST['lugar_nacimiento'] ?? null;
                $email_institucional = $_POST['email_institucional'] ?? null;
                $ultimo_grado_estudios = $_POST['ultimo_grado_estudios'] ?? null;
                $profesion = $_POST['profesion'] ?? null;
                $sexo = $_POST['sexo'] ?? null;
                $p01 = $_POST['p01'] ?? null;
                $bono_desempeno = $_POST['bono_desempeno'] ?? 0.00;
                $renta_transporte = $_POST['renta_transporte'] ?? 0.00;
                $otros_ingresos = $_POST['otros_ingresos'] ?? 0.00;
                $isr = $_POST['isr'] ?? 0.00;
                $fondo_pensiones = $_POST['fondo_pensiones'] ?? 0.00;
                $issste = $_POST['issste'] ?? 0.00;
                $seguro_colectivo = $_POST['seguro_colectivo'] ?? 0.00;
                $otros_descuentos = $_POST['otros_descuentos'] ?? 0.00;
                $padre_madre = $_POST['padre_madre'] ?? null;
                $numero_hijos = $_POST['numero_hijos'] ?? 0;
                $edad_hijos = $_POST['edad_hijos'] ?? null;
                $vulnerabilidad = $_POST['vulnerabilidad'] ?? null;
                $motivo = $_POST['motivo'] ?? null;
                $discapacidad = $_POST['discapacidad'] ?? null;
                $identificacion_etnia = $_POST['identificacion_etnia'] ?? null;
                $obligacion_civil_mercantil = $_POST['obligacion_civil_mercantil'] ?? null;
                $nombramiento = $_POST['nombramiento'] ?? null;
                $puesto_finanzas = $_POST['puesto_finanzas'] ?? null;
                $adscripcion_finanzas = $_POST['adscripcion_finanzas'] ?? null;
                
                // Mapear genero al campo sexo para compatibilidad con DB
                $sexo = null;
                if ($genero == 'Hombre') $sexo = 'Masculino';
                if ($genero == 'Mujer') $sexo = 'Femenino';
                
                // Procesar foto
                $nombre_foto = null;
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    try {
                        $nombre_foto = procesarFoto($_FILES['foto']);
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Error al procesar la foto: " . $e->getMessage();
                        header('Location: empleados.php');
                        exit;
                    }
                }
                
                try {
                    $pdo->beginTransaction();
                    
                    // Concatenar apellidos para la columna apellido
                    $apellido = trim($apellido_paterno . ' ' . $apellido_materno);
                    
                    // Usar nombre de foto si existe, de lo contrario la existente
                    $foto_final = $nombre_foto ? $nombre_foto : $foto;
                    
                    // UPDATE dinámico según columnas existentes
                    $colsStmt = $pdo->query("SHOW COLUMNS FROM empleados");
                    $colsArr = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
                    $colsSet = array_flip($colsArr);

                    $fieldMap = [
                        'numero_empleado' => $numero_empleado,
                        'nombres' => $nombres,
                        'apellido_paterno' => $apellido_paterno,
                        'apellido_materno' => $apellido_materno,
                        'apellido' => $apellido,
                        'email' => $email,
                        'fecha_nacimiento' => $fecha_nacimiento,
                        'genero' => $genero,
                        'sexo' => $sexo,
                        'p01' => $p01,
                        'bono_desempeno' => $bono_desempeno,
                        'renta_transporte' => $renta_transporte,
                        'otros_ingresos' => $otros_ingresos,
                        'isr' => $isr,
                        'fondo_pensiones' => $fondo_pensiones,
                        'issste' => $issste,
                        'seguro_colectivo' => $seguro_colectivo,
                        'otros_descuentos' => $otros_descuentos,
                        'padre_madre' => $padre_madre,
                        'numero_hijos' => $numero_hijos,
                        'edad_hijos' => $edad_hijos,
                        'vulnerabilidad' => $vulnerabilidad,
                        'motivo' => $motivo,
                        'discapacidad' => $discapacidad,
                        'identificacion_etnia' => $identificacion_etnia,
                        'obligacion_civil_mercantil' => $obligacion_civil_mercantil,
                        'nombramiento' => $nombramiento,
                        'puesto_finanzas' => $puesto_finanzas,
                        'adscripcion_finanzas' => $adscripcion_finanzas,
                        'direccion' => $direccion,
                        'telefono_celular' => $telefono_celular,
                        'telefono_particular' => $telefono_particular,
                        'estado_nacimiento' => $estado_nacimiento,
                        'lugar_nacimiento' => $lugar_nacimiento,
                        'rfc' => $rfc,
                        'curp' => $curp,
                        'email_institucional' => $email_institucional,
                        'ultimo_grado_estudios' => $ultimo_grado_estudios,
                        'profesion' => $profesion,
                        'foto' => $foto_final,
                        'categoria' => $categoria,
                        'puesto_trabajo_id' => $puesto_trabajo_id,
                        'fecha_ingreso' => $fecha_ingreso,
                        'ingreso_oficial' => $ingreso_oficial,
                        'salario' => $salario,
                        'sueldo_bruto' => $sueldo_bruto,
                        'sueldo_neto' => $sueldo_neto,
                        'estatus' => $estatus,
                        'vencimiento' => $vencimiento,
                        'horario' => $horario,
                        'checador' => $checador,
                        'dependencia_id' => $dependencia_id,
                    ];

                    $sets = [];
                    $params = [];
                    foreach ($fieldMap as $col => $val) {
                        if (isset($colsSet[$col])) { $sets[] = "$col = ?"; $params[] = $val; }
                    }
                    if (empty($sets)) { throw new Exception('No hay columnas para actualizar en empleados'); }
                    $sql = 'UPDATE empleados SET ' . implode(', ', $sets) . ' WHERE id = ?';
                    $params[] = $id;
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    // Sincronizar usuario vinculado al empleado
                    try {
                        $datosEmpleado = [
                            'id' => $id,
                            'numero_empleado' => $numero_empleado,
                            'email' => $email,
                            'email_institucional' => $email_institucional,
                            'nombres' => $nombres,
                            'apellido_paterno' => $apellido_paterno,
                            'apellido_materno' => $apellido_materno,
                        ];

                        $sync = crearUsuarioSistemaParaEmpleado($pdo, $datosEmpleado, [
                            'autor_id' => $_SESSION['user_id'] ?? null,
                        ]);

                        if ($sync['status'] === 'created') {
                            $_SESSION['password_temp'] = $sync['password'];
                            $_SESSION['email_temp'] = $sync['email'];
                            $_SESSION['username_temp'] = $sync['username'];
                        }
                    } catch (Throwable $eSync) {
                        file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Error sincronizando usuario en editar: " . $eSync->getMessage() . "\n", FILE_APPEND);
                    }
                    
                    $pdo->commit();
                    
                    // Registrar auditoría
                    logActivity('empleado_editado', "Empleado editado: ID $id", $_SESSION['user_id']);
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log("Error al actualizar empleado y usuario: " . $e->getMessage());
                }
                break;
                
            case 'eliminar':
                $id = $_POST['id'];
                $sql = "UPDATE empleados SET activo = FALSE WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                
                // Registrar auditoría
                logActivity('empleado_eliminado', "Empleado eliminado: ID $id", $_SESSION['user_id']);
                break;
        }
        file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Redirigiendo a empleados.php\n", FILE_APPEND);
        header('Location: empleados.php');
        exit;
    }
}

// Obtener estadísticas
function procesarFoto($archivo, $nombre_archivo = null) {
    if (!isset($archivo) || $archivo['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Validar tipo de archivo
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($archivo['type'], $tipos_permitidos)) {
        throw new Exception('Tipo de archivo no permitido. Solo se permiten JPG, PNG, GIF y WebP.');
    }
    
    // Validar tamaño (máximo 5MB)
    if ($archivo['size'] > 5 * 1024 * 1024) {
        throw new Exception('El archivo es demasiado grande. Máximo 5MB.');
    }
    
    // Generar nombre único si no se proporciona
    if (!$nombre_archivo) {
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombre_archivo = uniqid('foto_') . '_' . time() . '.' . $extension;
    }
    
    $ruta_destino = '../../fotos_empleados/' . $nombre_archivo;
    
    // Crear directorio si no existe
    if (!is_dir('../../fotos_empleados')) {
        mkdir('../../fotos_empleados', 0755, true);
    }
    
    // Mover archivo
    if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        return $nombre_archivo;
    } else {
        throw new Exception('Error al guardar la imagen.');
    }
}

function eliminarFoto($nombre_archivo) {
    if ($nombre_archivo && file_exists('../../fotos_empleados/' . $nombre_archivo)) {
        unlink('../../fotos_empleados/' . $nombre_archivo);
    }
}

function obtenerEstadisticasEmpleados() {
    global $pdo;
    $stats = [];
    
    // Total empleados
    $sql = "SELECT COUNT(*) as total FROM empleados WHERE activo = TRUE";
    $stmt = $pdo->query($sql);
    $stats['total'] = $stmt->fetch()['total'];
    
    // Por género
    $sql = "SELECT genero, COUNT(*) as cantidad FROM empleados WHERE activo = TRUE GROUP BY genero";
    $stmt = $pdo->query($sql);
    $stats['por_genero'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Por dependencia
            $sql = "SELECT d.nombre, COUNT(*) as cantidad 
            FROM empleados e 
            LEFT JOIN areas d ON e.dependencia_id = d.id 
            WHERE e.activo = TRUE 
            GROUP BY d.nombre 
            ORDER BY cantidad DESC";
    $stmt = $pdo->query($sql);
    $stats['por_dependencia'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $stats;
}

$empleados = obtenerEmpleados(getCondicionAlcance('empleados', 'e.dependencia_id'));
$estadisticas = obtenerEstadisticasEmpleados();
$puestos = obtenerPuestosTrabajo();
$dependencias = obtenerDependenciasSimple();

require_once '../../includes/header.php';

$password_notification = '';
// Mostrar notificación de contraseña temporal si existe
if (isset($_SESSION['password_temp']) && isset($_SESSION['email_temp'])) {
    $usuarioTemporal = $_SESSION['username_temp'] ?? 'No asignado';
    $password_notification = '
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <h5><i class="fas fa-user-plus"></i> Usuario creado exitosamente</h5>
        <p><strong>Usuario:</strong> <code>' . htmlspecialchars($usuarioTemporal) . '</code></p>
        <p><strong>Email:</strong> ' . htmlspecialchars($_SESSION['email_temp']) . '</p>
        <p><strong>Contraseña temporal:</strong> <code>' . htmlspecialchars($_SESSION['password_temp']) . '</code></p>
        <p class="mb-0"><small>El usuario puede cambiar su contraseña desde el módulo de usuarios del sistema.</small></p>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    
    // Limpiar las variables de sesión
    unset($_SESSION['password_temp']);
    unset($_SESSION['email_temp']);
    unset($_SESSION['username_temp']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados - SIC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .search-input-group {
            position: relative;
            margin-bottom: 20px;
        }
        .search-input-group .form-control {
            border-radius: 20px 0 0 20px;
            border: 2px solid #e9ecef;
            padding-left: 15px;
        }
        .search-input-group .btn {
            border-radius: 0 20px 20px 0;
            border: 2px solid #ffc107;
            background: linear-gradient(135deg, #ffc107, #ff8c00);
            color: white;
            border-left: none;
        }
        .search-input-group .btn:hover {
            background: linear-gradient(135deg, #ff8c00, #ffc107);
        }
        .no-results {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }
        .stats-card {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .modal-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }
        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
        }
        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #ff8c00);
            border: none;
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
        }
        
        /* Estilos para los resultados de búsqueda */
        #resultadosPuesto, #resultadosDependencia {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            background: white;
        }
        
        #resultadosPuesto .list-group-item, #resultadosDependencia .list-group-item {
            border: none;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        #resultadosPuesto .list-group-item:hover, #resultadosDependencia .list-group-item:hover {
            background-color: #f8f9fa;
        }
        
        #resultadosPuesto .list-group-item:last-child, #resultadosDependencia .list-group-item:last-child {
            border-bottom: none;
        }
        
        /* Estilos para la foto del empleado */
        .photo-container {
            position: relative;
            display: inline-block;
            margin: 20px 0;
        }
        
        .photo-placeholder {
            width: 150px;
            height: 150px;
            border: 3px dashed #ccc;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .photo-placeholder:hover {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
        
        .photo-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            border-radius: 50%;
        }
        
        .photo-preview {
            position: relative;
            display: inline-block;
        }
        
        .employee-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #007bff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .photo-remove-btn {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        
        /* Estilos para ficha laboral */
        .ficha-laboral {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Modal responsivo en móvil */
        #modalFichaLaboral .modal-dialog {
            margin: 10px;
            max-width: calc(100% - 20px);
        }
        
        #modalFichaLaboral .modal-body {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding: 15px;
        }
        
        @media (max-width: 768px) {
            #modalFichaLaboral .modal-dialog {
                margin: 0;
                max-width: 100%;
                height: 100vh;
            }
            
            #modalFichaLaboral .modal-content {
                height: 100vh;
                border-radius: 0;
                display: flex;
                flex-direction: column;
            }
            
            #modalFichaLaboral .modal-header {
                flex-shrink: 0;
                padding: 15px;
            }
            
            #modalFichaLaboral .modal-body {
                flex: 1;
                overflow-y: auto;
                overflow-x: auto;
                max-height: calc(100vh - 140px);
                padding: 10px;
            }
            
            #modalFichaLaboral .modal-footer {
                flex-shrink: 0;
                padding: 10px;
            }
            
            .ficha-laboral-print {
                padding: 10px;
            }
            
            .ficha-section {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .ficha-section table {
                min-width: 600px;
                width: max-content;
            }
            
            .ficha-label {
                min-width: 120px;
                width: auto;
                white-space: nowrap;
            }
            
            .ficha-value {
                min-width: 100px;
                width: auto;
            }
        }
        
        /* Contenedor para tablas con scroll horizontal en móvil */
        .table-responsive-ficha {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 10px 0;
        }
        
        @media (max-width: 768px) {
            .table-responsive-ficha {
                display: block;
                width: 100%;
            }
        }
        
        .ficha-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 20px;
        }
        
        .ficha-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .ficha-section h5 {
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        
        .ficha-section table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .ficha-row {
            page-break-inside: avoid;
        }
        
        .ficha-row td {
            padding: 8px 12px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }
        
        .ficha-label {
            font-weight: 600;
            color: #495057;
            width: 35%;
            background-color: #f8f9fa;
        }
        
        .ficha-value {
            color: #212529;
            width: 15%;
        }
        
        .ficha-value[colspan="3"] {
            width: 65%;
        }
        
        .ficha-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #667eea;
            margin: 0 auto 20px;
            display: block;
        }
        
        @media (max-width: 480px) {
            .ficha-photo {
                width: 100px;
                height: 100px;
            }
            
            .ficha-section h5 {
                font-size: 1rem;
            }
            
            .ficha-row td {
                padding: 6px 8px;
                font-size: 0.9rem;
            }
            
            .ficha-label {
                font-size: 0.85rem;
            }
            
            .ficha-value {
                font-size: 0.85rem;
            }
        }
        
        .empleado-row:hover {
            background-color: #f8f9fa;
        }
        
        /* Botón de compartir */
        .btn-compartir {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-compartir:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .dropdown-compartir {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-menu-compartir {
            display: none;
            position: absolute;
            right: 0;
            bottom: 100%;
            background: white;
            min-width: 200px;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
            padding: 8px;
            z-index: 1000;
            margin-bottom: 5px;
        }
        
        .dropdown-menu-compartir.show {
            display: block;
        }
        
        .dropdown-menu-compartir a {
            display: block;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.2s;
        }
        
        .dropdown-menu-compartir a:hover {
            background: #f8f9fa;
        }
        
        .dropdown-menu-compartir a i {
            margin-right: 8px;
            width: 20px;
        }
        
        @media (max-width: 768px) {
            .dropdown-menu-compartir {
                right: 0;
                left: auto;
                min-width: 180px;
            }
            
            .btn-compartir {
                font-size: 0.85rem;
                padding: 6px 12px;
            }
        }
        
        /* Estilos para impresión - Nota: La impresión se realiza desde ver_ficha_laboral.php */
        @media print {
            /* Estos estilos son solo como respaldo si se imprime desde el modal */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            @page {
                size: letter portrait;
                margin: 0.5in;
            }
            
            body {
                background: white !important;
            }
            
            .navbar,
            .sidebar,
            .container-fluid > *:not(#modalFichaLaboral),
            .modal-backdrop,
            .modal-header,
            .modal-footer,
            .no-print {
                display: none !important;
            }
            
            #modalFichaLaboral {
                display: block !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .modal-dialog {
                max-width: 100% !important;
                margin: 0 !important;
            }
            
            .modal-content {
                border: none !important;
                box-shadow: none !important;
            }
            
            .modal-body {
                padding: 0 !important;
            }
            
            .ficha-laboral-print {
                display: block !important;
                visibility: visible !important;
            }
            
            .ficha-laboral-print * {
                visibility: visible !important;
            }
            
            .ficha-laboral-print .text-center {
                text-align: center !important;
                border-bottom: 3px solid #000 !important;
                padding-bottom: 15pt !important;
                margin-bottom: 20pt !important;
            }
            
            .ficha-laboral-print h3 {
                font-size: 18pt !important;
                font-weight: bold !important;
                margin-bottom: 10pt !important;
                color: #000 !important;
                page-break-after: avoid !important;
            }
            
            .ficha-laboral-print .text-muted {
                color: #333 !important;
                font-size: 10pt !important;
            }
            
            .ficha-laboral-print .ficha-photo {
                width: 120px !important;
                height: 120px !important;
                border: 3px solid #333 !important;
                margin-bottom: 15pt !important;
                page-break-inside: avoid !important;
                display: block !important;
                visibility: visible !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                max-width: 120px !important;
                max-height: 120px !important;
            }
            
            /* Asegurar que las imágenes se impriman */
            .ficha-laboral-print img {
                display: block !important;
                visibility: visible !important;
                page-break-inside: avoid !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .ficha-section {
                margin-bottom: 25pt !important;
                page-break-inside: avoid !important;
                border: 2px solid #333 !important;
                padding: 15pt !important;
                border-radius: 0 !important;
                background: white !important;
            }
            
            .ficha-section h5 {
                font-size: 14pt !important;
                font-weight: bold !important;
                color: #000 !important;
                border-bottom: 3px solid #000 !important;
                padding-bottom: 8pt !important;
                margin-bottom: 15pt !important;
                margin-top: 0 !important;
                page-break-after: avoid !important;
            }
            
            .ficha-section h5 i {
                margin-right: 6pt !important;
            }
            
            .ficha-section table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin: 0 !important;
                border: 1px solid #ccc !important;
            }
            
            .ficha-section table tr:first-child td {
                border-top: none !important;
            }
            
            .ficha-row {
                page-break-inside: avoid !important;
            }
            
            .ficha-row td {
                padding: 7pt 10pt !important;
                border-bottom: 1px solid #ddd !important;
                border-right: 1px solid #ddd !important;
                vertical-align: top !important;
            }
            
            .ficha-row td:last-child {
                border-right: none !important;
            }
            
            .ficha-label {
                font-weight: 600 !important;
                color: #000 !important;
                width: 35% !important;
                font-size: 10pt !important;
                background-color: #f5f5f5 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .ficha-value {
                color: #000 !important;
                width: 15% !important;
                font-size: 10pt !important;
            }
            
            .ficha-value[colspan="3"] {
                width: 65% !important;
            }
            
            .row {
                margin: 0 !important;
                display: block !important;
            }
            
            .col-md-6 {
                width: 100% !important;
                float: none !important;
                margin-bottom: 15pt !important;
                page-break-inside: avoid !important;
            }
            
            .ficha-laboral-print .border-top {
                border-top: 2px solid #333 !important;
                margin-top: 20pt !important;
                padding-top: 10pt !important;
            }
            
            .ficha-laboral-print small {
                font-size: 9pt !important;
                color: #666 !important;
            }
            
            .mb-4 {
                margin-bottom: 15pt !important;
            }
            
            .mb-2 {
                margin-bottom: 8pt !important;
            }
            
            .mt-4 {
                margin-top: 15pt !important;
            }
            
            .pt-4 {
                padding-top: 15pt !important;
            }
            
            /* Evitar saltos de página en lugares inadecuados */
            .ficha-section:first-child {
                page-break-before: auto !important;
            }
            
            .ficha-row:first-child {
                page-break-before: auto !important;
            }
            
            /* Asegurar que los iconos no se impriman como imágenes rotas */
            .fas, .fa, i {
                font-family: "Font Awesome 6 Free" !important;
            }
            
            @page {
                size: letter portrait;
                margin: 0.5in;
            }
            
            /* Para múltiples páginas */
            .ficha-section {
                orphans: 3;
                widows: 3;
            }
        }
        
        @media (max-width: 768px) {
            .ficha-row {
                flex-direction: column;
            }
            
            .ficha-label {
                min-width: auto;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php if (!empty($password_notification)) { echo $password_notification; } ?>
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-users"></i> Gestión de Empleados</h1>
                    <div>
                        <a href="index.php" class="btn btn-secondary me-2">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                        <a href="puestos_trabajo.php" class="btn btn-warning me-2">
                            <i class="fas fa-briefcase"></i> Puestos
                        </a>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                            <i class="fas fa-plus"></i> Nuevo Empleado
                        </button>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <h3><?php echo $estadisticas['total']; ?></h3>
                                <p class="mb-0">Total Empleados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3><?php echo count(array_filter($estadisticas['por_genero'], function($g) { return $g['genero'] == 'Hombre'; })); ?></h3>
                                <p class="mb-0">Hombres</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h3><?php echo count(array_filter($estadisticas['por_genero'], function($g) { return $g['genero'] == 'Mujer'; })); ?></h3>
                                <p class="mb-0">Mujeres</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3><?php echo count($estadisticas['por_dependencia']); ?></h3>
                                <p class="mb-0">Dependencias</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buscador -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="search-input-group">
                            <div class="input-group">
                                <input type="text" id="buscarEmpleado" class="form-control" placeholder="Buscar empleado por nombre, número, RFC o CURP...">
                                <button class="btn" type="button" onclick="limpiarBusqueda()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div id="contadorResultados" class="text-muted"></div>
                    </div>
                </div>

                <!-- Tabla de empleados -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Número</th>
                                        <th>Nombre</th>
                                        <th>Género</th>
                                        <th>Teléfono</th>
                                        <th>Puesto</th>
                                        <th>Dependencia</th>
                                        <th>Fecha Ingreso</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaEmpleados">
                                    <?php foreach ($empleados as $empleado): ?>
                                    <tr class="empleado-row" 
                                        data-empleado='<?php echo htmlspecialchars(json_encode($empleado), ENT_QUOTES, 'UTF-8'); ?>'
                                        data-nombre="<?php echo strtolower($empleado['nombre_completo']); ?>" 
                                        data-numero="<?php echo strtolower($empleado['numero_empleado']); ?>"
                                        data-rfc="<?php echo strtolower($empleado['rfc'] ?? ''); ?>"
                                        data-curp="<?php echo strtolower($empleado['curp'] ?? ''); ?>"
                                        style="cursor: pointer;">
                                        <td><?php echo htmlspecialchars($empleado['numero_empleado'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($empleado['nombre_completo'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($empleado['genero'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($empleado['telefono_celular'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($empleado['puesto_nombre'] ?? 'Sin asignar'); ?></td>
                                        <td><?php echo htmlspecialchars($empleado['dependencia_nombre'] ?? 'Sin asignar'); ?></td>
                                        <td><?php echo $empleado['fecha_ingreso'] ? date('d/m/Y', strtotime($empleado['fecha_ingreso'])) : 'Sin fecha'; ?></td>
                                        <td class="text-center" onclick="event.stopPropagation();">
                                            <button class="btn btn-sm btn-warning" onclick="editarEmpleado(<?php echo htmlspecialchars(json_encode($empleado), ENT_QUOTES, 'UTF-8'); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="eliminarEmpleado(<?php echo $empleado['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar -->
    <div class="modal fade" id="modalAgregar" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Nuevo Empleado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="agregar">
                        
                        <!-- Foto del Empleado -->
                        <div class="row">
                            <div class="col-12 text-center mb-4">
                                <div class="photo-container">
                                    <div id="photo_preview_container" class="photo-preview" style="display: none;">
                                        <img id="photo_preview" src="" alt="Foto del empleado" class="employee-photo">
                                        <button type="button" class="btn btn-sm btn-danger photo-remove-btn" onclick="removePhoto()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div id="photo_placeholder" class="photo-placeholder">
                                        <i class="fas fa-camera fa-3x text-muted"></i>
                                        <p class="text-muted mt-2">Foto del empleado</p>
                                    </div>
                                    <input type="file" name="foto" id="foto" class="form-control photo-input" accept="image/*" onchange="previewPhoto(this)">
                                    <button type="button" class="btn btn-primary btn-sm mt-2" onclick="document.getElementById('foto').click()">
                                        <i class="fas fa-camera"></i> Seleccionar Foto
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                 <div class="mb-3">
                                     <label class="form-label">Número de Empleado *</label>
                                     <input type="number" name="numero_empleado" class="form-control" required min="1">
                                 </div>
                             </div>
                            <div class="col-md-3">
                                 <div class="mb-3">
                                    <label class="form-label">Apellido Paterno *</label>
                                    <input type="text" name="apellido_paterno" id="apellido_paterno" class="form-control" required 
                                           style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                                 </div>
                             </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Apellido Materno</label>
                                    <input type="text" name="apellido_materno" id="apellido_materno" class="form-control" 
                                           style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Nombre(s) *</label>
                                    <input type="text" name="nombres" id="nombres" class="form-control" required 
                                           style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Correo Electrónico *</label>
                                    <input type="email" name="email" id="email" class="form-control" required 
                                           placeholder="ejemplo@secope.gob.mx">
                                    <small class="text-muted">Se creará automáticamente un usuario del sistema con este correo</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                 <div class="mb-3">
                                     <label class="form-label">Estado de Nacimiento</label>
                                     <select name="estado_nacimiento" id="estado_nacimiento" class="form-control">
                                         <option value="">Seleccionar estado...</option>
                                         <option value="AGUASCALIENTES">Aguascalientes</option>
                                         <option value="BAJA CALIFORNIA">Baja California</option>
                                         <option value="BAJA CALIFORNIA SUR">Baja California Sur</option>
                                         <option value="CAMPECHE">Campeche</option>
                                         <option value="COAHUILA">Coahuila</option>
                                         <option value="COLIMA">Colima</option>
                                         <option value="CHIAPAS">Chiapas</option>
                                         <option value="CHIHUAHUA">Chihuahua</option>
                                         <option value="CIUDAD DE MEXICO">Ciudad de México</option>
                                         <option value="DURANGO">Durango</option>
                                         <option value="GUANAJUATO">Guanajuato</option>
                                         <option value="GUERRERO">Guerrero</option>
                                         <option value="HIDALGO">Hidalgo</option>
                                         <option value="JALISCO">Jalisco</option>
                                         <option value="MEXICO">México</option>
                                         <option value="MICHOACAN">Michoacán</option>
                                         <option value="MORELOS">Morelos</option>
                                         <option value="NAYARIT">Nayarit</option>
                                         <option value="NUEVO LEON">Nuevo León</option>
                                         <option value="OAXACA">Oaxaca</option>
                                         <option value="PUEBLA">Puebla</option>
                                         <option value="QUERETARO">Querétaro</option>
                                         <option value="QUINTANA ROO">Quintana Roo</option>
                                         <option value="SAN LUIS POTOSI">San Luis Potosí</option>
                                         <option value="SINALOA">Sinaloa</option>
                                         <option value="SONORA">Sonora</option>
                                         <option value="TABASCO">Tabasco</option>
                                         <option value="TAMAULIPAS">Tamaulipas</option>
                                         <option value="TLAXCALA">Tlaxcala</option>
                                         <option value="VERACRUZ">Veracruz</option>
                                         <option value="YUCATAN">Yucatán</option>
                                         <option value="ZACATECAS">Zacatecas</option>
                                     </select>
                                 </div>
                             </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Nacimiento *</label>
                                    <input type="date" name="fecha_nacimiento" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Género *</label>
                                    <select name="genero" class="form-control" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="Hombre">Hombre</option>
                                        <option value="Mujer">Mujer</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <textarea name="direccion" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Teléfono Celular</label>
                                    <input type="tel" name="telefono_celular" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Teléfono Particular</label>
                                    <input type="tel" name="telefono_particular" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">RFC</label>
                                    <input type="text" name="rfc" id="rfc" class="form-control" placeholder="Ingresa el RFC">
                                    <small class="text-muted">Ingresa el RFC del empleado</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">CURP</label>
                                    <input type="text" name="curp" id="curp" class="form-control" placeholder="Ingresa el CURP">
                                    <small class="text-muted">Ingresa el CURP del empleado</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Puesto de Trabajo</label>
                                    <div class="input-group">
                                        <input type="text" id="buscarPuesto" class="form-control" placeholder="Buscar puesto..." autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button" onclick="limpiarBusquedaPuesto()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div id="resultadosPuesto" class="list-group mt-2" style="max-height: 200px; overflow-y: auto; display: none; position: absolute; z-index: 1000; width: 100%;">
                                    </div>
                                    <select name="puesto_trabajo_id" id="selectPuesto" class="form-control mt-2">
                                        <option value="">Seleccionar puesto...</option>
                                        <?php foreach ($puestos as $puesto): ?>
                                        <option value="<?php echo $puesto['id']; ?>"><?php echo htmlspecialchars($puesto['nombre'] ?? ''); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Dependencia</label>
                                    <div class="input-group">
                                        <input type="text" id="buscarDependencia" class="form-control" placeholder="Buscar dependencia..." autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button" onclick="limpiarBusquedaDependencia()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div id="resultadosDependencia" class="list-group mt-2" style="max-height: 200px; overflow-y: auto; display: none; position: absolute; z-index: 1000; width: 100%;">
                                    </div>
                                    <select name="dependencia_id" id="selectDependencia" class="form-control mt-2">
                                        <option value="">Seleccionar dependencia...</option>
                                        <?php foreach ($dependencias as $dependencia): ?>
                                        <option value="<?php echo $dependencia['id']; ?>"><?php echo htmlspecialchars($dependencia['nombre'] ?? ''); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Ingreso</label>
                                    <input type="date" name="fecha_ingreso" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Salario</label>
                                    <input type="number" name="salario" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <!-- Nuevos campos agregados -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Categoría</label>
                                    <select name="categoria" class="form-control">
                                        <option value="">Seleccionar...</option>
                                        <option value="Confianza">Confianza</option>
                                        <option value="SINDICALIZADO (Base)">SINDICALIZADO (Base)</option>
                                        <option value="SUPERNUMERARIO (Eventual)">SUPERNUMERARIO (Eventual)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Ingreso Oficial</label>
                                    <input type="date" name="ingreso_oficial" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Estatus</label>
                                    <select name="estatus" class="form-control">
                                        <option value="Activo">Activo</option>
                                        <option value="Inactivo">Inactivo</option>
                                        <option value="Suspendido">Suspendido</option>
                                        <option value="Jubilado">Jubilado</option>
                                        <option value="Fallecido">Fallecido</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Sueldo Bruto</label>
                                    <input type="number" name="sueldo_bruto" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Sueldo Neto</label>
                                    <input type="number" name="sueldo_neto" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Vencimiento</label>
                                    <input type="date" name="vencimiento" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Horario</label>
                                    <input type="text" name="horario" class="form-control" placeholder="Horario de trabajo">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Método de Registro de Asistencia</label>
                                    <select name="checador" class="form-control">
                                        <option value="">Seleccionar...</option>
                                        <option value="CODIGO">CODIGO</option>
                                        <option value="FACIAL">FACIAL</option>
                                        <option value="HUELLA">HUELLA</option>
                                        <option value="OTRO">OTRO</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Lugar de Nacimiento</label>
                                    <input type="text" name="lugar_nacimiento" class="form-control" placeholder="Lugar de nacimiento">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Email Institucional</label>
                                    <input type="email" name="email_institucional" class="form-control" placeholder="Email institucional">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Último Grado de Estudios</label>
                                    <input type="text" name="ultimo_grado_estudios" class="form-control" placeholder="Último grado de estudios">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Profesión</label>
                                    <input type="text" name="profesion" class="form-control" placeholder="Profesión">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">P01</label>
                                    <input type="text" name="p01" class="form-control" placeholder="Sueldo en P01">
                                </div>
                            </div>
                        </div>

                        <!-- Información Salarial -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-primary mb-3"><i class="fas fa-money-bill-wave"></i> Información Salarial</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Bono por Desempeño</label>
                                    <input type="number" name="bono_desempeno" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Renta y Transporte</label>
                                    <input type="number" name="renta_transporte" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Otros Ingresos</label>
                                    <input type="number" name="otros_ingresos" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ISR</label>
                                    <input type="number" name="isr" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Fondo de Pensiones</label>
                                    <input type="number" name="fondo_pensiones" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ISSSTE</label>
                                    <input type="number" name="issste" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Seguro Colectivo</label>
                                    <input type="number" name="seguro_colectivo" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Otros Descuentos</label>
                                    <input type="number" name="otros_descuentos" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <!-- Información Familiar -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-primary mb-3"><i class="fas fa-users"></i> Información Familiar</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Padre/Madre</label>
                                    <input type="text" name="padre_madre" class="form-control" placeholder="Información de padre/madre">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Número de Hijos</label>
                                    <input type="number" name="numero_hijos" class="form-control" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Edad de los Hijos</label>
                                    <input type="text" name="edad_hijos" class="form-control" placeholder="Edades de los hijos">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Vulnerabilidad</label>
                                    <input type="text" name="vulnerabilidad" class="form-control" placeholder="Vulnerabilidad">
                                </div>
                            </div>
                        </div>

                        <!-- Información Adicional -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-primary mb-3"><i class="fas fa-info-circle"></i> Información Adicional</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Motivo</label>
                                    <textarea name="motivo" class="form-control" rows="2" placeholder="Motivo"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Discapacidad</label>
                                    <input type="text" name="discapacidad" class="form-control" placeholder="Discapacidad">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Identificación con Etnia</label>
                                    <input type="text" name="identificacion_etnia" class="form-control" placeholder="Identificación con etnia">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Obligación Civil o Mercantil</label>
                                    <input type="text" name="obligacion_civil_mercantil" class="form-control" placeholder="Obligación civil o mercantil">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombramiento</label>
                                    <input type="text" name="nombramiento" class="form-control" placeholder="Nombramiento">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Puesto Finanzas</label>
                                    <input type="text" name="puesto_finanzas" class="form-control" placeholder="Puesto en finanzas">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Adscripción Finanzas</label>
                                    <input type="text" name="adscripcion_finanzas" class="form-control" placeholder="Adscripción en finanzas">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
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
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Empleado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="editar">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="foto_actual" id="edit_foto_actual" value="">
                        
                        <!-- Foto del Empleado -->
                        <div class="row">
                            <div class="col-12 text-center mb-4">
                                <div class="photo-container">
                                    <div id="edit_photo_preview_container" class="photo-preview" style="display: none;">
                                        <img id="edit_photo_preview" src="" alt="Foto del empleado" class="employee-photo">
                                        <button type="button" class="btn btn-sm btn-danger photo-remove-btn" onclick="removeEditPhoto()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div id="edit_photo_placeholder" class="photo-placeholder">
                                        <i class="fas fa-camera fa-3x text-muted"></i>
                                        <p class="text-muted mt-2">Foto del empleado</p>
                                    </div>
                                    <input type="file" name="foto" id="edit_foto" class="form-control photo-input" accept="image/*" onchange="previewEditPhoto(this)">
                                    <button type="button" class="btn btn-primary btn-sm mt-2" onclick="document.getElementById('edit_foto').click()">
                                        <i class="fas fa-camera"></i> Seleccionar Foto
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                 <div class="mb-3">
                                     <label class="form-label">Número de Empleado *</label>
                                     <input type="number" name="numero_empleado" id="edit_numero_empleado" class="form-control" required min="1">
                                 </div>
                             </div>
                            <div class="col-md-3">
                                 <div class="mb-3">
                                    <label class="form-label">Apellido Paterno *</label>
                                    <input type="text" name="apellido_paterno" id="edit_apellido_paterno" class="form-control" required 
                                           style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                                 </div>
                             </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Apellido Materno</label>
                                    <input type="text" name="apellido_materno" id="edit_apellido_materno" class="form-control" 
                                           style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Nombre(s) *</label>
                                    <input type="text" name="nombres" id="edit_nombres" class="form-control" required 
                                           style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Correo Electrónico *</label>
                                    <input type="email" name="email" id="edit_email" class="form-control" required 
                                           placeholder="ejemplo@secope.gob.mx">
                                    <small class="text-muted">Se actualizará automáticamente el usuario del sistema</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                 <div class="mb-3">
                                     <label class="form-label">Fecha de Nacimiento *</label>
                                     <input type="date" name="fecha_nacimiento" id="edit_fecha_nacimiento" class="form-control" required>
                                 </div>
                             </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Género *</label>
                                    <select name="genero" id="edit_genero" class="form-control" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="Hombre">Hombre</option>
                                        <option value="Mujer">Mujer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                 <div class="mb-3">
                                     <label class="form-label">Estado de Nacimiento</label>
                                     <select name="estado_nacimiento" id="edit_estado_nacimiento" class="form-control">
                                         <option value="">Seleccionar estado...</option>
                                         <option value="AGUASCALIENTES">Aguascalientes</option>
                                         <option value="BAJA CALIFORNIA">Baja California</option>
                                         <option value="BAJA CALIFORNIA SUR">Baja California Sur</option>
                                         <option value="CAMPECHE">Campeche</option>
                                         <option value="COAHUILA">Coahuila</option>
                                         <option value="COLIMA">Colima</option>
                                         <option value="CHIAPAS">Chiapas</option>
                                         <option value="CHIHUAHUA">Chihuahua</option>
                                         <option value="CIUDAD DE MEXICO">Ciudad de México</option>
                                         <option value="DURANGO">Durango</option>
                                         <option value="GUANAJUATO">Guanajuato</option>
                                         <option value="GUERRERO">Guerrero</option>
                                         <option value="HIDALGO">Hidalgo</option>
                                         <option value="JALISCO">Jalisco</option>
                                         <option value="MEXICO">México</option>
                                         <option value="MICHOACAN">Michoacán</option>
                                         <option value="MORELOS">Morelos</option>
                                         <option value="NAYARIT">Nayarit</option>
                                         <option value="NUEVO LEON">Nuevo León</option>
                                         <option value="OAXACA">Oaxaca</option>
                                         <option value="PUEBLA">Puebla</option>
                                         <option value="QUERETARO">Querétaro</option>
                                         <option value="QUINTANA ROO">Quintana Roo</option>
                                         <option value="SAN LUIS POTOSI">San Luis Potosí</option>
                                         <option value="SINALOA">Sinaloa</option>
                                         <option value="SONORA">Sonora</option>
                                         <option value="TABASCO">Tabasco</option>
                                         <option value="TAMAULIPAS">Tamaulipas</option>
                                         <option value="TLAXCALA">Tlaxcala</option>
                                         <option value="VERACRUZ">Veracruz</option>
                                         <option value="YUCATAN">Yucatán</option>
                                         <option value="ZACATECAS">Zacatecas</option>
                                     </select>
                                 </div>
                             </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <textarea name="direccion" id="edit_direccion" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Teléfono Celular</label>
                                    <input type="tel" name="telefono_celular" id="edit_telefono_celular" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Teléfono Particular</label>
                                    <input type="tel" name="telefono_particular" id="edit_telefono_particular" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                             <div class="col-md-4">
                                 <div class="mb-3">
                                     <label class="form-label">RFC</label>
                                     <input type="text" name="rfc" id="edit_rfc" class="form-control" placeholder="Ingresa el RFC">
                                 </div>
                             </div>
                             <div class="col-md-4">
                                 <div class="mb-3">
                                     <label class="form-label">CURP</label>
                                     <input type="text" name="curp" id="edit_curp" class="form-control" placeholder="Ingresa el CURP">
                                 </div>
                             </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Puesto de Trabajo</label>
                                    <select name="puesto_trabajo_id" id="edit_puesto_trabajo_id" class="form-control">
                                        <option value="">Seleccionar puesto...</option>
                                        <?php foreach ($puestos as $puesto): ?>
                                        <option value="<?php echo $puesto['id']; ?>"><?php echo htmlspecialchars($puesto['nombre'] ?? ''); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Dependencia</label>
                                    <select name="dependencia_id" id="edit_dependencia_id" class="form-control">
                                        <option value="">Seleccionar dependencia...</option>
                                        <?php foreach ($dependencias as $dependencia): ?>
                                        <option value="<?php echo $dependencia['id']; ?>"><?php echo htmlspecialchars($dependencia['nombre'] ?? ''); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Ingreso</label>
                                    <input type="date" name="fecha_ingreso" id="edit_fecha_ingreso" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Salario</label>
                                    <input type="number" name="salario" id="edit_salario" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <!-- Nuevos campos agregados -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Categoría</label>
                                    <select name="categoria" id="edit_categoria" class="form-control">
                                        <option value="">Seleccionar...</option>
                                        <option value="Confianza">Confianza</option>
                                        <option value="SINDICALIZADO (Base)">SINDICALIZADO (Base)</option>
                                        <option value="SUPERNUMERARIO (Eventual)">SUPERNUMERARIO (Eventual)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Ingreso Oficial</label>
                                    <input type="date" name="ingreso_oficial" id="edit_ingreso_oficial" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Estatus</label>
                                    <select name="estatus" id="edit_estatus" class="form-control">
                                        <option value="Activo">Activo</option>
                                        <option value="Inactivo">Inactivo</option>
                                        <option value="Suspendido">Suspendido</option>
                                        <option value="Jubilado">Jubilado</option>
                                        <option value="Fallecido">Fallecido</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Sueldo Bruto</label>
                                    <input type="number" name="sueldo_bruto" id="edit_sueldo_bruto" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Sueldo Neto</label>
                                    <input type="number" name="sueldo_neto" id="edit_sueldo_neto" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Vencimiento</label>
                                    <input type="date" name="vencimiento" id="edit_vencimiento" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Horario</label>
                                    <input type="text" name="horario" id="edit_horario" class="form-control" placeholder="Horario de trabajo">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Checador</label>
                                    <select name="checador" id="edit_checador" class="form-control">
                                        <option value="">Seleccionar...</option>
                                        <option value="CODIGO">CODIGO</option>
                                        <option value="FACIAL">FACIAL</option>
                                        <option value="HUELLA">HUELLA</option>
                                        <option value="OTRO">OTRO</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Lugar de Nacimiento</label>
                                    <input type="text" name="lugar_nacimiento" id="edit_lugar_nacimiento" class="form-control" placeholder="Lugar de nacimiento">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Email Institucional</label>
                                    <input type="email" name="email_institucional" id="edit_email_institucional" class="form-control" placeholder="Email institucional">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Último Grado de Estudios</label>
                                    <input type="text" name="ultimo_grado_estudios" id="edit_ultimo_grado_estudios" class="form-control" placeholder="Último grado de estudios">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Profesión</label>
                                    <input type="text" name="profesion" id="edit_profesion" class="form-control" placeholder="Profesión">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">P01</label>
                                    <input type="text" name="p01" id="edit_p01" class="form-control" placeholder="Código P01">
                                </div>
                            </div>
                        </div>

                        <!-- Información Salarial -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-primary mb-3"><i class="fas fa-money-bill-wave"></i> Información Salarial</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Bono por Desempeño</label>
                                    <input type="number" name="bono_desempeno" id="edit_bono_desempeno" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Renta y Transporte</label>
                                    <input type="number" name="renta_transporte" id="edit_renta_transporte" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Otros Ingresos</label>
                                    <input type="number" name="otros_ingresos" id="edit_otros_ingresos" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ISR</label>
                                    <input type="number" name="isr" id="edit_isr" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Fondo de Pensiones</label>
                                    <input type="number" name="fondo_pensiones" id="edit_fondo_pensiones" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ISSSTE</label>
                                    <input type="number" name="issste" id="edit_issste" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Seguro Colectivo</label>
                                    <input type="number" name="seguro_colectivo" id="edit_seguro_colectivo" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Otros Descuentos</label>
                                    <input type="number" name="otros_descuentos" id="edit_otros_descuentos" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <!-- Información Familiar -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-primary mb-3"><i class="fas fa-users"></i> Información Familiar</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Padre/Madre</label>
                                    <input type="text" name="padre_madre" id="edit_padre_madre" class="form-control" placeholder="Información de padre/madre">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Número de Hijos</label>
                                    <input type="number" name="numero_hijos" id="edit_numero_hijos" class="form-control" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Edad de los Hijos</label>
                                    <input type="text" name="edad_hijos" id="edit_edad_hijos" class="form-control" placeholder="Edades de los hijos">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Vulnerabilidad</label>
                                    <input type="text" name="vulnerabilidad" id="edit_vulnerabilidad" class="form-control" placeholder="Vulnerabilidad">
                                </div>
                            </div>
                        </div>

                        <!-- Información Adicional -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-primary mb-3"><i class="fas fa-info-circle"></i> Información Adicional</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Motivo</label>
                                    <textarea name="motivo" id="edit_motivo" class="form-control" rows="2" placeholder="Motivo"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Discapacidad</label>
                                    <input type="text" name="discapacidad" id="edit_discapacidad" class="form-control" placeholder="Discapacidad">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Identificación con Etnia</label>
                                    <input type="text" name="identificacion_etnia" id="edit_identificacion_etnia" class="form-control" placeholder="Identificación con etnia">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Obligación Civil o Mercantil</label>
                                    <input type="text" name="obligacion_civil_mercantil" id="edit_obligacion_civil_mercantil" class="form-control" placeholder="Obligación civil o mercantil">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombramiento</label>
                                    <input type="text" name="nombramiento" id="edit_nombramiento" class="form-control" placeholder="Nombramiento">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Puesto Finanzas</label>
                                    <input type="text" name="puesto_finanzas" id="edit_puesto_finanzas" class="form-control" placeholder="Puesto en finanzas">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Adscripción Finanzas</label>
                                    <input type="text" name="adscripcion_finanzas" id="edit_adscripcion_finanzas" class="form-control" placeholder="Adscripción en finanzas">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ficha Laboral -->
    <div class="modal fade" id="modalFichaLaboral" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content ficha-laboral">
                <div class="modal-header ficha-header">
                    <h4 class="modal-title">
                        <i class="fas fa-id-card"></i> Ficha Laboral del Empleado
                    </h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="fichaContent">
                    <!-- Contenido se llena dinámicamente -->
                </div>
                <div class="modal-footer no-print">
                    <div class="dropdown-compartir">
                        <button type="button" class="btn btn-compartir" onclick="toggleCompartirMenu()">
                            <i class="fas fa-share-alt"></i> Compartir
                        </button>
                        <div class="dropdown-menu-compartir" id="menuCompartir">
                            <a href="#" onclick="compartirPorEmail(event); return false;">
                                <i class="fas fa-envelope"></i> Enviar por Correo
                            </a>
                            <a href="#" onclick="compartirPorWhatsApp(event); return false;">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                            <a href="#" onclick="descargarPDF(event); return false;">
                                <i class="fas fa-file-pdf"></i> Descargar como PDF
                            </a>
                            <a href="#" onclick="copiarEnlace(event); return false;">
                                <i class="fas fa-link"></i> Copiar Enlace
                            </a>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="imprimirFicha()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // URL del sitio desde PHP
        const SITE_URL = '<?php echo defined("SITE_URL") ? SITE_URL : "https://secope.gusati.net"; ?>';
        
        // Debug del formulario y configuración de event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const formAgregar = document.querySelector('#modalAgregar form');
            if (formAgregar) {
                formAgregar.addEventListener('submit', function(e) {
                    console.log('Formulario enviado');
                    console.log('Datos del formulario:', new FormData(this));
                });
            }
            
            // Event listeners para filas de empleados (mostrar ficha laboral)
            document.querySelectorAll('.empleado-row').forEach(row => {
                row.addEventListener('click', function(e) {
                    // No mostrar ficha si se hace click en los botones de acción
                    if (e.target.closest('td.text-center') || e.target.closest('button')) {
                        return;
                    }
                    const empleadoData = this.getAttribute('data-empleado');
                    if (empleadoData) {
                        try {
                            const empleado = JSON.parse(empleadoData);
                            mostrarFichaLaboral(empleado);
                        } catch (e) {
                            console.error('Error al parsear datos del empleado:', e);
                        }
                    }
                });
            });
            
            // Buscador principal de empleados
            const buscarEmpleado = document.getElementById('buscarEmpleado');
            if (buscarEmpleado) {
                buscarEmpleado.addEventListener('input', filtrarEmpleados);
            }
            
            // Event delegation para buscadores en modales (funciona con elementos dinámicos)
            document.addEventListener('input', function(e) {
                if (e.target.id === 'buscarPuesto') {
                    filtrarPuestos();
                } else if (e.target.id === 'buscarDependencia') {
                    filtrarDependencias();
                }
            });
            
            // Ocultar resultados cuando se hace clic fuera
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#buscarPuesto') && !e.target.closest('#resultadosPuesto')) {
                    const resultadosPuesto = document.getElementById('resultadosPuesto');
                    if (resultadosPuesto) {
                        resultadosPuesto.style.display = 'none';
                    }
                }
                if (!e.target.closest('#buscarDependencia') && !e.target.closest('#resultadosDependencia')) {
                    const resultadosDependencia = document.getElementById('resultadosDependencia');
                    if (resultadosDependencia) {
                        resultadosDependencia.style.display = 'none';
                    }
                }
            });
        });
        
        function filtrarEmpleados() {
            const busqueda = document.getElementById('buscarEmpleado').value.toLowerCase();
            const filas = document.querySelectorAll('#tablaEmpleados tr');
            let resultados = 0;
            
            filas.forEach(fila => {
                const nombre = fila.getAttribute('data-nombre') || '';
                const numero = fila.getAttribute('data-numero') || '';
                const rfc = fila.getAttribute('data-rfc') || '';
                const curp = fila.getAttribute('data-curp') || '';
                
                if (nombre.includes(busqueda) || numero.includes(busqueda) || 
                    rfc.includes(busqueda) || curp.includes(busqueda)) {
                    fila.style.display = '';
                    resultados++;
                } else {
                    fila.style.display = 'none';
                }
            });
            
            actualizarContadorResultados(resultados, filas.length);
        }
        
        function limpiarBusqueda() {
            document.getElementById('buscarEmpleado').value = '';
            filtrarEmpleados();
        }
        
        function actualizarContadorResultados(resultados, total) {
            const contador = document.getElementById('contadorResultados');
            if (resultados === 0 && total > 0) {
                contador.innerHTML = '<div class="no-results">No se encontraron empleados que coincidan con la búsqueda</div>';
            } else {
                contador.innerHTML = `Mostrando ${resultados} de ${total} empleados`;
            }
        }
        
        function filtrarPuestos() {
            console.log('filtrarPuestos ejecutándose');
            const busqueda = document.getElementById('buscarPuesto').value.toLowerCase();
            console.log('Búsqueda:', busqueda);
            const resultadosDiv = document.getElementById('resultadosPuesto');
            const select = document.getElementById('selectPuesto');
            
            if (busqueda.length < 2) {
                resultadosDiv.style.display = 'none';
                return;
            }
            
            // Filtrar opciones
            const opciones = Array.from(select.options).filter(opcion => 
                opcion.value !== '' && opcion.textContent.toLowerCase().includes(busqueda)
            );
            console.log('Opciones filtradas:', opciones.length);
            
            if (opciones.length > 0) {
                // Mostrar resultados filtrados
                resultadosDiv.innerHTML = opciones.map(opcion => 
                    `<a href="#" class="list-group-item list-group-item-action" 
                        onclick="seleccionarPuesto('${opcion.value}', '${opcion.textContent.replace(/'/g, "\\'")}')">
                        ${opcion.textContent}
                    </a>`
                ).join('');
                resultadosDiv.style.display = 'block';
            } else {
                resultadosDiv.innerHTML = '<div class="list-group-item text-muted">No se encontraron resultados</div>';
                resultadosDiv.style.display = 'block';
            }
        }
        
        function filtrarDependencias() {
            console.log('filtrarDependencias ejecutándose');
            const busqueda = document.getElementById('buscarDependencia').value.toLowerCase();
            console.log('Búsqueda:', busqueda);
            const resultadosDiv = document.getElementById('resultadosDependencia');
            const select = document.getElementById('selectDependencia');
            
            if (busqueda.length < 2) {
                resultadosDiv.style.display = 'none';
                return;
            }
            
            // Filtrar opciones
            const opciones = Array.from(select.options).filter(opcion => 
                opcion.value !== '' && opcion.textContent.toLowerCase().includes(busqueda)
            );
            console.log('Opciones filtradas:', opciones.length);
            
            if (opciones.length > 0) {
                // Mostrar resultados filtrados
                resultadosDiv.innerHTML = opciones.map(opcion => 
                    `<a href="#" class="list-group-item list-group-item-action" 
                        onclick="seleccionarDependencia('${opcion.value}', '${opcion.textContent.replace(/'/g, "\\'")}')">
                        ${opcion.textContent}
                    </a>`
                ).join('');
                resultadosDiv.style.display = 'block';
            } else {
                resultadosDiv.innerHTML = '<div class="list-group-item text-muted">No se encontraron resultados</div>';
                resultadosDiv.style.display = 'block';
            }
        }
        
        function seleccionarPuesto(id, nombre) {
            document.getElementById('selectPuesto').value = id;
            document.getElementById('buscarPuesto').value = nombre;
            document.getElementById('resultadosPuesto').style.display = 'none';
        }
        
        function seleccionarDependencia(id, nombre) {
            document.getElementById('selectDependencia').value = id;
            document.getElementById('buscarDependencia').value = nombre;
            document.getElementById('resultadosDependencia').style.display = 'none';
        }
        
        function limpiarBusquedaPuesto() {
            document.getElementById('buscarPuesto').value = '';
            document.getElementById('selectPuesto').value = '';
            document.getElementById('resultadosPuesto').style.display = 'none';
        }
        
        function limpiarBusquedaDependencia() {
            document.getElementById('buscarDependencia').value = '';
            document.getElementById('selectDependencia').value = '';
            document.getElementById('resultadosDependencia').style.display = 'none';
        }
        
        // Función para mostrar ficha laboral
        function mostrarFichaLaboral(empleado) {
            const content = document.getElementById('fichaContent');
            const modal = new bootstrap.Modal(document.getElementById('modalFichaLaboral'));
            
            // Función auxiliar para formatear fechas
            function formatDate(dateStr) {
                if (!dateStr || dateStr === '0000-00-00') return 'No especificada';
                const date = new Date(dateStr);
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return `${day}/${month}/${year}`;
            }
            
            // Función auxiliar para formatear moneda
            function formatCurrency(amount) {
                if (!amount || amount === 0) return '$0.00';
                return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(amount);
            }
            
            // Función auxiliar para mostrar valor o "No especificado"
            function showValue(value, defaultValue = 'No especificado') {
                return (value && value !== '' && value !== '0' && value !== 0) ? value : defaultValue;
            }
            
            // Obtener foto del empleado
            const fotoUrl = empleado.foto ? `fotos_empleados/${empleado.foto}` : null;
            let fotoHtml = '';
            if (fotoUrl) {
                // Crear imagen con carga asíncrona para asegurar que esté lista antes de imprimir
                fotoHtml = `<img src="${fotoUrl}" alt="Foto del empleado" class="ficha-photo" style="display: block !important; visibility: visible !important; max-width: 120px; max-height: 120px; border: 3px solid #333; margin: 0 auto 15pt; object-fit: cover;" onerror="this.outerHTML='<div style=\\'width: 120px; height: 120px; border: 3px solid #333; margin: 0 auto 15pt; display: flex; align-items: center; justify-content: center; background: #f5f5f5;\\'><span style=\\'font-size: 10pt; color: #999;\\'>Foto no disponible</span></div>';">`;
            } else {
                fotoHtml = '<div style="width: 120px; height: 120px; border: 3px solid #333; margin: 0 auto 15pt; display: flex; align-items: center; justify-content: center; background: #f5f5f5;"><span style="font-size: 10pt; color: #999;">Sin foto</span></div>';
            }
            
            // Construir HTML de la ficha
            content.innerHTML = `
                <div class="ficha-laboral-print" style="max-width: 100%; overflow-x: auto;">
                    <div class="text-center mb-4">
                        ${fotoHtml}
                        <h3 class="mb-2">${showValue(empleado.nombres)} ${showValue(empleado.apellido_paterno)} ${showValue(empleado.apellido_materno)}</h3>
                        <p class="text-muted mb-0">Número de Empleado: ${showValue(empleado.numero_empleado)}</p>
                    </div>
                    
                    <!-- DATOS PERSONALES -->
                    <div class="ficha-section">
                        <h5><i class="fas fa-user"></i> Datos Personales</h5>
                        <div class="table-responsive-ficha">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr class="ficha-row">
                                    <td class="ficha-label">Nombres:</td>
                                    <td class="ficha-value">${showValue(empleado.nombres)}</td>
                                    <td class="ficha-label">RFC:</td>
                                    <td class="ficha-value">${showValue(empleado.rfc)}</td>
                                </tr>
                                <tr class="ficha-row">
                                    <td class="ficha-label">Apellido Paterno:</td>
                                    <td class="ficha-value">${showValue(empleado.apellido_paterno)}</td>
                                    <td class="ficha-label">CURP:</td>
                                    <td class="ficha-value">${showValue(empleado.curp)}</td>
                                </tr>
                                <tr class="ficha-row">
                                    <td class="ficha-label">Apellido Materno:</td>
                                    <td class="ficha-value">${showValue(empleado.apellido_materno)}</td>
                                    <td class="ficha-label">Teléfono Celular:</td>
                                    <td class="ficha-value">${showValue(empleado.telefono_celular)}</td>
                                </tr>
                                <tr class="ficha-row">
                                    <td class="ficha-label">Fecha de Nacimiento:</td>
                                    <td class="ficha-value">${formatDate(empleado.fecha_nacimiento)}</td>
                                    <td class="ficha-label">Teléfono Particular:</td>
                                    <td class="ficha-value">${showValue(empleado.telefono_particular)}</td>
                                </tr>
                                <tr class="ficha-row">
                                    <td class="ficha-label">Género:</td>
                                    <td class="ficha-value">${showValue(empleado.genero)}</td>
                                    <td class="ficha-label">Email Personal:</td>
                                    <td class="ficha-value">${showValue(empleado.email)}</td>
                                </tr>
                                <tr class="ficha-row">
                                    <td class="ficha-label">Estado de Nacimiento:</td>
                                    <td class="ficha-value">${showValue(empleado.estado_nacimiento)}</td>
                                    <td class="ficha-label">Email Institucional:</td>
                                    <td class="ficha-value">${showValue(empleado.email_institucional)}</td>
                                </tr>
                                <tr class="ficha-row">
                                    <td class="ficha-label">Lugar de Nacimiento:</td>
                                    <td class="ficha-value" colspan="3">${showValue(empleado.lugar_nacimiento)}</td>
                                </tr>
                                <tr class="ficha-row">
                                    <td class="ficha-label">Dirección:</td>
                                    <td class="ficha-value" colspan="3">${showValue(empleado.direccion)}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- DATOS LABORALES -->
                    <div class="ficha-section">
                        <h5><i class="fas fa-briefcase"></i> Datos Laborales</h5>
                        <div class="table-responsive-ficha">
                            <table style="width: 100%; border-collapse: collapse;">
                            <tr class="ficha-row">
                                <td class="ficha-label">Puesto de Trabajo:</td>
                                <td class="ficha-value">${showValue(empleado.puesto_nombre)}</td>
                                <td class="ficha-label">Nombramiento:</td>
                                <td class="ficha-value">${showValue(empleado.nombramiento)}</td>
                            </tr>
                            <tr class="ficha-row">
                                <td class="ficha-label">Dependencia:</td>
                                <td class="ficha-value">${showValue(empleado.dependencia_nombre)}</td>
                                <td class="ficha-label">Horario:</td>
                                <td class="ficha-value">${showValue(empleado.horario)}</td>
                            </tr>
                            <tr class="ficha-row">
                                <td class="ficha-label">Categoría:</td>
                                <td class="ficha-value">${showValue(empleado.categoria)}</td>
                                <td class="ficha-label">Número de Checador:</td>
                                <td class="ficha-value">${showValue(empleado.checador)}</td>
                            </tr>
                            <tr class="ficha-row">
                                <td class="ficha-label">Fecha de Ingreso:</td>
                                <td class="ficha-value">${formatDate(empleado.fecha_ingreso)}</td>
                                <td class="ficha-label">Vencimiento de Contrato:</td>
                                <td class="ficha-value">${formatDate(empleado.vencimiento)}</td>
                            </tr>
                            <tr class="ficha-row">
                                <td class="ficha-label">Ingreso Oficial:</td>
                                <td class="ficha-value">${formatDate(empleado.ingreso_oficial)}</td>
                                <td class="ficha-label">Estatus:</td>
                                <td class="ficha-value">${showValue(empleado.estatus, 'Activo')}</td>
                            </tr>
                            <tr class="ficha-row">
                                <td class="ficha-label">Puesto (Finanzas):</td>
                                <td class="ficha-value">${showValue(empleado.puesto_finanzas)}</td>
                                <td class="ficha-label">Adscripción (Finanzas):</td>
                                <td class="ficha-value">${showValue(empleado.adscripcion_finanzas)}</td>
                            </tr>
                        </table>
                        </div>
                    </div>
                    
                    <!-- DATOS ACADÉMICOS -->
                    <div class="ficha-section">
                        <h5><i class="fas fa-graduation-cap"></i> Datos Académicos</h5>
                        <div class="table-responsive-ficha">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr class="ficha-row">
                                    <td class="ficha-label">Último Grado de Estudios:</td>
                                    <td class="ficha-value">${showValue(empleado.ultimo_grado_estudios)}</td>
                                    <td class="ficha-label">Profesión:</td>
                                    <td class="ficha-value">${showValue(empleado.profesion)}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- DATOS FINANCIEROS -->
                    <div class="ficha-section">
                        <h5><i class="fas fa-dollar-sign"></i> Datos Financieros</h5>
                        <div class="table-responsive-ficha">
                            <table style="width: 100%; border-collapse: collapse;">
                            <tr class="ficha-row">
                                <td class="ficha-label">Salario:</td>
                                <td class="ficha-value">${formatCurrency(empleado.salario)}</td>
                                <td class="ficha-label">ISR:</td>
                                <td class="ficha-value">${formatCurrency(empleado.isr)}</td>
                            </tr>
                            <tr class="ficha-row">
                                <td class="ficha-label">Sueldo Bruto:</td>
                                <td class="ficha-value">${formatCurrency(empleado.sueldo_bruto)}</td>
                                <td class="ficha-label">Fondo de Pensiones:</td>
                                <td class="ficha-value">${formatCurrency(empleado.fondo_pensiones)}</td>
                            </tr>
                            <tr class="ficha-row">
                                <td class="ficha-label">Sueldo Neto:</td>
                                <td class="ficha-value">${formatCurrency(empleado.sueldo_neto)}</td>
                                <td class="ficha-label">ISSSTE:</td>
                                <td class="ficha-value">${formatCurrency(empleado.issste)}</td>
                            </tr>
                            <tr class="ficha-row">
                                <td class="ficha-label">Bono de Desempeño:</td>
                                <td class="ficha-value">${formatCurrency(empleado.bono_desempeno)}</td>
                                <td class="ficha-label">Seguro Colectivo:</td>
                                <td class="ficha-value">${formatCurrency(empleado.seguro_colectivo)}</td>
                            </tr>
                            <tr class="ficha-row">
                                <td class="ficha-label">Renta de Transporte:</td>
                                <td class="ficha-value">${formatCurrency(empleado.renta_transporte)}</td>
                                <td class="ficha-label">Otros Descuentos:</td>
                                <td class="ficha-value">${formatCurrency(empleado.otros_descuentos)}</td>
                            </tr>
                            <tr class="ficha-row">
                                <td class="ficha-label">Otros Ingresos:</td>
                                <td class="ficha-value">${formatCurrency(empleado.otros_ingresos)}</td>
                                <td class="ficha-label">P01:</td>
                                <td class="ficha-value">${showValue(empleado.p01)}</td>
                            </tr>
                        </table>
                        </div>
                    </div>
                    
                    <!-- DATOS FAMILIARES Y SOCIALES -->
                    <div class="ficha-section">
                        <h5><i class="fas fa-users"></i> Datos Familiares y Sociales</h5>
                        <div class="table-responsive-ficha">
                            <table style="width: 100%; border-collapse: collapse;">
                            <tr class="ficha-row">
                                <td class="ficha-label">Padre/Madre:</td>
                                <td class="ficha-value">${showValue(empleado.padre_madre)}</td>
                                <td class="ficha-label">Vulnerabilidad:</td>
                                <td class="ficha-value">${showValue(empleado.vulnerabilidad)}</td>
                            </tr>
                            <tr class="ficha-row">
                                <td class="ficha-label">Número de Hijos:</td>
                                <td class="ficha-value">${showValue(empleado.numero_hijos, '0')}</td>
                                <td class="ficha-label">Discapacidad:</td>
                                <td class="ficha-value">${showValue(empleado.discapacidad)}</td>
                            </tr>
                            <tr class="ficha-row">
                                <td class="ficha-label">Edad de Hijos:</td>
                                <td class="ficha-value">${showValue(empleado.edad_hijos)}</td>
                                <td class="ficha-label">Identificación Étnica:</td>
                                <td class="ficha-value">${showValue(empleado.identificacion_etnia)}</td>
                            </tr>
                            ${empleado.motivo ? `
                            <tr class="ficha-row">
                                <td class="ficha-label">Motivo:</td>
                                <td class="ficha-value" colspan="3">${empleado.motivo}</td>
                            </tr>
                            ` : ''}
                            ${empleado.obligacion_civil_mercantil ? `
                            <tr class="ficha-row">
                                <td class="ficha-label">Obligación Civil/Mercantil:</td>
                                <td class="ficha-value" colspan="3">${empleado.obligacion_civil_mercantil}</td>
                            </tr>
                            ` : ''}
                        </table>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4 pt-4 border-top" style="margin-top: 25pt; padding-top: 15pt; border-top: 2px solid #333;">
                        <p style="margin: 0; font-size: 9pt; color: #666;">
                            <strong>SECRETARÍA DE COMUNICACIONES Y OBRAS PÚBLICAS DEL ESTADO</strong><br>
                            Sistema Integral de Gestión - Ficha Laboral<br>
                            Documento generado el ${new Date().toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' })} a las ${new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}
                        </p>
                    </div>
                </div>
            `;
            
            modal.show();
            
            // Pre-cargar la imagen para asegurar que esté lista al imprimir
            if (fotoUrl) {
                const img = new Image();
                img.src = fotoUrl;
                img.onload = function() {
                    // Imagen cargada, lista para imprimir
                };
                img.onerror = function() {
                    // Si la imagen falla, no hacer nada (ya hay manejo de error en el HTML)
                };
            }
            
            // Guardar datos del empleado para compartir
            window.currentEmpleado = empleado;
            window.currentFichaContent = content.innerHTML;
        }
        
        // Funciones para compartir
        function toggleCompartirMenu() {
            const menu = document.getElementById('menuCompartir');
            menu.classList.toggle('show');
            
            // Cerrar al hacer clic fuera
            document.addEventListener('click', function cerrarMenu(e) {
                if (!e.target.closest('.dropdown-compartir')) {
                    menu.classList.remove('show');
                    document.removeEventListener('click', cerrarMenu);
                }
            });
        }
        
        function compartirPorEmail(e) {
            e.preventDefault();
            const empleado = window.currentEmpleado;
            if (!empleado || !empleado.id) return;
            
            generarTokenFicha(empleado.id).then(token => {
                const nombreCompleto = `${empleado.nombres || ''} ${empleado.apellido_paterno || ''} ${empleado.apellido_materno || ''}`.trim();
                const linkFicha = SITE_URL + '/public/ver_ficha_laboral.php?id=' + empleado.id + '&token=' + token;
                const asunto = encodeURIComponent(`Ficha Laboral - ${nombreCompleto}`);
                const cuerpo = encodeURIComponent(`Estimado/a,\n\nAdjunto encontrarás el enlace seguro para ver la ficha laboral de ${nombreCompleto}.\n\nNúmero de Empleado: ${empleado.numero_empleado || 'N/A'}\n\nEnlace (válido por 7 días): ${linkFicha}\n\nSaludos cordiales.`);
                
                // Intentar abrir el cliente de correo
                window.location.href = `mailto:?subject=${asunto}&body=${cuerpo}`;
                
                mostrarNotificacion('Se abrió tu cliente de correo con el enlace seguro de la ficha.', 'success');
            }).catch(error => {
                console.error('Error generando token:', error);
                console.error('Detalles del error:', error.message);
                mostrarNotificacion(`Error al generar enlace seguro: ${error.message}. Verifica que el archivo generar_token_ficha.php esté en el servidor.`, 'danger');
            });
        }
        
        function compartirPorWhatsApp(e) {
            e.preventDefault();
            const empleado = window.currentEmpleado;
            if (!empleado || !empleado.id) return;
            
            generarTokenFicha(empleado.id).then(token => {
                const nombreCompleto = `${empleado.nombres || ''} ${empleado.apellido_paterno || ''} ${empleado.apellido_materno || ''}`.trim();
                const linkFicha = SITE_URL + '/public/ver_ficha_laboral.php?id=' + empleado.id + '&token=' + token;
                const mensaje = encodeURIComponent(`Ficha Laboral - ${nombreCompleto}\nNúmero de Empleado: ${empleado.numero_empleado || 'N/A'}\n\nVer ficha completa (válido 7 días): ${linkFicha}`);
                
                window.open(`https://wa.me/?text=${mensaje}`, '_blank');
                mostrarNotificacion('Abriendo WhatsApp...', 'success');
            }).catch(error => {
                console.error('Error generando token:', error);
                console.error('Detalles del error:', error.message);
                mostrarNotificacion(`Error al generar enlace seguro: ${error.message}. Verifica que el archivo generar_token_ficha.php esté en el servidor.`, 'danger');
            });
        }
        
        function descargarPDF(e) {
            e.preventDefault();
            const empleado = window.currentEmpleado;
            if (!empleado || !empleado.id) {
                mostrarNotificacion('Error: No se pudo obtener la información del empleado.', 'danger');
                return;
            }
            
            mostrarNotificacion('Abriendo página para guardar como PDF...', 'info');
            
            // Abrir la página de ficha en una nueva ventana
            const url = `public/ver_ficha_laboral.php?id=${empleado.id}`;
            const ventana = window.open(url, '_blank');
            
            if (ventana) {
                // Esperar a que la página cargue y luego mostrar el diálogo de impresión
                ventana.onload = function() {
                    setTimeout(() => {
                        ventana.print();
                        mostrarNotificacion('Usa "Guardar como PDF" en el diálogo de impresión para descargar el PDF.', 'success');
                    }, 500);
                };
            } else {
                mostrarNotificacion('Por favor, permite ventanas emergentes para generar el PDF.', 'warning');
            }
        }
        
        function copiarEnlace(e) {
            e.preventDefault();
            const empleado = window.currentEmpleado;
            if (!empleado || !empleado.id) return;
            
            // Generar token seguro para el enlace
            generarTokenFicha(empleado.id).then(token => {
                const url = SITE_URL + '/public/ver_ficha_laboral.php?id=' + empleado.id + '&token=' + token;
                copiarUrlAlPortapapeles(url);
            }).catch(error => {
                console.error('Error generando token:', error);
                console.error('Detalles del error:', error.message);
                mostrarNotificacion(`Error al generar enlace seguro: ${error.message}. Verifica que el archivo generar_token_ficha.php esté en el servidor.`, 'danger');
            });
        }
        
        function generarTokenFicha(empleadoId) {
            // Llamar al servidor para generar token seguro
            return fetch('api/generar_token_ficha.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin', // Incluir cookies de sesión
                body: JSON.stringify({ empleado_id: empleadoId })
            })
            .then(response => {
                return response.json().then(data => {
                    if (!response.ok) {
                        // Incluir información de debug si está disponible
                        const errorMsg = data.message || `Error HTTP: ${response.status} ${response.statusText}`;
                        const debugInfo = data.debug ? ` (Debug: ${JSON.stringify(data.debug)})` : '';
                        throw new Error(errorMsg + debugInfo);
                    }
                    return data;
                });
            })
            .then(data => {
                if (data.success) {
                    return data.token;
                } else {
                    const debugInfo = data.debug ? ` (Debug: ${JSON.stringify(data.debug)})` : '';
                    throw new Error((data.message || 'Error generando token') + debugInfo);
                }
            })
            .catch(error => {
                console.error('Error en generarTokenFicha:', error);
                throw error;
            });
        }
        
        function copiarUrlAlPortapapeles(url) {
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    mostrarNotificacion('Enlace seguro copiado al portapapeles. El enlace expirará en 7 días.', 'success');
                }).catch(() => {
                    fallbackCopiarEnlace(url);
                });
            } else {
                fallbackCopiarEnlace(url);
            }
        }
        
        function fallbackCopiarEnlace(texto) {
            const textArea = document.createElement('textarea');
            textArea.value = texto;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                mostrarNotificacion('Enlace copiado al portapapeles', 'success');
            } catch (err) {
                mostrarNotificacion('Error al copiar. El enlace es: ' + texto, 'warning');
            }
            document.body.removeChild(textArea);
        }
        
        function imprimirFicha() {
            const empleado = window.currentEmpleado;
            if (!empleado || !empleado.id) {
                alert('Error: No se pudo obtener la información del empleado.');
                return;
            }
            
            // Abrir la página de ficha en una nueva ventana para imprimir
            const url = `public/ver_ficha_laboral.php?id=${empleado.id}`;
            const ventana = window.open(url, '_blank');
            
            if (ventana) {
                // Esperar a que la página cargue y luego imprimir
                ventana.onload = function() {
                    setTimeout(() => {
                        ventana.print();
                    }, 500);
                };
            } else {
                alert('Por favor, permite ventanas emergentes para imprimir la ficha.');
            }
        }
        
        function mostrarNotificacion(mensaje, tipo) {
            // Crear notificación
            const alerta = document.createElement('div');
            alerta.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
            alerta.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alerta.innerHTML = `
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alerta);
            
            // Auto-remover después de 3 segundos
            setTimeout(() => {
                if (alerta.parentNode) {
                    alerta.remove();
                }
            }, 3000);
        }
        
        // Funciones para editar y eliminar
        function editarEmpleado(empleado) {
            document.getElementById('edit_id').value = empleado.id;
            document.getElementById('edit_numero_empleado').value = empleado.numero_empleado;
            document.getElementById('edit_apellido_paterno').value = empleado.apellido_paterno || '';
            document.getElementById('edit_apellido_materno').value = empleado.apellido_materno || '';
            document.getElementById('edit_nombres').value = empleado.nombres || '';
            document.getElementById('edit_email').value = empleado.email || '';
            document.getElementById('edit_fecha_nacimiento').value = empleado.fecha_nacimiento;
            document.getElementById('edit_genero').value = empleado.genero;
            document.getElementById('edit_direccion').value = empleado.direccion;
            document.getElementById('edit_telefono_celular').value = empleado.telefono_celular;
            document.getElementById('edit_telefono_particular').value = empleado.telefono_particular;
            document.getElementById('edit_estado_nacimiento').value = empleado.estado_nacimiento;
            document.getElementById('edit_rfc').value = empleado.rfc;
            document.getElementById('edit_curp').value = empleado.curp;
            document.getElementById('edit_puesto_trabajo_id').value = empleado.puesto_trabajo_id || '';
            document.getElementById('edit_dependencia_id').value = empleado.dependencia_id || '';
            document.getElementById('edit_fecha_ingreso').value = empleado.fecha_ingreso || '';
            document.getElementById('edit_salario').value = empleado.salario || '';
            
            // Nuevos campos
            // Manejar la foto actual
            if (empleado.foto && empleado.foto.trim() !== '') {
                document.getElementById('edit_foto_actual').value = empleado.foto;
                const previewContainer = document.getElementById('edit_photo_preview_container');
                const placeholder = document.getElementById('edit_photo_placeholder');
                const img = document.getElementById('edit_photo_preview');
                
                img.src = 'fotos_empleados/' + empleado.foto;
                previewContainer.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                document.getElementById('edit_photo_preview_container').style.display = 'none';
                document.getElementById('edit_photo_placeholder').style.display = 'flex';
                document.getElementById('edit_foto_actual').value = '';
            }
            document.getElementById('edit_categoria').value = empleado.categoria || '';
            document.getElementById('edit_ingreso_oficial').value = empleado.ingreso_oficial || '';
            document.getElementById('edit_sueldo_bruto').value = empleado.sueldo_bruto || '';
            document.getElementById('edit_sueldo_neto').value = empleado.sueldo_neto || '';
            document.getElementById('edit_estatus').value = empleado.estatus || 'Activo';
            document.getElementById('edit_vencimiento').value = empleado.vencimiento || '';
            document.getElementById('edit_horario').value = empleado.horario || '';
            document.getElementById('edit_checador').value = empleado.checador || '';
            document.getElementById('edit_lugar_nacimiento').value = empleado.lugar_nacimiento || '';
            // fraccionamiento_colonia eliminado, ahora va en direccion
            document.getElementById('edit_email_institucional').value = empleado.email_institucional || '';
            document.getElementById('edit_ultimo_grado_estudios').value = empleado.ultimo_grado_estudios || '';
            document.getElementById('edit_profesion').value = empleado.profesion || '';
            // Sexo ya no es necesario, se mapea desde genero
            document.getElementById('edit_p01').value = empleado.p01 || '';
            
            // Campos de información salarial
            document.getElementById('edit_bono_desempeno').value = empleado.bono_desempeno || '';
            document.getElementById('edit_renta_transporte').value = empleado.renta_transporte || '';
            document.getElementById('edit_otros_ingresos').value = empleado.otros_ingresos || '';
            document.getElementById('edit_isr').value = empleado.isr || '';
            document.getElementById('edit_fondo_pensiones').value = empleado.fondo_pensiones || '';
            document.getElementById('edit_issste').value = empleado.issste || '';
            document.getElementById('edit_seguro_colectivo').value = empleado.seguro_colectivo || '';
            document.getElementById('edit_otros_descuentos').value = empleado.otros_descuentos || '';
            
            // Campos de información familiar
            document.getElementById('edit_padre_madre').value = empleado.padre_madre || '';
            document.getElementById('edit_numero_hijos').value = empleado.numero_hijos || '';
            document.getElementById('edit_edad_hijos').value = empleado.edad_hijos || '';
            document.getElementById('edit_vulnerabilidad').value = empleado.vulnerabilidad || '';
            
            // Campos de información adicional
            document.getElementById('edit_motivo').value = empleado.motivo || '';
            document.getElementById('edit_discapacidad').value = empleado.discapacidad || '';
            document.getElementById('edit_identificacion_etnia').value = empleado.identificacion_etnia || '';
            document.getElementById('edit_obligacion_civil_mercantil').value = empleado.obligacion_civil_mercantil || '';
            document.getElementById('edit_nombramiento').value = empleado.nombramiento || '';
            document.getElementById('edit_puesto_finanzas').value = empleado.puesto_finanzas || '';
            document.getElementById('edit_adscripcion_finanzas').value = empleado.adscripcion_finanzas || '';
            
            new bootstrap.Modal(document.getElementById('modalEditar')).show();
        }
        
        function previewPhoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewContainer = document.getElementById('photo_preview_container');
                    const placeholder = document.getElementById('photo_placeholder');
                    const img = document.getElementById('photo_preview');
                    
                    img.src = e.target.result;
                    previewContainer.style.display = 'block';
                    placeholder.style.display = 'none';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function removePhoto() {
            document.getElementById('foto').value = '';
            document.getElementById('photo_preview_container').style.display = 'none';
            document.getElementById('photo_placeholder').style.display = 'flex';
        }
        
        function previewEditPhoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewContainer = document.getElementById('edit_photo_preview_container');
                    const placeholder = document.getElementById('edit_photo_placeholder');
                    const img = document.getElementById('edit_photo_preview');
                    
                    img.src = e.target.result;
                    previewContainer.style.display = 'block';
                    placeholder.style.display = 'none';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function removeEditPhoto() {
            document.getElementById('edit_foto').value = '';
            document.getElementById('edit_photo_preview_container').style.display = 'none';
            document.getElementById('edit_photo_placeholder').style.display = 'flex';
        }
        
        function eliminarEmpleado(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este empleado?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Inicializar contador
        actualizarContadorResultados(<?php echo count($empleados); ?>, <?php echo count($empleados); ?>);
        
        // Protección contra pérdida de datos al cerrar el modal
        let formModified = false;
        
        // Detectar cambios en los formularios
        document.addEventListener('DOMContentLoaded', function() {
            // Formulario de agregar
            const formAgregar = document.querySelector('#modalAgregar form');
            if (formAgregar) {
                const inputs = formAgregar.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.addEventListener('change', function() {
                        formModified = true;
                    });
                    input.addEventListener('input', function() {
                        formModified = true;
                    });
                });
                
                // Resetear cuando se envía el formulario
                formAgregar.addEventListener('submit', function() {
                    formModified = false;
                });
            }
            
            // Formulario de editar
            const formEditar = document.querySelector('#modalEditar form');
            if (formEditar) {
                const inputsEditar = formEditar.querySelectorAll('input, select, textarea');
                inputsEditar.forEach(input => {
                    input.addEventListener('change', function() {
                        formModified = true;
                    });
                    input.addEventListener('input', function() {
                        formModified = true;
                    });
                });
                
                // Resetear cuando se envía el formulario
                formEditar.addEventListener('submit', function() {
                    formModified = false;
                });
            }
            
            // Interceptar cierre del modal
            const modalAgregar = document.getElementById('modalAgregar');
            if (modalAgregar) {
                modalAgregar.addEventListener('hide.bs.modal', function(event) {
                    if (formModified) {
                        if (!confirm('¿Estás seguro de cerrar el formulario? Los datos no guardados se perderán.')) {
                            event.preventDefault();
                            event.stopPropagation();
                        } else {
                            formModified = false;
                        }
                    }
                });
            }
            
            const modalEditar = document.getElementById('modalEditar');
            if (modalEditar) {
                modalEditar.addEventListener('hide.bs.modal', function(event) {
                    if (formModified) {
                        if (!confirm('¿Estás seguro de cerrar el formulario? Los cambios no guardados se perderán.')) {
                            event.preventDefault();
                            event.stopPropagation();
                        } else {
                            formModified = false;
                        }
                    }
                });
            }
        });
    </script>
<?php require_once 'includes/footer.php'; ?>
