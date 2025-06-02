<?php
session_start();
require 'db_config.php';

// 1. Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Podrías redirigir a una página de no autorizado o simplemente a index
    header("Location: index.php");
    exit();
}

$status = 'error_delete'; // Estado por defecto

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $modelo_id = (int)$_GET['id'];

    try {
        // Obtener las rutas de los archivos para eliminarlos del servidor
        $stmt_files = $pdo->prepare("SELECT imagen_url, archivo_stl FROM modelos WHERE id = :id");
        $stmt_files->execute([':id' => $modelo_id]);
        $archivos = $stmt_files->fetch(PDO::FETCH_ASSOC);

        if ($archivos) {
            // Eliminar el modelo de la base de datos
            // Si tienes ON DELETE CASCADE en la tabla 'favoritos', se borrarán de allí también.
            $stmt_delete = $pdo->prepare("DELETE FROM modelos WHERE id = :id");
            if ($stmt_delete->execute([':id' => $modelo_id])) {
                // Si la eliminación de la BD fue exitosa, eliminar los archivos físicos
                if (!empty($archivos['imagen_url']) && file_exists($archivos['imagen_url'])) {
                    unlink($archivos['imagen_url']);
                }
                if (!empty($archivos['archivo_stl']) && file_exists($archivos['archivo_stl'])) {
                    unlink($archivos['archivo_stl']);
                }
                $status = 'deleted';
            }
        } else {
            // Modelo no encontrado para eliminar
            $status = 'notfound_delete'; // Podrías manejar este estado diferente en manage_models.php
        }
    } catch (PDOException $e) {
        error_log("Error al eliminar modelo ID {$modelo_id}: " . $e->getMessage());
        // $status permanece 'error_delete'
    }
}

// Redirigir de vuelta a la página de gestión con un mensaje de estado
header("Location: manage_models.php?status=" . $status);
exit();
?>