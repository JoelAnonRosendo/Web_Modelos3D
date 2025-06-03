<?php
session_start();
require 'db_config.php';

// Determinar la URL de redirección
$default_redirect_url = 'index.php';
$redirect_url = $default_redirect_url;

if (isset($_POST['redirect_to']) && !empty($_POST['redirect_to'])) {
    // Sanitizar la URL proporcionada. Podría ser más robusto.
    $potential_redirect = filter_var($_POST['redirect_to'], FILTER_SANITIZE_URL);
    // Validación básica: asegurar que no es una URL externa si no se espera.
    // Aquí asumimos que solo son rutas internas o URLs completas del propio sitio.
    if ($potential_redirect) {
        $redirect_url = $potential_redirect;
    }
} elseif (isset($_SERVER['HTTP_REFERER'])) {
    $redirect_url = $_SERVER['HTTP_REFERER'];
}


if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $redirect_url; // Guardar a dónde volver
    header('Location: login.php');
    exit();
}

if (isset($_POST['modelo_id'])) {
    $usuario_id = $_SESSION['user_id'];
    $modelo_id = (int)$_POST['modelo_id'];

    // Validar que el modelo existe
    $stmt_check_modelo = $pdo->prepare("SELECT id FROM modelos WHERE id = :modelo_id");
    $stmt_check_modelo->execute([':modelo_id' => $modelo_id]);
    if(!$stmt_check_modelo->fetch()){
        // Modelo no existe, podrías setear un mensaje de error en sesión y redirigir
        $_SESSION['message_toggle_fav'] = ['type' => 'error', 'text' => 'El modelo especificado no existe.'];
        header("Location: " . $redirect_url); // Redirigir a donde se estaba
        exit();
    }

    // Comprobar si ya es favorito
    $stmt_check_fav = $pdo->prepare("SELECT id FROM favoritos WHERE usuario_id = :usuario_id AND modelo_id = :modelo_id");
    $stmt_check_fav->execute(['usuario_id' => $usuario_id, 'modelo_id' => $modelo_id]);
    $es_favorito = $stmt_check_fav->fetch();

    try {
        if ($es_favorito) {
            // Si ya es favorito, quitarlo
            $stmt_toggle = $pdo->prepare("DELETE FROM favoritos WHERE id = :fav_id"); // Usar el ID del favorito para borrar
            $stmt_toggle->execute([':fav_id' => $es_favorito['id']]);
            $_SESSION['message_toggle_fav'] = ['type' => 'success', 'text' => 'Modelo eliminado de favoritos.'];
        } else {
            // Si no es favorito, añadirlo
            $stmt_toggle = $pdo->prepare("INSERT INTO favoritos (usuario_id, modelo_id) VALUES (:usuario_id, :modelo_id)");
            $stmt_toggle->execute(['usuario_id' => $usuario_id, 'modelo_id' => $modelo_id]);
            $_SESSION['message_toggle_fav'] = ['type' => 'success', 'text' => 'Modelo añadido a favoritos.'];
        }
    } catch (PDOException $e) {
        error_log("Error al cambiar estado de favorito: UsuarioID {$usuario_id}, ModeloID {$modelo_id} - " . $e->getMessage());
        $_SESSION['message_toggle_fav'] = ['type' => 'error', 'text' => 'Hubo un problema al actualizar tus favoritos. Inténtalo de nuevo.'];
    }

    header("Location: " . $redirect_url);
    exit();

} else {
    // Si no se envió modelo_id
    $_SESSION['message_toggle_fav'] = ['type' => 'error', 'text' => 'No se especificó ningún modelo.'];
    header("Location: " . $redirect_url); // Redirigir a donde se estaba o al default
    exit();
}
?>