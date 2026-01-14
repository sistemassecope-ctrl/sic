<!-- logout.php -->
<?php
session_start();
session_destroy();
header("Location: index.php");
?>


<!-- proteger.php -->
<?php
session_start();
if (!isset($_SESSION['rfc'])) {
    header("Location: index.php");
    exit;
}
?>
