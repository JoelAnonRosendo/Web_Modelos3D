<?php
session_start(); // Esencial al inicio de cada página que use sesiones
require 'db_config.php'; // Para acceder a $pdo si es necesario aquí

// --- Obtener modelos de la base de datos ---
$stmt_modelos = $pdo->query("SELECT * FROM modelos ORDER BY id DESC LIMIT 8"); // Obtener los últimos 8, por ejemplo
$modelos = $stmt_modelos->fetchAll(PDO::FETCH_ASSOC);

// --- Para verificar si un modelo es favorito del usuario actual (si está logueado) ---
$favoritos_usuario = [];
if (isset($_SESSION['user_id'])) {
    $stmt_fav = $pdo->prepare("SELECT modelo_id FROM favoritos WHERE usuario_id = :user_id");
    $stmt_fav->execute(['user_id' => $_SESSION['user_id']]);
    $ids_favoritos = $stmt_fav->fetchAll(PDO::FETCH_COLUMN); // Obtiene solo la columna modelo_id
    $favoritos_usuario = array_flip($ids_favoritos); // Facilita la búsqueda con isset($favoritos_usuario[$modelo_id])
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda de Modelos 3D - PrintVerse</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <style>
        .favorite-btn {
            background-color: #6c757d; /* Color secundario por defecto */
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
            margin-top: 10px;
            display: inline-block;
        }
        .favorite-btn.is-favorite {
            background-color: #ffc107; /* Color de acento cuando es favorito */
            color: #333;
        }
        .user-greeting { margin-right: 15px; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1>Print<span class="highlight">Verse</span></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php#home">Inicio</a></li>
                    <li><a href="index.php#models">Modelos</a></li>
                    <li><a href="index.php#categories">Categorías</a></li>
                    <li><a href="#about">Sobre Nosotros</a></li>
                    <li><a href="#contact">Contacto</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="favorites.php">Favoritos</a></li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                        <li><a href="add_model.php" class="btn btn-warning" style="padding: 8px 12px; font-size:0.9em; margin-left:10px;">Añadir Modelo</a></li>
                        <li><a href="manage_models.php" class="btn btn-warning" style="padding: 8px 12px; font-size:0.9em; margin-left:10px;">Gestionar Modelos</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="header-icons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="user-greeting">Hola, <?php echo htmlspecialchars($_SESSION['user_alias']); ?>!</span>
                    <a href="logout.php" class="btn btn-primary" style="padding: 8px 12px; font-size:0.9em; margin-left:10px;">Salir</a>
                <?php else: ?>
                    <a href="login.php" class="btn" style="padding: 8px 12px; font-size:0.9em; margin-right:5px;">Login</a>
                    <a href="register.php" class="btn btn-primary" style="padding: 8px 12px; font-size:0.9em;">Registro</a>
                <?php endif; ?>
                <!-- <a href="#" aria-label="Carrito"><img src="img/cart-icon.svg" alt="Carrito" class="icon"><span>0</span></a> -->
            </div>
        </div>
    </header>

    <!-- ... (tu sección hero sigue igual) ... -->
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
                <div class="grid-container">
                    <?php foreach ($modelos as $modelo): ?>
                        <div class="product-card">
                            <img src="<?php echo htmlspecialchars($modelo['imagen_url']); ?>" alt="<?php echo htmlspecialchars($modelo['nombre_modelo']); ?>">
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($modelo['nombre_modelo']); ?></h3>
                                <!-- <p class="category">Robótica / Juguetes</p> -->
                                <p class="price">$<?php echo htmlspecialchars($modelo['precio']); ?></p>

                                <?php if (isset($_SESSION['user_id'])): // Solo mostrar si está logueado ?>
                                    <form action="toggle_favorite.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="modelo_id" value="<?php echo $modelo['id']; ?>">
                                        <?php
                                        $es_fav_actual = isset($favoritos_usuario[$modelo['id']]);
                                        $btn_class = $es_fav_actual ? 'is-favorite' : '';
                                        $btn_text = $es_fav_actual ? 'Quitar Favorito ★' : 'Favorito ☆';
                                        ?>
                                        <button type="submit" class="favorite-btn <?php echo $btn_class; ?>"><?php echo $btn_text; ?></button>
                                    </form>
                                <?php endif; ?>

                                <a href="modelo_detalle.php?id=<?php echo $modelo['id']; ?>" class="btn">Ver Detalles</a>
                                 <?php // Necesitarías crear modelo_detalle.php para mostrar info completa y link de descarga ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($modelos)): ?>
                        <p>No hay modelos disponibles por el momento.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <!-- ... (resto de tus secciones categories, about-us) ... -->
    </main>

    <footer id="contact">
        <!-- ... (tu footer sigue igual) ... -->
    </footer>
</body>
</html>