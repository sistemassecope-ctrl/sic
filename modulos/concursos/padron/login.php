<!-- login.php -->
<?php
session_start();
include("conexion.php");

$rfc = $_POST['rfc'];
$password = $_POST['password'];

$stmt = $conexion->prepare("SELECT * FROM usuarios_padron WHERE rfc = ?");
$stmt->bind_param("s", $rfc);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $usuario = $resultado->fetch_assoc();
    if (password_verify($password, $usuario['password'])) {
        $_SESSION['rfc'] = $usuario['rfc'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Contraseña incorrecta.";
    }
} else {
    $error = "Usuario no encontrado.";
}

if (isset($error)) {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Error de Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/pao/assets/css/styles.css">
</head>
<body class="bg-light min-vh-100 d-flex flex-column align-items-center justify-content-start">
    <div class="text-center my-4">
        <a href="index.php"><img src="/pao/img/logo_secope.png" alt="Logo SECOPE" style="max-height:100px;"></a>
    </div>
    <div class="container bg-white rounded-4 shadow p-4 mb-4" style="max-width:400px;">
        <h2 class="mb-3 text-danger">Error de Autenticación</h2>
        <div class="alert alert-danger text-center">
            <?php echo $error; ?>
        </div>
        <a href="index.php" class="btn btn-primary w-100">Volver al inicio</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
?>
