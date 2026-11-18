<?php
/**
 * login.php
 * Pantalla de ingreso. Cumple dos roles en el mismo archivo:
 *   - GET : muestra el formulario.
 *   - POST: procesa el formulario, valida y, si es correcto, inicia la sesión.
 */
require_once 'includes/auth.php';
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

// Si ya hay sesión iniciada, no tiene sentido mostrar el login.
if (usuario_logueado()) {
    header('Location: dashboard.php');
    exit;
}

$usuario     = '';     // para volver a mostrar lo escrito si hubo error
$errores     = [];     // errores por campo: ['usuario' => '...', 'clave' => '...']
$error_login = '';     // error general: credenciales incorrectas

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) Leer los datos. trim() quita espacios sobrantes; ?? '' evita avisos si falta el campo.
    $usuario = trim($_POST['usuario'] ?? '');
    $clave   = $_POST['clave'] ?? '';          // la contraseña NO se recorta: los espacios pueden ser parte de ella

    // 2) Validar en el servidor.
    if ($usuario === '') {
        $errores['usuario'] = 'Ingresá tu usuario.';
    }
    if ($clave === '') {
        $errores['clave'] = 'Ingresá tu contraseña.';
    }

    // 3) Si los datos están completos, consultar la base.
    if (!$errores) {
        // Consulta preparada: el "?" se reemplaza por el valor de forma segura,
        // así lo escrito por el usuario nunca se interpreta como código SQL.
        $stmt = $conexion->prepare(
            'SELECT id, usuario, password_hash, nombre, apellido, rol
               FROM usuarios
              WHERE usuario = ?'
        );
        $stmt->bind_param('s', $usuario);       // 's' = el parámetro es un string
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();   // una fila o null si no existe
        $stmt->close();

        // 4) Comparar la contraseña escrita con el hash guardado.
        if ($fila && password_verify($clave, $fila['password_hash'])) {
            iniciar_sesion($fila);
            header('Location: dashboard.php');
            exit;
        }

        // Mensaje único para "usuario inexistente" y "contraseña mala":
        // así no se revela cuál de los dos datos falló.
        $error_login = 'El usuario o la contraseña no son correctos. Revisá los datos e intentá de nuevo.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar · Apsy</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap">

    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="pagina-login">
<div class="login">

    <!-- Panel izquierdo: identidad de la marca (se oculta en el celular) -->
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

    <!-- Panel derecho: formulario -->
    <section class="login__acceso">
        <p class="login__ayuda">¿Necesitás ayuda? <a href="contacto.php">Contactanos</a></p>

        <form class="login__formulario" method="post" action="login.php" novalidate>
            <div class="login__bienvenida">
                <span class="login__simbolo"><?= icono('scissors', 25) ?></span>
                <h1>Bienvenida a Apsy</h1>
                <p>Organizá tu peluquería en un solo lugar</p>
            </div>

            <div class="login__campos">
                <div class="campo<?= isset($errores['usuario']) ? ' campo--error' : '' ?>">
                    <label class="campo__etiqueta" for="usuario">Usuario</label>
                    <div class="campo__caja campo__caja--icono">
                        <input class="campo__control" type="text" id="usuario" name="usuario"
                               value="<?= e($usuario) ?>" autocomplete="username" autofocus>
                        <span class="campo__icono"><?= icono('user-round', 16) ?></span>
                    </div>
                    <?php if (isset($errores['usuario'])): ?>
                        <p class="campo__error"><?= e($errores['usuario']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="campo<?= isset($errores['clave']) ? ' campo--error' : '' ?>">
                    <label class="campo__etiqueta" for="clave">Contraseña</label>
                    <div class="campo__caja campo__caja--icono">
                        <input class="campo__control" type="password" id="clave" name="clave"
                               autocomplete="current-password">
                        <button class="campo__icono" type="button" data-ver-clave
                                aria-label="Mostrar contraseña" aria-pressed="false">
                            <span data-icono-oculta><?= icono('eye-off', 16) ?></span>
                            <span data-icono-visible hidden><?= icono('eye', 16) ?></span>
                        </button>
                    </div>
                    <?php if (isset($errores['clave'])): ?>
                        <p class="campo__error"><?= e($errores['clave']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($error_login !== ''): ?>
                <div class="alerta alerta--error" role="alert">
                    <?= icono('circle-alert', 17) ?>
                    <p><?= e($error_login) ?></p>
                </div>
            <?php endif; ?>

            <button class="boton boton--primario boton--grande boton--bloque" type="submit">
                Ingresar <?= icono('arrow-right', 17) ?>
            </button>

            <a class="login__recuperar" href="recuperar.php">¿Te olvidaste la contraseña?</a>
        </form>

        <p class="login__pie">© <?= date('Y') ?> Apsy · Todo tu salón, en un solo lugar.</p>
    </section>
</div>

<script src="assets/js/login.js"></script>
</body>
</html>
