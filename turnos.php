<?php
/**
 * turnos.php
 * Módulo TURNOS (Agenda): listado del día con filtros por fecha, profesional y estado,
 * más alta, edición y eliminación de turnos.
 *   1) procesar formularios (POST)  2) consultar la agenda (GET)  3) mostrar la página.
 */
require_once 'includes/auth.php';
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';
require_once 'includes/turnos_funciones.php';

requerir_login();

const POR_PAGINA = 5;

/** Busca un turno por id. Devuelve la fila o null. */
function buscar_turno(mysqli $conexion, int $id): ?array
{
    $stmt = $conexion->prepare('SELECT id FROM turnos WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $fila ?: null;
}

$datos       = ['id' => '', 'cliente' => '', 'profesional' => '', 'servicio' => '',
                'fecha' => '', 'hora' => '', 'estado' => 'pendiente'];
$errores     = [];
$abrir_modal = false;

/* ---------------------------------------------------------------------
   1) PROCESAR FORMULARIOS (POST)
   --------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = post_texto('accion');

    // ---------- Agregar o editar ----------
    if ($accion === 'guardar') {
        $datos = [
            'id'          => post_texto('id'),
            'cliente'     => post_texto('cliente'),
            'profesional' => post_texto('profesional'),
            'servicio'    => post_texto('servicio'),
            'fecha'       => post_texto('fecha'),
            'hora'        => post_texto('hora'),
            'estado'      => post_texto('estado'),
        ];
        $id = (int) $datos['id'];          // 0 = turno nuevo

        if ($id > 0 && buscar_turno($conexion, $id) === null) {
            flash('error', 'No encontramos ese turno. Puede que ya haya sido eliminado.');
            header('Location: ' . url_con([]));
            exit;
        }

        // Toda la validación (campos + horario + superposición) vive en validar_turno()
        [$errores, $hora_fin] = validar_turno($conexion, $datos, $id);

        if (!$errores) {
            $cliente_id = (int) $datos['cliente'];
            $prof_id    = (int) $datos['profesional'];
            $serv_id    = (int) $datos['servicio'];

            if ($id > 0) {
                $stmt = $conexion->prepare(
                    'UPDATE turnos SET cliente_id = ?, profesional_id = ?, servicio_id = ?,
                                       fecha = ?, hora_inicio = ?, hora_fin = ?, estado = ?
                      WHERE id = ?'
                );
                $stmt->bind_param('iiissssi', $cliente_id, $prof_id, $serv_id,
                                  $datos['fecha'], $datos['hora'], $hora_fin, $datos['estado'], $id);
                $texto = 'El turno se actualizó correctamente.';
            } else {
                $stmt = $conexion->prepare(
                    'INSERT INTO turnos (cliente_id, profesional_id, servicio_id, fecha, hora_inicio, hora_fin, estado)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param('iiissss', $cliente_id, $prof_id, $serv_id,
                                  $datos['fecha'], $datos['hora'], $hora_fin, $datos['estado']);
                $texto = 'El turno se agregó correctamente a la agenda.';
            }
            $stmt->execute();
            $stmt->close();

            flash('ok', $texto);
            // Se vuelve a la agenda del día del turno guardado, sin filtros que lo oculten.
            header('Location: ' . url_con(['fecha' => $datos['fecha'], 'profesional' => '', 'estado' => '', 'pagina' => '']));
            exit;
        }

        $abrir_modal = true;   // hubo errores: se vuelve a mostrar el formulario
    }

    // ---------- Eliminar ----------
    elseif ($accion === 'eliminar') {
        $id = (int) post_texto('id');

        if (buscar_turno($conexion, $id) === null) {
            flash('error', 'No encontramos ese turno. Puede que ya haya sido eliminado.');
        } else {
            // A diferencia de clientes y profesionales, el turno se borra de verdad:
            // nadie depende de él. (Para conservarlo se lo puede pasar a "Cancelado".)
            $stmt = $conexion->prepare('DELETE FROM turnos WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            flash('ok', 'El turno se eliminó de la agenda.');
        }
        header('Location: ' . url_con([]));
        exit;
    }
}

/* ---------------------------------------------------------------------
   2) CONSULTAR LA AGENDA (filtros + paginación)
   --------------------------------------------------------------------- */
// Fecha: si falta o no es válida, se usa hoy.
$fecha = get_texto('fecha');
if (!fecha_valida($fecha)) {
    $fecha = date('Y-m-d');
}

$filtro_profesional = (int) get_texto('profesional');          // 0 = todos
$filtro_estado      = get_texto('estado');
if (!isset(estados_turno()[$filtro_estado])) {
    $filtro_estado = '';                                         // '' = todos
}

// Se arma el WHERE sumando una condición por cada filtro que esté activo.
// Los valores NUNCA se pegan en el SQL: van como "?" y se enlazan con bind_param.
$condiciones = ['t.fecha = ?'];
$tipos       = 's';
$valores     = [$fecha];

if ($filtro_profesional > 0) {
    $condiciones[] = 't.profesional_id = ?';
    $tipos        .= 'i';
    $valores[]     = $filtro_profesional;
}
if ($filtro_estado !== '') {
    $condiciones[] = 't.estado = ?';
    $tipos        .= 's';
    $valores[]     = $filtro_estado;
}

$desde = 'FROM turnos t
          INNER JOIN clientes c      ON c.id = t.cliente_id
          INNER JOIN profesionales p ON p.id = t.profesional_id
          INNER JOIN servicios s     ON s.id = t.servicio_id
          WHERE ' . implode(' AND ', $condiciones);

// 2a) Cantidad de turnos que cumplen los filtros
$stmt = $conexion->prepare("SELECT COUNT(*) AS total $desde");
$stmt->bind_param($tipos, ...$valores);
$stmt->execute();
$total = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// 2b) Turnos de la página pedida
$pag = paginar($total, POR_PAGINA, (int) get_texto('pagina'));

$stmt = $conexion->prepare(
    "SELECT t.id, t.cliente_id, t.profesional_id, t.servicio_id, t.fecha, t.hora_inicio, t.estado,
            c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
            p.nombre AS prof_nombre,    p.apellido AS prof_apellido,
            s.nombre AS servicio
     $desde
     ORDER BY t.hora_inicio, p.apellido
     LIMIT ? OFFSET ?"
);
$stmt->bind_param($tipos . 'ii', ...[...$valores, $pag['por_pagina'], $pag['offset']]);
$stmt->execute();
$turnos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Si se llega desde el dashboard con ?nuevo=1, se abre el modal "Nuevo turno" con la fecha del día mostrado.
if (!$abrir_modal && get_texto('nuevo') === '1') {
    $abrir_modal    = true;
    $datos['fecha'] = $fecha;
}

// Listas para los desplegables (filtro y formulario)
$profesionales_op = opciones_personas($conexion, 'profesionales', max($filtro_profesional, (int) $datos['profesional']));
$clientes_op      = opciones_personas($conexion, 'clientes', (int) $datos['cliente']);
$servicios_op     = opciones_servicios($conexion);
$estados          = estados_turno();

/* ---------------------------------------------------------------------
   3) MOSTRAR LA PÁGINA
   --------------------------------------------------------------------- */
$titulo_pagina = 'Agenda de turnos';
$pagina_activa = 'turnos';
$scripts_extra = ['assets/js/modales.js', 'assets/js/turnos.js'];
$mensaje_flash = flash_obtener();

require 'includes/header.php';
?>
<div class="encabezado-pagina">
    <div>
        <h1>Agenda de turnos</h1>
        <p>Organizá cada encuentro y acompañá el ritmo de tu salón.</p>
    </div>
    <button class="boton boton--primario" type="button" data-abrir-modal="modal-turno"
            data-id="" data-cliente="" data-profesional="" data-servicio=""
            data-fecha="<?= e($fecha) ?>" data-hora="" data-estado="pendiente"
            data-titulo="Nuevo turno" data-subtitulo="Elegí los datos de la reserva y guardá el turno en tu agenda."
            data-boton="Guardar turno">
        + Nuevo turno
    </button>
</div>

<?php if ($mensaje_flash): ?>
    <div class="alerta alerta--<?= $mensaje_flash['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="status">
        <?= icono($mensaje_flash['tipo'] === 'ok' ? 'circle-check' : 'circle-alert', 17) ?>
        <p><?= e($mensaje_flash['texto']) ?></p>
    </div>
<?php endif; ?>

<!-- Filtros: al cambiar cualquiera, JavaScript envía el formulario (sin JS queda el botón Filtrar) -->
<form class="filtros" method="get" action="turnos.php" data-autoenvio>
    <div class="campo">
        <label class="campo__etiqueta" for="filtro-fecha">Fecha</label>
        <input class="campo__control" type="date" id="filtro-fecha" name="fecha" value="<?= e($fecha) ?>">
    </div>

    <div class="campo">
        <label class="campo__etiqueta" for="filtro-profesional">Profesional</label>
        <select class="campo__control" id="filtro-profesional" name="profesional">
            <option value="">Todos los profesionales</option>
            <?php foreach ($profesionales_op as $op): ?>
                <option value="<?= (int) $op['id'] ?>"<?= $filtro_profesional === (int) $op['id'] ? ' selected' : '' ?>><?= e($op['texto']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label class="campo__etiqueta" for="filtro-estado">Estado</label>
        <select class="campo__control" id="filtro-estado" name="estado">
            <option value="">Todos los estados</option>
            <?php foreach ($estados as $clave => $etiqueta): ?>
                <option value="<?= e($clave) ?>"<?= $filtro_estado === $clave ? ' selected' : '' ?>><?= e($etiqueta) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <a class="boton boton--secundario" href="<?= e(url_con(['fecha' => date('Y-m-d'), 'pagina' => ''])) ?>">Hoy</a>
    <noscript><button class="boton boton--secundario" type="submit">Filtrar</button></noscript>
</form>

<div class="resumen-fecha">
    <h2><?= e(fecha_larga(new DateTime($fecha))) ?></h2>
    <p><?= $total === 0 ? 'Sin turnos programados' : $total . ($total === 1 ? ' turno programado' : ' turnos programados') ?></p>
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
                <th class="tabla__acciones">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$turnos): ?>
            <tr>
                <td class="tabla__vacio" colspan="6">
                    <?= icono('panels-top-left', 16) ?>
                    <?= ($filtro_profesional > 0 || $filtro_estado !== '')
                        ? 'No hay turnos que coincidan con los filtros.'
                        : 'No hay turnos para este día.' ?>
                </td>
            </tr>
        <?php endif; ?>

        <?php foreach ($turnos as $t): ?>
            <?php
            $cliente  = $t['cliente_nombre'] . ' ' . $t['cliente_apellido'];
            $prof     = $t['prof_nombre'] . ' ' . $t['prof_apellido'];
            $hora     = substr($t['hora_inicio'], 0, 5);
            $fecha_es = (new DateTime($t['fecha']))->format('d/m/Y');
            ?>
            <tr>
                <td data-label="Hora"><?= e($hora) ?></td>
                <td data-label="Cliente"><?= e($cliente) ?></td>
                <td data-label="Profesional"><?= e($prof) ?></td>
                <td data-label="Servicio"><?= e($t['servicio']) ?></td>
                <td data-label="Estado"><span class="badge badge--<?= e($t['estado']) ?>"><?= e($estados[$t['estado']] ?? $t['estado']) ?></span></td>
                <td class="tabla__acciones" data-label="Acciones">
                    <div class="acciones">
                        <button class="boton-icono boton-icono--editar" type="button"
                                aria-label="Editar el turno de <?= e($cliente) ?> a las <?= e($hora) ?>"
                                data-abrir-modal="modal-turno"
                                data-id="<?= (int) $t['id'] ?>"
                                data-cliente="<?= (int) $t['cliente_id'] ?>"
                                data-profesional="<?= (int) $t['profesional_id'] ?>"
                                data-servicio="<?= (int) $t['servicio_id'] ?>"
                                data-fecha="<?= e($t['fecha']) ?>"
                                data-hora="<?= e($hora) ?>"
                                data-estado="<?= e($t['estado']) ?>"
                                data-titulo="Editar turno"
                                data-subtitulo="Actualizá los datos de la reserva y guardá los cambios."
                                data-boton="Guardar cambios">
                            <?= icono('pencil', 16) ?>
                        </button>
                        <button class="boton-icono" type="button"
                                aria-label="Eliminar el turno de <?= e($cliente) ?> a las <?= e($hora) ?>"
                                data-abrir-modal="modal-eliminar"
                                data-id="<?= (int) $t['id'] ?>"
                                data-titulo="¿Eliminar turno?"
                                data-detalle="Vas a quitar de la agenda el turno de <?= e($cliente) ?> con <?= e($prof) ?>, el <?= e($fecha_es) ?> a las <?= e($hora) ?>."
                                data-boton="Eliminar turno">
                            <?= icono('trash-2', 16) ?>
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php
    $etiqueta  = 'turnos';
    $mostradas = count($turnos);
    require 'parciales/paginacion.php';
    ?>
</div>

<?php
require 'parciales/modal_turno.php';
require 'parciales/modal_eliminar.php';
require 'includes/footer.php';
