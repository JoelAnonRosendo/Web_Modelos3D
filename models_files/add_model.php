<?php
session_start();
require 'db_config.php'; // Asegúrate de que la ruta a db_config.php es correcta

// 1. Verificar si el usuario está logueado y es admin
// (Si $_SESSION['is_admin'] no está seteado o no es true, redirigir)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php"); // Redirigir a la página principal o a una de "no autorizado"
    exit();
}

$error_message = '';
$success_message = '';

// Variables para repoblar el formulario en caso de error
$nombre_modelo_val = '';
$descripcion_val = '';
$precio_val = '';

// 2. Procesar el formulario cuando se envíe (método POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Repoblar valores para el formulario en caso de error
    $nombre_modelo_val = trim($_POST['nombre_modelo']);
    $descripcion_val = trim($_POST['descripcion']);
    $precio_val = $_POST['precio']; // Mantener como string para repoblar, se validará después

    $nombre_modelo = trim($_POST['nombre_modelo']);
    $descripcion = trim($_POST['descripcion']);
    $precio_input = $_POST['precio']; // Tomar como string para validación
    
    $imagen_url_final = null;
    $archivo_stl_final = null;

    // Validar precio
    if (!is_numeric($precio_input) || (float)$precio_input < 0) {
        $error_message .= " El precio debe ser un número válido y no negativo.";
    } else {
        $precio = (float)$precio_input;
    }

    // Manejo de la subida de la imagen del modelo
    if (isset($_FILES['imagen_modelo']) && $_FILES['imagen_modelo']['error'] == UPLOAD_ERR_OK) {
        $target_dir_img = "img/model_images/"; // Carpeta para imágenes de modelos
        if (!is_dir($target_dir_img)) { // Crear si no existe
            if (!mkdir($target_dir_img, 0775, true) && !is_dir($target_dir_img)) {
                $error_message .= " Fallo al crear el directorio de imágenes.";
            }
        }
        
        if (empty(trim($error_message))) { // Solo proceder si el directorio se creó o ya existía
            $img_info = getimagesize($_FILES["imagen_modelo"]["tmp_name"]); // Verificar que es una imagen
            if ($img_info === false) {
                $error_message .= " El archivo subido para la imagen no es una imagen válida.";
            } else {
                $imagen_extension = strtolower(pathinfo(basename($_FILES["imagen_modelo"]["name"]), PATHINFO_EXTENSION));
                $imagen_nombre_unico = 'img_' . uniqid('', true) . '.' . $imagen_extension; // Nombre de archivo único
                $target_file_img = $target_dir_img . $imagen_nombre_unico;
                $allowed_img_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($imagen_extension, $allowed_img_types) && $_FILES["imagen_modelo"]["size"] < 5 * 1024 * 1024) { // Max 5MB
                    if (move_uploaded_file($_FILES["imagen_modelo"]["tmp_name"], $target_file_img)) {
                        $imagen_url_final = $target_file_img; // Guardar la ruta relativa para la BD
                    } else {
                        $error_message .= " Error al mover la imagen del modelo subida. Verifique permisos.";
                    }
                } else {
                    $error_message .= " Tipo de archivo de imagen no permitido o imagen muy grande (máx 5MB).";
                }
            }
        }
    } elseif (isset($_FILES['imagen_modelo']) && $_FILES['imagen_modelo']['error'] != UPLOAD_ERR_NO_FILE) {
        // UPLOAD_ERR_NO_FILE significa que no se subió archivo, lo cual es opcional.
        // Cualquier otro error sí es un problema.
        $error_message .= " Error al subir la imagen del modelo: Código " . $_FILES['imagen_modelo']['error'];
    }


    // Manejo de la subida del archivo 3D (STL, OBJ, etc.)
    if (isset($_FILES['archivo_modelo_3d']) && $_FILES['archivo_modelo_3d']['error'] == UPLOAD_ERR_OK) {
        $target_dir_stl = "models_files/"; // Carpeta para archivos 3D
         if (!is_dir($target_dir_stl)) { // Crear si no existe
            if (!mkdir($target_dir_stl, 0775, true) && !is_dir($target_dir_stl)) {
                $error_message .= " Fallo al crear el directorio de archivos 3D.";
            }
        }

        if (empty(trim($error_message))) { // Solo proceder si el directorio se creó o ya existía
            $stl_nombre = basename($_FILES["archivo_modelo_3d"]["name"]);
            $stl_extension = strtolower(pathinfo($stl_nombre, PATHINFO_EXTENSION));
            $stl_nombre_unico = 'model_' . uniqid('', true) . '.' . $stl_extension; // Nombre de archivo único
            $target_file_stl = $target_dir_stl . $stl_nombre_unico;
            $allowed_stl_types = ['stl', 'obj', '3mf']; // Tipos de archivo 3D permitidos

            if (in_array($stl_extension, $allowed_stl_types) && $_FILES["archivo_modelo_3d"]["size"] < 50 * 1024 * 1024) { // Max 50MB
                if (move_uploaded_file($_FILES["archivo_modelo_3d"]["tmp_name"], $target_file_stl)) {
                    $archivo_stl_final = $target_file_stl; // Guardar la ruta relativa para la BD
                } else {
                    $error_message .= " Error al mover el archivo 3D subido. Verifique permisos.";
                }
            } else {
                $error_message .= " Tipo de archivo 3D no permitido o archivo muy grande (máx 50MB).";
            }
        }
    } elseif (!isset($_FILES['archivo_modelo_3d']) || $_FILES['archivo_modelo_3d']['error'] == UPLOAD_ERR_NO_FILE) {
        $error_message .= " El archivo 3D del modelo es obligatorio."; // Hacer el archivo 3D obligatorio
    } elseif (isset($_FILES['archivo_modelo_3d']) && $_FILES['archivo_modelo_3d']['error'] != UPLOAD_ERR_NO_FILE) {
         $error_message .= " Error al subir el archivo 3D: Código " . $_FILES['archivo_modelo_3d']['error'];
    }


    // Validación final de campos de texto obligatorios
    if (empty($nombre_modelo)) {
        $error_message .= " El nombre del modelo es obligatorio.";
    }
    
    // Si no hay errores Y el archivo 3D (que es obligatorio) se ha procesado correctamente:
    if (empty(trim($error_message)) && !empty($archivo_stl_final)) {
        try {
            $sql = "INSERT INTO modelos (nombre_modelo, descripcion, precio, imagen_url, archivo_stl) 
                    VALUES (:nombre_modelo, :descripcion, :precio, :imagen_url, :archivo_stl)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nombre_modelo' => $nombre_modelo,
                ':descripcion' => $descripcion,
                ':precio' => $precio, // $precio ya es float validado
                ':imagen_url' => $imagen_url_final, // Puede ser NULL si no se subió imagen opcional
                ':archivo_stl' => $archivo_stl_final // Ruta al archivo 3D
            ]);
            $success_message = "¡Modelo añadido correctamente!";
            // Limpiar valores para que el formulario aparezca vacío después del éxito
            $nombre_modelo_val = '';
            $descripcion_val = '';
            $precio_val = '';
        } catch (PDOException $e) {
            $error_message = "Error al guardar el modelo en la base de datos. ";
            error_log("Error DB en add_model.php: " . $e->getMessage()); // Loguear el error real
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
    <link rel="stylesheet" href="style.css"> <!-- Asegúrate que la ruta a tu CSS es correcta -->
    <!-- Estilos para este formulario específico -->
    <style>
        .admin-panel-container { max-width: 700px; margin: 30px auto; padding: 0 15px; }
        .admin-form { 
            padding: 25px; 
            background: var(--card-bg-color, #fff); 
            border-radius: 8px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
            color: var(--text-color, #333);
        }
        .admin-form h2 { 
            text-align: center; 
            margin-bottom: 25px; 
            font-family: 'Orbitron', sans-serif; /* O tu fuente para títulos */
        }
        .form-group { 
            margin-bottom: 20px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: bold; 
            font-size: 0.95em;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="file"],
        .form-group textarea { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ccc; 
            border-radius: 5px; 
            box-sizing: border-box; 
            font-size: 1em;
        }
        .form-group textarea { 
            min-height: 120px; 
            resize: vertical; 
        }
        .form-group .btn-submit { /* Estilo específico para el botón de submit de este form */
            width: 100%; 
            padding: 12px 20px;
            background-color: var(--primary-color, #007bff);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .form-group .btn-submit:hover {
            background-color: darken(var(--primary-color, #007bff), 10%);
        }
        .message { /* Estilos para mensajes de error/éxito */
            padding: 12px; 
            margin-bottom: 20px; 
            border-radius: 5px; 
            text-align: center;
            font-size: 0.95em;
        }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;}
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;}

        /* Header y Footer simplificados para el panel admin */
        .admin-header, .admin-footer {
            background-color: var(--header-bg, #212529);
            color: var(--header-text, #f8f9fa);
            padding: 15px 0;
            text-align: center;
        }
        .admin-header .container, .admin-footer .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 900px; /* Ajusta según necesidad */
            margin: 0 auto;
            padding: 0 15px;
        }
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
                <a href="add_model.php" style="font-weight:bold;">Añadir Modelo</a>
                <!-- <a href="manage_models.php">Gestionar Modelos</a> (página futura) -->
                <?php if (isset($_SESSION['user_alias'])): ?>
                    <span style="margin-left:15px;">Hola, <?php echo htmlspecialchars($_SESSION['user_alias']); ?> (<a href="logout.php">Salir</a>)</span>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="admin-panel-container">
        <form action="add_model.php" method="POST" enctype="multipart/form-data" class="admin-form">
            <h2>Añadir Nuevo Modelo 3D</h2>

            <?php if (!empty(trim($error_message))): ?><div class="message error"><?php echo htmlspecialchars(trim($error_message)); ?></div><?php endif; ?>
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
                <label for="precio">Precio (ej: 12.99, poner 0 para gratis):</label>
                <input type="number" id="precio" name="precio" step="0.01" min="0" required value="<?php echo htmlspecialchars($precio_val); ?>">
            </div>
            <div class="form-group">
                <label for="imagen_modelo">Imagen de Portada del Modelo (Opcional: JPG, PNG, GIF, WebP - max 5MB):</label>
                <input type="file" id="imagen_modelo" name="imagen_modelo" accept="image/jpeg,image/png,image/gif,image/webp">
            </div>
            <div class="form-group">
                <label for="archivo_modelo_3d">Archivo del Modelo 3D (Obligatorio: STL, OBJ, 3MF - max 50MB):</label>
                <input type="file" id="archivo_modelo_3d" name="archivo_modelo_3d" accept=".stl,.obj,.3mf" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-submit">Añadir Modelo</button>
            </div>
        </form>
    </div>

    <footer class="admin-footer">
        <div class="container">
            <p>© <?php echo date("Y"); ?> PrintVerse Admin Panel</p>
        </div>
    </footer>
</body>
</html>