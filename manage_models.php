<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit();
}

$modelos = [];
$error_message_page = ''; // Para errores generales de carga de la página
$categorias_nombres = [];

try {
    // Seleccionar también categoria_id y orden_destacado_index
    // Es importante que 'categoria_id' exista en la tabla 'modelos'
    // y que 'categorias' exista para obtener los nombres.
    $stmt = $pdo->query("SELECT id, nombre_modelo, precio, imagen_url, categoria_id, orden_destacado_index FROM modelos ORDER BY id DESC");
    $modelos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($modelos) > 0) {
        // Cargar nombres de categorías para mostrar en la tabla
        $stmt_cat_nombres = $pdo->query("SELECT id, nombre_categoria FROM categorias"); // Asegúrate que 'categorias' existe
        while($cat_row = $stmt_cat_nombres->fetch(PDO::FETCH_ASSOC)) {
            $categorias_nombres[$cat_row['id']] = $cat_row['nombre_categoria'];
        }
    }

} catch (PDOException $e) {
    $error_message_page = "Error al obtener los modelos o categorías: " . $e->getMessage();
    error_log("Error DB en manage_models.php: " . $e->getMessage());
    $modelos = []; // Evitar errores si la consulta falla
}

$page_title = "Gestionar Modelos - Panel de Administración";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/manage_models.css">
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

    <div class="admin-panel-container manage-models-container">
        <h2>Gestionar Modelos 3D</h2>

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'deleted'): ?>
                <div class="message success">Modelo eliminado correctamente.</div>
            <?php elseif ($_GET['status'] == 'notfound_delete'): ?>
                <div class="message error">Modelo no encontrado para eliminar o ya fue eliminado.</div>
            <?php elseif ($_GET['status'] == 'error_delete'): ?>
                <div class="message error">Error al intentar eliminar el modelo.</div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (!empty($error_message_page)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message_page); ?></div>
        <?php endif; ?>


        <?php if (!empty($modelos)): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Nombre del Modelo</th>
                        <th>Categoría</th>
                        <th>Dest. Index</th>
                        <th>Precio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modelos as $modelo): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($modelo['id']); ?></td>
                            <td>
                                <?php if (!empty($modelo['imagen_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($modelo['imagen_url']); ?>" alt="Miniatura" class="thumbnail">
                                <?php else: ?>
                                    <span class="no-image-placeholder">S/I</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($modelo['nombre_modelo']); ?></td>
                            <td>
                                <?php
                                if (!empty($modelo['categoria_id']) && isset($categorias_nombres[$modelo['categoria_id']])) {
                                    echo htmlspecialchars($categorias_nombres[$modelo['categoria_id']]);
                                } else {
                                    echo '<span class="no-category-info">Sin categoría</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (!empty($modelo['orden_destacado_index'])) {
                                    echo '<span class="destacado-badge">#' . htmlspecialchars($modelo['orden_destacado_index']) . '</span>';
                                } else {
                                    echo '<span class="not-featured-info">No</span>';
                                }
                                ?>
                            </td>
                            <td>$<?php echo number_format((float)$modelo['precio'], 2); ?></td>
                            <td class="actions">
                                <a href="edit_model.php?id=<?php echo $modelo['id']; ?>" class="edit-btn">Editar</a>
                                <a href="delete_model.php?id=<?php echo $modelo['id']; ?>" class="delete-btn" onclick="return confirm('¿Estás seguro de que quieres eliminar este modelo?\nID: <?php echo $modelo['id']; ?> - Nombre: <?php echo htmlspecialchars(addslashes($modelo['nombre_modelo'])); ?>\nEsta acción no se puede deshacer.');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <?php if (empty($error_message_page)): ?>
                <p class="no-models-message-admin">No hay modelos para gestionar. <a href="add_model.php">Añade uno nuevo</a>.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer class="admin-footer">
        <div class="container">
            <p>© <?php echo date("Y"); ?> Arnerazo3D Admin Panel</p>
        </div>
    </footer>
</body>
</html>