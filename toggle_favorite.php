<?php
session_start();
require 'db_config.php';

// Determinar la URL de redirección
$default_redirect_url = 'index.php';
$redirect_url = $default_redirect_url;

// Intentar obtener la URL de la que se vino o la especificada en el POST
if (isset($_POST['redirect_to']) && !empty($_POST['redirect_to'])) {
    $potential_redirect = filter_var($_POST['redirect_to'], FILTER_SANITIZE_URL);
    if ($potential_redirect) { // Asegurarse de que el filtro no lo haya dejado vacío
        // Validación simple: chequear si es una ruta relativa o absoluta de este sitio
        // Para URLs completas, verifica que pertenezcan al mismo host.
        // Para rutas relativas (que no empiezan con http), se asume que son seguras.
        $is_internal_path = strpos($potential_redirect, 'http') !== 0;
        $is_same_host_url = !$is_internal_path && strpos($potential_redirect, $_SERVER['HTTP_HOST']) !== false;

        if ($is_internal_path || $is_same_host_url) {
            $redirect_url = $potential_redirect;
        }
    }
} elseif (isset($_SERVER['HTTP_REFERER'])) {
    // Similar validación para HTTP_REFERER si se quiere ser más estricto
    $referer_url = $_SERVER['HTTP_REFERER'];
    $is_internal_referer_path = strpos($referer_url, 'http') !== 0;
    $is_same_host_referer_url = !$is_internal_referer_path && strpos($referer_url, $_SERVER['HTTP_HOST']) !== false;
    if ($is_internal_referer_path || $is_same_host_referer_url) {
        $redirect_url = $referer_url;
    }
}


// 1. Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $redirect_url; // Guardar a dónde volver tras el login
    header('Location: login.php'); // Redirige a login
    exit();
}

// 2. Verificar si se envió modelo_id
if (isset($_POST['modelo_id'])) {
    $usuario_id = $_SESSION['user_id'];
    $modelo_id = (int)$_POST['modelo_id']; // Asegurarse de que es un entero

    // 3. (Opcional pero recomendado) Validar que el modelo_id existe en la tabla modelos
    $stmt_check_modelo = $pdo->prepare("SELECT id FROM modelos WHERE id = :modelo_id");
    $stmt_check_modelo->execute([':modelo_id' => $modelo_id]);
    if(!$stmt_check_modelo->fetch()){
        $_SESSION['message_toggle_fav'] = ['type' => 'error', 'text' => 'El modelo especificado no existe.'];
        header("Location: " . $redirect_url); // Redirigir a donde se estaba o al default
        exit();
    }

    // 4. Comprobar si ya es favorito usando COUNT(*)
    $stmt_check_fav = $pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE usuario_id = :usuario_id AND modelo_id = :modelo_id");
    $stmt_check_fav->execute(['usuario_id' => $usuario_id, 'modelo_id' => $modelo_id]);
    $is_already_favorite = $stmt_check_fav->fetchColumn() > 0; // true si count > 0, false si no

    try {
        if ($is_already_favorite) {
            // Si ya es favorito, quitarlo usando la combinación de usuario_id y modelo_id
            $stmt_toggle = $pdo->prepare("DELETE FROM favoritos WHERE usuario_id = :usuario_id AND modelo_id = :modelo_id");
            $stmt_toggle->execute([
                ':usuario_id' => $usuario_id,
                ':modelo_id' => $modelo_id
            ]);
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

    // 5. Redirigir
    header("Location: " . $redirect_url);
    exit();

} else {
    // Si no se envió modelo_id
    $_SESSION['message_toggle_fav'] = ['type' => 'error', 'text' => 'No se especificó ningún modelo.'];
    header("Location: " . $redirect_url); // Redirigir a donde se estaba o al default
    exit();
}
?>