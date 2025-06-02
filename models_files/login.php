<?php
session_start();
require 'db_config.php';

// ... (código existente para redirigir si ya está logueado) ...
// ... (código existente para manejo de errores) ...

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo_alias = trim($_POST['correo_alias']);
    $contrasena = $_POST['contrasena'];

    if (empty($correo_alias) || empty($contrasena)) {
        $error_message = "Debes ingresar tu correo/alias y contraseña.";
    } else {
        // MODIFICADO: Seleccionar también es_admin
        $stmt = $pdo->prepare("SELECT id, alias, contraseña, es_admin FROM usuarios WHERE correo = :identificador OR alias = :identificador");
        $stmt->execute(['identificador' => $correo_alias]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($contrasena, $usuario['contraseña'])) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_alias'] = $usuario['alias'];
            $_SESSION['is_admin'] = (bool)$usuario['es_admin']; // Guardar como booleano en la sesión
            header("Location: index.php");
            exit();
        } else {
            $error_message = "Correo/alias o contraseña incorrectos.";
        }
    }
}
?>
<!-- ... (resto del HTML del login.php sigue igual) ... -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - PrintVerse</title>
    <link rel="stylesheet" href="style.css">
    <!-- Reutiliza los estilos de auth-form de register.php -->
    <style>
        .auth-form { max-width: 400px; margin: 50px auto; padding: 20px; background: var(--card-bg-color); border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .auth-form h2 { text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-group .btn { width: 100%; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;}
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;}
    </style>
</head>
<body>
    <div class="container">
        <form action="login.php" method="POST" class="auth-form">
            <h2>Iniciar Sesión</h2>
            <?php if ($error_message): ?><div class="message error"><?php echo $error_message; ?></div><?php endif; ?>
            <div class="form-group">
                <label for="correo_alias">Correo Electrónico o Alias:</label>
                <input type="text" id="correo_alias" name="correo_alias" required>
            </div>
            <div class="form-group">
                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn">Entrar</button>
            </div>
            <p style="text-align:center;">¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
        </form>
    </div>
</body>
</html>