<?php
/**
 * disponibilidad.php
 * Mini servicio que responde en JSON si un horario está libre.
 * Lo consulta assets/js/turnos.js mientras la usuaria completa el formulario,
 * para mostrar "Horario disponible · Duración del servicio: 60 min".
 * Usa evaluar_horario(): la misma función que valida al guardar.
 * Es solo un AVISO: la validación que manda es la del servidor al guardar.
 */
require_once 'includes/auth.php';
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';
require_once 'includes/turnos_funciones.php';

header('Content-Type: application/json; charset=utf-8');

function responder(bool $disponible, string $mensaje): void
{
    echo json_encode(['disponible' => $disponible, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!usuario_logueado()) {
    http_response_code(401);
    responder(false, 'Tu sesión venció. Volvé a ingresar.');
}

$profesional_id = (int) get_texto('profesional');
$servicio_id    = (int) get_texto('servicio');
$fecha          = get_texto('fecha');
$hora           = get_texto('hora');
$excluir_id     = (int) get_texto('excluir');      // turno que se está editando (0 si es nuevo)

if ($profesional_id <= 0 || $servicio_id <= 0 || !fecha_valida($fecha) || !hora_valida($hora)) {
    responder(false, 'Completá profesional, servicio, fecha y hora para ver la disponibilidad.');
}

$evaluacion = evaluar_horario($conexion, $profesional_id, $servicio_id, $fecha, $hora, $excluir_id);

responder(
    $evaluacion['ok'],
    $evaluacion['ok']
        ? 'Horario disponible · Duración del servicio: ' . $evaluacion['duracion'] . ' min'
        : $evaluacion['error']
);
