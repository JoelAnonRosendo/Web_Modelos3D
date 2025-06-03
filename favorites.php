<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];
$modelos_favoritos = [];

try {
    $stmt = $pdo->prepare("
        SELECT m.*
        FROM modelos m
        INNER JOIN favoritos f ON m.id = f.modelo_id
        WHERE f.usuario_id = :usuario_id
        ORDER BY m.nombre_modelo ASC
    ");
    $stmt->execute(['usuario_id' => $usuario_id]);
    $modelos_favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener favoritos en favorites.php: " . $e->getMessage());
    // Podrías mostrar un mensaje de error al usuario si lo deseas.
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Favoritos - Arnerazo3D</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/favorites.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><a href="index.php">Arnerazo<span class="highlight">3D</span></a></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="all_models.php">Modelos</a></li>
                    <li><a href="index.php#categories">Categorías</a></li>
                    <li><a href="index.php#about">Sobre Nosotros</a></li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="favorites.php" class="nav-active">Favoritos</a></li>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                            <li><a href="add_model.php" class="header-nav-btn">Añadir Modelo</a></li>
                            <li><a href="manage_models.php" class="header-nav-btn">Gestionar</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="header-icons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="user-greeting">Hola, <?php echo htmlspecialchars($_SESSION['user_alias']); ?>!
                        (<a href="logout.php">Salir</a>)
                    </span>
                <?php else: ?>
                    <a href="login.php" class="header-nav-btn btn-login">Login</a>
                    <a href="register.php" class="header-nav-btn btn-register">Registro</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <section id="my-favorites" class="my-favorites-section product-grid">
            <div class="container">
                <h2>Mis Modelos Favoritos</h2>
                <?php if (empty($modelos_favoritos)): ?>
                    <p class="no-favorites-message">Aún no has añadido ningún modelo a tus favoritos. ¡Explora nuestra <a href="all_models.php">colección</a>!</p>
                <?php else: ?>
                    <div class="grid-container">
                        <?php foreach ($modelos_favoritos as $modelo): ?>
                            <div class="product-card">
                                <a href="modelo_detalle.php?id=<?php echo $modelo['id']; ?>" class="product-image-link">
                                    <img src="<?php echo htmlspecialchars($modelo['imagen_url'] ?? 'img/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($modelo['nombre_modelo']); ?>">
                                </a>
                                <div class="product-info">
                                    <h3>
                                        <a href="modelo_detalle.php?id=<?php echo $modelo['id']; ?>">
                                            <?php echo htmlspecialchars($modelo['nombre_modelo']); ?>
                                        </a>
                                    </h3>
                                    <p class="price">
                                        <?php 
                                        if (isset($modelo['precio']) && (float)$modelo['precio'] > 0) {
                                            echo '$' . number_format((float)$modelo['precio'], 2);
                                        } else {
                                            echo 'Gratis';
                                        }
                                        ?>
                                    </p>
                                    <div class="card-actions">
                                        <form action="toggle_favorite.php" method="POST">
                                            <input type="hidden" name="modelo_id" value="<?php echo $modelo['id']; ?>">
                                            <input type="hidden" name="redirect_to" value="favorites.php">
                                            <button type="submit" class="favorite-btn is-favorite">Quitar Fav ★</button>
                                        </form>
                                        <a href="modelo_detalle.php?id=<?php echo $modelo['id']; ?>" class="btn">Ver Detalles</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>© <?php echo date("Y"); ?> Arnerazo3D - Tienda de Modelos 3D. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollToPlugin.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="./js/animation.js"></script>
</body>
</html>