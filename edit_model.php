<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit();
}

$error_message = '';
$success_message = '';
$modelo_id = null;
$modelo_actual_data = null; // Renombrar para claridad

// Variables para repoblar el formulario
$nombre_modelo_val = '';
$descripcion_val = '';
$precio_val = '';
$url_compra_externa_val = ''; // NUEVA
$imagen_actual_url = null;
$archivo_actual_url = null;


// Obtener el ID y cargar datos si es GET
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $modelo_id = (int)$_GET['id'];
    if ($_SERVER["REQUEST_METHOD"] != "POST") { // Solo cargar en GET inicial
        try {
            $stmt = $pdo->prepare("SELECT * FROM modelos WHERE id = :id");
            $stmt->execute(['id' => $modelo_id]);
            $modelo_actual_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$modelo_actual_data) {
                $error_message = "Modelo no encontrado.";
                $modelo_id = null; 
            } else {
                $nombre_modelo_val = $modelo_actual_data['nombre_modelo'];
                $descripcion_val = $modelo_actual_data['descripcion'];
                $precio_val = $modelo_actual_data['precio'];
                $url_compra_externa_val = $modelo_actual_data['url_compra_externa'] ?? '';
                $imagen_actual_url = $modelo_actual_data['imagen_url'];
                $archivo_actual_url = $modelo_actual_data['archivo_stl'];
            }
        } catch (PDOException $e) {
            $error_message = "Error al cargar el modelo: " . $e->getMessage();
            $modelo_id = null;
        }
    }
} elseif ($_SERVER["REQUEST_METHOD"] != "POST") { // Si no hay ID en GET y no es un POST
    $error_message = "ID de modelo no proporcionado o inválido.";
}

// Procesar el formulario cuando se envíe (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['modelo_id']) && filter_var($_POST['modelo_id'], FILTER_VALIDATE_INT)) {
        $modelo_id = (int)$_POST['modelo_id'];
        // Volver a cargar los datos originales para tener las rutas de archivo actuales y el valor de la URL externa
        $stmt_curr = $pdo->prepare("SELECT imagen_url, archivo_stl, url_compra_externa FROM modelos WHERE id = :id");
        $stmt_curr->execute([':id' => $modelo_id]);
        $archivos_db = $stmt_curr->fetch(PDO::FETCH_ASSOC);

        if(!$archivos_db){
            $error_message .= " Modelo original no encontrado para actualización. ";
            $modelo_id = null; // Invalidar si no podemos continuar
        } else {
            // Rutas de archivos para posible eliminación si se reemplazan
            $imagen_actual_url_db = $archivos_db['imagen_url'];
            $archivo_actual_url_db = $archivos_db['archivo_stl'];
            // La URL externa actual para repoblar el campo si no se envía una nueva en POST
            $url_compra_externa_val = $_POST['url_compra_externa'] ?? $archivos_db['url_compra_externa'] ?? '';
        }

    } else {
        $error_message .= " ID de modelo faltante en el envío. ";
        $modelo_id = null;
    }

    // Repoblar valores del formulario enviados en POST
    $nombre_modelo_val = trim($_POST['nombre_modelo']);
    $descripcion_val = trim($_POST['descripcion']);
    $precio_val = $_POST['precio'];
    // $url_compra_externa_val ya se pobló arriba condicionalmente

    if($modelo_id) { // Solo continuar si tenemos un modelo_id válido
        $nombre_modelo_post = trim($_POST['nombre_modelo']);
        $descripcion_post = trim($_POST['descripcion']);
        $precio_input_post = $_POST['precio'];
        $url_compra_externa_input_post = trim($_POST['url_compra_externa'] ?? null);

        $imagen_url_para_db = $imagen_actual_url_db ?? null;
        $archivo_stl_para_db = $archivo_actual_url_db ?? null;
        $url_compra_final_para_db = $archivos_db['url_compra_externa'] ?? null; // Por defecto mantener la actual de DB


        if (!is_numeric($precio_input_post) || (float)$precio_input_post < 0) {
            $error_message .= " El precio debe ser un número válido y no negativo.";
        } else {
            $precio_post_float = (float)$precio_input_post;
        }

        // Validar URL externa si se proporcionó
        if (!empty($url_compra_externa_input_post)) {
            if (filter_var($url_compra_externa_input_post, FILTER_VALIDATE_URL)) {
                $url_compra_final_para_db = $url_compra_externa_input_post;
            } else {
                $error_message .= " La URL de compra externa no es válida.";
            }
        } else { // Si se envía vacía, se debe setear a NULL en la DB
            $url_compra_final_para_db = null;
        }


        // Manejo de actualización de imagen (código igual, omitido por brevedad)
        // ... si se sube nueva imagen, $imagen_url_para_db = $nueva_ruta;
        // ... si se marca eliminar_imagen_actual, $imagen_url_para_db = null;
        // (Lógica de imagen de edit_model.php anterior)
        if (isset($_FILES['nueva_imagen_modelo']) && $_FILES['nueva_imagen_modelo']['error'] == UPLOAD_ERR_OK) {
            $target_dir_img = "img/model_images/";
            $img_info = getimagesize($_FILES["nueva_imagen_modelo"]["tmp_name"]);
            if ($img_info === false) { $error_message .= " El nuevo archivo para la imagen no es válido."; }
            else { /* ... lógica de subida, mover, borrar vieja ... */ 
                $imagen_extension = strtolower(pathinfo(basename($_FILES["nueva_imagen_modelo"]["name"]), PATHINFO_EXTENSION));
                $imagen_nombre_unico = 'img_' . uniqid('', true) . '.' . $imagen_extension;
                $target_file_img = $target_dir_img . $imagen_nombre_unico;
                $allowed_img_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($imagen_extension, $allowed_img_types) && $_FILES["nueva_imagen_modelo"]["size"] < 5 * 1024 * 1024) {
                    if (move_uploaded_file($_FILES["nueva_imagen_modelo"]["tmp_name"], $target_file_img)) {
                        if (!empty($imagen_actual_url_db) && file_exists($imagen_actual_url_db)) { unlink($imagen_actual_url_db); }
                        $imagen_url_para_db = $target_file_img;
                    } else { $error_message .= " Error al mover la nueva imagen."; }
                } else { $error_message .= " Tipo de nueva imagen no permitido o muy grande.";}
            }
        } elseif (isset($_FILES['nueva_imagen_modelo']) && $_FILES['nueva_imagen_modelo']['error'] != UPLOAD_ERR_NO_FILE) {
            $error_message .= " Error al subir la nueva imagen: Código " . $_FILES['nueva_imagen_modelo']['error'];
        }
        if (isset($_POST['eliminar_imagen_actual']) && $_POST['eliminar_imagen_actual'] == '1') {
            if (!empty($imagen_actual_url_db) && file_exists($imagen_actual_url_db)) { unlink($imagen_actual_url_db); }
            $imagen_url_para_db = null;
        }


        // Manejo de actualización de archivo 3D (código igual, omitido por brevedad)
        // ... si se sube nuevo archivo, $archivo_stl_para_db = $nueva_ruta_stl;
        // (Lógica de archivo 3D de edit_model.php anterior)
        if (isset($_FILES['nuevo_archivo_modelo_3d']) && $_FILES['nuevo_archivo_modelo_3d']['error'] == UPLOAD_ERR_OK) {
            $target_dir_stl = "models_files/";
            /* ... lógica de subida, mover, borrar viejo ... */
            $stl_extension = strtolower(pathinfo(basename($_FILES["nuevo_archivo_modelo_3d"]["name"]), PATHINFO_EXTENSION));
            $stl_nombre_unico = 'model_' . uniqid('', true) . '.' . $stl_extension;
            $target_file_stl = $target_dir_stl . $stl_nombre_unico;
            $allowed_stl_types = ['stl', 'obj', '3mf'];
            if (in_array($stl_extension, $allowed_stl_types) && $_FILES["nuevo_archivo_modelo_3d"]["size"] < 50 * 1024 * 1024) {
                if (move_uploaded_file($_FILES["nuevo_archivo_modelo_3d"]["tmp_name"], $target_file_stl)) {
                    if (!empty($archivo_actual_url_db) && file_exists($archivo_actual_url_db)) { unlink($archivo_actual_url_db); }
                    $archivo_stl_para_db = $target_file_stl;
                } else { $error_message .= " Error al mover el nuevo archivo 3D."; }
            } else { $error_message .= " Tipo de nuevo archivo 3D no permitido o muy grande."; }
        } elseif (isset($_FILES['nuevo_archivo_modelo_3d']) && $_FILES['nuevo_archivo_modelo_3d']['error'] != UPLOAD_ERR_NO_FILE) {
            $error_message .= " Error al subir el nuevo archivo 3D: Código " . $_FILES['nuevo_archivo_modelo_3d']['error'];
        }

        // Validar si el archivo STL sigue siendo necesario después de la edición
        $stl_requerido_tras_edicion = (empty($url_compra_final_para_db) || (isset($precio_post_float) && $precio_post_float <= 0));
        if ($stl_requerido_tras_edicion && empty($archivo_stl_para_db)) {
            $error_message .= " El archivo 3D es obligatorio si el modelo es gratuito o no tiene URL de compra externa.";
        }


        if (empty($nombre_modelo_post)) {
            $error_message .= " El nombre del modelo es obligatorio.";
        }
        
        if (empty(trim($error_message))) {
            try {
                $sql = "UPDATE modelos SET 
                            nombre_modelo = :nombre_modelo, 
                            descripcion = :descripcion, 
                            precio = :precio, 
                            url_compra_externa = :url_compra_externa,
                            imagen_url = :imagen_url, 
                            archivo_stl = :archivo_stl 
                        WHERE id = :id";
                $stmt_update = $pdo->prepare($sql);
                $stmt_update->execute([
                    ':nombre_modelo' => $nombre_modelo_post,
                    ':descripcion' => $descripcion_post,
                    ':precio' => $precio_post_float,
                    ':url_compra_externa' => $url_compra_final_para_db,
                    ':imagen_url' => $imagen_url_para_db,
                    ':archivo_stl' => $archivo_stl_para_db,
                    ':id' => $modelo_id
                ]);
                $success_message = "¡Modelo actualizado correctamente! <a href='manage_models.php'>Volver a la lista</a>";
                // Actualizar variables para mostrar en el formulario si no se redirige
                $imagen_actual_url = $imagen_url_para_db;
                $archivo_actual_url = $archivo_stl_para_db;
                $url_compra_externa_val = $url_compra_final_para_db; // Para que el campo <input> refleje el cambio

            } catch (PDOException $e) {
                $error_message = "Error al actualizar el modelo en la base de datos: " . $e->getMessage();
            }
        }
    } // fin if($modelo_id) del POST
}


$page_title = ($modelo_id && !$error_message && !$success_message) ? "Editar Modelo #" . htmlspecialchars($modelo_id) : "Editar Modelo - Admin";
if ($modelo_id && !$_POST && !$modelo_actual_data && !$error_message) { // Si hubo un GET válido pero no se cargó el modelo por error de DB por ej.
    if(empty($error_message)) $error_message = "No se pudieron cargar los datos del modelo para editar.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-panel-container { max-width: 700px; margin: 30px auto; padding: 0 15px; }
        .admin-form { padding: 25px; background: var(--card-bg-color, #fff); border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); color: var(--text-color, #333); }
        .admin-form h2 { text-align: center; margin-bottom: 25px; font-family: 'Orbitron', sans-serif; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; font-size: 0.95em;}
        .form-group input[type="text"], .form-group input[type="number"], .form-group input[type="file"], .form-group textarea, .form-group input[type="url"] { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 1em;}
        .form-group textarea { min-height: 120px; resize: vertical; }
        .form-group .btn-submit { width: 100%; padding: 12px 20px; background-color: var(--primary-color, #007bff); color: white; border: none; border-radius: 5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: background-color 0.3s ease;}
        .form-group .btn-submit:hover { background-color: darken(var(--primary-color, #007bff), 10%);}
        .message { padding: 12px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-size: 0.95em;}
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;}
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;}
        .current-file { font-size: 0.9em; color: #555; margin-top: 5px; }
        .current-file img { max-width:150px; max-height:100px; border:1px solid #eee; display:block; margin-top:5px;}
        .form-group small { font-size: 0.85em; color: #555; display: block; margin-top: 5px; }
        .admin-header, .admin-footer { background-color: var(--header-bg, #212529); color: var(--header-text, #f8f9fa); padding: 15px 0; text-align: center;}
        .admin-header .container, .admin-footer .container { display: flex; justify-content: space-between; align-items: center; max-width: 900px; margin: 0 auto; padding: 0 15px;}
        .admin-header a { color: var(--accent-color, #ffc107); text-decoration: none; margin: 0 10px; }
        .admin-header h1 a { color: var(--header-text, #f8f9fa); text-decoration:none;}
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <div class="logo"><h1><a href="index.php" style="text-decoration:none; color:var(--header-text);">Arnerazo<span class="highlight">3D</span></a></h1></div>
            <nav>
                <a href="index.php">Ver Sitio</a>
                <a href="add_model.php">Añadir Modelo</a>
                <a href="manage_models.php">Gestionar Modelos</a>
                <?php if (isset($_SESSION['user_alias'])): ?>
                    <span style="margin-left:15px;">Hola, <?php echo htmlspecialchars($_SESSION['user_alias']); ?> (<a href="logout.php">Salir</a>)</span>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="admin-panel-container">
        
        <?php if (!empty(trim($error_message))): ?><div class="message error"><?php echo nl2br(htmlspecialchars(trim($error_message))); ?></div><?php endif; ?>
        <?php if ($success_message): ?><div class="message success"><?php echo $success_message; ?></div><?php endif; ?>

        <?php if ($modelo_id && ($modelo_actual_data || $_SERVER["REQUEST_METHOD"] == "POST") ): // Solo mostrar formulario si tenemos ID y datos (o es un POST con datos) ?>
            <form action="edit_model.php?id=<?php echo $modelo_id; ?>" method="POST" enctype="multipart/form-data" class="admin-form">
                <h2>Editar Modelo #<?php echo htmlspecialchars($modelo_id); ?></h2>
                <input type="hidden" name="modelo_id" value="<?php echo htmlspecialchars($modelo_id); ?>">

                <div class="form-group">
                    <label for="nombre_modelo">Nombre del Modelo:</label>
                    <input type="text" id="nombre_modelo" name="nombre_modelo" required value="<?php echo htmlspecialchars($nombre_modelo_val); ?>">
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion"><?php echo htmlspecialchars($descripcion_val); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="precio">Precio (ej: 12.99):</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" required value="<?php echo htmlspecialchars($precio_val); ?>">
                </div>
                
                <div class="form-group">
                    <label for="url_compra_externa">URL de Compra Externa (Opcional):</label>
                    <input type="url" id="url_compra_externa" name="url_compra_externa" placeholder="https://ejemplo.com/producto/modelo-xyz" value="<?php echo htmlspecialchars($url_compra_externa_val); ?>">
                    <small>Si se rellena y el precio es > 0, el botón "Comprar" redirigirá aquí.</small>
                </div>

                <div class="form-group">
                    <label for="nueva_imagen_modelo">Cambiar Imagen de Portada (JPG, PNG, GIF, WebP - max 5MB):</label>
                    <input type="file" id="nueva_imagen_modelo" name="nueva_imagen_modelo" accept="image/jpeg,image/png,image/gif,image/webp">
                    <?php if ($imagen_actual_url): ?>
                        <div class="current-file">
                            Imagen actual: <a href="<?php echo htmlspecialchars($imagen_actual_url); ?>" target="_blank"><?php echo basename($imagen_actual_url); ?></a><br>
                            <img src="<?php echo htmlspecialchars($imagen_actual_url); ?>" alt="Imagen actual">
                            <label style="font-weight:normal; margin-top:5px;"><input type="checkbox" name="eliminar_imagen_actual" value="1"> Eliminar imagen actual</label>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="nuevo_archivo_modelo_3d">Cambiar Archivo 3D (STL, OBJ, 3MF - max 50MB):</label>
                    <input type="file" id="nuevo_archivo_modelo_3d" name="nuevo_archivo_modelo_3d" accept=".stl,.obj,.3mf">
                    <?php if ($archivo_actual_url): ?>
                        <div class="current-file">
                            Archivo 3D actual: <a href="<?php echo htmlspecialchars($archivo_actual_url); ?>" target="_blank" download><?php echo basename($archivo_actual_url); ?></a>
                            <p style="font-size:0.8em; color:#777;">(Si no seleccionas uno nuevo, se mantendrá el actual).</p>
                        </div>
                    <?php else: ?>
                        <p style="color:orange; font-weight:bold;">Este modelo no tiene un archivo 3D asociado. Es recomendable subir uno si es para descarga local/gratuita.</p>
                    <?php endif; ?>
                    <small>El archivo 3D es obligatorio si el modelo es gratuito en este sitio o no tiene URL de compra externa.</small>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-submit">Actualizar Modelo</button>
                </div>
                <p style="text-align:center;"><a href="manage_models.php">Cancelar y volver a la lista</a></p>
            </form>
        <?php elseif (!$success_message): 
            echo '<div class="message error">No se pudo cargar el formulario. Verifique el ID o <a href="manage_models.php">vuelva a la lista</a>.</div>';
        endif; ?>
    </div>

    <footer class="admin-footer">
        <div class="container"><p>© <?php echo date("Y"); ?> PrintVerse Admin Panel</p></div>
    </footer>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollToPlugin.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="./animation.js"></script>
</body>
</html>