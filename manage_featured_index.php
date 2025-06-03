<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit();
}

$error_message = '';
$success_message = '';
$all_models_list = [];

try {
    $stmt_all = $pdo->query("SELECT id, nombre_modelo FROM modelos ORDER BY nombre_modelo ASC");
    $all_models_list = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error al cargar la lista de modelos: " . $e->getMessage();
    error_log("DB Error (all_models_list) in manage_featured_index: " . $e->getMessage());
}

$featured_models_ids = [1 => null, 2 => null, 3 => null]; // Posición => modelo_id
try {
    $stmt_feat = $pdo->query("SELECT id, orden_destacado_index FROM modelos WHERE orden_destacado_index IS NOT NULL AND orden_destacado_index IN (1,2,3)");
    while ($row = $stmt_feat->fetch(PDO::FETCH_ASSOC)) {
        if (array_key_exists($row['orden_destacado_index'], $featured_models_ids)) {
            $featured_models_ids[$row['orden_destacado_index']] = (int)$row['id']; // Asegurar que es int
        }
    }
} catch (PDOException $e) {
    $error_message .= " Error al cargar los modelos destacados actuales: " . $e->getMessage();
    error_log("DB Error (featured_models_ids) in manage_featured_index: " . $e->getMessage());
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $slot1_model_id = isset($_POST['slot_1_model_id']) ? (int)$_POST['slot_1_model_id'] : 0;
    $slot2_model_id = isset($_POST['slot_2_model_id']) ? (int)$_POST['slot_2_model_id'] : 0;
    $slot3_model_id = isset($_POST['slot_3_model_id']) ? (int)$_POST['slot_3_model_id'] : 0;

    $selected_for_slots = [];
    if ($slot1_model_id > 0) $selected_for_slots[] = $slot1_model_id;
    if ($slot2_model_id > 0) $selected_for_slots[] = $slot2_model_id;
    if ($slot3_model_id > 0) $selected_for_slots[] = $slot3_model_id;
    
    // Verifica que si hay elementos seleccionados, no haya duplicados.
    // array_filter quita los '0' (que representan "Ninguno")
    $filtered_selected = array_filter($selected_for_slots);
    if (count($filtered_selected) !== count(array_unique($filtered_selected))) {
        $error_message = "Por favor, selecciona modelos diferentes para cada posición destacada. Una posición puede quedar vacía.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt_clear = $pdo->prepare("UPDATE modelos SET orden_destacado_index = NULL WHERE orden_destacado_index IS NOT NULL");
            $stmt_clear->execute();

            $new_assignments = [
                1 => $slot1_model_id,
                2 => $slot2_model_id,
                3 => $slot3_model_id
            ];

            $stmt_assign = $pdo->prepare("UPDATE modelos SET orden_destacado_index = :orden WHERE id = :id");

            foreach ($new_assignments as $orden => $model_id) {
                if ($model_id > 0) {
                    $stmt_assign->execute([':orden' => $orden, ':id' => $model_id]);
                }
            }

            $pdo->commit();
            $success_message = "¡Modelos destacados del index actualizados correctamente!";
            
            // Actualizar $featured_models_ids para reflejar los cambios en el formulario
            $featured_models_ids = [
                1 => $slot1_model_id ?: null, 
                2 => $slot2_model_id ?: null, 
                3 => $slot3_model_id ?: null
            ];

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error al actualizar los modelos destacados: " . $e->getMessage();
            error_log("Error DB (transaction) manage_featured_index: " . $e->getMessage());
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
                <a href="manage_models.php">Gestionar Modelos</a>
                <a href="manage_featured_index.php" class="admin-nav-active">Destacados Index</a>
                <?php if (isset($_SESSION['user_alias'])): ?>
                    <span class="user-greeting">Hola, <?php echo htmlspecialchars($_SESSION['user_alias']); ?> (<a href="logout.php">Salir</a>)</span>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="admin-panel-container">
        <form action="manage_featured_index.php" method="POST" class="admin-form-common">
            <h2>Seleccionar Modelos Destacados para la Página de Inicio</h2>
            <p class="form-intro-text">Elige hasta tres modelos que se mostrarán en la sección "Modelos Destacados" de la página principal. Las posiciones vacías no se mostrarán.</p>

            <?php if (!empty(trim($error_message))): ?><div class="message error"><?php echo htmlspecialchars(trim($error_message)); ?></div><?php endif; ?>
            <?php if ($success_message): ?><div class="message success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>

            <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="form-group">
                <label for="slot_<?php echo $i; ?>_model_id">Modelo Destacado - Posición <?php echo $i; ?>:</label>
                <select id="slot_<?php echo $i; ?>_model_id" name="slot_<?php echo $i; ?>_model_id">
                    <option value="0">-- Ninguno --</option>
                    <?php if (!empty($all_models_list)): ?>
                        <?php foreach ($all_models_list as $model): ?>
                            <option value="<?php echo $model['id']; ?>" <?php echo (isset($featured_models_ids[$i]) && $featured_models_ids[$i] == $model['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($model['nombre_modelo']); ?> (ID: <?php echo $model['id']; ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="0" disabled>No hay modelos para seleccionar</option>
                    <?php endif; ?>
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