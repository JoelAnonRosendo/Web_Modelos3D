<?php
session_start();
require 'db_config.php';

// 1. Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit();
}

// Obtener todos los modelos para listarlos
try {
    $stmt = $pdo->query("SELECT id, nombre_modelo, precio, imagen_url FROM modelos ORDER BY id DESC");
    $modelos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error al obtener los modelos: " . $e->getMessage();
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
    <link rel="stylesheet" href="style.css"> <!-- Tu CSS principal -->
    <style>
        .admin-panel-container { max-width: 900px; margin: 30px auto; padding: 0 15px; }
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .admin-table th, .admin-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: middle;
        }
        .admin-table th { background-color: #f2f2f2; color: var(--text-color);}
        .admin-table img.thumbnail { max-width: 80px; max-height: 60px; border-radius: 4px; }
        .actions a {
            margin-right: 10px;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            color: white;
            font-size: 0.9em;
        }
        .actions .edit-btn { background-color: var(--primary-color, #007bff); }
        .actions .delete-btn { background-color: #dc3545; } /* Rojo para eliminar */
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;}
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;}
        /* Reutilizar header y footer de add_model.php si son similares */
         .admin-header, .admin-footer {
            background-color: var(--header-bg, #212529); color: var(--header-text, #f8f9fa);
            padding: 15px 0; text-align: center;
        }
        .admin-header .container, .admin-footer .container {
            display: flex; justify-content: space-between; align-items: center;
            max-width: 900px; margin: 0 auto; padding: 0 15px;
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
                <a href="add_model.php">Añadir Modelo</a>
                <a href="manage_models.php" style="font-weight:bold;">Gestionar Modelos</a>
                <?php if (isset($_SESSION['user_alias'])): ?>
                    <span style="margin-left:15px;">Hola, <?php echo htmlspecialchars($_SESSION['user_alias']); ?> (<a href="logout.php">Salir</a>)</span>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="admin-panel-container">
        <h2>Gestionar Modelos 3D</h2>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
            <div class="message success">Modelo eliminado correctamente.</div>
        <?php endif; ?>
        <?php if (isset($_GET['status']) && $_GET['status'] == 'error_delete'): ?>
            <div class="message error">Error al eliminar el modelo.</div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($modelos)): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Nombre del Modelo</th>
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
                                    Sin imagen
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($modelo['nombre_modelo']); ?></td>
                            <td>$<?php echo number_format((float)$modelo['precio'], 2); ?></td>
                            <td class="actions">
                                <a href="edit_model.php?id=<?php echo $modelo['id']; ?>" class="edit-btn">Editar</a>
                                <a href="delete_model.php?id=<?php echo $modelo['id']; ?>" class="delete-btn" onclick="return confirm('¿Estás seguro de que quieres eliminar este modelo? Esta acción no se puede deshacer.');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay modelos para gestionar. <a href="add_model.php">Añade uno nuevo</a>.</p>
        <?php endif; ?>
    </div>

    <footer class="admin-footer">
        <div class="container">
            <p>© <?php echo date("Y"); ?> PrintVerse Admin Panel</p>
        </div>
    </footer>
    <!-- Librerías (CDN) -->
    <!-- GSAP y ScrollTrigger -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

    <!-- Lenis Scroll -->
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>

    <!-- Tu script personalizado de animaciones -->
    <script src="./animation.js"></script> <!-- Cambia esto a la ruta correcta -->
</body>
</html>