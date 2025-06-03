<?php
session_start();
require 'db_config.php';

$modelos = [];
$page_error_message = '';
try {
    $stmt_modelos = $pdo->query("SELECT id, nombre_modelo, precio, imagen_url, descripcion
                                FROM modelos
                                WHERE orden_destacado_index IS NOT NULL
                                ORDER BY orden_destacado_index ASC
                                LIMIT 3");
    $modelos = $stmt_modelos->fetchAll(PDO::FETCH_ASSOC);

    if (empty($modelos)) {
        // Si no hay destacados, tomar los 3 últimos como fallback
        $stmt_fallback = $pdo->query("SELECT id, nombre_modelo, precio, imagen_url, descripcion FROM modelos ORDER BY id DESC LIMIT 3");
        $modelos = $stmt_fallback->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Error al obtener modelos en index.php: " . $e->getMessage());
    $page_error_message = "Hubo un problema al cargar los modelos. Inténtelo más tarde.";
}

$favoritos_usuario = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt_fav = $pdo->prepare("SELECT modelo_id FROM favoritos WHERE usuario_id = :user_id");
        $stmt_fav->execute(['user_id' => $_SESSION['user_id']]);
        $ids_favoritos = $stmt_fav->fetchAll(PDO::FETCH_COLUMN);
        $favoritos_usuario = array_flip($ids_favoritos);
    } catch (PDOException $e) {
        error_log("Error al obtener favoritos en index.php para usuario {$_SESSION['user_id']}: " . $e->getMessage());
    }
}

$page_title = "Tienda de Modelos 3D - Arnerazo3D";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="css/style.css">
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
                    <li><a href="index.php" class="nav-active">Inicio</a></li>
                    <li><a href="all_models.php">Modelos</a></li>
                    <li><a href="#categories">Categorías</a></li>
                    <li><a href="#about">Sobre Nosotros</a></li>
                    <li><a href="#contact">Contacto</a></li>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="favorites.php">Favoritos</a></li>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                            <li><a href="add_model.php" class="header-nav-btn">Añadir</a></li>
                            <li><a href="manage_models.php" class="header-nav-btn">Gestionar</a></li>
                            <li><a href="manage_featured_index.php" class="header-nav-btn">Destacados</a></li>
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

    <section id="home" class="hero">
        <div class="container">
            <h2>Modelos 3D Increíbles, Listos para Imprimir</h2>
            <p>Explora nuestra colección curada de diseños únicos y de alta calidad.</p>
            <a href="all_models.php" class="btn btn-primary">Ver Colección</a>
        </div>
    </section>

    <main>
        <section id="models" class="product-grid">
            <div class="container">
                <h2>Modelos Destacados</h2>
                <?php if (isset($page_error_message)): echo "<p class='message error'>".htmlspecialchars($page_error_message)."</p>"; endif; ?>
                
                <div class="grid-container">
                    <?php if (!empty($modelos)): ?>
                        <?php foreach ($modelos as $modelo): ?>
                            <div class="product-card">
                                <a href="modelo_detalle.php?id=<?php echo $modelo['id']; ?>" class="product-image-link">
                                    <img src="<?php echo htmlspecialchars($modelo['imagen_url'] ?? 'img/placeholder.png'); ?>" alt="Imagen de <?php echo htmlspecialchars($modelo['nombre_modelo']); ?>">
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
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <form action="toggle_favorite.php" method="POST">
                                                <input type="hidden" name="modelo_id" value="<?php echo $modelo['id']; ?>">
                                                <input type="hidden" name="redirect_to" value="index.php#models">
                                                <?php
                                                $es_fav_actual = isset($favoritos_usuario[$modelo['id']]);
                                                $btn_class = $es_fav_actual ? 'is-favorite' : '';
                                                $btn_text = $es_fav_actual ? 'Quitar Fav ★' : 'Favorito ☆';
                                                ?>
                                                <button type="submit" class="favorite-btn <?php echo $btn_class; ?>"><?php echo $btn_text; ?></button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="modelo_detalle.php?id=<?php echo $modelo['id']; ?>" class="btn">Ver Detalles</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php if (empty($page_error_message)): ?>
                            <p class="no-models-message" style="grid-column: 1 / -1;">No hay modelos disponibles por el momento. ¡Vuelve pronto!</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
        <section id="categories" class="categories-section">
            <div class="container">
                <h2>Explora por Categorías</h2>
                <div class="category-list">
                    <a href="all_models.php?categoria_id=1" class="category-item">Figuras y Miniaturas</a>
                    <a href="all_models.php?categoria_id=2" class="category-item">Herramientas y Gadgets</a>
                    <a href="all_models.php?categoria_id=3" class="category-item">Decoración del Hogar</a>
                </div>
            </div>
        </section>
        <section id="about" class="about-us">
            <div class="container">
                <h2>Sobre Arnerazo3D</h2>
                <p>En Arnerazo3D, somos apasionados por la impresión 3D y creemos en el poder de la comunidad para crear e innovar. Ofrecemos una plataforma donde diseñadores pueden compartir sus creaciones y entusiastas pueden encontrar modelos únicos para sus proyectos. ¡Únete a nosotros en esta aventura tridimensional!</p>
            </div>
        </section>
    </main>

    <footer id="contact">
        <div class="container">
            <p>© <?php echo date("Y"); ?> Arnerazo3D - Tienda de Modelos 3D. Todos los derechos reservados.</p>
            <p>Contáctanos: <a href="mailto:info@arnerazo3d.example.com">info@arnerazo3d.example.com</a></p>
            <div class="social-links">
                <a href="#">Facebook</a> | <a href="#">Instagram</a> | <a href="#">Twitter</a>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollToPlugin.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="./js/animation.js"></script>
</body>
</html>