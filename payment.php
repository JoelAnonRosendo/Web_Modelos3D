<?php
session_start();
require 'db_config.php';

// 1. Redirigir si no está logueado
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['modelo_id'])) {
        $_SESSION['redirect_after_login'] = 'payment.php?modelo_id=' . urlencode($_GET['modelo_id']);
    }
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];
$modelo_id_get = $_GET['modelo_id'] ?? null; // Para repoblar link
$modelo = null;
$error_message = '';
$success_message = '';

// Repoblar campos
$nombre_tarjeta_val = $_POST['nombre_tarjeta'] ?? '';
$numero_tarjeta_val = $_POST['numero_tarjeta'] ?? '';
$fecha_exp_val = $_POST['fecha_exp'] ?? '';
$cvc_val = $_POST['cvc'] ?? '';

// 2. Obtener ID del modelo y sus detalles
if (isset($_GET['modelo_id']) && filter_var($_GET['modelo_id'], FILTER_VALIDATE_INT)) {
    $modelo_id = (int)$_GET['modelo_id'];
    try {
        $stmt = $pdo->prepare("SELECT id, nombre_modelo, precio, imagen_url FROM modelos WHERE id = :id");
        $stmt->execute([':id' => $modelo_id]);
        $modelo_data_checkout = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$modelo_data_checkout) {
            $error_message = "Modelo no encontrado.";
            $modelo_id = null; 
        } elseif (isset($modelo_data_checkout['precio']) && (float)$modelo_data_checkout['precio'] <= 0) {
            header("Location: modelo_detalle.php?id=" . $modelo_id . "&status=free_no_payment_needed");
            exit();
        }
    } catch (PDOException $e) {
        $error_message = "Error al cargar los detalles del modelo: " . $e->getMessage();
        $modelo_id = null;
    }
} else {
    $error_message = "ID de modelo no válido o no proporcionado.";
    $modelo_id = null; // Asegurarse de que no se intente procesar nada si no hay ID
}

// 3. Verificar si el usuario ya ha comprado este modelo (si el modelo_id es válido)
if ($modelo_id && $usuario_id) {
    try {
        $stmt_check = $pdo->prepare("SELECT id FROM compras WHERE usuario_id = :usuario_id AND modelo_id = :modelo_id AND estado_pago = 'completado'");
        $stmt_check->execute([':usuario_id' => $usuario_id, ':modelo_id' => $modelo_id]);
        if ($stmt_check->fetch()) {
            header("Location: modelo_detalle.php?id=" . $modelo_id . "&status=already_owned");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error al verificar compra existente en payment.php: " . $e->getMessage());
    }
}

// 4. Procesar el formulario de pago (simulado)
if ($_SERVER["REQUEST_METHOD"] == "POST" && $modelo_id && $modelo_data_checkout) {
    $nombre_tarjeta = trim($_POST['nombre_tarjeta']);
    $numero_tarjeta_raw = str_replace(' ', '', trim($_POST['numero_tarjeta']));
    $fecha_exp = trim($_POST['fecha_exp']);
    $cvc = trim($_POST['cvc']);

    // Validaciones
    if (empty($nombre_tarjeta) || empty($numero_tarjeta_raw) || empty($fecha_exp) || empty($cvc)) {
        $error_message = "Todos los campos de la tarjeta son obligatorios.";
    } elseif (!preg_match('/^\d{13,19}$/', $numero_tarjeta_raw)) {
        $error_message = "Formato de número de tarjeta inválido.";
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\s*\/\s*([0-9]{2})$/', $fecha_exp, $matches_exp)) { 
        $error_message = "Formato de fecha de expiración inválido (MM/AA).";
    } elseif (isset($matches_exp[2])) { // Validar si la fecha no ha pasado
        $ano_actual_dos_digitos = date('y');
        $mes_actual = date('m');
        $exp_mes = $matches_exp[1];
        $exp_ano_dos_digitos = $matches_exp[2];
        if ($exp_ano_dos_digitos < $ano_actual_dos_digitos || ($exp_ano_dos_digitos == $ano_actual_dos_digitos && $exp_mes < $mes_actual)) {
            $error_message = "La tarjeta ha expirado.";
        }
    }
    if (!preg_match('/^\d{3,4}$/', $cvc) && empty($error_message)) { // Solo si no hay otro error ya
        $error_message = "Formato de CVC inválido.";
    }

    if (empty($error_message)) {
        // ------ SIMULACIÓN DE PAGO ------
        $simular_exito = true;
        if ($cvc === '000' || substr($numero_tarjeta_raw, -3) === '000') { // Simular fallo
            $simular_exito = false;
            $error_message = "Pago rechazado por el banco (simulado). Por favor, verifica tus datos.";
        }

        if ($simular_exito) {
            try {
                $pdo->beginTransaction();
                $sql_compra = "INSERT INTO compras (usuario_id, modelo_id, precio_compra, metodo_pago, transaccion_id_gateway, estado_pago)
                               VALUES (:usuario_id, :modelo_id, :precio_compra, :metodo_pago, :transaccion_id, :estado_pago)";
                $stmt_compra = $pdo->prepare($sql_compra);
                $stmt_compra->execute([
                    ':usuario_id' => $usuario_id,
                    ':modelo_id' => $modelo_id,
                    ':precio_compra' => $modelo_data_checkout['precio'],
                    ':metodo_pago' => 'simulado_tarjeta',
                    ':transaccion_id' => 'SIMUL_' . strtoupper(uniqid()),
                    ':estado_pago' => 'completado'
                ]);
                $pdo->commit();

                $success_message = "¡Pago simulado exitoso! Has 'comprado' el modelo: " . htmlspecialchars($modelo_data_checkout['nombre_modelo']) . ".";
                $modelo_data_checkout = null; // Para no mostrar el form
                $nombre_tarjeta_val = $numero_tarjeta_val = $fecha_exp_val = $cvc_val = '';

            } catch (PDOException $e) {
                $pdo->rollBack();
                $error_message = "Error al registrar la compra simulada.";
                error_log("Error DB en payment.php: " . $e->getMessage());
            }
        }
    }
}

$page_title = ($modelo_id && $modelo_data_checkout && empty($success_message)) ? "Pagar: " . htmlspecialchars($modelo_data_checkout['nombre_modelo']) : "Proceso de Pago";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - PrintVerse</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <style>
        .payment-container { max-width: 600px; margin: 40px auto; padding: 30px; background-color: var(--card-bg-color, #fff); border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .payment-container h1, .payment-container h2 { text-align: center; margin-bottom: 25px; }
        .model-summary { display: flex; align-items: center; gap: 20px; padding: 15px; background-color: var(--bg-color); border-radius: 6px; margin-bottom: 30px; border: 1px solid #eee; }
        .model-summary img { width: 80px; height: 80px; object-fit: cover; border-radius: 4px; }
        .model-summary-info h3 { margin: 0 0 5px 0; font-size: 1.2em; }
        .model-summary-info p { margin: 0; font-size: 1.3em; font-weight: bold; color: var(--primary-color); }

        .payment-form .form-group { margin-bottom: 20px; }
        .payment-form label { display: block; margin-bottom: 8px; font-weight: bold; font-size: 0.95em; }
        .payment-form input[type="text"], .payment-form input[type="tel"] { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 1em; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }

        .btn-pay { display: block; width: 100%; padding: 15px; font-size: 1.2em; text-transform: uppercase; letter-spacing: 1px; background-color: var(--accent-color); color: var(--header-bg); border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; font-weight:bold; }
        .btn-pay:hover { background-color: darken(var(--accent-color), 10%); }

        .message { padding: 12px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-size: 0.95em;}
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;}
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;}
        .payment-secure-info { text-align: center; font-size: 0.9em; color: var(--secondary-color); margin-top: 25px; padding: 10px; background-color: #f9f9f9; border-radius:4px; }
        .payment-secure-info svg { vertical-align: middle; width: 16px; height: 16px; margin-right: 5px; fill: currentColor; }
    </style>
</head>
<body>
    <header> <!-- Header simplificado para página de pago -->
        <div class="container">
            <div class="logo"><h1><a href="index.php" style="text-decoration:none; color:var(--header-text);">Print<span class="highlight">Verse</span></a></h1></div>
            <nav>
                <ul>
                    <li><a href="index.php#models">Ver más Modelos</a></li>
                    <?php if (isset($_SESSION['user_alias'])): ?>
                         <li class="nav-user-greeting" style="margin-left:auto;"><span >Hola, <?php echo htmlspecialchars($_SESSION['user_alias']); ?>!</span></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="payment-container">
             <h1><?php echo htmlspecialchars($page_title); ?></h1>

            <?php if (!empty($error_message)): ?><div class="message error"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="message success"><?php echo $success_message; ?></div>
                <p style="text-align:center; margin-top:20px;">
                    <a href="modelo_detalle.php?id=<?php echo htmlspecialchars($modelo_id_get ?? $modelo_id ?? ''); ?>" class="btn">Ir a Descargar</a>
                    <a href="index.php" class="btn btn-primary" style="margin-left:10px;">Seguir Explorando</a>
                </p>
            <?php endif; ?>

            <?php if ($modelo_id && $modelo_data_checkout && empty($success_message)): ?>
                <div class="model-summary">
                    <img src="<?php echo htmlspecialchars($modelo_data_checkout['imagen_url'] ?? 'img/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($modelo_data_checkout['nombre_modelo']); ?>">
                    <div class="model-summary-info">
                        <h3><?php echo htmlspecialchars($modelo_data_checkout['nombre_modelo']); ?></h3>
                        <p>Total: $<?php echo number_format((float)$modelo_data_checkout['precio'], 2); ?></p>
                    </div>
                </div>

                <form action="payment.php?modelo_id=<?php echo $modelo_id; ?>" method="POST" class="payment-form">
                    <h2>Información de Pago (Simulado)</h2>
                    <div class="form-group">
                        <label for="nombre_tarjeta">Nombre en la Tarjeta</label>
                        <input type="text" id="nombre_tarjeta" name="nombre_tarjeta" required value="<?php echo htmlspecialchars($nombre_tarjeta_val); ?>">
                    </div>
                    <div class="form-group">
                        <label for="numero_tarjeta">Número de Tarjeta</label>
                        <input type="tel" id="numero_tarjeta" name="numero_tarjeta" placeholder="0000 0000 0000 0000" required pattern="^\d{4}\s?\d{4}\s?\d{4}\s?\d{4}$" inputmode="numeric" maxlength="19" title="Debe ser un número de 16 dígitos, opcionalmente con espacios cada 4." value="<?php echo htmlspecialchars($numero_tarjeta_val); ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_exp">Expiración (MM/AA)</label>
                            <input type="tel" id="fecha_exp" name="fecha_exp" placeholder="MM/AA" required pattern="^(0[1-9]|1[0-2])\s*\/\s*\d{2}$" maxlength="7" title="Formato MM/AA" value="<?php echo htmlspecialchars($fecha_exp_val); ?>">
                        </div>
                        <div class="form-group">
                            <label for="cvc">CVC</label>
                            <input type="tel" id="cvc" name="cvc" placeholder="123" required pattern="\d{3,4}" inputmode="numeric" maxlength="4" title="3 o 4 dígitos" value="<?php echo htmlspecialchars($cvc_val); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-pay">Pagar $<?php echo number_format((float)$modelo_data_checkout['precio'], 2); ?></button>
                    </div>
                     <p class="payment-secure-info">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 1.99994C12.5523 1.99994 13 2.44767 13 2.99994V4.44103C15.2451 4.73559 17.1143 5.93771 18.3817 7.69503L19.5071 6.90977C19.9605 6.6308 20.5395 6.74454 20.8184 7.19797C21.0973 7.65139 20.9836 8.23043 20.5301 8.5094L19.3622 9.32471C20.2312 10.7201 20.7077 12.3187 20.7077 13.9999C20.7077 18.541 16.7487 22.4999 12.2077 22.4999C12.1394 22.4999 12.0714 22.4964 12.0038 22.4919C11.936 22.4963 11.8678 22.4999 11.7996 22.4999C7.25843 22.4999 3.30048 18.541 3.29958 13.9999C3.29958 12.3196 3.77524 10.7216 4.64343 9.32641L3.47396 8.50961C3.02046 8.23056 2.90675 7.65152 3.18571 7.19809C3.46467 6.74466 4.04362 6.63093 4.49706 6.90992L5.62134 7.69497C6.88822 5.93785 8.75682 4.7359 10.9996 4.4411V2.99994C10.9996 2.44767 11.4473 1.99994 11.9996 1.99994H12ZM12 9.99994C10.3431 9.99994 9 11.3431 9 12.9999V15.9999C9 16.5522 9.44772 16.9999 10 16.9999H14C14.5523 16.9999 15 16.5522 15 15.9999V12.9999C15 11.3431 13.6569 9.99994 12 9.99994Z"></path></svg>
                        <strong>Este es un entorno de simulación.</strong> NO introduzcas datos de tarjeta reales.
                    </p>
                </form>
            <?php elseif(empty($error_message) && empty($success_message) && !$modelo_id_get && !$modelo_id): ?>
                <div class="message error">No se pudo determinar el modelo a comprar. <a href="index.php" class="btn">Volver al inicio</a>.</div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container"><p>© <?php echo date("Y"); ?> PrintVerse. Todos los derechos reservados.</p></div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollToPlugin.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="./animation.js"></script>
    <script>
        <?php if ($modelo_id && $modelo_data_checkout && empty($success_message)): ?>
        const cardInput = document.getElementById('numero_tarjeta');
        if (cardInput) {
            cardInput.addEventListener('input', function (e) {
                let val = e.target.value.replace(/\D/g, '');
                let formattedVal = '';
                for (let i = 0; i < val.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedVal += ' ';
                    }
                    formattedVal += val[i];
                }
                e.target.value = formattedVal.substring(0, 19);
            });
        }

        const expiryInput = document.getElementById('fecha_exp');
        if (expiryInput) {
            expiryInput.addEventListener('input', function (e) {
                let val = e.target.value.replace(/\D/g, '');
                if (val.length >= 2 && e.target.value.indexOf('/') === -1 && e.inputType !== 'deleteContentBackward') {
                    val = val.substring(0, 2) + '/' + val.substring(2);
                } else if (val.length === 2 && e.inputType !== 'deleteContentBackward' && e.target.value.indexOf('/') === -1) {
                     // Solo añadir '/' si se están añadiendo caracteres y no hay ya uno.
                    if (e.target.value.length < this.oldValue?.length || this.oldValue?.endsWith('/')) { // this.oldValue para evitar añadir / al borrar
                        // no hacer nada o gestionar mejor borrado
                    } else {
                         val += '/';
                    }
                }
                 e.target.value = val.substring(0, 5);
                 this.oldValue = e.target.value; // Guardar valor actual para referencia en siguiente input
            });
            expiryInput.addEventListener('focus', function(e){ this.oldValue = e.target.value; }); // Para inicializar oldValue
        }
        <?php endif; ?>
    </script>
</body>
</html>