<?php
session_start(); // Esencial al inicio de cada página que use sesiones
require 'db_config.php'; // Para acceder a $pdo si es necesario aquí

// --- Obtener modelos DESTACADOS para la página principal ---
// Esto ahora obtendrá los modelos que el admin marcó para el index.
try {
    // Nueva consulta para modelos destacados del index
    $stmt_modelos = $pdo->query("SELECT id, nombre_modelo, precio, imagen_url, descripcion
                                FROM modelos
                                WHERE orden_destacado_index IS NOT NULL
                                ORDER BY orden_destacado_index ASC
                                 LIMIT 3"); // Se mostrarán hasta 3, en el orden especificado
    $modelos = $stmt_modelos->fetchAll(PDO::FETCH_ASSOC);

    // [Opcional] Lógica de respaldo si no hay destacados definidos
    // Si no hay modelos destacados configurados por el admin, muestra los 3 últimos, por ejemplo.
    if (empty($modelos)) {
        $stmt_fallback = $pdo->query("SELECT id, nombre_modelo, precio, imagen_url, descripcion FROM modelos ORDER BY id DESC LIMIT 3");
        $modelos = $stmt_fallback->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $modelos = []; // Dejar array vacío si hay error
    error_log("Error al obtener modelos en index.php: " . $e->getMessage());
    $page_error_message = "Hubo un problema al cargar los modelos. Inténtelo más tarde.";
}


// --- Para verificar si un modelo es favorito del usuario actual (si está logueado) ---
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
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <style>
        .favorite-btn {
            background-color: var(--secondary-color, #6c757d);
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
            margin-top: 10px;
            display: inline-block;
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
        }
        .favorite-btn.is-favorite {
            background-color: var(--accent-color, #ffc107);
            color: var(--header-bg, #212529);
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
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="all_models.php">Modelos</a></li>
                    <li><a href="index.php#categories">Categorías</a></li>
                    <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? '' : 'index.php'); ?>#about">Sobre Nosotros</a></li>
                    <?php if (basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
                        <li><a href="#contact">Contacto</a></li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="favorites.php">Favoritos</a></li>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                            <li><a href="add_model.php" class="btn header-nav-btn">Añadir</a></li>
                            <li><a href="manage_models.php" class="btn header-nav-btn">Gestionar</a></li>
                            <!-- Añade aquí si quieres el enlace a "Destacados Index" también -->
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
                <?php if (isset($page_error_message)): echo "<p class='message error'>{$page_error_message}</p>"; endif; ?>
                
                <div class="grid-container">
                    <?php if (!empty($modelos)): ?>
                        <?php foreach ($modelos as $modelo): ?>
                            <div class="product-card">
                                <a href="modelo_detalle.php?id=<?php echo $modelo['id']; ?>" style="text-decoration:none; display:block; overflow:hidden;">
                                    <img src="<?php echo htmlspecialchars($modelo['imagen_url'] ?? 'img/placeholder.png'); ?>" alt="Imagen de <?php echo htmlspecialchars($modelo['nombre_modelo']); ?>">
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
                                    
                                    <div class="card-actions" style="margin-top:auto; padding-top:10px;">
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <form action="toggle_favorite.php" method="POST" style="display:inline-block; margin-right:5px; vertical-align: middle;">
                                                <input type="hidden" name="modelo_id" value="<?php echo $modelo['id']; ?>">
                                                <?php
                                                $es_fav_actual = isset($favoritos_usuario[$modelo['id']]);
                                                $btn_class = $es_fav_actual ? 'is-favorite' : '';
                                                $btn_text = $es_fav_actual ? 'Quitar Fav ★' : 'Favorito ☆';
                                                ?>
                                                <button type="submit" class="favorite-btn <?php echo $btn_class; ?>"><?php echo $btn_text; ?></button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <a href="modelo_detalle.php?id=<?php echo $modelo['id']; ?>" class="btn" style="display:inline-block; vertical-align: middle;">Ver Detalles</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align:center; grid-column: 1 / -1;">No hay modelos disponibles por el momento. ¡Vuelve pronto!</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
        <section id="categories" class="categories-section">
            <!-- Tu sección de categorías no cambia -->
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
            <!-- Tu sección sobre nosotros no cambia -->
            <div class="container">
                <h2>Sobre Arnerazo3D</h2>
                <p>En Arnerazo3D, somos apasionados por la impresión 3D...</p>
            </div>
        </section>
    </main>

    <footer id="contact">
        <!-- Tu footer no cambia -->
        <div class="container">
            <p>© <?php echo date("Y"); ?> Arnerazo3D - Tienda de Modelos 3D. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollToPlugin.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="./animation.js"></script>
</body>
</html>