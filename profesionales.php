<?php
/**
 * profesionales.php
 * Módulo PROFESIONALES: listado con búsqueda y paginación + alta, edición y baja lógica.
 * Sigue la misma estructura que clientes.php:
 *   1) procesar formularios (POST)  2) consultar el listado  3) mostrar la página.
 */
require_once 'includes/auth.php';
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

requerir_login();

const POR_PAGINA = 5;

/** Busca un profesional ACTIVO por id. Devuelve la fila o null si no existe. */
function buscar_profesional(mysqli $conexion, int $id): ?array
{
    $stmt = $conexion->prepare('SELECT id, nombre, apellido, especialidad FROM profesionales WHERE id = ? AND activo = 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $fila ?: null;
}

$datos       = ['id' => '', 'nombre' => '', 'apellido' => '', 'especialidad' => ''];
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
            'id'           => post_texto('id'),
            'nombre'       => post_texto('nombre'),
            'apellido'     => post_texto('apellido'),
            'especialidad' => post_texto('especialidad'),
        ];
        $id = (int) $datos['id'];          // 0 = profesional nuevo

        $mensaje = validar_nombre_persona($datos['nombre'], 'nombre');
        if ($mensaje !== '') { $errores['nombre'] = $mensaje; }

        $mensaje = validar_nombre_persona($datos['apellido'], 'apellido');
        if ($mensaje !== '') { $errores['apellido'] = $mensaje; }

        $mensaje = validar_especialidad($datos['especialidad']);
        if ($mensaje !== '') { $errores['especialidad'] = $mensaje; }

        if ($id > 0 && buscar_profesional($conexion, $id) === null) {
            flash('error', 'No encontramos a ese profesional. Puede que ya haya sido eliminado.');
            header('Location: ' . url_con([]));
            exit;
        }

        if (!$errores) {
            if ($id > 0) {
                $stmt = $conexion->prepare('UPDATE profesionales SET nombre = ?, apellido = ?, especialidad = ? WHERE id = ?');
                $stmt->bind_param('sssi', $datos['nombre'], $datos['apellido'], $datos['especialidad'], $id);
                $texto = 'Los datos del profesional se actualizaron correctamente.';
            } else {
                $stmt = $conexion->prepare('INSERT INTO profesionales (nombre, apellido, especialidad) VALUES (?, ?, ?)');
                $stmt->bind_param('sss', $datos['nombre'], $datos['apellido'], $datos['especialidad']);
                $texto = 'El profesional se agregó correctamente.';
            }
            $stmt->execute();
            $stmt->close();

            flash('ok', $texto);
            header('Location: ' . url_con([]));      // Post-Redirect-Get
            exit;
        }

        $abrir_modal = true;   // hubo errores: se vuelve a mostrar el formulario
    }

    // ---------- Eliminar (baja lógica) ----------
    elseif ($accion === 'eliminar') {
        $id          = (int) post_texto('id');
        $profesional = buscar_profesional($conexion, $id);

        if ($profesional === null) {
            flash('error', 'No encontramos a ese profesional. Puede que ya haya sido eliminado.');
        } else {
            // Solo se marca como inactivo: los turnos históricos siguen apuntando a su fila.
            $stmt = $conexion->prepare('UPDATE profesionales SET activo = 0 WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            flash('ok', 'Eliminaste a ' . $profesional['nombre'] . ' ' . $profesional['apellido'] . '. Sus turnos se conservan en el historial.');
        }
        header('Location: ' . url_con([]));
        exit;
    }
}

/* ---------------------------------------------------------------------
   2) CONSULTAR EL LISTADO (con búsqueda y paginación)
   --------------------------------------------------------------------- */
$busqueda   = mb_substr(get_texto('q'), 0, 100);
$texto_like = '%' . addcslashes($busqueda, '%_\\') . '%';

$filtro = "FROM profesionales
            WHERE activo = 1
              AND (nombre LIKE ? OR apellido LIKE ?
                   OR CONCAT(nombre, ' ', apellido) LIKE ? OR especialidad LIKE ?)";

$stmt = $conexion->prepare("SELECT COUNT(*) AS total $filtro");
$stmt->bind_param('ssss', $texto_like, $texto_like, $texto_like, $texto_like);
$stmt->execute();
$total = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$pag = paginar($total, POR_PAGINA, (int) get_texto('pagina'));

$stmt = $conexion->prepare("SELECT id, nombre, apellido, especialidad $filtro
                            ORDER BY apellido, nombre
                            LIMIT ? OFFSET ?");
$stmt->bind_param('ssssii', $texto_like, $texto_like, $texto_like, $texto_like, $pag['por_pagina'], $pag['offset']);
$stmt->execute();
$profesionales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ---------------------------------------------------------------------
   3) MOSTRAR LA PÁGINA
   --------------------------------------------------------------------- */
$titulo_pagina = 'Profesionales';
$pagina_activa = 'profesionales';
$scripts_extra = ['assets/js/modales.js'];
$mensaje_flash = flash_obtener();

require 'includes/header.php';
?>
<div class="encabezado-pagina">
    <div>
        <h1>Profesionales</h1>
        <p>Un gran equipo, una experiencia única para cada cliente.</p>
    </div>
    <button class="boton boton--primario" type="button" data-abrir-modal="modal-profesional"
            data-id="" data-nombre="" data-apellido="" data-especialidad=""
            data-titulo="Agregar profesional" data-subtitulo="Sumá una persona a tu equipo de trabajo.">
        + Agregar profesional
    </button>
</div>

<?php if ($mensaje_flash): ?>
    <div class="alerta alerta--<?= $mensaje_flash['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="status">
        <?= icono($mensaje_flash['tipo'] === 'ok' ? 'circle-check' : 'circle-alert', 17) ?>
        <p><?= e($mensaje_flash['texto']) ?></p>
    </div>
<?php endif; ?>

<div class="herramientas">
    <form class="buscador" method="get" action="profesionales.php" role="search">
        <?= icono('search', 17) ?>
        <input type="search" name="q" value="<?= e($busqueda) ?>" placeholder="Buscar por nombre o especialidad"
               aria-label="Buscar profesionales" enterkeyhint="search" maxlength="100">
    </form>
    <p class="herramientas__total">
        <?= $busqueda !== ''
            ? $total . ($total === 1 ? ' resultado' : ' resultados')
            : $total . ($total === 1 ? ' profesional' : ' profesionales') ?>
    </p>
</div>

<div class="tabla-tarjeta">
    <table class="tabla">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Especialidad</th>
                <th class="tabla__acciones">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$profesionales): ?>
            <tr>
                <td class="tabla__vacio" colspan="4">
                    <?= icono('panels-top-left', 16) ?>
                    <?= $busqueda !== '' ? 'No encontramos profesionales con ese dato.' : 'Todavía no hay profesionales cargados.' ?>
                </td>
            </tr>
        <?php endif; ?>

        <?php foreach ($profesionales as $p): ?>
            <?php $nombre_completo = $p['nombre'] . ' ' . $p['apellido']; ?>
            <tr>
                <td data-label="Nombre"><?= e($p['nombre']) ?></td>
                <td data-label="Apellido"><?= e($p['apellido']) ?></td>
                <td data-label="Especialidad"><?= e($p['especialidad']) ?></td>
                <td class="tabla__acciones" data-label="Acciones">
                    <div class="acciones">
                        <button class="boton-icono boton-icono--editar" type="button"
                                aria-label="Editar a <?= e($nombre_completo) ?>"
                                data-abrir-modal="modal-profesional"
                                data-id="<?= (int) $p['id'] ?>"
                                data-nombre="<?= e($p['nombre']) ?>"
                                data-apellido="<?= e($p['apellido']) ?>"
                                data-especialidad="<?= e($p['especialidad']) ?>"
                                data-titulo="Editar profesional"
                                data-subtitulo="Actualizá los datos de esta persona del equipo.">
                            <?= icono('pencil', 16) ?>
                        </button>
                        <button class="boton-icono" type="button"
                                aria-label="Eliminar a <?= e($nombre_completo) ?>"
                                data-abrir-modal="modal-eliminar"
                                data-id="<?= (int) $p['id'] ?>"
                                data-titulo="¿Eliminar a <?= e($nombre_completo) ?>?"
                                data-detalle="Vas a quitar a esta persona del equipo. Los turnos ya registrados se conservarán en el historial."
                                data-boton="Eliminar profesional">
                            <?= icono('trash-2', 16) ?>
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php
    $etiqueta  = 'profesionales';
    $mostradas = count($profesionales);
    require 'parciales/paginacion.php';
    ?>
</div>

<section class="aviso-suave">
    <?= icono('users-round', 25) ?>
    <h2>Cada talento, en su lugar.</h2>
    <p>La especialidad te ayuda a asignar el profesional indicado en cada turno y organizar mejor la agenda del salón.</p>
    <p class="aviso-suave__consejo"><?= icono('circle-check', 15) ?> Los cambios se reflejan en la agenda.</p>
</section>

<?php
require 'parciales/modal_profesional.php';
require 'parciales/modal_eliminar.php';
require 'includes/footer.php';
