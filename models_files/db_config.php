<?php
$db_host = 'localhost'; // o tu host de base de datos
$db_name = 'webmodelos3d'; // el nombre de tu base de datos
$db_user = 'root'; // tu usuario de base de datos (por defecto en XAMPP)
$db_pass = ''; // tu contraseña de base de datos (por defecto vacía en XAMPP)

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>