<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// Simulate auth if needed or just get DB
$pdo = getConnection();
session_start();
$userId = $_SESSION['user_id'] ?? 0;

echo "Usuario Logueado ID: " . $userId . "\n";

echo "\n--- VERIFICANDO DOCUMENTOS RECIENTES ---\n";
$docs = $pdo->query("SELECT id, folio_sistema, titulo, fase_actual, estatus, contenido_json FROM documentos ORDER BY id DESC LIMIT 5")->fetchAll();
print_r($docs);

if (!empty($docs)) {
    $lastDocId = $docs[0]['id'];
    echo "\n--- FLUJO DE FIRMAS PARA DOC ID $lastDocId ---\n";
    $flujos = $pdo->query("SELECT * FROM documento_flujo_firmas WHERE documento_id = $lastDocId")->fetchAll();
    print_r($flujos);

    echo "\n--- FIRMANTE ESPERADO ---\n";
    foreach ($flujos as $f) {
        if ($f['estatus'] == 'pendiente') {
            echo "Pendiente por firmar: Usuario ID {$f['firmante_id']} (Rol: {$f['rol_firmante']})\n";
            if ($f['firmante_id'] == $userId) {
                echo "✅ ¡COINCIDE con tu usuario! Deberías ver el botón.\n";
            } else {
                echo "❌ NO COINCIDE. Tú eres ID $userId.\n";
            }
        }
    }
}
