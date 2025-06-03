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
$modelo_actual_data = null; // Para almacenar los datos originales cargados en GET

// Variables para repoblar el formulario (se llenan desde DB en GET o desde POST si hay error)
$nombre_modelo_val = '';
$descripcion_val = '';
$precio_val = '';
$url_compra_externa_val = '';
$imagen_actual_url = null; // URL de la imagen a mostrar en el form (puede ser de la DB o la nueva tras un POST exitoso)
$archivo_actual_url = null; // URL del STL a mostrar en el form

// Variables para almacenar las URLs de los archivos actualmente en la BD ANTES de un posible UPDATE
$imagen_actual_url_db = null;
$archivo_actual_url_db = null;


// --- Cargar datos iniciales en GET ---
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $modelo_id = (int)$_GET['id'];
    if ($_SERVER["REQUEST_METHOD"] != "POST") { // Solo cargar de DB en la primera visita GET
        try {
            $stmt = $pdo->prepare("SELECT * FROM modelos WHERE id = :id");
            $stmt->execute(['id' => $modelo_id]);
            $modelo_actual_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$modelo_actual_data) {
                $error_message = "Modelo no encontrado.";
                $modelo_id = null; 
            } else {
                // Poblar campos del formulario y las URLs de DB
                $nombre_modelo_val = $modelo_actual_data['nombre_modelo'];
                $descripcion_val = $modelo_actual_data['descripcion'];
                $precio_val = $modelo_actual_data['precio'];
                $url_compra_externa_val = $modelo_actual_data['url_compra_externa'] ?? '';
                $imagen_actual_url = $modelo_actual_data['imagen_url']; // Para mostrar en el form
                $archivo_actual_url = $modelo_actual_data['archivo_stl']; // Para mostrar en el form
                // Guardar también las URLs de la DB para la lógica de borrado/actualización
                $imagen_actual_url_db = $modelo_actual_data['imagen_url'];
                $archivo_actual_url_db = $modelo_actual_data['archivo_stl'];
            }
        } catch (PDOException $e) {
            $error_message = "Error al cargar el modelo: " . $e->getMessage();
            error_log("Error DB en edit_model.php (GET): " . $e->getMessage());
            $modelo_id = null;
        }
    }
} elseif ($_SERVER["REQUEST_METHOD"] != "POST") { // Si no es GET con ID y no es POST, error
    $error_message = "ID de modelo no proporcionado o inválido.";
}

// --- Procesar el formulario cuando se envíe (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['modelo_id']) && filter_var($_POST['modelo_id'], FILTER_VALIDATE_INT)) {
        $modelo_id = (int)$_POST['modelo_id'];
        
        // Para la lógica de actualización, necesitamos las URLs de los archivos que ESTÁN en la DB *antes* del intento de update
        // Estas se obtienen al cargar la página por GET, pero si hay un error de POST y se recarga,
        // es mejor volver a consultarlas o pasarlas en campos hidden (consultar es más seguro).
        $stmt_current_db = $pdo->prepare("SELECT imagen_url, archivo_stl, url_compra_externa FROM modelos WHERE id = :id");
        $stmt_current_db->execute([':id' => $modelo_id]);
        $current_db_files = $stmt_current_db->fetch(PDO::FETCH_ASSOC);

        if (!$current_db_files) {
            $error_message .= " No se pudo encontrar el modelo original para la actualización (ID: $modelo_id). ";
            $modelo_id = null; // Detener el proceso
        } else {
            $imagen_actual_url_db = $current_db_files['imagen_url'];
            $archivo_actual_url_db = $current_db_files['archivo_stl'];
        }
    } else {
        $error_message .= " ID de modelo faltante en el envío POST. ";
        $modelo_id = null;
    }

    // Repoblar valores del formulario con los datos del POST para que el usuario no los pierda en caso de error
    $nombre_modelo_val = trim($_POST['nombre_modelo'] ?? '');
    $descripcion_val = trim($_POST['descripcion'] ?? '');
    $precio_val = $_POST['precio'] ?? ''; // Validar como numérico después
    $url_compra_externa_val = trim($_POST['url_compra_externa'] ?? '');
    // Para mostrar en el form, si hay error, las URLs de archivo que estaban antes del POST
    $imagen_actual_url = $imagen_actual_url_db; 
    $archivo_actual_url = $archivo_actual_url_db;


    if($modelo_id) { // Solo continuar si tenemos un modelo_id válido del POST
        $nombre_modelo_post = trim($_POST['nombre_modelo']);
        $descripcion_post = trim($_POST['descripcion']);
        $precio_input_post = $_POST['precio'];
        $url_compra_externa_input_post = trim($_POST['url_compra_externa'] ?? null);

        // Estas serán las URLs que irán a la BD si todo sale bien
        $imagen_url_para_db = $imagen_actual_url_db; // Por defecto, mantener la de la DB
        $archivo_stl_para_db = $archivo_actual_url_db; // Por defecto, mantener la de la DB
        $url_compra_final_para_db = $current_db_files['url_compra_externa'] ?? null; // Por defecto

        $precio_post_float = null; // Inicializar
        if (!is_numeric($precio_input_post) || (float)$precio_input_post < 0) {
            $error_message .= " El precio debe ser un número válido y no negativo.";
        } else {
            $precio_post_float = (float)$precio_input_post;
        }

        if (!empty($url_compra_externa_input_post)) {
            if (filter_var($url_compra_externa_input_post, FILTER_VALIDATE_URL)) {
                $url_compra_final_para_db = $url_compra_externa_input_post;
            } else {
                $error_message .= " La URL de compra externa no es válida.";
            }
        } else { // Si se envía vacía, se debe setear a NULL en la DB
            $url_compra_final_para_db = null;
        }

        // --- Manejo de la nueva imagen ---
        if (isset($_FILES['nueva_imagen_modelo']) && $_FILES['nueva_imagen_modelo']['error'] == UPLOAD_ERR_OK) {
            $target_dir_img = "img/model_images/";
            if (!is_dir($target_dir_img)) { if (!mkdir($target_dir_img, 0775, true) && !is_dir($target_dir_img)) { $error_message .= " Fallo al crear el directorio de imágenes."; }}
            if(empty(trim($error_message))) {
                $img_info = getimagesize($_FILES["nueva_imagen_modelo"]["tmp_name"]);
                if ($img_info === false) { $error_message .= " El nuevo archivo para la imagen no es válido."; }
                else {
                    $imagen_extension = strtolower(pathinfo(basename($_FILES["nueva_imagen_modelo"]["name"]), PATHINFO_EXTENSION));
                    $imagen_nombre_unico = 'img_' . uniqid('', true) . '.' . $imagen_extension;
                    $target_file_img_new = $target_dir_img . $imagen_nombre_unico;
                    $allowed_img_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (in_array($imagen_extension, $allowed_img_types) && $_FILES["nueva_imagen_modelo"]["size"] < 5 * 1024 * 1024) {
                        if (move_uploaded_file($_FILES["nueva_imagen_modelo"]["tmp_name"], $target_file_img_new)) {
                            if (!empty($imagen_actual_url_db) && file_exists($imagen_actual_url_db)) { unlink($imagen_actual_url_db); }
                            $imagen_url_para_db = $target_file_img_new; // Actualizar para la DB
                        } else { $error_message .= " Error al mover la nueva imagen."; }
                    } else { $error_message .= " Tipo de nueva imagen no permitido o muy grande.";}
                }
            }
        } elseif (isset($_FILES['nueva_imagen_modelo']) && $_FILES['nueva_imagen_modelo']['error'] != UPLOAD_ERR_NO_FILE) {
            $error_message .= " Error al subir la nueva imagen: Código " . $_FILES['nueva_imagen_modelo']['error'];
        }
        
        // Checkbox "Eliminar imagen actual" (solo si no se subió una nueva que la reemplace)
        if (isset($_POST['eliminar_imagen_actual']) && $_POST['eliminar_imagen_actual'] == '1' && !(isset($_FILES['nueva_imagen_modelo']) && $_FILES['nueva_imagen_modelo']['error'] == UPLOAD_ERR_OK)) {
            if (!empty($imagen_actual_url_db) && file_exists($imagen_actual_url_db)) { unlink($imagen_actual_url_db); }
            $imagen_url_para_db = null; // Setear a null en DB
        }

        // --- Manejo del nuevo archivo 3D ---
        if (isset($_FILES['nuevo_archivo_modelo_3d']) && $_FILES['nuevo_archivo_modelo_3d']['error'] == UPLOAD_ERR_OK) {
            $target_dir_stl = "models_files/";
            if (!is_dir($target_dir_stl)) { if (!mkdir($target_dir_stl, 0775, true) && !is_dir($target_dir_stl)) { $error_message .= " Fallo al crear el directorio de archivos 3D."; }}
            if(empty(trim($error_message))) {
                $stl_extension = strtolower(pathinfo(basename($_FILES["nuevo_archivo_modelo_3d"]["name"]), PATHINFO_EXTENSION));
                $stl_nombre_unico = 'model_' . uniqid('', true) . '.' . $stl_extension;
                $target_file_stl_new = $target_dir_stl . $stl_nombre_unico;
                $allowed_stl_types = ['stl', 'obj', '3mf'];
                if (in_array($stl_extension, $allowed_stl_types) && $_FILES["nuevo_archivo_modelo_3d"]["size"] < 50 * 1024 * 1024) {
                    if (move_uploaded_file($_FILES["nuevo_archivo_modelo_3d"]["tmp_name"], $target_file_stl_new)) {
                        if (!empty($archivo_actual_url_db) && file_exists($archivo_actual_url_db)) { unlink($archivo_actual_url_db); }
                        $archivo_stl_para_db = $target_file_stl_new; // Actualizar para la DB
                    } else { $error_message .= " Error al mover el nuevo archivo 3D."; }
                } else { $error_message .= " Tipo de nuevo archivo 3D no permitido o muy grande."; }
            }
        } elseif (isset($_FILES['nuevo_archivo_modelo_3d']) && $_FILES['nuevo_archivo_modelo_3d']['error'] != UPLOAD_ERR_NO_FILE) {
            $error_message .= " Error al subir el nuevo archivo 3D: Código " . $_FILES['nuevo_archivo_modelo_3d']['error'];
        }

        // Validar si el archivo STL sigue siendo necesario después de la edición
        $stl_requerido_tras_edicion = (empty($url_compra_final_para_db) || (isset($precio_post_float) && $precio_post_float <= 0));
        if ($stl_requerido_tras_edicion && empty($archivo_stl_para_db)) { // $archivo_stl_para_db es el que irá a la DB
            $error_message .= " El archivo 3D es obligatorio si el modelo es gratuito o no tiene URL de compra externa y no se ha proporcionado/mantenido uno.";
        }

        if (empty($nombre_modelo_post)) {
            $error_message .= " El nombre del modelo es obligatorio.";
        }
        
        if (empty(trim($error_message))) { // Si no hubo errores de validación ni de subida
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
                    ':precio' => $precio_post_float, // $precio_post_float es el float validado
                    ':url_compra_externa' => $url_compra_final_para_db,
                    ':imagen_url' => $imagen_url_para_db,
                    ':archivo_stl' => $archivo_stl_para_db,
                    ':id' => $modelo_id
                ]);
                $success_message = "¡Modelo actualizado correctamente! <a href='manage_models.php'>Volver a la lista</a>";
                
                // Actualizar las variables que se usan para mostrar en el form para que reflejen el estado POST-UPDATE
                $imagen_actual_url = $imagen_url_para_db; 
                $archivo_actual_url = $archivo_stl_para_db;
                // $url_compra_externa_val ya está poblado con el valor del POST
                // $nombre_modelo_val y $descripcion_val y $precio_val también

            } catch (PDOException $e) {
                $error_message = "Error al actualizar el modelo en la base de datos. ";
                error_log("Error DB en edit_model.php (POST): " . $e->getMessage());
            }
        }
    } // fin if($modelo_id) del POST
}


$page_title_display = $modelo_id ? "Editar Modelo: " . htmlspecialchars($nombre_modelo_val) : "Editar Modelo - Admin";
// Si $nombre_modelo_val está vacío al inicio pero $modelo_actual_data sí tenía nombre:
if ($modelo_id && empty($nombre_modelo_val) && isset($modelo_actual_data['nombre_modelo']) && $_SERVER["REQUEST_METHOD"] !== "POST") {
    $page_title_display = "Editar Modelo: " . htmlspecialchars($modelo_actual_data['nombre_modelo']);
} elseif ($modelo_id && $nombre_modelo_val && $error_message && $_SERVER["REQUEST_METHOD"] === "POST") {
     // Si hay un error en POST pero $nombre_modelo_val se repobló del POST, usarlo
    $page_title_display = "Editar Modelo (Error): " . htmlspecialchars($nombre_modelo_val);
}


if ($modelo_id && $_SERVER["REQUEST_METHOD"] !== "POST" && !$modelo_actual_data && empty($error_message)) { 
    // Este caso es si el ID es válido en GET, no es un POST, no se pudo cargar el modelo de la DB y NO hay error previo.
    // (Indica un fallo en la carga inicial que no estableció $error_message)
    $error_message = "No se pudieron cargar los datos del modelo para editar. Es posible que el modelo no exista.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title_display); ?></title>
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
                <a href="add_model.php">Añadir Modelo</a>
                <a href="manage_models.php" class="admin-nav-active">Gestionar Modelos</a>
                <a href="manage_featured_index.php">Destacados Index</a>
                <?php if (isset($_SESSION['user_alias'])): ?>
                    <span class="user-greeting">Hola, <?php echo htmlspecialchars($_SESSION['user_alias']); ?> (<a href="logout.php">Salir</a>)</span>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="admin-panel-container">
        
        <?php if (!empty(trim($error_message))): ?><div class="message error"><?php echo nl2br(htmlspecialchars(trim($error_message))); ?></div><?php endif; ?>
        <?php if ($success_message): ?><div class="message success"><?php echo $success_message; ?></div><?php endif; ?>

        <?php 
        // Mostrar formulario si:
        // 1. Tenemos un $modelo_id válido (sea de GET o POST).
        // 2. Y ( (es un GET y $modelo_actual_data se cargó) O (es un POST, independientemente de si $modelo_actual_data se cargó antes en GET, porque los datos vienen del form) )
        // 3. Y no queremos mostrar el formulario si $success_message está presente y NO hubo error posterior a ese success (para limpiar el form)
        $show_form = false;
        if ($modelo_id) {
            if ($_SERVER["REQUEST_METHOD"] == "POST") { // Si es POST, mostrar siempre que haya ID, errores se manejan arriba
                $show_form = true;
            } elseif ($modelo_actual_data) { // Si es GET, solo si se cargaron datos
                $show_form = true;
            }
        }
        // Si hubo éxito y NO hay errores después de ese éxito, podríamos optar por NO mostrar el form
        // Pero para edición, es común mostrarlo con los datos actualizados y el mensaje de éxito.
        // Si $success_message está y no hay $error_message, el usuario ve los datos actualizados.
        
        if ($show_form):
        ?>
            <form action="edit_model.php?id=<?php echo $modelo_id; ?>" method="POST" enctype="multipart/form-data" class="admin-form-common">
                <h2>Editar Modelo #<?php echo htmlspecialchars($modelo_id); ?>: <?php echo htmlspecialchars($nombre_modelo_val); ?></h2>
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
                    <small>Si se rellena y el precio es > 0, el botón "Comprar" redirigirá aquí. Dejar vacío para eliminar la URL.</small>
                </div>

                <div class="form-group">
                    <label for="nueva_imagen_modelo">Cambiar Imagen de Portada (JPG, PNG, GIF, WebP - max 5MB):</label>
                    <input type="file" id="nueva_imagen_modelo" name="nueva_imagen_modelo" accept="image/jpeg,image/png,image/gif,image/webp">
                    <?php if ($imagen_actual_url): // Usa la variable que se actualiza tras un POST exitoso ?>
                        <div class="current-file">
                            Imagen actual: <a href="<?php echo htmlspecialchars($imagen_actual_url); ?>" target="_blank"><?php echo basename(htmlspecialchars($imagen_actual_url)); ?></a><br>
                            <img src="<?php echo htmlspecialchars($imagen_actual_url); ?>" alt="Imagen actual">
                            <label><input type="checkbox" name="eliminar_imagen_actual" value="1"> Eliminar imagen actual (si no subes una nueva)</label>
                        </div>
                    <?php elseif(isset($modelo_actual_data) && empty($modelo_actual_data['imagen_url']) ): // Si originalmente no tenía imagen ?>
                        <p class="missing-file-warning">Este modelo no tiene una imagen de portada.</p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="nuevo_archivo_modelo_3d">Cambiar Archivo 3D (STL, OBJ, 3MF - max 50MB):</label>
                    <input type="file" id="nuevo_archivo_modelo_3d" name="nuevo_archivo_modelo_3d" accept=".stl,.obj,.3mf">
                    <?php if ($archivo_actual_url):  // Usa la variable que se actualiza tras un POST exitoso ?>
                        <div class="current-file">
                            Archivo 3D actual: <a href="<?php echo htmlspecialchars($archivo_actual_url); ?>" target="_blank" download><?php echo basename(htmlspecialchars($archivo_actual_url)); ?></a>
                            <p>Si no seleccionas un archivo nuevo, se mantendrá el actual.</p>
                        </div>
                    <?php elseif(isset($modelo_actual_data) && empty($modelo_actual_data['archivo_stl'])): ?>
                        <p class="missing-file-warning">Este modelo no tiene un archivo 3D asociado.</p>
                    <?php endif; ?>
                    <small>El archivo 3D es obligatorio si el modelo es gratuito en este sitio o no tiene URL de compra externa.</small>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-submit">Actualizar Modelo</button>
                </div>
                <p class="form-cancel-link"><a href="manage_models.php">Cancelar y volver a la lista</a></p>
            </form>
        <?php 
        // Mensaje si no se puede mostrar el formulario por no encontrar el modelo o ID inválido,
        // y no hay un mensaje de éxito de una operación anterior que deba prevalecer.
        elseif (!$success_message && empty($error_message) && $modelo_id && !$modelo_actual_data && $_SERVER["REQUEST_METHOD"] != "POST"  ):
             // Este es el caso específico de GET donde el modelo_id es válido, pero no se encontró $modelo_actual_data
             // y aún no se ha seteado $error_message por ello.
            echo '<div class="message error">No se pudieron cargar los datos del modelo para editar. Es posible que el modelo no exista o haya ocurrido un error. <a href="manage_models.php">Volver a la lista</a>.</div>';
        elseif (!$success_message && !$modelo_id && !empty($error_message) ):
            // Si $modelo_id es null (desde el inicio por ID inválido o reseteado por error de POST)
            // El mensaje de error ya se muestra, aquí solo el enlace.
            echo '<p class="form-cancel-link" style="text-align:center;"><a href="manage_models.php">Volver a la lista de modelos</a>.</p>';
        endif; 
        ?>
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