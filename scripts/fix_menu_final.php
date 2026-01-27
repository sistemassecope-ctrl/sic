<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

echo "--- CORRIGIENDO MENÚS ---\n";

// 1. Verificar y Desactivar ID 26 (Recursos Humanos > Áreas)
$stmt26 = $pdo->query("SELECT modulos.*, p.nombre_modulo as nombre_padre FROM modulos LEFT JOIN modulos p ON modulos.id_padre = p.id WHERE modulos.id = 26");
$mod26 = $stmt26->fetch();

if ($mod26) {
    echo "ID 26: {$mod26['nombre_modulo']} (Padre: {$mod26['nombre_padre']})\n";
    if (stripos($mod26['nombre_padre'], 'Humanos') !== false) {
        $pdo->prepare("UPDATE modulos SET estado = 0 WHERE id = 26")->execute();
        echo "✅ ID 26 Desactivado (Eliminado de RH).\n";
    } else {
        echo "⚠️ ALERTA: ID 26 no parece ser de RH. Padre es '{$mod26['nombre_padre']}'. NO SE TOCÓ.\n";
    }
} else {
    echo "❌ No se encontró ID 26.\n";
}

// 2. Asegurar ID 32 Activo (Administración > Áreas)
$stmt32 = $pdo->query("SELECT modulos.*, p.nombre_modulo as nombre_padre FROM modulos LEFT JOIN modulos p ON modulos.id_padre = p.id WHERE modulos.id = 32");
$mod32 = $stmt32->fetch();

if ($mod32) {
    echo "ID 32: {$mod32['nombre_modulo']} (Padre: {$mod32['nombre_padre']})\n";
    if ($mod32['estado'] == 0) {
        $pdo->prepare("UPDATE modulos SET estado = 1 WHERE id = 32")->execute();
        echo "✅ ID 32 Reactivado (Restaurado en Administración).\n";
    } else {
        echo "ℹ️ ID 32 ya está activo.\n";
    }
}
