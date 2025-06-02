<?php
session_start();
require 'db_config.php'; // Conexión a la BD

$modelo = null;
$error_message = '';
$modelo_id = null;

// 1. Obtener y validar el ID del modelo desde la URL
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $modelo_id = (int)$_GET['id'];

    // 2. Consultar la base de datos para obtener los detalles del modelo
    try {
        $stmt = $pdo->prepare("SELECT * FROM modelos WHERE id = :id");
        $stmt->execute(['id' => $modelo_id]);
        $modelo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$modelo) {
            $error_message = "El modelo solicitado no fue encontrado.";
        }
    } catch (PDOException $e) {
        $error_message = "Error al cargar el modelo: " . $e->getMessage(); // Para desarrollo, en producción loguear y mensaje genérico
        error_log("Error en modelo_detalle.php: " . $e->getMessage());
    }
} else {
    $error_message = "ID de modelo no válido o no proporcionado.";
}

// --- Para verificar si el modelo es favorito del usuario actual (si está logueado) ---
$es_favorito_actual = false;
if (isset($_SESSION['user_id']) && $modelo_id) {
    $stmt_fav = $pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE usuario_id = :user_id AND modelo_id = :modelo_id");
    $stmt_fav->execute(['user_id' => $_SESSION['user_id'], 'modelo_id' => $modelo_id]);
    if ($stmt_fav->fetchColumn() > 0) {
        $es_favorito_actual = true;
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
    <link rel="stylesheet" href="style.css"> <!-- Tu CSS principal -->
    <!-- Puedes añadir Google Fonts aquí si no están en style.css -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <style>
        .model-detail-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background-color: var(--card-bg-color, #fff);
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .model-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .model-header h1 {
            font-family: 'Orbitron', sans-serif;
            color: var(--text-color);
            font-size: 2.5em; /* Un poco más grande */
            margin-bottom: 10px;
        }
        .model-image-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .model-image-container img {
            max-width: 100%;
            max-height: 500px; /* Limitar altura máxima */
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .model-info {
            display: flex;
            flex-direction: column;
            gap: 20px; /* Espacio entre elementos de información */
        }
        .model-info .price {
            font-size: 2em;
            font-weight: bold;
            color: var(--primary-color, #007bff);
            text-align: right; /* Alinear precio a la derecha */
        }
        .model-info .description {
            font-size: 1.1em;
            line-height: 1.7;
            color: #555;
            white-space: pre-wrap; /* Para respetar saltos de línea en la descripción */
        }
        .model-actions {
            margin-top: 30px;
            display: flex;
            justify-content: space-between; /* Botones a los lados */
            align-items: center;
            flex-wrap: wrap; /* Para móviles */
            gap: 15px;
        }
        .model-actions .btn-download, .model-actions .btn-buy, .model-actions .favorite-btn-detail {
            padding: 12px 25px;
            font-size: 1.1em;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none; /* Para enlaces estilizados como botones */
            color: white; /* Texto blanco para botones primarios */
            text-align:center;
        }
        .model-actions .btn-download { background-color: var(--accent-color, #ffc107); color: var(--header-bg, #333); }
        .model-actions .btn-download:hover { background-color: darken(var(--accent-color, #ffc107), 10%); }

        .model-actions .btn-buy { background-color: var(--primary-color, #007bff); }
        .model-actions .btn-buy:hover { background-color: darken(var(--primary-color, #007bff), 10%); }
        
        /* Botón de favorito adaptado */
        .favorite-btn-detail {
            background-color: #6c757d;
        }
        .favorite-btn-detail.is-favorite {
            background-color: #ffc107; /* Naranja cuando es favorito */
            color: #333;
        }
         .user-greeting { margin-right: 15px; color: var(--header-text); } /* Para el header */
    </style>
</head>
<body>
    <header> <!-- Incluye tu header estándar de index.php -->
         <div class="container">
            <div class="logo"><h1><a href="index.php" style="text-decoration:none; color:var(--header-text);">Print<span class="highlight">Verse</span></a></h1></div>
            <nav>
                <ul>
                    <li><a href="index.php#home">Inicio</a></li>
                    <li><a href="index.php#models">Modelos</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="favorites.php">Favoritos</a></li>
                         <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                            <li><a href="add_model.php">Añadir Modelo</a></li>
                            <li><a href="manage_models.php">Gestionar Modelos</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="header-icons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="user-greeting">Hola, <?php echo htmlspecialchars($_SESSION['user_alias']); ?>!</span>
                    <a href="logout.php" class="btn btn-primary" style="padding: 8px 12px; font-size:0.9em; margin-left:5px;">Salir</a>
                <?php else: ?>
                    <a href="login.php" class="btn" style="padding: 8px 12px; font-size:0.9em; margin-right:5px;">Login</a>
                    <a href="register.php" class="btn btn-primary" style="padding: 8px 12px; font-size:0.9em;">Registro</a>
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
                        if ($modelo['precio'] > 0) {
                            echo '$' . number_format((float)$modelo['precio'], 2);
                        } else {
                            echo 'Gratis';
                        }
                        ?>
                    </div>
                    <div class="description">
                        <h3>Descripción del Modelo:</h3>
                        <p><?php echo nl2br(htmlspecialchars($modelo['descripcion'])); // nl2br para convertir saltos de línea en <br> ?></p>
                    </div>

                    <div class="model-actions">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form action="toggle_favorite.php" method="POST" style="display:inline;">
                                <input type="hidden" name="modelo_id" value="<?php echo $modelo['id']; ?>">
                                <?php
                                $fav_btn_class = $es_favorito_actual ? 'is-favorite' : '';
                                $fav_btn_text = $es_favorito_actual ? 'Quitar Favorito ★' : 'Añadir a Favoritos ☆';
                                ?>
                                <button type="submit" class="favorite-btn-detail <?php echo $fav_btn_class; ?>"><?php echo $fav_btn_text; ?></button>
                            </form>
                        <?php else: ?>
                            <p><a href="login.php">Inicia sesión</a> para añadir a favoritos.</p>
                        <?php endif; ?>

                        <?php
                        // Lógica de descarga/compra (simplificada)
                        $puede_descargar = false;
                        if ($modelo['precio'] == 0 || is_null($modelo['precio'])) { // Gratis
                            $puede_descargar = true;
                        } else {
                            // LÓGICA DE COMPRA (PENDIENTE):
                            // Aquí verificarías si $_SESSION['user_id'] ha comprado $modelo_id.
                            // Por ejemplo: $ha_comprado = verificar_compra($pdo, $_SESSION['user_id'], $modelo_id);
                            // if ($ha_comprado) $puede_descargar = true;
                            
                            // Por ahora, si no es gratis, mostramos botón "Comprar" que no hace nada
                            // y no se puede descargar.
                            // $puede_descargar = false; // (ya es false por defecto si el precio > 0)
                        }

                        if ($puede_descargar && !empty($modelo['archivo_stl'])): ?>
                            <a href="download_model.php?id=<?php echo $modelo['id']; ?>" class="btn-download">Descargar Modelo (.<?php echo pathinfo($modelo['archivo_stl'], PATHINFO_EXTENSION); ?>)</a>
                        <?php elseif (!$puede_descargar && $modelo['precio'] > 0): ?>
                            <a href="#comprar" class="btn-buy">Comprar por $<?php echo number_format((float)$modelo['precio'], 2); ?></a>
                            <!-- Este enlace #comprar no hará nada funcionalmente aún -->
                        <?php elseif (empty($modelo['archivo_stl'])): ?>
                            <p>Archivo de descarga no disponible.</p>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($error_message): ?>
                <div class="model-header"><h1>Error</h1></div>
                <p style="text-align:center; font-size:1.2em; color:red;"><?php echo htmlspecialchars($error_message); ?></p>
                <p style="text-align:center;"><a href="index.php" class="btn">Volver al inicio</a></p>
            <?php endif; ?>
        </div>
    </main>

    <footer> <!-- Incluye tu footer estándar de index.php -->
         <div class="container">
            <p>© <?php echo date("Y"); ?> PrintVerse - Tienda de Modelos 3D. Todos los derechos reservados.</p>
         </div>
    </footer>
</body>
</html>