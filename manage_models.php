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
    // Seleccionar también categoria_id y orden_destacado_index
    $stmt = $pdo->query("SELECT id, nombre_modelo, precio, imagen_url, categoria_id, orden_destacado_index FROM modelos ORDER BY id DESC");
    $modelos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Para mostrar el nombre de la categoría en lugar del ID (opcional, pero recomendado)
    $categorias_nombres = [];
    if (count($modelos) > 0) {
        $stmt_cat_nombres = $pdo->query("SELECT id, nombre_categoria FROM categorias");
        while($cat_row = $stmt_cat_nombres->fetch(PDO::FETCH_ASSOC)) {
            $categorias_nombres[$cat_row['id']] = $cat_row['nombre_categoria'];
        }
    }

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
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-panel-container { max-width: 1100px; margin: 30px auto; padding: 0 15px; } /* Aumentado ancho */
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.95em; }
        .admin-table th, .admin-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: middle;
        }
        .admin-table th { background-color: #f2f2f2; color: var(--text-color);}
        .admin-table img.thumbnail { max-width: 70px; max-height: 50px; border-radius: 4px; object-fit:cover; }
        .actions a {
            margin-right: 8px; /* Reducido para que quepan mejor */
            margin-bottom: 5px; /* Para cuando se apilan en pantallas pequeñas */
            padding: 5px 8px; /* Ligeramente más pequeños */
            text-decoration: none;
            border-radius: 4px;
            color: white;
            font-size: 0.85em; /* Ligeramente más pequeños */
            display: inline-block; /* Para que se comporten bien con márgenes */
        }
        .actions .edit-btn { background-color: var(--primary-color, #007bff); }
        .actions .delete-btn { background-color: #dc3545; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;}
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;}
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;} /* Estilo para error global */

        .admin-header, .admin-footer {
            background-color: var(--header-bg, #212529); color: var(--header-text, #f8f9fa);
            padding: 15px 0; text-align: center;
        }
        .admin-header .container, .admin-footer .container {
            display: flex; justify-content: space-between; align-items: center;
            max-width: 1100px; margin: 0 auto; padding: 0 15px; /* Aumentado ancho */
        }
        /* Ajustes para el NAV del admin-header para que quepan más elementos */
        .admin-header nav {
            display: flex;
            align-items: center;
            flex-wrap: wrap; /* Permitir que los enlaces se envuelvan si no caben */
            justify-content: flex-end; /* Alinearlos a la derecha del contenedor nav */
        }
        .admin-header nav a {
            color: var(--accent-color, #ffc107); text-decoration: none;
            margin: 0 6px; /* Espaciado entre enlaces */
            font-size: 0.85em; /* Reducir un poco el tamaño para que quepan más */
            white-space: nowrap; /* Evitar que el texto del enlace se rompa */
        }
        .admin-header .user-greeting-admin {
            font-size:0.85em;
            margin-left:8px; /* Espacio con el último enlace del nav */
            color: var(--header-text, #f8f9fa);
            white-space: nowrap;
        }
        .admin-header .user-greeting-admin a { color: var(--accent-color, #ffc107); }

        .admin-header h1 a { color: var(--header-text, #f8f9fa); text-decoration:none;}
        .admin-header h1 { margin-right: auto; /* Empuja la navegación a la derecha */ }

        .destacado-badge {
            display: inline-block;
            background-color: var(--accent-color);
            color: var(--header-bg);
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.75em;
            font-weight: bold;
            margin-left: 5px;
        }
         /* Responsive para tabla y acciones */
        @media (max-width: 850px) {
            .admin-table th:nth-child(1), /* Ocultar ID */
            .admin-table td:nth-child(1) {
                display: none;
            }
            .admin-table th, .admin-table td {
                font-size: 0.9em;
                padding: 7px;
            }
        }
        @media (max-width: 600px) {
            .admin-table th:nth-child(2), /* Ocultar Imagen */
            .admin-table td:nth-child(2),
            .admin-table th:nth-child(5), /* Ocultar Destacado Index (si es muy ancho) */
            .admin-table td:nth-child(5) {
                /* Puedes decidir ocultarlos o no */
                /* display: none; */
            }
             .admin-table th:nth-child(4) { /* Columna categoría */
                 /* Podrías ocultarla en móviles si es necesario */
            }
            .actions a {
                display: block; /* Para que cada botón ocupe una línea */
                margin-right: 0;
                margin-bottom: 5px;
                width: 100%;
                box-sizing: border-box;
                text-align: center;
            }
            .admin-header .container { flex-direction: column; align-items: flex-start; }
            .admin-header h1 { margin-bottom: 10px; }
            .admin-header nav { width: 100%; justify-content: flex-start; }
            .admin-header .user-greeting-admin { margin-top: 10px; margin-left:0; }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <div class="logo"><h1><a href="index.php" style="text-decoration:none; color:var(--header-text);">Arnerazo<span class="highlight">3D</span></a></h1></div>
            <nav>
                <a href="index.php">Ver Sitio</a>
                <a href="add_model.php">Añadir Modelo</a>
                <a href="manage_models.php" style="font-weight:bold;">Gestionar Modelos</a>
                <a href="manage_featured_index.php">Destacados Index</a>
                <!-- Podrías añadir gestión de categorías: <a href="manage_categories.php">Categorías</a> -->
                <?php if (isset($_SESSION['user_alias'])): ?>
                    <span class="user-greeting-admin">Hola, <?php echo htmlspecialchars($_SESSION['user_alias']); ?> (<a href="logout.php">Salir</a>)</span>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="admin-panel-container">
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
        <?php if (!empty($error_message)): /* Para errores globales al cargar la página */ ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
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
                                    <span style="font-size:0.8em; color:#777;">S/I</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($modelo['nombre_modelo']); ?></td>
                            <td>
                                <?php
                                if (!empty($modelo['categoria_id']) && isset($categorias_nombres[$modelo['categoria_id']])) {
                                    echo htmlspecialchars($categorias_nombres[$modelo['categoria_id']]);
                                } else {
                                    echo '<em>Sin categoría</em>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (!empty($modelo['orden_destacado_index'])) {
                                    echo '<span class="destacado-badge">#' . htmlspecialchars($modelo['orden_destacado_index']) . '</span>';
                                } else {
                                    echo '<em>No</em>';
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
            <?php if (empty($error_message)): /* Solo mostrar si no hubo un error global al cargar */ ?>
                <p>No hay modelos para gestionar. <a href="add_model.php">Añade uno nuevo</a>.</p>
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