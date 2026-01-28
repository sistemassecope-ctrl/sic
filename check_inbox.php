<?php
require_once __DIR__ . '/config/database.php';
$pdo = conectarDB();

echo "--- BANDEJA DEL USUARIO 1 (ADMIN) ---\n";
// Check if user 1 has any pending documents in the inbox table
$bandeja = $pdo->query("SELECT * FROM usuario_bandeja_documentos WHERE usuario_id = 1")->fetchAll();
print_r($bandeja);

echo "\n--- FLUJOS PENDIENTES PARA USUARIO 1 ---\n";
$flujos = $pdo->query("SELECT * FROM documento_flujo_firmas WHERE firmante_id = 1 AND estatus = 'pendiente'")->fetchAll();
print_r($flujos);
