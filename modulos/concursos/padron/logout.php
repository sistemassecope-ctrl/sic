<?php
// logout.php
session_start();
include("conexion.php");

if (isset($_SESSION['rfc'])) {
    $rfc = $_SESSION['rfc'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt_log = $conexion->prepare("INSERT INTO bitacora_seguridad (rfc, accion, ip_address, user_agent, detalles) VALUES (?, 'LOGOUT', ?, ?, 'Cierre de sesión voluntario')");
    $stmt_log->bind_param("sss", $rfc, $ip, $ua);
    $stmt_log->execute();
}

session_destroy();
header("Location: index.php");
exit;
?>
