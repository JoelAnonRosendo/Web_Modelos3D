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

// Obtener todos los modelos para los desplegables
$all_models_list = [];
try {
    $stmt_all = $pdo->query("SELECT id, nombre_modelo FROM modelos ORDER BY nombre_modelo ASC");
    $all_models_list = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error al cargar la lista de modelos: " . $e->getMessage();
}

// Obtener los modelos actualmente destacados para pre-seleccionar
$featured_models_ids = [1 => null, 2 => null, 3 => null]; // Posición => modelo_id
try {
    $stmt_feat = $pdo->query("SELECT id, orden_destacado_index FROM modelos WHERE orden_destacado_index IS NOT NULL AND orden_destacado_index IN (1,2,3)");
    while ($row = $stmt_feat->fetch(PDO::FETCH_ASSOC)) {
        if (array_key_exists($row['orden_destacado_index'], $featured_models_ids)) {
            $featured_models_ids[$row['orden_destacado_index']] = $row['id'];
        }
    }
} catch (PDOException $e) {
    $error_message .= " Error al cargar los modelos destacados actuales: " . $e->getMessage();
}


// Procesar el formulario cuando se envíe
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $slot1_model_id = isset($_POST['slot_1_model_id']) ? (int)$_POST['slot_1_model_id'] : 0;
    $slot2_model_id = isset($_POST['slot_2_model_id']) ? (int)$_POST['slot_2_model_id'] : 0;
    $slot3_model_id = isset($_POST['slot_3_model_id']) ? (int)$_POST['slot_3_model_id'] : 0;

    // Validación simple (puedes hacerla más robusta)
    // Asegurarse de que no se seleccione el mismo modelo para diferentes slots si es un requisito
    $selected_for_slots = [];
    if ($slot1_model_id > 0) $selected_for_slots[] = $slot1_model_id;
    if ($slot2_model_id > 0) $selected_for_slots[] = $slot2_model_id;
    if ($slot3_model_id > 0) $selected_for_slots[] = $slot3_model_id;

    if (count($selected_for_slots) !== count(array_unique($selected_for_slots)) && count(array_filter($selected_for_slots)) > 1 ) {
        $error_message = "Por favor, selecciona modelos diferentes para cada posición destacada o deja posiciones vacías si no deseas ocuparlas.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Limpiar todas las asignaciones de destacados existentes
            $stmt_clear = $pdo->prepare("UPDATE modelos SET orden_destacado_index = NULL WHERE orden_destacado_index IS NOT NULL");
            $stmt_clear->execute();

            // 2. Asignar nuevas posiciones (si se seleccionó un modelo > 0)
            $new_assignments = [
                1 => $slot1_model_id,
                2 => $slot2_model_id,
                3 => $slot3_model_id
            ];

            $stmt_assign = $pdo->prepare("UPDATE modelos SET orden_destacado_index = :orden WHERE id = :id");

            foreach ($new_assignments as $orden => $model_id) {
                if ($model_id > 0) { // Solo asignar si se eligió un modelo válido
                    $stmt_assign->execute([':orden' => $orden, ':id' => $model_id]);
                }
            }

            $pdo->commit();
            $success_message = "¡Modelos destacados del index actualizados correctamente!";
            
            // Volver a cargar los destacados para reflejar los cambios en el formulario
            $featured_models_ids = [1 => $slot1_model_id ?: null, 2 => $slot2_model_id ?: null, 3 => $slot3_model_id ?: null];

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error al actualizar los modelos destacados: " . $e->getMessage();
        }
    }
}

$page_title = "Gestionar Modelos Destacados del Index - Admin";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="style.css"> <!-- Reutiliza tu CSS de admin -->
    <style>
        /* Estilos de add_model.php pueden ser útiles */
        .admin-panel-container { max-width: 700px; margin: 30px auto; padding: 0 15px; }
        .admin-form { padding: 25px; background: var(--card-bg-color, #fff); border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); color: var(--text-color, #333); }
        .admin-form h2 { text-align: center; margin-bottom: 25px; font-family: 'Orbitron', sans-serif; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; font-size: 0.95em;}
        .form-group select {
            width: 100%; padding: 12px; border: 1px solid #ccc;
            border-radius: 5px; box-sizing: border-box; font-size: 1em;
        }
        .form-group .btn-submit { width: 100%; padding: 12px 20px; background-color: var(--primary-color, #007bff); color: white; border: none; border-radius: 5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: background-color 0.3s ease;}
        .message { padding: 12px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-size: 0.95em;}
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;}
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;}
        
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
                <a href="manage_featured_index.php" style="font-weight:bold;">Destacados Index</a>
                <?php if (isset($_SESSION['user_alias'])): ?>
                    <span style="margin-left:15px;">Hola, <?php echo htmlspecialchars($_SESSION['user_alias']); ?> (<a href="logout.php">Salir</a>)</span>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="admin-panel-container">
        <form action="manage_featured_index.php" method="POST" class="admin-form">
            <h2>Seleccionar Modelos Destacados para la Página de Inicio</h2>
            <p style="text-align:center; font-size:0.9em; margin-bottom:20px;">Elige los tres modelos que se mostrarán en la sección "Modelos Destacados" del index.</p>

            <?php if (!empty(trim($error_message))): ?><div class="message error"><?php echo htmlspecialchars(trim($error_message)); ?></div><?php endif; ?>
            <?php if ($success_message): ?><div class="message success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>

            <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="form-group">
                <label for="slot_<?php echo $i; ?>_model_id">Modelo Destacado - Posición <?php echo $i; ?>:</label>
                <select id="slot_<?php echo $i; ?>_model_id" name="slot_<?php echo $i; ?>_model_id">
                    <option value="0">-- Ninguno --</option>
                    <?php foreach ($all_models_list as $model): ?>
                        <option value="<?php echo $model['id']; ?>" <?php echo ($featured_models_ids[$i] == $model['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($model['nombre_modelo']); ?> (ID: <?php echo $model['id']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endfor; ?>
            
            <div class="form-group">
                <button type="submit" class="btn-submit">Guardar Destacados</button>
            </div>
        </form>
    </div>

    <footer class="admin-footer">
        <div class="container">
            <p>© <?php echo date("Y"); ?> Arnerazo3D Admin Panel</p>
        </div>
    </footer>
</body>
</html>