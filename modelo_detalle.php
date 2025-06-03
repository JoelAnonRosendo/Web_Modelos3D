<?php
session_start();
require 'db_config.php';

$modelo = null;
$error_message = '';
$modelo_id = null;

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $modelo_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM modelos WHERE id = :id");
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

$page_title = $modelo ? htmlspecialchars($modelo['nombre_modelo']) . " - Arnerazo3D" : "Detalle del Modelo - Arnerazo3D";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modelo_detalle.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo"><h1><a href="index.php">Arnerazo<span class="highlight">3D</span></a></h1></div>
            <nav>
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="all_models.php">Modelos</a></li>
                    <li><a href="index.php#categories">Categorías</a></li>
                    <li><a href="index.php#about">Sobre Nosotros</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="favorites.php">Favoritos</a></li>
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
                                echo ' <span class="external-sale-notice">(Venta externa)</span>';
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
                                <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                <?php
                                $fav_btn_class = $es_favorito_actual ? 'is-favorite' : '';
                                $fav_btn_text = $es_favorito_actual ? 'Quitar Favorito ★' : 'Añadir a Favoritos ☆';
                                ?>
                                <button type="submit" class="favorite-btn-detail <?php echo $fav_btn_class; ?>"><?php echo $fav_btn_text; ?></button>
                            </form>
                        <?php else: ?>
                            <a href="login.php?redirect=<?php echo urlencode(htmlspecialchars($_SERVER['REQUEST_URI'])); ?>" class="btn favorite-btn-detail">Inicia sesión para Favorito</a>
                        <?php endif; ?>

                        <?php
                        $es_gratuito = (isset($modelo['precio']) && (float)$modelo['precio'] <= 0);
                        $tiene_url_compra = !empty($modelo['url_compra_externa']) && filter_var($modelo['url_compra_externa'], FILTER_VALIDATE_URL);
                        $tiene_archivo_local_valido = !empty($modelo['archivo_stl']) && file_exists($modelo['archivo_stl']);

                        if ($tiene_url_compra && !$es_gratuito) {
                            echo '<a href="' . htmlspecialchars($modelo['url_compra_externa']) . '" class="btn-buy" target="_blank" rel="noopener noreferrer">Comprar por $' . number_format((float)$modelo['precio'], 2) . ' (Externo)</a>';
                        
                        } elseif ($es_gratuito && $tiene_archivo_local_valido) {
                            echo '<a href="download_handler.php?id=' . $modelo['id'] . '" class="btn-download">Descargar Modelo (.'. pathinfo($modelo['archivo_stl'], PATHINFO_EXTENSION) .')</a>';
                        
                        } elseif (!$es_gratuito && !$tiene_url_compra && $tiene_archivo_local_valido) {
                            echo '<p class="contact-message">Para adquirir este modelo por $'.number_format((float)$modelo['precio'], 2).', por favor, contacta con nosotros (pago en sitio pendiente de implementación).</p>'; 
                        
                        } elseif (!$tiene_archivo_local_valido && !$tiene_url_compra && !$es_gratuito) {
                            echo '<p class="not-available-message">Modelo no disponible para compra o descarga directa actualmente.</p>';
                        } elseif ($es_gratuito && !$tiene_archivo_local_valido){
                            echo '<p class="not-available-message">Este modelo está marcado como gratuito, pero el archivo de descarga no está disponible temporalmente.</p>';
                        }


                        if(isset($_SESSION['download_error']) && $_SESSION['download_error_model_id'] == $modelo_id) { 
                            echo '<p class="message error" style="flex-basis: 100%;">' . htmlspecialchars($_SESSION['download_error']) . '</p>';
                            unset($_SESSION['download_error']);
                            unset($_SESSION['download_error_model_id']);
                        }
                        ?>
                    </div>
                </div>

            <?php elseif ($error_message): ?>
                <div class="model-header"><h1>Error</h1></div>
                <p class="message error"><?php echo htmlspecialchars($error_message); ?></p>
                <p class="back-to-home-link"><a href="index.php" class="btn">Volver al inicio</a></p>
            <?php else: // No $modelo y no $error_message (estado inesperado) ?>
                <div class="model-header"><h1>Información no disponible</h1></div>
                <p class="message error">No se pudo cargar la información del modelo o el modelo no existe.</p>
                <p class="back-to-home-link"><a href="index.php" class="btn">Volver al inicio</a></p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container"><p>© <?php echo date("Y"); ?> Arnerazo3D - Tienda de Modelos 3D.</p></div>
    </footer>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollToPlugin.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="./js/animation.js"></script>
</body>
</html>