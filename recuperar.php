<?php
/**
 * recuperar.php
 * Informa el procedimiento de recuperación asistida de acceso.
 *
 * Por seguridad, el cambio de contraseña no se realiza desde esta pantalla.
 * El restablecimiento debe ser solicitado al responsable del sistema.
 */
require_once 'includes/funciones.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar acceso · Apsy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="pagina-login">
<div class="login">
    <!-- Panel izquierdo de la marca -->
    <section class="login__marca" aria-hidden="true">
        <div class="marca marca--clara">
            <span class="marca__simbolo"><?= icono('scissors', 22) ?></span>
            <span class="marca__nombre">Apsy</span>
        </div>
        <div class="login__mensaje">
            <span class="login__detalle"></span>
            <p class="login__promesa">Más tiempo para lo que mejor hacés.</p>
            <p class="login__texto">Tu agenda en orden. Tu equipo conectado. Cada cliente, en buenas manos.</p>
            <p class="login__firma">HECHO PARA EL RITMO DE TU SALÓN</p>
        </div>
    </section>

    <!-- Panel derecho con las instrucciones de recuperación -->
    <section class="login__acceso">
        <p class="login__ayuda"><a href="login.php">← Volver al login</a></p>

        <div class="login__formulario">
            <div class="login__bienvenida">
                <span class="login__simbolo"><?= icono('scissors', 25) ?></span>
                <h1>Recuperar acceso</h1>
                <p>Te ayudamos a recuperar el acceso a tu cuenta.</p>
            </div>

            <div class="alerta" role="status" style="margin-top: 1.5rem;">
                <?= icono('circle-alert', 17) ?>
                <p>
                    Por seguridad, no podés cambiar la contraseña desde esta pantalla.
                    Si la olvidaste, comunicate con el responsable del sistema para
                    solicitar el restablecimiento de tu acceso.
                </p>
            </div>

            <p style="margin-top: 1.5rem; text-align: center;">
                <a class="boton boton--primario boton--bloque" href="login.php">Volver a iniciar sesión</a>
            </p>

            <p class="login__pie" style="margin-top: 3rem;">© <?= date('Y') ?> Apsy · Todo tu salón, en un solo lugar.</p>
        </div>
    </section>
</div>
</body>
</html>
