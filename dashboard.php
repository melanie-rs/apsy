<?php
/**
 * dashboard.php
 * Pantalla de inicio con datos REALES de la base:
 *   - 4 indicadores del día (turnos de hoy, pendientes, confirmados, clientes),
 *   - los próximos turnos de hoy (con paginación),
 *   - el equipo que trabaja hoy y un recordatorio de turnos pendientes.
 * Reemplaza a la versión provisoria de la etapa 2.
 */
require_once 'includes/auth.php';
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';
require_once 'includes/turnos_funciones.php';

requerir_login();

const POR_PAGINA = 5;

$usuario = usuario_actual();
$hoy     = date('Y-m-d');                 // con la zona horaria de Argentina (ver conexion.php)

/* ---------------------------------------------------------------------
   1) INDICADORES DEL DÍA: turnos de hoy contados por estado
   --------------------------------------------------------------------- */
$stmt = $conexion->prepare('SELECT estado, COUNT(*) AS cantidad FROM turnos WHERE fecha = ? GROUP BY estado');
$stmt->bind_param('s', $hoy);
$stmt->execute();
$por_estado = [];                                     // ej.: ['pendiente' => 4, 'confirmado' => 6]
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $fila) {
    $por_estado[$fila['estado']] = (int) $fila['cantidad'];
}
$stmt->close();

$pendientes  = $por_estado['pendiente']  ?? 0;
$confirmados = $por_estado['confirmado'] ?? 0;
// "Turnos de hoy" no cuenta los cancelados: son los que realmente van a ocurrir.
$turnos_hoy  = array_sum($por_estado) - ($por_estado['cancelado'] ?? 0);

// Clientes activos, y cuántos se sumaron este mes
$primero_de_mes = date('Y-m-01');
$stmt = $conexion->prepare('SELECT COUNT(*) AS total, SUM(creado_en >= ?) AS nuevos FROM clientes WHERE activo = 1');
$stmt->bind_param('s', $primero_de_mes);
$stmt->execute();
$clientes       = $stmt->get_result()->fetch_assoc();
$stmt->close();
$total_clientes = (int) $clientes['total'];
$clientes_nuevos = (int) $clientes['nuevos'];          // SUM devuelve NULL si no hay filas: (int) lo vuelve 0

/* ---------------------------------------------------------------------
   2) PRÓXIMOS TURNOS DE HOY (sin cancelados), con paginación
   --------------------------------------------------------------------- */
$pag = paginar($turnos_hoy, POR_PAGINA, (int) get_texto('pagina'));

$stmt = $conexion->prepare(
    "SELECT t.hora_inicio, t.estado,
            CONCAT(c.nombre, ' ', c.apellido) AS cliente,
            CONCAT(p.nombre, ' ', p.apellido) AS profesional,
            s.nombre AS servicio
       FROM turnos t
      INNER JOIN clientes c      ON c.id = t.cliente_id
      INNER JOIN profesionales p ON p.id = t.profesional_id
      INNER JOIN servicios s     ON s.id = t.servicio_id
      WHERE t.fecha = ? AND t.estado <> 'cancelado'
      ORDER BY t.hora_inicio, p.apellido
      LIMIT ? OFFSET ?"
);
$stmt->bind_param('sii', $hoy, $pag['por_pagina'], $pag['offset']);
$stmt->execute();
$proximos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ---------------------------------------------------------------------
   3) EQUIPO DE HOY: profesionales activos con al menos un turno hoy
   --------------------------------------------------------------------- */
$stmt = $conexion->prepare(
    "SELECT p.nombre, p.apellido, p.especialidad, COUNT(t.id) AS cantidad
       FROM profesionales p
      INNER JOIN turnos t ON t.profesional_id = p.id AND t.fecha = ? AND t.estado <> 'cancelado'
      WHERE p.activo = 1
      GROUP BY p.id, p.nombre, p.apellido, p.especialidad
      ORDER BY cantidad DESC, p.apellido
      LIMIT 8"
);
$stmt->bind_param('s', $hoy);
$stmt->execute();
$equipo = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ---------------------------------------------------------------------
   4) MOSTRAR LA PÁGINA
   --------------------------------------------------------------------- */
$titulo_pagina = 'Inicio';
$pagina_activa = 'inicio';
$estados       = estados_turno();

require 'includes/header.php';
?>
<div class="encabezado-pagina">
    <div>
        <h1><?= e(saludo_segun_hora()) ?>, <?= e($usuario['nombre']) ?></h1>
        <p><?= e(fecha_larga()) ?> · Así viene tu día en el salón.</p>
    </div>
    <a class="boton boton--primario" href="turnos.php?nuevo=1">+ Nuevo turno</a>
</div>

<!-- Indicadores del día -->
<section class="indicadores" aria-label="Resumen del día">
    <article class="indicador indicador--destacado">
        <div class="indicador__etiqueta"><span>Turnos de hoy</span><?= icono('calendar-days', 19) ?></div>
        <p class="indicador__valor"><?= $turnos_hoy ?></p>
        <p class="indicador__nota">De <?= e(HORA_APERTURA) ?> a <?= e(HORA_CIERRE) ?> h</p>
    </article>

    <article class="indicador">
        <div class="indicador__etiqueta"><span>Pendientes</span><?= icono('clock-3', 19) ?></div>
        <p class="indicador__valor"><?= $pendientes ?></p>
        <p class="indicador__nota">Esperan confirmación</p>
    </article>

    <article class="indicador">
        <div class="indicador__etiqueta"><span>Confirmados</span><?= icono('circle-check', 19) ?></div>
        <p class="indicador__valor"><?= $confirmados ?></p>
        <p class="indicador__nota">Todo listo para recibirlos</p>
    </article>

    <article class="indicador">
        <div class="indicador__etiqueta"><span>Total de clientes</span><?= icono('users', 19) ?></div>
        <p class="indicador__valor"><?= $total_clientes ?></p>
        <p class="indicador__nota"><?= $clientes_nuevos ?><?= $clientes_nuevos === 1 ? ' nuevo' : ' nuevos' ?> este mes</p>
    </article>
</section>

<!-- Próximos turnos -->
<section class="seccion">
    <div class="seccion__encabezado">
        <div>
            <h2>Próximos turnos</h2>
            <p>Los primeros encuentros de tu jornada.</p>
        </div>
        <a class="enlace-flecha" href="turnos.php">Ver agenda completa <?= icono('arrow-up-right', 16) ?></a>
    </div>

    <div class="tabla-tarjeta">
        <table class="tabla">
            <thead>
                <tr>
                    <th class="col-hora">Hora</th>
                    <th>Cliente</th>
                    <th>Profesional</th>
                    <th>Servicio</th>
                    <th class="col-estado">Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$proximos): ?>
                <tr>
                    <td class="tabla__vacio" colspan="5">
                        <?= icono('panels-top-left', 16) ?> No hay turnos programados para hoy.
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($proximos as $t): ?>
                <tr>
                    <td data-label="Hora"><?= e(substr($t['hora_inicio'], 0, 5)) ?></td>
                    <td data-label="Cliente"><?= e($t['cliente']) ?></td>
                    <td data-label="Profesional"><?= e($t['profesional']) ?></td>
                    <td data-label="Servicio"><?= e($t['servicio']) ?></td>
                    <td data-label="Estado"><span class="badge badge--<?= e($t['estado']) ?>"><?= e($estados[$t['estado']] ?? $t['estado']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        $total     = $turnos_hoy;
        $etiqueta  = 'turnos de hoy';
        $mostradas = count($proximos);
        require 'parciales/paginacion.php';
        ?>
    </div>
</section>

<!-- Equipo de hoy + recordatorio -->
<section class="panorama">
    <article class="tarjeta">
        <div class="tarjeta__cabecera">
            <h2>Tu equipo hoy</h2>
            <p><?= count($equipo) ?> <?= count($equipo) === 1 ? 'profesional' : 'profesionales' ?></p>
        </div>

        <?php if (!$equipo): ?>
            <p class="texto-suave">Hoy no hay turnos asignados al equipo.</p>
        <?php endif; ?>

        <ul class="equipo">
            <?php foreach ($equipo as $p): ?>
                <li class="equipo__persona">
                    <span class="avatar avatar--chico"><?= e(iniciales($p['nombre'], $p['apellido'])) ?></span>
                    <div class="equipo__datos">
                        <p class="equipo__nombre"><?= e($p['nombre'] . ' ' . $p['apellido']) ?></p>
                        <p class="equipo__especialidad"><?= e($p['especialidad']) ?></p>
                    </div>
                    <p class="equipo__cantidad"><?= (int) $p['cantidad'] ?> <?= (int) $p['cantidad'] === 1 ? 'turno' : 'turnos' ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>

    <article class="aviso-suave aviso-suave--recordatorio">
        <?= icono('sparkles', 25) ?>
        <?php if ($pendientes > 0): ?>
            <h2>Un día organizado empieza con una agenda al día.</h2>
            <p>
                <?= $pendientes === 1
                    ? 'Tenés 1 turno pendiente. Confirmalo para que tu cliente tenga su lugar.'
                    : 'Tenés ' . $pendientes . ' turnos pendientes. Confirmalos para que cada cliente tenga su lugar.' ?>
            </p>
            <a class="aviso-suave__enlace" href="turnos.php?<?= e(http_build_query(['fecha' => $hoy, 'estado' => 'pendiente'])) ?>">
                Revisar pendientes <?= icono('arrow-right', 16) ?>
            </a>
        <?php else: ?>
            <h2>Tu agenda de hoy está al día.</h2>
            <p>No tenés turnos pendientes de confirmación. ¡Buen trabajo!</p>
            <a class="aviso-suave__enlace" href="turnos.php">
                Ver agenda completa <?= icono('arrow-right', 16) ?>
            </a>
        <?php endif; ?>
    </article>
</section>
<?php require 'includes/footer.php'; ?>
