<?php
// proteger_admin.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo permitir acceso a usuarios del sistema con nivel 1 (Administrador) o 3 (Supervisor)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_nivel'], [1, 3])) {
    // Redirigir al login principal si no tiene acceso
    header("Location: ../../../index.php?error=unauthorized");
    exit;
}
?>
