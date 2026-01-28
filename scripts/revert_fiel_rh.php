<?php
/**
 * Script de Reversión para la Integración de FIEL en Recursos Humanos
 * Ejecutar para deshacer cambios en base de datos y archivos.
 */

require_once __DIR__ . '/../config/database.php';
$db = getConnection();

echo "Iniciando reversión de integración FIEL...\n";

// 1. Revertir Cambios en Base de Datos
try {
    $db->exec("ALTER TABLE empleado_firmas 
               DROP COLUMN fiel_certificado_base64,
               DROP COLUMN fiel_password_hash,
               DROP COLUMN fiel_pin_hash,
               DROP COLUMN fiel_vencimiento,
               DROP COLUMN fiel_rfc,
               DROP COLUMN fiel_nombre,
               DROP COLUMN fiel_serie,
               DROP COLUMN fiel_estado");
    echo "[OK] Columnas de FIEL eliminadas de la tabla 'empleado_firmas'\n";
} catch (Exception $e) {
    echo "[INFO] Las columnas ya no existían o error: " . $e->getMessage() . "\n";
}

// 2. Restaurar Archivo de Formulario
$formFile = __DIR__ . '/../modulos/recursos-humanos/empleado-form.php';
$bakFile = $formFile . '.bak';

if (file_exists($bakFile)) {
    if (copy($bakFile, $formFile)) {
        echo "[OK] Archivo 'empleado-form.php' restaurado desde el backup.\n";
    } else {
        echo "[ERROR] No se pudo restaurar el archivo 'empleado-form.php'.\n";
    }
} else {
    echo "[WARNING] No se encontró el archivo backup (.bak).\n";
}

// 3. Eliminar API nueva
$apiFile = __DIR__ . '/../modulos/recursos-humanos/api/guardar-fiel.php';
if (file_exists($apiFile)) {
    unlink($apiFile);
    echo "[OK] API 'guardar-fiel.php' eliminada.\n";
}

echo "Reversión completada.\n";
