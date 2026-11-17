<?php
/**
 * includes/auth.php
 * Manejo de la sesión de usuario: iniciar, consultar, proteger páginas y cerrar.
 * Se incluye al principio de TODAS las páginas (antes de enviar cualquier HTML).
 */

// Inicia la sesión una sola vez. Las opciones de la cookie la hacen más segura:
//  - httponly: JavaScript no puede leer la cookie de sesión (protege ante XSS).
//  - samesite: el navegador no la envía en pedidos que vienen de otros sitios.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,          // dura hasta cerrar el navegador
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** ¿Hay un usuario con sesión iniciada? */
function usuario_logueado(): bool
{
    return isset($_SESSION['usuario_id']);
}

/** Si no hay sesión, manda al login y corta la ejecución de la página. */
function requerir_login(): void
{
    if (!usuario_logueado()) {
        header('Location: login.php');
        exit;                     // sin exit el resto de la página se seguiría ejecutando
    }
}

/** Guarda en la sesión los datos del usuario (nunca el hash de la contraseña). */
function iniciar_sesion(array $usuario): void
{
    // Genera un nuevo id de sesión al loguearse: evita el "session fixation".
    session_regenerate_id(true);

    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario']    = $usuario['usuario'];
    $_SESSION['nombre']     = $usuario['nombre'];
    $_SESSION['apellido']   = $usuario['apellido'];
    $_SESSION['rol']        = $usuario['rol'];
}

/** Devuelve los datos del usuario logueado para mostrarlos en pantalla. */
function usuario_actual(): array
{
    return [
        'id'       => $_SESSION['usuario_id'] ?? 0,
        'usuario'  => $_SESSION['usuario']    ?? '',
        'nombre'   => $_SESSION['nombre']     ?? '',
        'apellido' => $_SESSION['apellido']   ?? '',
        'rol'      => $_SESSION['rol']        ?? '',
    ];
}

/** Cierra la sesión por completo: variables, cookie y archivo de sesión. */
function cerrar_sesion(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();
}
