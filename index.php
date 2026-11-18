<?php
/**
 * index.php
 * Punto de entrada (http://localhost/apsy/). Decide a dónde ir según la sesión.
 */
require_once 'includes/auth.php';

header('Location: ' . (usuario_logueado() ? 'dashboard.php' : 'login.php'));
exit;
