<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();
$email = 'mario.cardiel@durango.gob.mx';
$s = $pdo->query("DESCRIBE usuarios_sistema");
print_r($s->fetchAll(PDO::FETCH_COLUMN));

$users = $pdo->query("SELECT * FROM usuarios_sistema")->fetchAll(PDO::FETCH_ASSOC);
foreach($users as $u) {
    if (strpos($u['usuario'], 'mario') !== false || strpos($u['email'] ?? '', 'mario') !== false) {
        echo "Found: " . json_encode($u) . "\n";
        $uid = $u['id'];
        $stmtP = $pdo->prepare("SELECT * FROM usuario_modulo_permisos WHERE id_usuario = ? AND id_modulo = 46");
        $stmtP->execute([$uid]);
        print_r($stmtP->fetchAll(PDO::FETCH_ASSOC));
    }
}
