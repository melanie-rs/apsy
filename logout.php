<?php
/**
 * logout.php
 * Cierra la sesión y vuelve al login. No muestra nada: solo procesa y redirige.
 */
require_once 'includes/auth.php';

cerrar_sesion();
header('Location: login.php');
exit;
