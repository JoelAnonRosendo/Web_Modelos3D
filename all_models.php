<?php
session_start();
require 'db_config.php';

$categorias_filtro = [];
try {
    $stmt_cats = $pdo->query("SELECT id, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC");
    $categorias_filtro = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener categorías en all_models.php: " . $e->getMessage());
}

$filtro_categoria_sql_condition = "";
$filter_params = [];
$categoria_seleccionada_id = null;

if (isset($_GET['categoria_id']) && filter_var($_GET['categoria_id'], FILTER_VALIDATE_INT) && (int)$_GET['categoria_id'] > 0) {
    $categoria_seleccionada_id = (int)$_GET['categoria_id'];
    // Asumiendo que en tu tabla 'modelos' tienes una columna 'categoria_id'
    // Si la columna se llama diferente, actualiza 'm.categoria_id' aquí.
    $filtro_categoria_sql_condition = "WHERE m.categoria_id = :categoria_id";
    $filter_params[':categoria_id'] = $categoria_seleccionada_id;
}

$modelos = [];
$page_error_message = '';
try {
    $sql_modelos = "SELECT m.id, m.nombre_modelo, m.precio, m.imagen_url, m.descripcion
                    FROM modelos m
                    $filtro_categoria_sql_condition
                    ORDER BY m.nombre_modelo ASC";

    $stmt_modelos = $pdo->prepare($sql_modelos);
    $stmt_modelos->execute($filter_params);
    $modelos = $stmt_modelos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $modelos = [];
    error_log("Error al obtener modelos en all_models.php: " . $e->getMessage());
    $page_error_message = "Hubo un problema al cargar los modelos. Inténtelo más tarde.";
}

$favoritos_usuario = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt_fav = $pdo->prepare("SELECT modelo_id FROM favoritos WHERE usuario_id = :user_id");
        $stmt_fav->execute(['user_id' => $_SESSION['user_id']]);
        $ids_favoritos = $stmt_fav->fetchAll(PDO::FETCH_COLUMN);
        $favoritos_usuario = array_flip($ids_favoritos); // Facilita la búsqueda con isset()
    } catch (PDOException $e) {
        error_log("Error al obtener favoritos en all_models.php para usuario {$_SESSION['user_id']}: " . $e->getMessage());
    }
}

$page_title = "Modelos 3D - Arnerazo3D";
if ($categoria_seleccionada_id && count($categorias_filtro) > 0) {
    foreach ($categorias_filtro as $cat_info) {
        if ($cat_info['id'] == $categoria_seleccionada_id) {
            $page_title = htmlspecialchars($cat_info['nombre_categoria']) . " - Modelos 3D - Arnerazo3D";
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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/all_models.css">
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
                    <li><a href="all_models.php" class="nav-active">Modelos</a></li>
                    <li><a href="index.php#categories">Categorías Populares</a></li> 
                    <li><a href="index.php#about">Sobre Nosotros</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="favorites.php">Mis Favoritos</a></li>
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
        <section id="all-models-display" class="product-grid">
            <div class="container">
                <div class="page-header-bar">
                    <h2>MODELOS 3D</h2>
                    <div class="filter-controls">
                        <form method="GET" action="all_models.php" id="categoryFilterForm">
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
                            <?php /* <button type="submit" class="btn btn-filter">Filtrar</button> */ ?>
                        </form>
                    </div>
                </div>
                
                <?php if (!empty($page_error_message)): echo "<p class='message error'>".htmlspecialchars($page_error_message)."</p>"; endif; ?>
                
                <div class="grid-container">
                    <?php if (!empty($modelos)): ?>
                        <?php foreach ($modelos as $modelo): ?>
                            <div class="product-card">
                                <a href="modelo_detalle.php?id=<?php echo $modelo['id']; ?>" class="product-image-link">
                                    <img src="<?php echo htmlspecialchars(!empty($modelo['imagen_url']) ? $modelo['imagen_url'] : 'img/placeholder.png'); ?>" alt="Imagen de <?php echo htmlspecialchars($modelo['nombre_modelo']); ?>">
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
                                                <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
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
                            <p class="no-models-message">No hay modelos que coincidan con tu filtro. Intenta con otra categoría o selecciona "TODO".</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="./js/animation.js"></script>
</body>
</html>