<?php
session_start(); // Iniciar sesión para redirigir si ya está logueado
require 'db_config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Si ya está logueado, redirigir al inicio
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $alias = trim($_POST['alias']);
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena']; // Contraseña en texto plano
    $contrasena_confirm = $_POST['contrasena_confirm'];

    if (empty($nombre) || empty($alias) || empty($correo) || empty($contrasena)) {
        $error_message = "Todos los campos son obligatorios.";
    } elseif ($contrasena !== $contrasena_confirm) {
        $error_message = "Las contraseñas no coinciden.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Formato de correo electrónico inválido.";
    } else {
        // Verificar si el correo o alias ya existen
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = :correo OR alias = :alias");
        $stmt->execute(['correo' => $correo, 'alias' => $alias]);
        if ($stmt->fetch()) {
            $error_message = "El correo electrónico o el alias ya están registrados.";
        } else {
            // Hashear la contraseña
            $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

            // Insertar nuevo usuario
            $sql = "INSERT INTO usuarios (nombre, alias, correo, contraseña) VALUES (:nombre, :alias, :correo, :contrasena)";
            $stmt = $pdo->prepare($sql);
            try {
                $stmt->execute([
                    'nombre' => $nombre,
                    'alias' => $alias,
                    'correo' => $correo,
                    'contrasena' => $contrasena_hash
                ]);
                $success_message = "¡Registro exitoso! Ahora puedes <a href='login.php'>iniciar sesión</a>.";
            } catch (PDOException $e) {
                $error_message = "Error al registrar: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - PrintVerse</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-form { max-width: 400px; margin: 50px auto; padding: 20px; background: var(--card-bg-color); border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .auth-form h2 { text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-group .btn { width: 100%; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;}
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;}
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;}
    </style>
</head>
<body>
    <?php // Podrías incluir un header común aquí si quieres ?>
    <div class="container">
        <form action="register.php" method="POST" class="auth-form">
            <h2>Crear Cuenta</h2>
            <?php if ($error_message): ?><div class="message error"><?php echo $error_message; ?></div><?php endif; ?>
            <?php if ($success_message): ?><div class="message success"><?php echo $success_message; ?></div><?php endif; ?>

            <div class="form-group">
                <label for="nombre">Nombre Completo:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div class="form-group">
                <label for="alias">Alias (Nombre de usuario):</label>
                <input type="text" id="alias" name="alias" required>
            </div>
            <div class="form-group">
                <label for="correo">Correo Electrónico:</label>
                <input type="email" id="correo" name="correo" required>
            </div>
            <div class="form-group">
                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" required>
            </div>
            <div class="form-group">
                <label for="contrasena_confirm">Confirmar Contraseña:</label>
                <input type="password" id="contrasena_confirm" name="contrasena_confirm" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn">Registrarse</button>
            </div>
            <p style="text-align:center;">¿Ya tienes cuenta? <a href="login.php">Inicia Sesión</a></p>
        </form>
    </div>
    <?php // Podrías incluir un footer común aquí si quieres ?>
    <!-- Librerías (CDN) -->
    <!-- GSAP y ScrollTrigger -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

    <!-- Lenis Scroll -->
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>

    <!-- Tu script personalizado de animaciones -->
    <script src="./animation.js"></script> <!-- Cambia esto a la ruta correcta -->
</body>
</html>