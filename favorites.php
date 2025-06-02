<?php
session_start();
require 'db_config.php';

// Si el usuario no está logueado, redirigirlo a la página de login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];

// Obtener los modelos favoritos del usuario
$stmt = $pdo->prepare("
    SELECT m.*
    FROM modelos m
    INNER JOIN favoritos f ON m.id = f.modelo_id
    WHERE f.usuario_id = :usuario_id
    ORDER BY m.nombre_modelo ASC
");
$stmt->execute(['usuario_id' => $usuario_id]);
$modelos_favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Favoritos - PrintVerse</title>
    <link rel="stylesheet" href="style.css"> <!-- Reutiliza tu CSS principal -->
    <!-- Puedes añadir estilos específicos para la página de favoritos si es necesario -->
</head>
<body>
    <header>
        <!-- Copia y adapta tu cabecera de index.php aquí para mantener la consistencia -->
        <div class="container">
            <div class="logo"><h1>Print<span class="highlight">Verse</span></h1></div>
            <nav>
                <ul>
                    <li><a href="index.php#home">Inicio</a></li>
                    <li><a href="index.php#models">Modelos</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="favorites.php">Favoritos</a></li>
                    <?php endif; ?>
                    <li><a href="index.php#about">Sobre Nosotros</a></li>
                </ul>
            </nav>
            <div class="header-icons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="user-greeting">Hola, <?php echo htmlspecialchars($_SESSION['user_alias']); ?>!</span>
                    <a href="logout.php" class="btn btn-primary" style="padding: 8px 12px; font-size:0.9em; margin-left:10px;">Salir</a>
                <?php else: ?>
                    <a href="login.php" class="btn">Login</a>
                    <a href="register.php" class="btn btn-primary">Registro</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <section id="my-favorites" class="product-grid" style="padding-top: 40px;">
            <div class="container">
                <h2>Mis Modelos Favoritos</h2>
                <?php if (empty($modelos_favoritos)): ?>
                    <p style="text-align: center;">Aún no has añadido ningún modelo a tus favoritos. ¡Explora nuestra <a href="index.php#models">colección</a>!</p>
                <?php else: ?>
                    <div class="grid-container">
                        <?php foreach ($modelos_favoritos as $modelo): ?>
                            <div class="product-card">
                                <img src="<?php echo htmlspecialchars($modelo['imagen_url']); ?>" alt="<?php echo htmlspecialchars($modelo['nombre_modelo']); ?>">
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($modelo['nombre_modelo']); ?></h3>
                                    <p class="price">$<?php echo htmlspecialchars($modelo['precio']); ?></p>
                                    <form action="toggle_favorite.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="modelo_id" value="<?php echo $modelo['id']; ?>">
                                        <button type="submit" class="favorite-btn is-favorite">Quitar Favorito ★</button>
                                    </form>
                                    <a href="modelo_detalle.php?id=<?php echo $modelo['id']; ?>" class="btn">Ver Detalles</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
         <!-- Copia y adapta tu footer de index.php aquí -->
         <div class="container">
            <p>© <?php echo date("Y"); ?> PrintVerse - Tienda de Modelos 3D. Todos los derechos reservados.</p>
         </div>
    </footer>
</body>
</html>