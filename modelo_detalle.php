<?php
session_start();
require 'db_config.php';

$modelo = null;
$error_message = '';
$modelo_id = null;

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $modelo_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM modelos WHERE id = :id"); // Obtener todas las columnas incluyendo url_compra_externa
        $stmt->execute(['id' => $modelo_id]);
        $modelo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$modelo) {
            $error_message = "El modelo solicitado no fue encontrado.";
        }
    } catch (PDOException $e) {
        $error_message = "Error al cargar el modelo."; 
        error_log("Error en modelo_detalle.php: " . $e->getMessage());
    }
} else {
    $error_message = "ID de modelo no válido o no proporcionado.";
}

$es_favorito_actual = false;
if (isset($_SESSION['user_id']) && $modelo_id) {
    try {
        $stmt_fav = $pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE usuario_id = :user_id AND modelo_id = :modelo_id");
        $stmt_fav->execute(['user_id' => $_SESSION['user_id'], 'modelo_id' => $modelo_id]);
        if ($stmt_fav->fetchColumn() > 0) {
            $es_favorito_actual = true;
        }
    } catch (PDOException $e) {
         error_log("Error al verificar favorito en modelo_detalle.php: " . $e->getMessage());
    }
}

$page_title = $modelo ? htmlspecialchars($modelo['nombre_modelo']) . " - PrintVerse" : "Detalle del Modelo - PrintVerse";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <style>
        .model-detail-container { max-width: 900px; margin: 40px auto; padding: 30px; background-color: var(--card-bg-color, #fff); border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .model-header { text-align: center; margin-bottom: 30px; }
        .model-header h1 { font-family: 'Orbitron', sans-serif; color: var(--text-color); font-size: 2.5em; margin-bottom: 10px; }
        .model-image-container { text-align: center; margin-bottom: 30px; }
        .model-image-container img { max-width: 100%; max-height: 500px; height: auto; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .model-info { display: flex; flex-direction: column; gap: 20px; }
        .model-info .price { font-size: 2em; font-weight: bold; color: var(--primary-color, #007bff); text-align: right; }
        .model-info .description { font-size: 1.1em; line-height: 1.7; color: #555; white-space: pre-wrap; }
        .model-actions { margin-top: 30px; display: flex; justify-content: space-around; align-items: center; flex-wrap: wrap; gap: 15px; }
        .model-actions .btn-download, .model-actions .btn-buy, .model-actions .favorite-btn-detail, .model-actions form button, .model-actions a.btn { padding: 12px 20px; font-size: 1em; text-transform: uppercase; letter-spacing: 1px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; color: white; text-align:center; flex-grow: 1; min-width: 180px; max-width: 300px; }
        .model-actions .btn-download { background-color: var(--accent-color, #ffc107); color: var(--header-bg, #333); }
        .model-actions .btn-download:hover { background-color: darken(var(--accent-color, #ffc107), 10%); }
        .model-actions .btn-buy { background-color: var(--primary-color, #007bff); }
        .model-actions .btn-buy:hover { background-color: darken(var(--primary-color, #007bff), 10%); }
        .favorite-btn-detail { background-color: var(--secondary-color); }
        .favorite-btn-detail.is-favorite { background-color: var(--accent-color); color: var(--header-bg); }
        .model-actions form { display: flex; flex-grow: 1; min-width: 180px; max-width: 300px; }
        .message { width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center; font-size: 0.9em; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;}
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;}
    </style>
</head>
<body>
    <header> <!-- Header Principal -->
        <div class="container">
            <div class="logo"><h1><a href="index.php" style="text-decoration:none; color:var(--header-text);">Print<span class="highlight">Verse</span></a></h1></div>
            <nav>
                <ul>
                    <li><a href="index.php#home">Inicio</a></li>
                    <li><a href="index.php#models">Modelos</a></li>
                    <li><a href="index.php#categories">Categorías</a></li>
                    <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? '' : 'index.php'); ?>#about">Sobre Nosotros</a></li>
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

    <main>
        <div class="model-detail-container">
            <?php if ($modelo): ?>
                <div class="model-header">
                    <h1><?php echo htmlspecialchars($modelo['nombre_modelo']); ?></h1>
                </div>

                <?php if (!empty($modelo['imagen_url'])): ?>
                    <div class="model-image-container">
                        <img src="<?php echo htmlspecialchars($modelo['imagen_url']); ?>" alt="Imagen de <?php echo htmlspecialchars($modelo['nombre_modelo']); ?>">
                    </div>
                <?php endif; ?>

                <div class="model-info">
                    <div class="price">
                        <?php
                        if (isset($modelo['precio']) && $modelo['precio'] > 0) {
                            echo '$' . number_format((float)$modelo['precio'], 2);
                            if (!empty($modelo['url_compra_externa'])) {
                                echo ' <span style="font-size:0.6em; color:var(--secondary-color);">(Venta externa)</span>';
                            }
                        } else {
                            echo 'Gratis';
                        }
                        ?>
                    </div>
                    <div class="description">
                        <h3>Descripción del Modelo:</h3>
                        <p><?php echo nl2br(htmlspecialchars($modelo['descripcion'])); ?></p>
                    </div>

                    <div class="model-actions">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form action="toggle_favorite.php" method="POST">
                                <input type="hidden" name="modelo_id" value="<?php echo $modelo['id']; ?>">
                                <?php
                                $fav_btn_class = $es_favorito_actual ? 'is-favorite' : '';
                                $fav_btn_text = $es_favorito_actual ? 'Quitar Favorito ★' : 'Añadir a Favoritos ☆';
                                ?>
                                <button type="submit" class="favorite-btn-detail <?php echo $fav_btn_class; ?>"><?php echo $fav_btn_text; ?></button>
                            </form>
                        <?php else: ?>
                            <a href="login.php?redirect=modelo_detalle.php?id=<?php echo $modelo['id']; ?>" class="btn" style="background-color: var(--secondary-color);">Inicia sesión para Favorito</a>
                        <?php endif; ?>

                        <?php
                        // Lógica de botones Comprar/Descargar
                        $es_gratuito = (isset($modelo['precio']) && (float)$modelo['precio'] <= 0);
                        $tiene_url_compra = !empty($modelo['url_compra_externa']) && filter_var($modelo['url_compra_externa'], FILTER_VALIDATE_URL);
                        $tiene_archivo_local = !empty($modelo['archivo_stl']);

                        if ($tiene_url_compra && !$es_gratuito) {
                            echo '<a href="' . htmlspecialchars($modelo['url_compra_externa']) . '" class="btn-buy" target="_blank" rel="noopener noreferrer">Comprar por $' . number_format((float)$modelo['precio'], 2) . ' (Externo)</a>';
                        } elseif ($es_gratuito && $tiene_archivo_local) {
                            echo '<a href="download_handler.php?id=' . $modelo['id'] . '" class="btn-download">Descargar Modelo (.'. pathinfo($modelo['archivo_stl'], PATHINFO_EXTENSION) .')</a>';
                        } elseif (!$es_gratuito && !$tiene_url_compra && $tiene_archivo_local) {
                             echo '<p style="text-align:center; width:100%; color: var(--secondary-color); flex-basis:100%;">Para adquirir este modelo, por favor, contacta con nosotros.</p>';
                        } elseif (!$tiene_archivo_local && !$tiene_url_compra && !$es_gratuito) {
                             echo '<p style="text-align:center; width:100%; color: var(--secondary-color); flex-basis:100%;">Modelo no disponible para compra o descarga directa actualmente.</p>';
                        } elseif ($es_gratuito && !$tiene_archivo_local){
                             echo '<p style="text-align:center; width:100%; color: var(--secondary-color); flex-basis:100%;">Este modelo está marcado como gratuito, pero el archivo no está disponible.</p>';
                        }

                        // Mensajes de estado de otros procesos (menos relevantes ahora, pero pueden ser útiles)
                        if(isset($_SESSION['download_error'])) {
                            echo '<p class="message error" style="order: -1; width:100%; flex-basis: 100%;">' . htmlspecialchars($_SESSION['download_error']) . '</p>';
                            unset($_SESSION['download_error']);
                        }
                        // (Puedes añadir más manejo de $_GET['status'] si es necesario para otros flujos)
                        ?>
                    </div>
                </div>

            <?php elseif ($error_message): ?>
                <div class="model-header"><h1>Error</h1></div>
                <p class="message error" style="font-size:1.2em;"><?php echo htmlspecialchars($error_message); ?></p>
                <p style="text-align:center;"><a href="index.php" class="btn">Volver al inicio</a></p>
            <?php else: ?>
                <div class="model-header"><h1>Información no disponible</h1></div>
                <p class="message error" style="font-size:1.2em;">No se pudo cargar la información del modelo.</p>
                <p style="text-align:center;"><a href="index.php" class="btn">Volver al inicio</a></p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
         <div class="container"><p>© <?php echo date("Y"); ?> PrintVerse - Tienda de Modelos 3D.</p></div>
    </footer>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollToPlugin.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="./animation.js"></script>
</body>
</html>