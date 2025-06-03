<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit();
}

$error_message = '';
$success_message = '';

// Variables para repoblar
$nombre_modelo_val = $_POST['nombre_modelo'] ?? '';
$descripcion_val = $_POST['descripcion'] ?? '';
$precio_val = $_POST['precio'] ?? '';
$url_compra_externa_val = $_POST['url_compra_externa'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_modelo = trim($_POST['nombre_modelo']);
    $descripcion = trim($_POST['descripcion']);
    $precio_input = $_POST['precio'];
    $url_compra_externa_input = trim($_POST['url_compra_externa'] ?? null);
    
    $imagen_url_final = null;
    $archivo_stl_final = null;
    $url_compra_final_db = null;
    $precio = null; // Inicializar precio

    if (!is_numeric($precio_input) || (float)$precio_input < 0) {
        $error_message .= " El precio debe ser un número válido y no negativo.";
    } else {
        $precio = (float)$precio_input;
    }

    if (!empty($url_compra_externa_input)) {
        if (filter_var($url_compra_externa_input, FILTER_VALIDATE_URL)) {
            $url_compra_final_db = $url_compra_externa_input;
        } else {
            $error_message .= " La URL de compra externa no es válida.";
        }
    }

    if (isset($_FILES['imagen_modelo']) && $_FILES['imagen_modelo']['error'] == UPLOAD_ERR_OK) {
        $target_dir_img = "img/model_images/";
        if (!is_dir($target_dir_img)) {
            if (!mkdir($target_dir_img, 0775, true) && !is_dir($target_dir_img)) {
                $error_message .= " Fallo al crear el directorio de imágenes.";
            }
        }
        if (empty(trim($error_message))) { // Procede solo si no hay error de directorio
            $img_info = getimagesize($_FILES["imagen_modelo"]["tmp_name"]);
            if ($img_info === false) {
                $error_message .= " El archivo subido para la imagen no es una imagen válida.";
            } else {
                $imagen_extension = strtolower(pathinfo(basename($_FILES["imagen_modelo"]["name"]), PATHINFO_EXTENSION));
                $imagen_nombre_unico = 'img_' . uniqid('', true) . '.' . $imagen_extension;
                $target_file_img = $target_dir_img . $imagen_nombre_unico;
                $allowed_img_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($imagen_extension, $allowed_img_types) && $_FILES["imagen_modelo"]["size"] < 5 * 1024 * 1024) {
                    if (move_uploaded_file($_FILES["imagen_modelo"]["tmp_name"], $target_file_img)) {
                        $imagen_url_final = $target_file_img;
                    } else {
                        $error_message .= " Error al mover la imagen del modelo subida.";
                    }
                } else {
                    $error_message .= " Tipo de archivo de imagen no permitido o imagen muy grande (máx 5MB).";
                }
            }
        }
    } elseif (isset($_FILES['imagen_modelo']) && $_FILES['imagen_modelo']['error'] != UPLOAD_ERR_NO_FILE) {
        $error_message .= " Error al subir la imagen del modelo: Código " . $_FILES['imagen_modelo']['error'];
    }

    if (isset($_FILES['archivo_modelo_3d']) && $_FILES['archivo_modelo_3d']['error'] == UPLOAD_ERR_OK) {
        $target_dir_stl = "models_files/";
        if (!is_dir($target_dir_stl)) {
            if (!mkdir($target_dir_stl, 0775, true) && !is_dir($target_dir_stl)) {
                $error_message .= " Fallo al crear el directorio de archivos 3D.";
            }
        }
        if (empty(trim($error_message))) { // Procede solo si no hay error de directorio
            $stl_nombre = basename($_FILES["archivo_modelo_3d"]["name"]);
            $stl_extension = strtolower(pathinfo($stl_nombre, PATHINFO_EXTENSION));
            $stl_nombre_unico = 'model_' . uniqid('', true) . '.' . $stl_extension;
            $target_file_stl = $target_dir_stl . $stl_nombre_unico;
            $allowed_stl_types = ['stl', 'obj', '3mf'];
            if (in_array($stl_extension, $allowed_stl_types) && $_FILES["archivo_modelo_3d"]["size"] < 50 * 1024 * 1024) { // Max 50MB
                if (move_uploaded_file($_FILES["archivo_modelo_3d"]["tmp_name"], $target_file_stl)) {
                    $archivo_stl_final = $target_file_stl;
                } else {
                    $error_message .= " Error al mover el archivo 3D subido.";
                }
            } else {
                $error_message .= " Tipo de archivo 3D no permitido o archivo muy grande (máx 50MB).";
            }
        }
    } elseif (!isset($_FILES['archivo_modelo_3d']) || $_FILES['archivo_modelo_3d']['error'] == UPLOAD_ERR_NO_FILE) {
        if (empty($url_compra_final_db) || (isset($precio) && $precio <= 0)) { // Precio ya debería estar definido aquí
            $error_message .= " El archivo 3D del modelo es obligatorio para modelos gratuitos o sin URL de compra externa.";
        }
    } elseif (isset($_FILES['archivo_modelo_3d']) && $_FILES['archivo_modelo_3d']['error'] != UPLOAD_ERR_NO_FILE) {
        $error_message .= " Error al subir el archivo 3D: Código " . $_FILES['archivo_modelo_3d']['error'];
    }

    if (empty($nombre_modelo)) {
        $error_message .= " El nombre del modelo es obligatorio.";
    }
    
    // Esta condición se debe evaluar con $precio ya definido
    $stl_requerido_para_db = (empty($url_compra_final_db) || (isset($precio) && $precio <= 0));

    if (empty(trim($error_message))) { 
        if ($stl_requerido_para_db && empty($archivo_stl_final)) {
            $error_message .= " El archivo 3D es obligatorio para esta configuración de precio/URL y no se ha subido o procesado correctamente.";
        } else {
            try {
                $sql = "INSERT INTO modelos (nombre_modelo, descripcion, precio, url_compra_externa, imagen_url, archivo_stl) 
                        VALUES (:nombre_modelo, :descripcion, :precio, :url_compra_externa, :imagen_url, :archivo_stl)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre_modelo' => $nombre_modelo,
                    ':descripcion' => $descripcion,
                    ':precio' => $precio, // Usar $precio que es float
                    ':url_compra_externa' => $url_compra_final_db,
                    ':imagen_url' => $imagen_url_final,
                    ':archivo_stl' => $archivo_stl_final
                ]);
                $success_message = "¡Modelo añadido correctamente!";
                $nombre_modelo_val = ''; $descripcion_val = ''; $precio_val = ''; $url_compra_externa_val = '';
            } catch (PDOException $e) {
                $error_message = "Error al guardar el modelo en la base de datos. ";
                error_log("Error DB en add_model.php: " . $e->getMessage());
            }
        }
    }
}
$page_title = "Añadir Nuevo Modelo 3D - Panel de Administración";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/add_model.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <div class="logo"><h1><a href="index.php">Arnerazo<span class="highlight">3D</span></a></h1></div>
            <nav>
                <a href="index.php">Ver Sitio</a>
                <a href="add_model.php" class="admin-nav-active">Añadir Modelo</a>
                <a href="manage_models.php">Gestionar Modelos</a>
                <a href="manage_featured_index.php">Destacados Index</a>
                <?php if (isset($_SESSION['user_alias'])): ?>
                    <span class="user-greeting">Hola, <?php echo htmlspecialchars($_SESSION['user_alias']); ?> (<a href="logout.php">Salir</a>)</span>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="admin-panel-container">
        <form action="add_model.php" method="POST" enctype="multipart/form-data" class="admin-form-common">
            <h2>Añadir Nuevo Modelo 3D</h2>

            <?php if (!empty(trim($error_message))): ?><div class="message error"><?php echo nl2br(htmlspecialchars(trim($error_message))); ?></div><?php endif; ?>
            <?php if ($success_message): ?><div class="message success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>

            <div class="form-group">
                <label for="nombre_modelo">Nombre del Modelo:</label>
                <input type="text" id="nombre_modelo" name="nombre_modelo" required value="<?php echo htmlspecialchars($nombre_modelo_val); ?>">
            </div>
            <div class="form-group">
                <label for="descripcion">Descripción:</label>
                <textarea id="descripcion" name="descripcion"><?php echo htmlspecialchars($descripcion_val); ?></textarea>
            </div>
            <div class="form-group">
                <label for="precio">Precio (ej: 12.99, poner 0 para descarga gratuita en este sitio):</label>
                <input type="number" id="precio" name="precio" step="0.01" min="0" required value="<?php echo htmlspecialchars($precio_val); ?>">
            </div>
            
            <div class="form-group">
                <label for="url_compra_externa">URL de Compra Externa (Opcional):</label>
                <input type="url" id="url_compra_externa" name="url_compra_externa" placeholder="https://ejemplo.com/producto/modelo-xyz" value="<?php echo htmlspecialchars($url_compra_externa_val); ?>">
                <small>Si se rellena y el precio es > 0, el botón "Comprar" en la web redirigirá a esta URL. Si no, el modelo se considerará para descarga/compra en este sitio.</small>
            </div>

            <div class="form-group">
                <label for="imagen_modelo">Imagen de Portada (Opcional: JPG, PNG, GIF, WebP - max 5MB):</label>
                <input type="file" id="imagen_modelo" name="imagen_modelo" accept="image/jpeg,image/png,image/gif,image/webp">
            </div>
            <div class="form-group">
                <label for="archivo_modelo_3d">Archivo del Modelo 3D (STL, OBJ, 3MF - max 50MB):</label>
                <input type="file" id="archivo_modelo_3d" name="archivo_modelo_3d" accept=".stl,.obj,.3mf">
                <small>Obligatorio si el modelo es gratuito en este sitio o no tiene URL de compra externa.</small>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-submit">Añadir Modelo</button>
            </div>
        </form>
    </div>

    <footer class="admin-footer">
        <div class="container"><p>© <?php echo date("Y"); ?> Arnerazo3D Admin Panel</p></div>
    </footer>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollToPlugin.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="./js/animation.js"></script>
</body>
</html>