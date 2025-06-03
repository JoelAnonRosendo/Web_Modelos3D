<?php
session_start();
require 'db_config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = '';
$success_message = '';

$nombre_val = $_POST['nombre'] ?? '';
$alias_val = $_POST['alias'] ?? '';
$correo_val = $_POST['correo'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $alias = trim($_POST['alias']);
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena'];
    $contrasena_confirm = $_POST['contrasena_confirm'];

    $nombre_val = $nombre; // Para repoblar
    $alias_val = $alias;   // Para repoblar
    $correo_val = $correo; // Para repoblar

    if (empty($nombre) || empty($alias) || empty($correo) || empty($contrasena)) {
        $error_message = "Todos los campos son obligatorios.";
    } elseif (strlen($contrasena) < 6) {
        $error_message = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($contrasena !== $contrasena_confirm) {
        $error_message = "Las contraseñas no coinciden.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Formato de correo electrónico inválido.";
    } elseif (strlen($alias) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $alias)) {
        $error_message = "El alias debe tener al menos 3 caracteres y solo puede contener letras, números y guiones bajos (_).";
    }
    else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = :correo OR alias = :alias");
        $stmt->execute(['correo' => $correo, 'alias' => $alias]);
        if ($stmt->fetch()) {
            $error_message = "El correo electrónico o el alias ya están registrados.";
        } else {
            $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nombre, alias, correo, contraseña) VALUES (:nombre, :alias, :correo, :contrasena)";
            $stmt_insert = $pdo->prepare($sql); // Cambiado el nombre de la variable
            try {
                $stmt_insert->execute([
                    'nombre' => $nombre,
                    'alias' => $alias,
                    'correo' => $correo,
                    'contrasena' => $contrasena_hash
                ]);
                $success_message = "¡Registro exitoso! Ahora puedes <a href='login.php'>iniciar sesión</a>.";
                $nombre_val = ''; $alias_val = ''; $correo_val = ''; // Limpiar para el form
            } catch (PDOException $e) {
                $error_message = "Error al registrar. Por favor, inténtelo de nuevo más tarde.";
                error_log("Error de registro (DB): " . $e->getMessage()); 
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
    <title>Registro - Arnerazo3D</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-form-container">
        <form action="register.php" method="POST" class="auth-form">
            <h2>Crear Cuenta</h2>
            <?php if (!empty($error_message)): ?><div class="message error"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>
            <?php if (!empty($success_message)): ?><div class="message success"><?php echo $success_message; ?></div><?php endif; ?>

            <?php if (empty($success_message)): ?>
            <div class="form-group">
                <label for="nombre">Nombre Completo:</label>
                <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($nombre_val); ?>">
            </div>
            <div class="form-group">
                <label for="alias">Alias (3+ caracteres, letras, números, _):</label>
                <input type="text" id="alias" name="alias" required value="<?php echo htmlspecialchars($alias_val); ?>">
            </div>
            <div class="form-group">
                <label for="correo">Correo Electrónico:</label>
                <input type="email" id="correo" name="correo" required value="<?php echo htmlspecialchars($correo_val); ?>">
            </div>
            <div class="form-group">
                <label for="contrasena">Contraseña (mín. 6 caracteres):</label>
                <input type="password" id="contrasena" name="contrasena" required>
            </div>
            <div class="form-group">
                <label for="contrasena_confirm">Confirmar Contraseña:</label>
                <input type="password" id="contrasena_confirm" name="contrasena_confirm" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn">Registrarse</button>
            </div>
            <?php endif; ?>
            <p>¿Ya tienes cuenta? <a href="login.php">Inicia Sesión</a></p>
            <p><a href="index.php">Volver al inicio</a></p>
        </form>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="./js/animation.js"></script>
</body>
</html>