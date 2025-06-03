<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];

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
    <title>Mis Favoritos - Arnerazo3D</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <style>
        .favorite-btn {
            background-color: var(--secondary-color, #6c757d);
            color: white;
            padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8em;
            display: inline-block; transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
        }
        .favorite-btn.is-favorite {
            background-color: var(--accent-color, #ffc107);
            color: var(--header-bg, #212529);
        }
         .product-card .card-actions { /* Específico para esta página quizás */
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .product-card .card-actions .btn {
             flex-basis: 48%; /* Para que los botones compartan espacio */
        }
        .product-card .card-actions form {
            flex-basis: 48%;
        }
        .product-card .card-actions .favorite-btn {
            width: 100%; /* Para que el botón fav ocupe el espacio del form */
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><a href="index.php" style="text-decoration:none; color:var(--header-text, #f8f9fa);">Arnerazo<span class="highlight">3D</span></a></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php#home">Inicio</a></li>
                    <li><a href="index.php#models">Modelos</a></li>
                    <li><a href="index.php#categories">Categorías</a></li>
                    <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? '' : 'index.php'); ?>#about">Sobre Nosotros</a></li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="favorites.php" style="color: var(--accent-color); font-weight:bold;">Favoritos</a></li>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                            <li><a href="add_model.php" class="btn header-nav-btn">Añadir Modelo</a></li>
                            <li><a href="manage_models.php" class="btn header-nav-btn">Gestionar</a></li>
                        <?php endif; ?>
                        
                        <li class="nav-user-greeting"><span >Hola, <?php echo htmlspecialchars($_SESSION['user_alias']); ?>!</span></li>
                        <li><a href="logout.php" class="btn btn-primary header-nav-btn header-nav-btn-accent">Salir</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="btn header-nav-btn">Login</a></li>
                        <li><a href="register.php" class="btn btn-primary header-nav-btn header-nav-btn-accent">Registro</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
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
                                <a href="modelo_detalle.php?id=<?php echo $modelo['id']; ?>" style="text-decoration:none; display:block; overflow:hidden;">
                                    <img src="<?php echo htmlspecialchars($modelo['imagen_url'] ?? 'img/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($modelo['nombre_modelo']); ?>">
                                </a>
                                <div class="product-info">
                                    <h3>
                                        <a href="modelo_detalle.php?id=<?php echo $modelo['id']; ?>" style="text-decoration:none; color:var(--text-color, #333);">
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
            <p>© <?php echo date("Y"); ?> PrintVerse - Tienda de Modelos 3D. Todos los derechos reservados.</p>
        </div>
    </footer>

    <!-- Librerías (CDN) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollToPlugin.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="./animation.js"></script>
</body>
</html>