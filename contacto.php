<?php
/**
 * contacto.php
 * Página de soporte y contacto con el diseño de dos columnas
 */
require_once 'includes/funciones.php';

$enviado = false;
$error   = '';
$nombre  = '';
$email   = '';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre  = post_texto('nombre');
    $email   = post_texto('email');
    $mensaje = post_texto('mensaje');

    if ($nombre === '' || $email === '' || $mensaje === '') {
        $error = 'Completá todos los campos para enviar tu mensaje.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresá un correo electrónico válido.';
    } else {
        // Aquí puedes guardar la consulta o enviarla
        $enviado = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Soporte y Contacto · Apsy</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap">

    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="pagina-login">
<div class="login">

    <!-- Panel izquierdo:  -->
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

    <!-- Panel derecho: formulario de contacto  -->
    <section class="login__acceso">
        <p class="login__ayuda"><a href="login.php">← Volver al login</a></p>

        <div class="login__formulario">
            <div class="login__bienvenida">
                <span class="login__simbolo"><?= icono('scissors', 25) ?></span>
                <h1>Centro de Soporte Apsy</h1>
                <p>¿Tenés algún inconveniente o consulta? Escribinos y te ayudamos.</p>
            </div>

            <?php if ($enviado): ?>
                <div class="alerta alerta--ok" role="status" style="margin-top: 1.5rem;">
                    <?= icono('circle-check', 17) ?>
                    <p>¡Mensaje enviado con éxito! Nos pondremos en contacto a la brevedad.</p>
                </div>
                <p style="margin-top: 1.5rem; text-align: center;">
                    <a class="boton boton--primario boton--bloque" href="login.php">
                        Volver al ingreso
                    </a>
                </p>
            <?php else: ?>
                <?php if ($error !== ''): ?>
                    <div class="alerta alerta--error" role="alert" style="margin-top: 1rem;">
                        <?= icono('circle-alert', 17) ?>
                        <p><?= e($error) ?></p>
                    </div>
                <?php endif; ?>

                <form method="post" action="contacto.php" novalidate style="margin-top: 1.5rem;">
                    <div class="login__campos">
                        <div class="campo">
                            <label class="campo__etiqueta" for="nombre">Tu nombre</label>
                            <div class="campo__caja">
                                <input class="campo__control" type="text" id="nombre" name="nombre" 
                                       value="<?= e($nombre) ?>" required autofocus>
                            </div>
                        </div>

                        <div class="campo">
                            <label class="campo__etiqueta" for="email">Correo electrónico</label>
                            <div class="campo__caja">
                                <input class="campo__control" type="email" id="email" name="email" 
                                       value="<?= e($email) ?>" required>
                            </div>
                        </div>

                        <div class="campo">
                            <label class="campo__etiqueta" for="mensaje">Mensaje o consulta</label>
                            <div class="campo__caja">
                                <textarea class="campo__control" id="mensaje" name="mensaje" rows="3" 
                                          style="padding: 0.75rem; resize: vertical;" required><?= e($mensaje) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <button class="boton boton--primario boton--grande boton--bloque" type="submit" style="margin-top: 1.5rem;">
                        Enviar mensaje <?= icono('arrow-right', 17) ?>
                    </button>
                </form>
            <?php endif; ?>

            <p class="login__pie" style="margin-top: 3rem;">© <?= date('Y') ?> Apsy · Todo tu salón, en un solo lugar.</p>
        </div>
    </section>
</div>
</body>
</html>