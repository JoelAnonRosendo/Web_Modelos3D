<?php
session_start();
require 'db_config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    // Podrías enviar un error JSON si usaras AJAX
    header('Location: login.php'); // Redirige a login si no está logueado
    exit();
}

if (isset($_POST['modelo_id'])) {
    $usuario_id = $_SESSION['user_id'];
    $modelo_id = (int)$_POST['modelo_id'];

    // Comprobar si ya es favorito
    $stmt = $pdo->prepare("SELECT * FROM favoritos WHERE usuario_id = :usuario_id AND modelo_id = :modelo_id");
    $stmt->execute(['usuario_id' => $usuario_id, 'modelo_id' => $modelo_id]);
    $es_favorito = $stmt->fetch();

    if ($es_favorito) {
        // Si ya es favorito, quitarlo
        $stmt = $pdo->prepare("DELETE FROM favoritos WHERE usuario_id = :usuario_id AND modelo_id = :modelo_id");
        $stmt->execute(['usuario_id' => $usuario_id, 'modelo_id' => $modelo_id]);
    } else {
        // Si no es favorito, añadirlo
        $stmt = $pdo->prepare("INSERT INTO favoritos (usuario_id, modelo_id) VALUES (:usuario_id, :modelo_id)");
        $stmt->execute(['usuario_id' => $usuario_id, 'modelo_id' => $modelo_id]);
    }

    // Redirigir de vuelta a la página desde donde se hizo la solicitud (o a index.php por defecto)
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: " . $redirect_url);
    exit();

} else {
    // Si no se envió modelo_id, redirigir
    header("Location: index.php");
    exit();
}
?>