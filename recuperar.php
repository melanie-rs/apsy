<?php
/**
 * recuperar.php
 * Proceso de recuperación y actualización de contraseña.
 */
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

$mensaje_exito = '';
$error = '';
$paso = 1; // 1: Pedir usuario, 2: Cambiar nueva contraseña
$usuario_encontrado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = post_texto('accion');

    if ($accion === 'verificar_usuario') {
        $usuario_ingresado = trim(post_texto('usuario'));
        
        $stmt = $conexion->prepare('SELECT id, usuario FROM usuarios WHERE usuario = ?');
        $stmt->bind_param('s', $usuario_ingresado);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($resultado) {
            $paso = 2;
            $usuario_encontrado = $resultado['usuario'];
        } else {
            $error = 'No encontramos ningún usuario con ese nombre en el sistema.';
        }
    } 
    elseif ($accion === 'cambiar_clave') {
        $usuario_ingresado = post_texto('usuario_oculto');
        $nueva_clave       = post_texto('nueva_clave');
        $repetir_clave     = post_texto('repetir_clave');

        if (strlen($nueva_clave) < 4) {
            $error = 'La contraseña debe tener al menos 4 caracteres.';
            $paso = 2;
            $usuario_encontrado = $usuario_ingresado;
        } elseif ($nueva_clave !== $repetir_clave) {
            $error = 'Las contraseñas no coinciden.';
            $paso = 2;
            $usuario_encontrado = $usuario_ingresado;
        } else {
            $hash = password_hash($nueva_clave, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare('UPDATE usuarios SET password_hash = ? WHERE usuario = ?');
            $stmt->bind_param('ss', $hash, $usuario_ingresado);
            $stmt->execute();
            $stmt->close();

            $mensaje_exito = '¡Contraseña actualizada con éxito! Ya podés ingresar al sistema.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contraseña · Apsy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="pagina-login">
<div class="login">
    <!-- Panel izquierdo de la marca con la tijerita -->
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

    <!-- Panel derecho con el formulario de blanqueo -->
    <section class="login__acceso">
        <p class="login__ayuda"><a href="login.php">← Volver al login</a></p>

        <div class="login__formulario">
            <div class="login__bienvenida">
                <span class="login__simbolo"><?= icono('scissors', 25) ?></span>
                <h1>Recuperar acceso</h1>
                <p>Te ayudamos a reestablecer tu contraseña</p>
            </div>

            <?php if ($mensaje_exito !== ''): ?>
                <div class="alerta alerta--ok" role="status" style="margin-top: 1.5rem;">
                    <?= icono('circle-check', 17) ?>
                    <p><?= e($mensaje_exito) ?></p>
                </div>
                <p style="margin-top: 1.5rem; text-align: center;">
                    <a class="boton boton--primario boton--bloque" href="login.php">Ir a iniciar sesión</a>
                </p>
            <?php else: ?>
                <?php if ($error !== ''): ?>
                    <div class="alerta alerta--error" role="alert" style="margin-top: 1rem;">
                        <?= icono('circle-alert', 17) ?>
                        <p><?= e($error) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($paso === 1): ?>
                    <form method="post" action="recuperar.php" novalidate style="margin-top: 1.5rem;">
                        <input type="hidden" name="accion" value="verificar_usuario">
                        <div class="campo">
                            <label class="campo__etiqueta" for="usuario">Ingresá tu nombre de usuario</label>
                            <div class="campo__caja">
                                <input class="campo__control" type="text" id="usuario" name="usuario" required autofocus>
                            </div>
                        </div>
                        <button class="boton boton--primario boton--grande boton--bloque" type="submit" style="margin-top: 1.5rem;">
                            Continuar <?= icono('arrow-right', 17) ?>
                        </button>
                    </form>
                <?php else: ?>
                    <form method="post" action="recuperar.php" novalidate style="margin-top: 1.5rem;">
                        <input type="hidden" name="accion" value="cambiar_clave">
                        <input type="hidden" name="usuario_oculto" value="<?= e($usuario_encontrado) ?>">
                        
                        <p class="texto-suave" style="margin-bottom: 1rem;">Usuario verificado: <strong><?= e($usuario_encontrado) ?></strong></p>

                        <div class="login__campos">
                            <div class="campo">
                                <label class="campo__etiqueta" for="nueva_clave">Nueva contraseña</label>
                                <div class="campo__caja">
                                    <input class="campo__control" type="password" id="nueva_clave" name="nueva_clave" required autofocus>
                                </div>
                            </div>

                            <div class="campo">
                                <label class="campo__etiqueta" for="repetir_clave">Repetir nueva contraseña</label>
                                <div class="campo__caja">
                                    <input class="campo__control" type="password" id="repetir_clave" name="repetir_clave" required>
                                </div>
                            </div>
                        </div>

                        <button class="boton boton--primario boton--grande boton--bloque" type="submit" style="margin-top: 1.5rem;">
                            Guardar nueva contraseña <?= icono('arrow-right', 17) ?>
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <p class="login__pie" style="margin-top: 3rem;">© <?= date('Y') ?> Apsy · Todo tu salón, en un solo lugar.</p>
        </div>
    </section>
</div>
</body>
</html>