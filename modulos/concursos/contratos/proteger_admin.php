<?php
// proteger_admin.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Adaptación de sesión PAO v2 a Módulo Legacy
if (isset($_SESSION['usuario_id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_SESSION['usuario_id'];
    $_SESSION['user_nivel'] = $_SESSION['usuario_tipo']; // 1=Admin
    $_SESSION['user_username'] = 'Usuario Sistema'; 
}

// Solo permitir acceso a usuarios del sistema con nivel 1 (Administrador) o usuarios explícitamente permitidos
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] != 1 && $_SESSION['usuario_tipo'] != 3)) {
    // Redirigir al login principal si no tiene acceso
    header("Location: ../../../index.php?error=unauthorized");
    exit;
}
?>
