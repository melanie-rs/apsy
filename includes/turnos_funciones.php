<?php
/**
 * includes/turnos_funciones.php
 * Lógica de negocio de los turnos: estados, validación de fecha y hora,
 * horario de atención y SUPERPOSICIÓN de horarios.
 * La usan turnos.php (al guardar) y disponibilidad.php (aviso en vivo).
 * Tenerla en un solo lugar garantiza que las dos partes apliquen LA MISMA regla.
 */

const HORA_APERTURA = '09:00';
const HORA_CIERRE   = '19:00';

/** Estados posibles: clave guardada en la base => texto que ve el usuario. */
function estados_turno(): array
{
    return [
        'pendiente'  => 'Pendiente',
        'confirmado' => 'Confirmado',
        'cancelado'  => 'Cancelado',
        'completado' => 'Completado',
    ];
}

/** ¿Es una fecha real con formato AAAA-MM-DD? Rechaza por ejemplo 2026-02-30. */
function fecha_valida(string $fecha): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d !== false && $d->format('Y-m-d') === $fecha;
}

/** ¿Es una hora real con formato HH:MM (00:00 a 23:59)? */
function hora_valida(string $hora): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora);
}

/**
 * Evalúa si un horario es posible para un profesional. Verifica, en orden:
 *   1) que el servicio exista (y obtiene su duración),
 *   2) que el turno entre dentro del horario de atención del salón,
 *   3) que el profesional no tenga OTRO turno que se pise (si $verificar_conflicto).
 *
 * Dos turnos se superponen cuando:   nuevo_inicio < fin_existente  Y  nuevo_fin > inicio_existente
 * Si uno termina justo cuando empieza el otro (10:00 y 10:00) NO se pisan.
 *
 * $excluir_id es el id del turno que se está editando (para que no choque consigo mismo).
 * Devuelve: ok (bool), error (texto), campo (dónde mostrar el error), hora_fin y duracion.
 */
function evaluar_horario(mysqli $conexion, int $profesional_id, int $servicio_id,
                         string $fecha, string $hora, int $excluir_id, bool $verificar_conflicto = true): array
{
    $resultado = ['ok' => false, 'error' => '', 'campo' => '', 'hora_fin' => '', 'duracion' => 0];

    // 1) Duración del servicio elegido
    $stmt = $conexion->prepare('SELECT duracion_min FROM servicios WHERE id = ?');
    $stmt->bind_param('i', $servicio_id);
    $stmt->execute();
    $servicio = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$servicio) {
        $resultado['error'] = 'Elegí un servicio de la lista.';
        $resultado['campo'] = 'servicio';
        return $resultado;
    }

    $duracion = (int) $servicio['duracion_min'];

    // La hora de fin = inicio + duración del servicio
    $inicio   = new DateTime($fecha . ' ' . $hora);
    $fin      = (clone $inicio)->modify('+' . $duracion . ' minutes');
    $hora_fin = $fin->format('H:i');

    $resultado['hora_fin'] = $hora_fin;
    $resultado['duracion'] = $duracion;
    $resultado['campo']    = 'hora';

    // 2) Horario de atención (las horas "HH:MM" se pueden comparar como texto)
    if ($hora < HORA_APERTURA) {
        $resultado['error'] = 'El salón atiende desde las ' . HORA_APERTURA . '.';
        return $resultado;
    }
    if ($fin->format('Y-m-d') !== $inicio->format('Y-m-d') || $hora_fin > HORA_CIERRE) {
        $resultado['error'] = 'El servicio terminaría a las ' . $hora_fin . ' y el salón cierra a las ' . HORA_CIERRE . '.';
        return $resultado;
    }

    // 3) Superposición con otros turnos del MISMO profesional el MISMO día.
    //    Los turnos cancelados no ocupan horario.
    if ($verificar_conflicto) {
        $stmt = $conexion->prepare(
            "SELECT hora_inicio, hora_fin
               FROM turnos
              WHERE profesional_id = ?
                AND fecha = ?
                AND estado <> 'cancelado'
                AND id <> ?
                AND hora_inicio < ?
                AND hora_fin > ?
              ORDER BY hora_inicio
              LIMIT 1"
        );
        $stmt->bind_param('isiss', $profesional_id, $fecha, $excluir_id, $hora_fin, $hora);
        $stmt->execute();
        $choque = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($choque) {
            $resultado['error'] = 'Ese profesional ya tiene un turno de ' . substr($choque['hora_inicio'], 0, 5)
                                . ' a ' . substr($choque['hora_fin'], 0, 5) . '. Elegí otro horario.';
            return $resultado;
        }
    }

    $resultado['ok'] = true;
    return $resultado;
}

/**
 * ¿Existe ese cliente/profesional y se puede usar en el turno?
 * Debe estar activo, salvo que sea el que el turno YA tenía (así se puede editar la hora
 * de un turno viejo aunque el cliente se haya dado de baja después).
 * $tabla y $columna son valores fijos escritos en este código, nunca datos del usuario.
 */
function existe_vigente(mysqli $conexion, string $tabla, string $columna, int $id, int $id_turno): bool
{
    $stmt = $conexion->prepare(
        "SELECT id FROM $tabla
          WHERE id = ? AND (activo = 1 OR id IN (SELECT $columna FROM turnos WHERE id = ?))"
    );
    $stmt->bind_param('ii', $id, $id_turno);
    $stmt->execute();
    $existe = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $existe;
}

/**
 * Valida todos los campos de un turno.
 * $d trae: cliente, profesional, servicio, fecha, hora, estado (todo como texto).
 * Devuelve [errores por campo, hora_fin calculada].
 */
function validar_turno(mysqli $conexion, array $d, int $id_turno): array
{
    $errores = [];

    // --- Cliente y profesional: tienen que existir y estar vigentes
    if ($d['cliente'] === '') {
        $errores['cliente'] = 'Elegí un cliente.';
    } elseif (!existe_vigente($conexion, 'clientes', 'cliente_id', (int) $d['cliente'], $id_turno)) {
        $errores['cliente'] = 'Elegí un cliente de la lista.';
    }

    if ($d['profesional'] === '') {
        $errores['profesional'] = 'Elegí un profesional.';
    } elseif (!existe_vigente($conexion, 'profesionales', 'profesional_id', (int) $d['profesional'], $id_turno)) {
        $errores['profesional'] = 'Elegí un profesional de la lista.';
    }

    if ($d['servicio'] === '') {
        $errores['servicio'] = 'Elegí un servicio.';
    }

    // --- Fecha, hora y estado
    if ($d['fecha'] === '') {
        $errores['fecha'] = 'La fecha es obligatoria.';
    } elseif (!fecha_valida($d['fecha'])) {
        $errores['fecha'] = 'Ingresá una fecha válida.';
    }

    if ($d['hora'] === '') {
        $errores['hora'] = 'La hora es obligatoria.';
    } elseif (!hora_valida($d['hora'])) {
        $errores['hora'] = 'Ingresá una hora válida.';
    }

    if ($d['estado'] === '') {
        $errores['estado'] = 'Elegí un estado.';
    } elseif (!isset(estados_turno()[$d['estado']])) {
        $errores['estado'] = 'Elegí un estado de la lista.';
    }

    // --- Horario: solo se evalúa si todo lo anterior está bien
    $hora_fin = '';
    if (!$errores) {
        $evaluacion = evaluar_horario(
            $conexion, (int) $d['profesional'], (int) $d['servicio'],
            $d['fecha'], $d['hora'], $id_turno,
            $d['estado'] !== 'cancelado'          // un turno cancelado no necesita lugar libre
        );

        if (!$evaluacion['ok']) {
            $errores[$evaluacion['campo']] = $evaluacion['error'];
        } else {
            $hora_fin = $evaluacion['hora_fin'];
        }
    }

    return [$errores, $hora_fin];
}

/** Clientes o profesionales para un <select>: los activos + el que ya estaba elegido (aunque esté inactivo). */
function opciones_personas(mysqli $conexion, string $tabla, int $incluir_id): array
{
    // $tabla viene de este código ('clientes' o 'profesionales'), nunca del usuario.
    $stmt = $conexion->prepare(
        "SELECT id, CONCAT(nombre, ' ', apellido) AS texto FROM $tabla
          WHERE activo = 1 OR id = ?
          ORDER BY apellido, nombre"
    );
    $stmt->bind_param('i', $incluir_id);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $filas;
}

/** Servicios para el <select> (con su duración). */
function opciones_servicios(mysqli $conexion): array
{
    return $conexion->query('SELECT id, nombre, duracion_min FROM servicios ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
}
