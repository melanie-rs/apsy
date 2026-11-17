<?php
/**
 * includes/conexion.php
 * Crea la conexión a MySQL con MySQLi (estilo orientado a objetos).
 * Se incluye desde las demás páginas con:  require_once 'includes/conexion.php';
 * Después de incluirlo, queda disponible la variable $conexion.
 */

// ---- Datos de conexión (valores por defecto de XAMPP) -----------------
const DB_HOST    = 'localhost';
const DB_USUARIO = 'root';
const DB_CLAVE   = '';          // XAMPP trae root sin contraseña
const DB_NOMBRE  = 'apsy';

// Hora de Argentina para PHP (date(), strtotime(), etc.)
date_default_timezone_set('America/Argentina/Cordoba');

// Hace que MySQLi lance excepciones (mysqli_sql_exception) cuando hay un
// error, en vez de devolver false en silencio. Así los errores no pasan
// desapercibidos y los podemos capturar con try/catch.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexion = new mysqli(DB_HOST, DB_USUARIO, DB_CLAVE, DB_NOMBRE);

    // Todo el intercambio con la base se hace en UTF-8 (tildes y ñ).
    $conexion->set_charset('utf8mb4');

    // Misma zona horaria en MySQL, para que CURDATE() coincida con la fecha
    // que ve el usuario (Argentina es UTC-3 y no tiene horario de verano).
    $conexion->query("SET time_zone = '-03:00'");

} catch (mysqli_sql_exception $e) {
    // El detalle técnico va al registro de errores de PHP, no a la pantalla.
    error_log('APSY - error de conexión: ' . $e->getMessage());

    http_response_code(500);
    exit('No se pudo conectar con la base de datos. '
       . 'Verificá que MySQL esté iniciado en XAMPP y que la base "apsy" exista.');
}
