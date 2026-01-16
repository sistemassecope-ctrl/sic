<?php
require_once 'config/db.php';

echo "--------------------------------\n";
echo "MySQL Authentication Plugin Check\n";
echo "--------------------------------\n";

try {
    $db = (new Database())->getConnection();

    // 1. Check Default Plugin (Skipped for compatibility with MySQL 8.4+)
    // $stmt = $db->query("SELECT @@default_authentication_plugin as default_plugin");

    // 2. Check Current User Plugin
    // We need to know who we are logged in as
    $stmtUser = $db->query("SELECT CURRENT_USER()");
    $currentUser = $stmtUser->fetchColumn(); // e.g., root@localhost
    echo "Current Logged User: " . $currentUser . "\n";

    // 3. Try to get specific plugin for this user from mysql.user
    // Note: split user and host
    $parts = explode('@', $currentUser);
    $u = $parts[0];
    $h = $parts[1] ?? '%';

    $stmtPlugin = $db->prepare("SELECT plugin FROM mysql.user WHERE user = ? AND host = ?");
    $stmtPlugin->execute([$u, $h]);
    $plugin = $stmtPlugin->fetchColumn();

    if ($plugin) {
        echo "User Plugin: " . $plugin . "\n";
    } else {
        echo "User Plugin: [No se pudo determinar o permisos insuficientes]\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "--------------------------------\n";
?>