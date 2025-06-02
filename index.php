<?php
session_start(); // Esencial al inicio de cada página que use sesiones
require 'db_config.php'; // Para acceder a $pdo si es necesario aquí

// --- Obtener modelos de la base de datos ---
// Es buena idea limitar el número de modelos en la página principal y paginar o tener un "ver más"
// También, manejar errores si la consulta falla.
try {
    // Seleccionamos más campos por si los necesitas más adelante en la tarjeta
    $stmt_modelos = $pdo->query("SELECT id, nombre_modelo, precio, imagen_url, descripcion FROM modelos ORDER BY id DESC LIMIT 12");
    $modelos = $stmt_modelos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $modelos = []; // Dejar array vacío si hay error
    // Loguear el error y/o mostrar un mensaje más amigable al usuario en un sitio en producción
    error_log("Error al obtener modelos en index.php: " . $e->getMessage());
    // $page_error_message = "Hubo un problema al cargar los modelos. Inténtelo más tarde.";
}


// --- Para verificar si un modelo es favorito del usuario actual (si está logueado) ---
$favoritos_usuario = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt_fav = $pdo->prepare("SELECT modelo_id FROM favoritos WHERE usuario_id = :user_id");
        $stmt_fav->execute(['user_id' => $_SESSION['user_id']]);
        $ids_favoritos = $stmt_fav->fetchAll(PDO::FETCH_COLUMN); // Obtiene solo la columna modelo_id
        $favoritos_usuario = array_flip($ids_favoritos); // Clave es modelo_id, valor es el índice (0,1,2..)
                                                       // Esto facilita la búsqueda con: isset($favoritos_usuario[$modelo_id])
    } catch (PDOException $e) {
        // No interrumpir la carga de la página por error en favoritos, pero loguear
        error_log("Error al obtener favoritos en index.php para usuario {$_SESSION['user_id']}: " . $e->getMessage());
    }
}

$page_title = "Tienda de Modelos 3D - PrintVerse";
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
        /* Estilos para el botón de favorito que tenías */
        .favorite-btn {
            background-color: var(--secondary-color, #6c757d); /* Color secundario por defecto */
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
            margin-top: 10px;
            display: inline-block; /* O flex si necesitas más control */
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
        }
        .favorite-btn.is-favorite {
            background-color: var(--accent-color, #ffc107); /* Naranja/amarillo cuando es favorito */
            color: var(--header-bg, #212529); /* Texto oscuro para contraste */
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><a href="index.php" style="text-decoration:none; color:var(--header-text, #f8f9fa);">Print<span class="highlight">Verse</span></a></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php#home">Inicio</a></li>
                    <li><a href="index.php#models">Modelos</a></li>
                    <li><a href="index.php#categories">Categorías</a></li>
                    <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? '' : 'index.php'); ?>#about">Sobre Nosotros</a></li>
                    <?php if (basename($_SERVER['PHP_SELF']) == 'index.php'): // Solo mostrar Contacto en nav de index ?>
                        <li><a href="#contact">Contacto</a></li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="favorites.php">Favoritos</a></li>
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

    <section id="home" class="hero">
        <div class="container">
            <h2>Modelos 3D Increíbles, Listos para Imprimir</h2>
            <p>Explora nuestra colección curada de diseños únicos y de alta calidad.</p>
            <a href="#models" class="btn btn-primary">Ver Colección</a>
        </div>
    </section>

    <main>
        <section id="models" class="product-grid">
            <div class="container">
                <h2>Modelos Destacados</h2>
                <?php // if (isset($page_error_message)): echo "<p class='message error'>{$page_error_message}</p>"; endif; ?>
                
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
             <div class="container">
                <h2>Explora por Categorías</h2>
                 <div class="category-list">
                    <a href="#figuras" class="category-item">Figuras y Miniaturas</a>
                    <a href="#gadgets" class="category-item">Herramientas y Gadgets</a>
                    <a href="#decoracion" class="category-item">Decoración del Hogar</a>
                    {/* Añade más categorías según necesites */}
                </div>
            </div>
        </section>
        <section id="about" class="about-us">
            <div class="container">
                <h2>Sobre PrintVerse</h2>
                <p>En PrintVerse, somos apasionados por la impresión 3D y creemos en el poder de la creatividad digital para transformar ideas en objetos tangibles. Nuestra misión es proporcionar una plataforma donde diseñadores talentosos puedan compartir sus creaciones y los entusiastas de la impresión 3D puedan encontrar modelos únicos y de alta calidad...</p>
                {/* Completa esta sección */}
            </div>
        </section>
    </main>

    <footer id="contact">
        <div class="container">
            <p>© <?php echo date("Y"); ?> PrintVerse - Tienda de Modelos 3D. Todos los derechos reservados.</p>
            <p>Contáctanos: <a href="mailto:info@printverse.example.com">info@printverse.example.com</a></p>
             <div class="social-links">
                <a href="#">Facebook</a> | <a href="#">Instagram</a> | <a href="#">Twitter</a>
            </div>
        </div>
    </footer>

    <!-- Librerías (CDN) -->
    <!-- GSAP y ScrollTrigger -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollToPlugin.min.js"></script>


    <!-- Lenis Scroll -->
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>

    <!-- Tu script personalizado de animaciones -->
    <script src="./animation.js"></script>
</body>
</html>