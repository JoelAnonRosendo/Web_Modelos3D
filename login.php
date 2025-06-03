<?php
session_start();
require 'db_config.php';

$error_message = ''; 

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$correo_alias_val = $_POST['correo_alias'] ?? ''; // Para repoblar

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo_alias = trim($_POST['correo_alias']);
    $contrasena = $_POST['contrasena'];
    $correo_alias_val = $correo_alias; // Repoblar en caso de error

    if (empty($correo_alias) || empty($contrasena)) {
        $error_message = "Debes ingresar tu correo/alias y contraseña.";
    } else {
        $stmt = $pdo->prepare("SELECT id, alias, contraseña, es_admin FROM usuarios WHERE correo = :identificador OR alias = :identificador");
        $stmt->execute(['identificador' => $correo_alias]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($contrasena, $usuario['contraseña'])) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_alias'] = $usuario['alias'];
            $_SESSION['is_admin'] = (bool)$usuario['es_admin'];
            
            $redirect_url = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: " . $redirect_url);
            exit();
        } else {
            $error_message = "Correo/alias o contraseña incorrectos.";
        }
    }
} else {
    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
         // Solo sanitizar, no validar completamente como URL porque puede ser una ruta relativa.
        $_SESSION['redirect_after_login'] = filter_var($_GET['redirect'], FILTER_SANITIZE_URL);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Arnerazo3D</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-form-container">
        <form action="login.php" method="POST" class="auth-form">
            <h2>Iniciar Sesión</h2>
            <?php if (!empty($error_message)): ?><div class="message error"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>
            <div class="form-group">
                <label for="correo_alias">Correo Electrónico o Alias:</label>
                <input type="text" id="correo_alias" name="correo_alias" required value="<?php echo htmlspecialchars($correo_alias_val); ?>">
            </div>
            <div class="form-group">
                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn">Entrar</button>
            </div>
            <p>¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
            <p><a href="index.php">Volver al inicio</a></p>
        </form>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="./js/animation.js"></script>
</body>
</html>