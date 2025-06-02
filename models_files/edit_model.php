<?php
session_start();
require 'db_config.php';

// 1. Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit();
}

$error_message = '';
$success_message = '';
$modelo_id = null;
$modelo_actual = null;

// Variables para repoblar el formulario
$nombre_modelo_val = '';
$descripcion_val = '';
$precio_val = '';
$imagen_actual_url = null;
$archivo_actual_url = null;


// 2. Obtener el ID del modelo a editar desde GET
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $modelo_id = (int)$_GET['id'];

    // Cargar datos del modelo actual si no es un POST (o si es un POST fallido para repoblar)
    if ($_SERVER["REQUEST_METHOD"] != "POST" || !empty($error_message)) { // También repoblar si hay error en POST
        try {
            $stmt = $pdo->prepare("SELECT * FROM modelos WHERE id = :id");
            $stmt->execute(['id' => $modelo_id]);
            $modelo_actual = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$modelo_actual) {
                $error_message = "Modelo no encontrado.";
                $modelo_id = null; // Invalidar ID si no se encuentra
            } else {
                // Pre-rellenar variables para el formulario
                $nombre_modelo_val = $modelo_actual['nombre_modelo'];
                $descripcion_val = $modelo_actual['descripcion'];
                $precio_val = $modelo_actual['precio'];
                $imagen_actual_url = $modelo_actual['imagen_url'];
                $archivo_actual_url = $modelo_actual['archivo_stl'];
            }
        } catch (PDOException $e) {
            $error_message = "Error al cargar el modelo: " . $e->getMessage();
            $modelo_id = null;
        }
    }
} else if ($_SERVER["REQUEST_METHOD"] != "POST") { // Si no hay ID en GET y no es POST, es un error de acceso
    $error_message = "ID de modelo no proporcionado o inválido.";
}


// 3. Procesar el formulario cuando se envíe
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // El ID del modelo debe venir de un campo oculto en el formulario POST
    if (isset($_POST['modelo_id']) && filter_var($_POST['modelo_id'], FILTER_VALIDATE_INT)) {
        $modelo_id = (int)$_POST['modelo_id'];
        // Recargar datos del modelo actual para tener las rutas de archivo viejas
        if (!$modelo_actual) { // Si no se cargó antes (ej. si GET falló o es el primer POST)
            try {
                $stmt_curr = $pdo->prepare("SELECT imagen_url, archivo_stl FROM modelos WHERE id = :id");
                $stmt_curr->execute([':id' => $modelo_id]);
                $archivos_actuales = $stmt_curr->fetch(PDO::FETCH_ASSOC);
                if ($archivos_actuales) {
                    $imagen_actual_url_db = $archivos_actuales['imagen_url'];
                    $archivo_actual_url_db = $archivos_actuales['archivo_stl'];
                } else {
                     $error_message .= " Modelo original no encontrado para actualización. ";
                     // Continuar, pero los archivos viejos no se podrán borrar si se reemplazan
                }
            } catch (PDOException $e) {
                $error_message .= " Error obteniendo datos del modelo actual. ";
            }
        } else {
            $imagen_actual_url_db = $modelo_actual['imagen_url'];
            $archivo_actual_url_db = $modelo_actual['archivo_stl'];
        }
    } else {
        $error_message .= " ID de modelo faltante en el envío del formulario. ";
        $modelo_id = null; // Invalidar para no proceder con la BD
    }

    // Repoblar campos del formulario para persistencia en caso de error
    $nombre_modelo_val = trim($_POST['nombre_modelo']);
    $descripcion_val = trim($_POST['descripcion']);
    $precio_val = $_POST['precio'];

    $nombre_modelo = trim($_POST['nombre_modelo']);
    $descripcion = trim($_POST['descripcion']);
    $precio_input = $_POST['precio'];
    
    $imagen_url_para_db = $imagen_actual_url_db ?? null; // Por defecto mantener la actual si no se sube nueva
    $archivo_stl_para_db = $archivo_actual_url_db ?? null;

    if (!is_numeric($precio_input) || (float)$precio_input < 0) {
        $error_message .= " El precio debe ser un número válido y no negativo.";
    } else {
        $precio = (float)$precio_input;
    }

    // --- Manejo de la ACTUALIZACIÓN de la imagen ---
    if (isset($_FILES['nueva_imagen_modelo']) && $_FILES['nueva_imagen_modelo']['error'] == UPLOAD_ERR_OK) {
        $target_dir_img = "img/model_images/";
        // (Validación y creación de directorio como en add_model.php omitida por brevedad, pero debería estar)
        $img_info = getimagesize($_FILES["nueva_imagen_modelo"]["tmp_name"]);
        if ($img_info === false) {
            $error_message .= " El nuevo archivo para la imagen no es una imagen válida.";
        } else {
            $imagen_extension = strtolower(pathinfo(basename($_FILES["nueva_imagen_modelo"]["name"]), PATHINFO_EXTENSION));
            $imagen_nombre_unico = 'img_' . uniqid('', true) . '.' . $imagen_extension;
            $target_file_img = $target_dir_img . $imagen_nombre_unico;
            $allowed_img_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($imagen_extension, $allowed_img_types) && $_FILES["nueva_imagen_modelo"]["size"] < 5 * 1024 * 1024) {
                if (move_uploaded_file($_FILES["nueva_imagen_modelo"]["tmp_name"], $target_file_img)) {
                    // Eliminar la imagen vieja si existía y se subió una nueva exitosamente
                    if (!empty($imagen_actual_url_db) && file_exists($imagen_actual_url_db)) {
                        unlink($imagen_actual_url_db);
                    }
                    $imagen_url_para_db = $target_file_img; // Usar la nueva ruta
                } else {
                    $error_message .= " Error al mover la nueva imagen del modelo.";
                }
            } else {
                $error_message .= " Tipo de archivo de nueva imagen no permitido o imagen muy grande.";
            }
        }
    } elseif (isset($_FILES['nueva_imagen_modelo']) && $_FILES['nueva_imagen_modelo']['error'] != UPLOAD_ERR_NO_FILE) {
        $error_message .= " Error al subir la nueva imagen: Código " . $_FILES['nueva_imagen_modelo']['error'];
    }
    
    // Checkbox para eliminar imagen actual
    if (isset($_POST['eliminar_imagen_actual']) && $_POST['eliminar_imagen_actual'] == '1') {
        if (!empty($imagen_actual_url_db) && file_exists($imagen_actual_url_db)) {
            unlink($imagen_actual_url_db);
        }
        $imagen_url_para_db = null; // Poner a NULL si se elimina
    }


    // --- Manejo de la ACTUALIZACIÓN del archivo 3D ---
    if (isset($_FILES['nuevo_archivo_modelo_3d']) && $_FILES['nuevo_archivo_modelo_3d']['error'] == UPLOAD_ERR_OK) {
        $target_dir_stl = "models_files/";
        // (Validación y creación de directorio como en add_model.php omitida por brevedad, pero debería estar)
        $stl_nombre = basename($_FILES["nuevo_archivo_modelo_3d"]["name"]);
        $stl_extension = strtolower(pathinfo($stl_nombre, PATHINFO_EXTENSION));
        $stl_nombre_unico = 'model_' . uniqid('', true) . '.' . $stl_extension;
        $target_file_stl = $target_dir_stl . $stl_nombre_unico;
        $allowed_stl_types = ['stl', 'obj', '3mf'];

        if (in_array($stl_extension, $allowed_stl_types) && $_FILES["nuevo_archivo_modelo_3d"]["size"] < 50 * 1024 * 1024) {
            if (move_uploaded_file($_FILES["nuevo_archivo_modelo_3d"]["tmp_name"], $target_file_stl)) {
                 // Eliminar el archivo 3D viejo si existía y se subió uno nuevo exitosamente
                if (!empty($archivo_actual_url_db) && file_exists($archivo_actual_url_db)) {
                    unlink($archivo_actual_url_db);
                }
                $archivo_stl_para_db = $target_file_stl; // Usar la nueva ruta
            } else {
                $error_message .= " Error al mover el nuevo archivo 3D.";
            }
        } else {
            $error_message .= " Tipo de nuevo archivo 3D no permitido o archivo muy grande.";
        }
    } elseif (isset($_FILES['nuevo_archivo_modelo_3d']) && $_FILES['nuevo_archivo_modelo_3d']['error'] != UPLOAD_ERR_NO_FILE) {
        $error_message .= " Error al subir el nuevo archivo 3D: Código " . $_FILES['nuevo_archivo_modelo_3d']['error'];
    }


    if (empty($nombre_modelo)) {
        $error_message .= " El nombre del modelo es obligatorio.";
    }
    
    if (empty(trim($error_message)) && $modelo_id) { // Solo si no hay errores y el ID del modelo es válido
        try {
            $sql = "UPDATE modelos SET 
                        nombre_modelo = :nombre_modelo, 
                        descripcion = :descripcion, 
                        precio = :precio, 
                        imagen_url = :imagen_url, 
                        archivo_stl = :archivo_stl 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nombre_modelo' => $nombre_modelo,
                ':descripcion' => $descripcion,
                ':precio' => $precio,
                ':imagen_url' => $imagen_url_para_db,
                ':archivo_stl' => $archivo_stl_para_db,
                ':id' => $modelo_id
            ]);
            $success_message = "¡Modelo actualizado correctamente! <a href='manage_models.php'>Volver a la lista</a>";
            // Recargar los datos actuales después de la actualización para mostrarlos actualizados
            // O simplemente mostrar el mensaje de éxito y el enlace para volver.
            // Para simplificar, solo mostraremos mensaje. La próxima vez que se edite se cargarán los nuevos datos.
            
        } catch (PDOException $e) {
            $error_message = "Error al actualizar el modelo en la base de datos: " . $e->getMessage();
        }
    }
}


$page_title = $modelo_id ? "Editar Modelo #{$modelo_id} - Admin" : "Error al Editar Modelo - Admin";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="style.css">
    <!-- Copia los estilos de .admin-form, .form-group, etc., de add_model.php aquí -->
    <style>
        .admin-panel-container { max-width: 700px; margin: 30px auto; padding: 0 15px; }
        .admin-form { padding: 25px; background: var(--card-bg-color, #fff); border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); color: var(--text-color, #333); }
        .admin-form h2 { text-align: center; margin-bottom: 25px; font-family: 'Orbitron', sans-serif; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; font-size: 0.95em;}
        .form-group input[type="text"], .form-group input[type="number"], .form-group input[type="file"], .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 1em;}
        .form-group textarea { min-height: 120px; resize: vertical; }
        .form-group .btn-submit { width: 100%; padding: 12px 20px; background-color: var(--primary-color, #007bff); color: white; border: none; border-radius: 5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: background-color 0.3s ease;}
        .form-group .btn-submit:hover { background-color: darken(var(--primary-color, #007bff), 10%);}
        .message { padding: 12px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-size: 0.95em;}
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;}
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;}
        .current-file { font-size: 0.9em; color: #555; margin-top: 5px; }
        .current-file img { max-width:150px; max-height:100px; border:1px solid #eee; display:block; margin-top:5px;}
        /* Reutilizar header y footer de add_model.php si son similares */
         .admin-header, .admin-footer { background-color: var(--header-bg, #212529); color: var(--header-text, #f8f9fa); padding: 15px 0; text-align: center;}
        .admin-header .container, .admin-footer .container { display: flex; justify-content: space-between; align-items: center; max-width: 900px; margin: 0 auto; padding: 0 15px;}
        .admin-header a { color: var(--accent-color, #ffc107); text-decoration: none; margin: 0 10px; }
        .admin-header h1 a { color: var(--header-text, #f8f9fa); text-decoration:none;}
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="container">
             <h1><a href="index.php">PrintVerse</a><span style="font-size:0.7em; color:var(--accent-color, #ffc107);"> - Panel Admin</span></h1>
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
        
        <?php if (!empty(trim($error_message))): ?><div class="message error"><?php echo htmlspecialchars(trim($error_message)); ?></div><?php endif; ?>
        <?php if ($success_message): ?><div class="message success"><?php echo $success_message; /* Puede tener HTML del enlace */ ?></div><?php endif; ?>

        <?php if ($modelo_id && $modelo_actual): // Solo mostrar formulario si tenemos un modelo válido ?>
            <form action="edit_model.php?id=<?php echo $modelo_id; /* Para que la URL se mantenga si hay errores de validación POST */?>" method="POST" enctype="multipart/form-data" class="admin-form">
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
                    <label for="nueva_imagen_modelo">Cambiar Imagen de Portada (JPG, PNG, GIF, WebP - max 5MB):</label>
                    <input type="file" id="nueva_imagen_modelo" name="nueva_imagen_modelo" accept="image/jpeg,image/png,image/gif,image/webp">
                    <?php if ($imagen_actual_url): ?>
                        <div class="current-file">
                            Imagen actual: <a href="<?php echo htmlspecialchars($imagen_actual_url); ?>" target="_blank"><?php echo basename($imagen_actual_url); ?></a><br>
                            <img src="<?php echo htmlspecialchars($imagen_actual_url); ?>" alt="Imagen actual">
                            <label style="font-weight:normal; margin-top:5px;"><input type="checkbox" name="eliminar_imagen_actual" value="1"> Eliminar imagen actual (dejar sin imagen)</label>
                        </div>
                    <?php endif; ?>
                </div>

                 <div class="form-group">
                    <label for="nuevo_archivo_modelo_3d">Cambiar Archivo del Modelo 3D (STL, OBJ, 3MF - max 50MB):</label>
                    <input type="file" id="nuevo_archivo_modelo_3d" name="nuevo_archivo_modelo_3d" accept=".stl,.obj,.3mf">
                     <?php if ($archivo_actual_url): ?>
                        <div class="current-file">
                            Archivo 3D actual: <a href="<?php echo htmlspecialchars($archivo_actual_url); ?>" target="_blank" download><?php echo basename($archivo_actual_url); ?></a>
                            <p style="font-size:0.8em; color:#777;">(Si no seleccionas un nuevo archivo 3D, se mantendrá el actual. El archivo 3D es obligatorio).</p>
                        </div>
                    <?php else: ?>
                        <p style="color:red; font-weight:bold;">¡ATENCIÓN! Este modelo no tiene un archivo 3D asociado. DEBES subir uno.</p>
                         <script>document.getElementById('nuevo_archivo_modelo_3d').required = true;</script> <!-- Hacerlo requerido si no hay uno actual -->
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-submit">Actualizar Modelo</button>
                </div>
                <p style="text-align:center;"><a href="manage_models.php">Cancelar y volver a la lista</a></p>
            </form>
        <?php elseif (!$success_message): // Si no hay mensaje de éxito y tampoco se pudo cargar el modelo/ID
            echo '<div class="message error">No se pudo cargar el formulario de edición. Verifique el ID del modelo o <a href="manage_models.php">vuelva a la lista</a>.</div>';
        endif; ?>
    </div>

    <footer class="admin-footer">
        <div class="container">
            <p>© <?php echo date("Y"); ?> PrintVerse Admin Panel</p>
        </div>
    </footer>
</body>
</html>