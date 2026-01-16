<?php
try {
    // Conexión como root para permisos elevados si es necesario
    // Intentaremos usar la conexión definida en config/db.php
    require_once 'config/db.php';

    // Nota: Normalmente se necesita un usuario administrador (root) para hacer ALTER USER
    // Si 'sic_test' no tiene GRANT OPTION, esto fallará.
    // Asumiremos que tenemos acceso root temporal o que db.php tiene credenciales privilegiadas.
    // Si falla, pediremos credenciales root.

    // OVERRIDE TEMPORAL: Usar credenciales ROOT por defecto de WampServer (root / sin password) 
    // para asegurar que podemos alterar usuarios.
    $dsn = "mysql:host=127.0.0.1;charset=utf8mb4";
    $rootUser = 'root';
    $rootPass = '';

    $pdo = new PDO($dsn, $rootUser, $rootPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "Conectado como ROOT.\n";

    // 1. Actualizar para localhost
    // Verificar si existe primero para evitar error 1396
    $stmtLocal = $pdo->query("SELECT user FROM mysql.user WHERE user='sic_test' AND host='localhost'");
    if ($stmtLocal->fetch()) {
        $sqlLocal = "ALTER USER 'sic_test'@'localhost' IDENTIFIED WITH caching_sha2_password BY 'sic_test.2025';";
        $pdo->exec($sqlLocal);
        echo "[OK] Usuario 'sic_test'@'localhost' actualizado.\n";
    } else {
        echo "[INFO] Usuario 'sic_test'@'localhost' no existe. Saltando.\n";
    }

    // 2. Verificar/Crear/Actualizar para acceso remoto (%)
    // Primero verificamos si existe
    $stmt = $pdo->query("SELECT user, host FROM mysql.user WHERE user='sic_test' AND host='%'");
    if ($stmt->fetch()) {
        // Existe, actualizamos
        $sqlRemote = "ALTER USER 'sic_test'@'%' IDENTIFIED WITH caching_sha2_password BY 'sic_test.2025';";
        $pdo->exec($sqlRemote);
        echo "[OK] Usuario 'sic_test'@'%' actualizado a caching_sha2_password.\n";
    } else {
        // No existe, lo creamos para permitir acceso remoto
        $sqlCreate = "CREATE USER 'sic_test'@'%' IDENTIFIED WITH caching_sha2_password BY 'sic_test.2025';";
        $pdo->exec($sqlCreate);
        // Otorgar permisos
        $pdo->exec("GRANT ALL PRIVILEGES ON sic.* TO 'sic_test'@'%';");
        echo "[OK] Usuario 'sic_test'@'%' creado con permisos remotos.\n";
    }

    $pdo->exec("FLUSH PRIVILEGES;");
    echo "Privilegios recargados.\n";
    echo "LISTO: El servidor ahora solicitará autenticación moderna (SHA2).";

} catch (PDOException $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo "Nota: Si falla la conexión root, verifica si tiene contraseña.";
}
?>