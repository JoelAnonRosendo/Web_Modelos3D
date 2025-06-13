<?php
session_start();
require 'db_config.php';

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    // Es mejor redirigir con un mensaje si es posible, pero para un handler, morir es aceptable
    // si la URL fue manipulada.
    header("HTTP/1.1 400 Bad Request");
    echo "ID de modelo inválido.";
    exit;
}
$modelo_id_descarga = (int)$_GET['id'];

try {
    $stmt_modelo = $pdo->prepare("SELECT nombre_modelo, archivo_stl, precio FROM modelos WHERE id = :id");
    $stmt_modelo->execute([':id' => $modelo_id_descarga]);
    $modelo_desc_data = $stmt_modelo->fetch(PDO::FETCH_ASSOC);

    if (!$modelo_desc_data || empty($modelo_desc_data['archivo_stl'])) {
        $_SESSION['download_error'] = "Modelo o archivo de descarga no encontrado.";
        header("Location: modelo_detalle.php?id=" . $modelo_id_descarga . "&status=file_not_found_handler");
        exit();
    }

    $puede_descargar = false;

    // 1. Comprobar si el modelo es gratuito
    if (isset($modelo_desc_data['precio']) && (float)$modelo_desc_data['precio'] <= 0) {
        $puede_descargar = true;
    }
    // 2. Si no es gratuito, comprobar si el usuario está logueado y lo ha comprado
    elseif (isset($_SESSION['user_id'])) {
        $usuario_id_descarga = $_SESSION['user_id'];
        $stmt_compra = $pdo->prepare("SELECT id FROM compras WHERE usuario_id = :usuario_id AND modelo_id = :modelo_id AND estado_pago = 'completado'");
        $stmt_compra->execute([':usuario_id' => $usuario_id_descarga, ':modelo_id' => $modelo_id_descarga]);
        if ($stmt_compra->fetch()) {
            $puede_descargar = true;
        }
    }

    if ($puede_descargar) {
        $filepath = $modelo_desc_data['archivo_stl'];

        if (file_exists($filepath) && is_readable($filepath)) { // Verificar legibilidad también
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream'); // Tipo genérico. Ajustar si es necesario
                                                               // e.g., 'application/sla' for STL
                                                               // 'application/vnd.ms-pki.stl'
            header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            
            // Limpiar buffers de salida para evitar corrupción del archivo
            if (ob_get_level()) { // Si hay algún buffer activo
                 ob_end_clean();
            }
            
            readfile($filepath);
            exit;
        } else {
            error_log("Archivo no encontrado o no legible en el servidor para modelo ID {$modelo_id_descarga}: {$filepath}");
            $_SESSION['download_error'] = "El archivo del modelo no se puede acceder. Contacta al administrador.";
            header("Location: modelo_detalle.php?id=" . $modelo_id_descarga . "&status=server_file_issue_handler");
            exit();
        }
    } else {
        // Si no puede descargar (ej. necesita comprar o loguearse)
        if(isset($modelo_desc_data['precio']) && $modelo_desc_data['precio'] > 0 && !isset($_SESSION['user_id'])) {
             $_SESSION['redirect_after_login'] = 'modelo_detalle.php?id=' . $modelo_id_descarga; // Redirigir a detalles post-login
             header("Location: login.php");
        } else {
            $_SESSION['download_error'] = "Acceso denegado para la descarga de este modelo.";
            header("Location: modelo_detalle.php?id=" . $modelo_id_descarga . "&status=purchase_required_handler");
        }
        exit();
    }

} catch (PDOException $e) {
    error_log("Error al procesar descarga para modelo ID {$modelo_id_descarga}: " . $e->getMessage());
    $_SESSION['download_error'] = "Ocurrió un error al procesar tu solicitud de descarga.";
    header("Location: modelo_detalle.php?id=" . $modelo_id_descarga . "&status=download_error_handler");
    exit();
}
?>