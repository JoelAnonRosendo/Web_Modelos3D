<?php
session_start();
require 'db_config.php'; // Asegúrate de que la ruta a db_config.php es correcta

// --- Obtener categorías para el filtro ---
$categorias_filtro = [];
try {
    // Asumiendo que tendrás una tabla 'categorias' con 'id' y 'nombre_categoria'
    $stmt_cats = $pdo->query("SELECT id, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC");
    $categorias_filtro = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener categorías en all_models.php: " . $e->getMessage());
    // No mostrar error de categorías al usuario, el filtro simplemente no funcionará completamente
}

// --- Determinar el filtro de categoría activo ---
$filtro_categoria_sql_condition = "";
$filter_params = []; // Cambiado a params para claridad con el query principal
$categoria_seleccionada_id = null;

if (isset($_GET['categoria_id']) && filter_var($_GET['categoria_id'], FILTER_VALIDATE_INT) && (int)$_GET['categoria_id'] > 0) {
    $categoria_seleccionada_id = (int)$_GET['categoria_id'];
    // Asumiendo que en tu tabla 'modelos' tienes una columna 'categoria_id'
    $filtro_categoria_sql_condition = "WHERE m.categoria_id = :categoria_id";
    $filter_params[':categoria_id'] = $categoria_seleccionada_id;
}
// Si categoria_id no está seteado, es 0, o inválido, se muestran todos (TODO)


// --- Obtener modelos de la base de datos ---
$modelos = [];
$page_error_message = '';
try {
    // Se usa 'm' como alias para la tabla modelos
    $sql_modelos = "SELECT m.id, m.nombre_modelo, m.precio, m.imagen_url, m.descripcion
                    FROM modelos m
                    $filtro_categoria_sql_condition
                    ORDER BY m.nombre_modelo ASC";

    $stmt_modelos = $pdo->prepare($sql_modelos);
    $stmt_modelos->execute($filter_params);
    $modelos = $stmt_modelos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $modelos = []; // Dejar array vacío si hay error
    error_log("Error al obtener modelos en all_models.php: " . $e->getMessage());
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
        error_log("Error al obtener favoritos en all_models.php para usuario {$_SESSION['user_id']}: " . $e->getMessage());
    }
}

$page_title = "Modelos 3D - PrintVerse"; // Título más general, o dinámico si se filtra
if ($categoria_seleccionada_id && count($categorias_filtro) > 0) {
    foreach ($categorias_filtro as $cat_info) {
        if ($cat_info['id'] == $categoria_seleccionada_id) {
            $page_title = htmlspecialchars($cat_info['nombre_categoria']) . " - Modelos 3D - PrintVerse";
            break;
        }
    }
}
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
        /* Estilos del index.php para botones de favorito y saludo */
        .favorite-btn {
            background-color: #6c757d; color: white; padding: 5px 10px;
            border: none; border-radius: 4px; cursor: pointer; font-size: 0.8em;
            margin-top: 10px; display: inline-block; transition: background-color 0.2s ease-in-out;
        }
        .favorite-btn.is-favorite {
            background-color: var(--accent-color, #ffc107); color: var(--header-bg, #333);
        }
        .user-greeting {
            margin-right: 15px; color: var(--header-text, #f8f9fa);
            display: inline-block; vertical-align: middle;
        }
        .header-icons .btn, .header-icons .btn-primary { vertical-align: middle; }

        /* Estilos específicos para esta página (filtros) */
        .page-header-bar {
            margin-bottom: 30px;
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .page-header-bar h2 {
            margin-bottom: 0;
            text-align: left;
            font-size: 2em;
        }
        .filter-controls {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 15px;
        }
        .filter-dropdown { /* Estilo para el SELECT */
            padding: 8px 30px 8px 15px; /* Espacio derecha para la flecha */
            border: 1.5px solid #000;
            border-radius: 20px; /* Bordes redondeados */
            background-color: var(--card-bg-color, #fff);
            color: var(--text-color, #333);
            cursor: pointer;
            font-family: 'Roboto', sans-serif;
            font-size: 0.9em;
            min-width: 150px; /* Ancho mínimo */
            appearance: none; /* Quitar flecha por defecto del navegador */
            -webkit-appearance: none;
            -moz-appearance: none;
            /* Flecha personalizada como background */
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black' width='18px' height='18px'%3e%3cpath d='M7 10l5 5 5-5H7z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center; /* Posición de la flecha */
            background-size: 12px; /* Tamaño de la flecha */
        }
        .filter-dropdown:focus { /* Opcional: estilo al enfocar */
            outline: none;
            border-color: var(--primary-color); /* o un color de acento */
            box-shadow: 0 0 0 2px rgba(0,123,255,.25); /* Similar a Bootstrap focus */
        }
        .product-grid { padding-top: 0; }
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
                    <li><a href="all_models.php" style="font-weight:bold;">Modelos</a></li>
                    <li><a href="index.php#categories">Categorías Populares</a></li> 
                    <li><a href="index.php#about">Sobre Nosotros</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="favorites.php">Mis Favoritos</a></li>
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
                    <a href="logout.php" class="btn btn-primary" style="padding: 8px 12px; font-size:0.9em;">Salir</a>
                <?php else: ?>
                    <a href="login.php" class="btn" style="padding: 8px 12px; font-size:0.9em; margin-right:5px;">Login</a>
                    <a href="register.php" class="btn btn-primary" style="padding: 8px 12px; font-size:0.9em;">Registro</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <section id="all-models-display" class="product-grid">
            <div class="container">
                <div class="page-header-bar">
                    <h2>MODELOS 3D</h2>
                    <div class="filter-controls">
                        <form method="GET" action="all_models.php" id="categoryFilterForm" style="display: inline;">
                            <select name="categoria_id" class="filter-dropdown" onchange="document.getElementById('categoryFilterForm').submit();">
                                <option value="0" <?php echo (!$categoria_seleccionada_id) ? 'selected' : ''; ?>>TODO</option>
                                <?php if (!empty($categorias_filtro)): ?>
                                    <?php foreach ($categorias_filtro as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($categoria_seleccionada_id == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['nombre_categoria']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php /* Si no quieres que se envíe automáticamente con onchange, añade un botón
                            <button type="submit" class="btn" style="margin-left:10px; padding: 8px 15px; font-size:0.9em;">Filtrar</button>
                            */ ?>
                        </form>
                    </div>
                </div>
                
                <?php if (!empty($page_error_message)): echo "<p class='message error' style='text-align:center;'>".htmlspecialchars($page_error_message)."</p>"; endif; ?>
                
                <div class="grid-container">
                    <?php if (!empty($modelos)): ?>
                        <?php foreach ($modelos as $modelo): ?>
                            <div class="product-card">
                                <a href="modelo_detalle.php?id=<?php echo $modelo['id']; ?>" style="text-decoration:none; display:block; overflow:hidden;">
                                    <img src="<?php echo htmlspecialchars(!empty($modelo['imagen_url']) ? $modelo['imagen_url'] : 'img/placeholder.png'); ?>" alt="Imagen de <?php echo htmlspecialchars($modelo['nombre_modelo']); ?>">
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
                        <?php if (empty($page_error_message)): // Solo muestra este mensaje si no hay error global ?>
                            <p style="text-align:center; grid-column: 1 / -1;">No hay modelos que coincidan con tu filtro. Intenta con otra categoría o selecciona "TODO".</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="./animation.js"></script>
</body>
</html>