<?php
// proteger.php
session_start();
if (!isset($_SESSION['rfc'])) {
    header("Location: index.php");
    exit;
}
?> 