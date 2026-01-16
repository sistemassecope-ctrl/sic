<?php
include("proteger.php");
include("conexion.php");
$rfc = $_SESSION['rfc'];
$tipo_persona = (strlen($rfc) === 12) ? 'moral' : 'fisica';

// Definir los requisitos según tipo de persona
if ($tipo_persona === 'fisica') {
    $requisitos = [
        1 => "Solicitud de inscripción por escrito en original y firma autógrafa dirigida a la C. Arq. Ana Rosa Hernández Rentería, Secretaria de SECOPE",
        2 => "Copia comprobante de domicilio fiscal actualizado. En caso de tener domicilio fiscal fuera de la ciudad de Durango, se tendrá que presentar oficio donde manifieste un domicilio en esta capital para oír y recibir notificaciones",
        3 => "Hoja de registro al padrón de contratistas",
        4 => "Acta de nacimiento copia simple y original",
        5 => "Identificación de la persona física",
        6 => "Constancia de situación fiscal reciente",
        7 => "Declaración anual fiscal con acuse de recibo y sello digital",
        8 => "Estados financieros en original con firma autógrafa",
        9 => "Registro actualizado de la Cámara Mexicana de la Industria de la Construcción",
        10 => "Curriculum personal en original y firma autógrafa del interesado y del responsable Técnico",
        11 => "Registro del Instituto Mexicano del Seguro Social e Instituto del Fondo Nacional para la Vivienda de los Trabajadores",
        12 => "Relación de contratos en original y firma autógrafa de “obras y/o servicios en vigor”",
        13 => "Constancia de validación de las dependencias y/o empresas con las que haya trabajado el año anterior a la solicitud",
        14 => "Relación de maquinaria y equipo en original y firma autógrafa acompañada del soporte que acredite la propiedad de la misma",
        15 => "Declaración escrita y bajo protesta de decir verdad de no encontrarse en los supuestos del artículo 63 de la Ley de Obra Pública",
        16 => "Declaración por escrito y bajo protesta de decir verdad que ha presentado en tiempo y forma las declaraciones del ejercicio por impuestos federales",
        17 => "Manifestación por escrito si pertenece o no, a algún colegio, cámara o asociación relacionada con la construcción",
        18 => "Escrito en donde manifieste bajo protesta de decir verdad, que la documentación e información proporcionada por el interesado es veraz"
    ];
} else {
    $requisitos = [
        1 => "Solicitud de inscripción o refrendo por escrito dirigida a la C. Arq. Ana Rosa Hernández Rentería, Secretaria de la SECOPE",
        2 => "Copia comprobante de domicilio fiscal actualizado (más oficio si es foráneo para oír y recibir notificaciones)",
        3 => "Hoja de registro al padrón de contratistas, disponible en el Departamento de Concursos y Contratos",
        4 => "Acta constitutiva y modificaciones en su caso, copia simple y original para su cotejo",
        5 => "Identificación del representante legal, copia simple y original para su cotejo",
        6 => "Constancia de situación fiscal reciente (si hay suspensión y reactivación)",
        7 => "Declaración anual fiscal con acuse de recibo ante el SAT conteniendo su sello digital",
        8 => "Estados financieros (balance y estado de resultados) en original con firma autógrafa y copia de cédula del contador",
        9 => "Registro actualizado de la C.M.I.C. (opcional) copia simple y original para su cotejo",
        10 => "Currículum personal en original y firma autógrafa del interesado y del responsable técnico (con soporte y cédula)",
        11 => "Registro del IMSS e INFONAVIT (último pago realizado del INFONAVIT) copia simple y original para su cotejo",
        12 => "Relación de contratos en original y firma autógrafa de “obras y/o servicios en vigor” (anexar copias)",
        13 => "Constancia de validación de las dependencias y/o empresas con las que haya trabajado en los dos años anteriores",
        14 => "Relación de maquinaria y equipo en original con fotografías y el soporte que acredite la propiedad",
        15 => "Alta ante el Registro Estatal de Contribuyentes",
        16 => "Declaración escrita y bajo protesta de decir verdad de no encontrarse en los supuestos del artículo 63 de la Ley de Obra Pública",
        17 => "Escrito y bajo protesta de decir verdad que ha presentado en tiempo y forma las declaraciones de impuestos federales",
        18 => "Manifestación por escrito si pertenece a algún colegio, cámara o asociación relacionada con la construcción",
        19 => "Escrito donde manifieste que la documentación e información proporcionada es veraz, deslindando de responsabilidad a la dependencia"
    ];
}

$mensaje = '';
$tipo_alerta = '';

// Detectar si el post excede post_max_size (en ese caso $_POST y $_FILES están vacíos)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $mensaje = "El archivo es demasiado grande para ser procesado por el servidor (excede post_max_size). Intenta con un archivo más pequeño.";
    $tipo_alerta = "danger";
}

// Procesar subida de archivos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['documento'])) {
    $req_id = (int)$_POST['requisito_id'];
    
    if (isset($requisitos[$req_id])) {
        $file = $_FILES['documento'];
        
        // Manejar errores de subida de PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE   => "El archivo excede el límite permitido por el servidor (upload_max_filesize).",
                UPLOAD_ERR_FORM_SIZE  => "El archivo excede el límite permitido por el formulario.",
                UPLOAD_ERR_PARTIAL    => "El archivo se subió parcialmente.",
                UPLOAD_ERR_NO_FILE    => "No se seleccionó ningún archivo.",
                UPLOAD_ERR_NO_TMP_DIR => "Falta la carpeta temporal en el servidor.",
                UPLOAD_ERR_CANT_WRITE => "Error al escribir el archivo en el disco.",
                UPLOAD_ERR_EXTENSION  => "Una extensión de PHP detuvo la subida."
            ];
            $mensaje = "Error de subida: " . ($errors[$file['error']] ?? "Desconocido.");
            $tipo_alerta = "danger";
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            
            if (in_array($ext, $allowed)) {
                if ($file['size'] <= 10 * 1024 * 1024) { 
                    $upload_dir = __DIR__ . '/uploads/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    
                    $new_name = $rfc . '_REQ' . $req_id . '_' . time() . '.' . $ext;
                    $dest_path = $upload_dir . $new_name;
                    
                    if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                        $stmt = $conexion->prepare("INSERT INTO documentos_padron (rfc, requisito_id, nombre_archivo) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nombre_archivo = ?, fecha_subida = NOW()");
                        $stmt->bind_param("siss", $rfc, $req_id, $new_name, $new_name);
                        
                        if ($stmt->execute()) {
                            $mensaje = "Documento $req_id cargado correctamente.";
                            $tipo_alerta = "success";
                        } else {
                            $mensaje = "Error al guardar en base de datos: " . $stmt->error;
                            $tipo_alerta = "danger";
                        }
                    } else {
                        $mensaje = "Error al mover el archivo al servidor.";
                        $tipo_alerta = "danger";
                    }
                } else {
                    $mensaje = "El archivo excede el tamaño máximo permitido (10MB).";
                    $tipo_alerta = "warning";
                }
            } else {
                $mensaje = "Formato no válido. Solo se permiten PDF, JPG, PNG.";
                $tipo_alerta = "warning";
            }
        }
    }
}

// Obtener documentos ya subidos
$docs_subidos = [];
$stmt = $conexion->prepare("SELECT requisito_id, nombre_archivo, fecha_subida FROM documentos_padron WHERE rfc = ?");
$stmt->bind_param("s", $rfc);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $docs_subidos[$row['requisito_id']] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga de Documentación - Padrón de Contratistas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .req-item { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 15px; padding: 15px; }
        .status-icon { font-size: 1.2rem; }
        .text-done { color: #198754; }
        .text-pending { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary"><i class="fas fa-folder-open me-2"></i><?php echo $tipo_persona === 'moral' ? 'Requisitos Persona Moral' : 'Requisitos Persona Física'; ?></h2>
            <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Volver al Dashboard</a>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_alerta; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Formatos permitidos: <strong>PDF, JPG, PNG</strong>. Tamaño máximo: <strong>10MB</strong> por archivo.
            La información se guarda automáticamente al subir cada documento.
        </div>

        <div class="row">
            <?php foreach ($requisitos as $id => $descripcion): ?>
                <?php 
                    $subido = isset($docs_subidos[$id]); 
                    $archivo = $subido ? $docs_subidos[$id]['nombre_archivo'] : '';
                    $fecha = $subido ? date('d/m/Y H:i', strtotime($docs_subidos[$id]['fecha_subida'])) : '';
                ?>
                <div class="col-md-12">
                    <div class="req-item d-flex align-items-center justify-content-between flex-wrap">
                        <div class="flex-grow-1 pe-3">
                            <h5 class="mb-1">
                                <span class="badge bg-secondary me-2"><?php echo $id; ?></span>
                                <?php echo htmlspecialchars($descripcion); ?>
                            </h5>
                            <?php if ($subido): ?>
                                <small class="text-success"><i class="fas fa-check-circle me-1"></i>Subido el: <?php echo $fecha; ?></small>
                            <?php else: ?>
                                <small class="text-muted"><i class="fas fa-times-circle me-1"></i>Pendiente</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                            <?php if ($subido): ?>
                                <a href="uploads/<?php echo $archivo; ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Ver documento">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            <?php endif; ?>
                            
                            <form action="" method="POST" enctype="multipart/form-data" class="d-flex align-items-center">
                                <input type="hidden" name="requisito_id" value="<?php echo $id; ?>">
                                <label class="btn btn-sm btn-<?php echo $subido ? 'warning' : 'primary'; ?> text-white" style="cursor: pointer;">
                                    <i class="fas fa-upload me-1"></i> <?php echo $subido ? 'Actualizar' : 'Subir'; ?>
                                    <input type="file" name="documento" hidden onchange="this.form.submit()">
                                </label>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
