<?php
/**
 * clientes.php
 * Módulo CLIENTES: listado con búsqueda y paginación + alta, edición y baja lógica.
 *
 * Orden de la página:
 *   1) Si llegó un formulario (POST): validar y guardar / eliminar.
 *   2) Consultar la base para el listado (GET).
 *   3) Mostrar el HTML.
 */
require_once 'includes/auth.php';
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

requerir_login();

const POR_PAGINA = 5;

/** Busca un cliente ACTIVO por id. Devuelve la fila o null si no existe. */
function buscar_cliente(mysqli $conexion, int $id): ?array
{
    $stmt = $conexion->prepare('SELECT id, nombre, apellido, telefono FROM clientes WHERE id = ? AND activo = 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $fila ?: null;
}

$datos        = ['id' => '', 'nombre' => '', 'apellido' => '', 'telefono' => ''];
$errores      = [];
$abrir_modal  = false;      // true → el modal se reabre solo mostrando los errores

/* ---------------------------------------------------------------------
   1) PROCESAR FORMULARIOS (POST)
   --------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = post_texto('accion');

    // ---------- Agregar o editar ----------
    if ($accion === 'guardar') {
        $datos = [
            'id'       => post_texto('id'),
            'nombre'   => post_texto('nombre'),
            'apellido' => post_texto('apellido'),
            'telefono' => post_texto('telefono'),
        ];
        $id = (int) $datos['id'];          // 0 = cliente nuevo

        $mensaje = validar_nombre_persona($datos['nombre'], 'nombre');
        if ($mensaje !== '') { $errores['nombre'] = $mensaje; }

        $mensaje = validar_nombre_persona($datos['apellido'], 'apellido');
        if ($mensaje !== '') { $errores['apellido'] = $mensaje; }

        $mensaje = validar_telefono($datos['telefono']);
        if ($mensaje !== '') { $errores['telefono'] = $mensaje; }

        // Si es una edición, el cliente tiene que existir todavía.
        if ($id > 0 && buscar_cliente($conexion, $id) === null) {
            flash('error', 'No encontramos ese cliente. Puede que ya haya sido eliminado.');
            header('Location: ' . url_con([]));
            exit;
        }

        if (!$errores) {
            $telefono = normalizar_telefono($datos['telefono']);     // se guarda solo con dígitos

            if ($id > 0) {
                $stmt = $conexion->prepare('UPDATE clientes SET nombre = ?, apellido = ?, telefono = ? WHERE id = ?');
                $stmt->bind_param('sssi', $datos['nombre'], $datos['apellido'], $telefono, $id);
                $texto = 'Los datos del cliente se actualizaron correctamente.';
            } else {
                $stmt = $conexion->prepare('INSERT INTO clientes (nombre, apellido, telefono) VALUES (?, ?, ?)');
                $stmt->bind_param('sss', $datos['nombre'], $datos['apellido'], $telefono);
                $texto = 'El cliente se agregó correctamente.';
            }
            $stmt->execute();
            $stmt->close();

            flash('ok', $texto);
            header('Location: ' . url_con([]));      // Post-Redirect-Get: evita reenviar el formulario al recargar
            exit;
        }

        $abrir_modal = true;   // hubo errores: no se redirige, se vuelve a mostrar el formulario
    }

    // ---------- Eliminar (baja lógica) ----------
    elseif ($accion === 'eliminar') {
        $id      = (int) post_texto('id');
        $cliente = buscar_cliente($conexion, $id);

        if ($cliente === null) {
            flash('error', 'No encontramos ese cliente. Puede que ya haya sido eliminado.');
        } else {
            // No se borra la fila: solo se marca como inactivo, así sus turnos quedan en el historial.
            $stmt = $conexion->prepare('UPDATE clientes SET activo = 0 WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            flash('ok', 'Eliminaste a ' . $cliente['nombre'] . ' ' . $cliente['apellido'] . '. Sus turnos se conservan en el historial.');
        }
        header('Location: ' . url_con([]));
        exit;
    }
}

/* ---------------------------------------------------------------------
   2) CONSULTAR EL LISTADO (con búsqueda y paginación)
   --------------------------------------------------------------------- */
$busqueda = mb_substr(get_texto('q'), 0, 100);

// Se escapan % y _ para que el usuario no los use como comodines del LIKE.
$texto_like = '%' . addcslashes($busqueda, '%_\\') . '%';

// Para el teléfono: si lo escrito parece un número ("11 3456", "(351) 555"),
// se buscan solo sus dígitos, así "11 3456" encuentra "1134567890".
// Si tiene letras u otros símbolos, no se trata como teléfono.
$parece_telefono = (bool) preg_match('/^[\d\s()-]+$/', $busqueda);
$tel_like = '%' . ($parece_telefono ? normalizar_telefono($busqueda) : addcslashes($busqueda, '%_\\')) . '%';

$filtro = "FROM clientes
            WHERE activo = 1
              AND (nombre LIKE ? OR apellido LIKE ?
                   OR CONCAT(nombre, ' ', apellido) LIKE ? OR telefono LIKE ?)";

// 2a) ¿Cuántos clientes cumplen el filtro?
$stmt = $conexion->prepare("SELECT COUNT(*) AS total $filtro");
$stmt->bind_param('ssss', $texto_like, $texto_like, $texto_like, $tel_like);
$stmt->execute();
$total = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// 2b) Datos de la página pedida
$pag = paginar($total, POR_PAGINA, (int) get_texto('pagina'));

$stmt = $conexion->prepare("SELECT id, nombre, apellido, telefono $filtro
                            ORDER BY apellido, nombre
                            LIMIT ? OFFSET ?");
$stmt->bind_param('ssssii', $texto_like, $texto_like, $texto_like, $tel_like, $pag['por_pagina'], $pag['offset']);
$stmt->execute();
$clientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ---------------------------------------------------------------------
   3) MOSTRAR LA PÁGINA
   --------------------------------------------------------------------- */
$titulo_pagina = 'Clientes';
$pagina_activa = 'clientes';
$scripts_extra = ['assets/js/modales.js'];
$mensaje_flash = flash_obtener();

require 'includes/header.php';
?>
<div class="encabezado-pagina">
    <div>
        <h1>Clientes</h1>
        <p>Conocé a tus clientes y mantené sus datos siempre al día.</p>
    </div>
    <button class="boton boton--primario" type="button" data-abrir-modal="modal-cliente"
            data-id="" data-nombre="" data-apellido="" data-telefono=""
            data-titulo="Agregar cliente" data-subtitulo="Completá los datos para crear su ficha.">
        + Agregar cliente
    </button>
</div>

<?php if ($mensaje_flash): ?>
    <div class="alerta alerta--<?= $mensaje_flash['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="status">
        <?= icono($mensaje_flash['tipo'] === 'ok' ? 'circle-check' : 'circle-alert', 17) ?>
        <p><?= e($mensaje_flash['texto']) ?></p>
    </div>
<?php endif; ?>

<div class="herramientas">
    <form class="buscador" method="get" action="clientes.php" role="search">
        <?= icono('search', 17) ?>
        <input type="search" name="q" value="<?= e($busqueda) ?>" placeholder="Buscar por nombre o teléfono"
               aria-label="Buscar clientes" enterkeyhint="search" maxlength="100">
    </form>
    <p class="herramientas__total">
        <?= $busqueda !== ''
            ? $total . ($total === 1 ? ' resultado' : ' resultados')
            : $total . ($total === 1 ? ' cliente en total' : ' clientes en total') ?>
    </p>
</div>

<div class="tabla-tarjeta">
    <table class="tabla">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Teléfono</th>
                <th class="tabla__acciones">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$clientes): ?>
            <tr>
                <td class="tabla__vacio" colspan="4">
                    <?= icono('panels-top-left', 16) ?>
                    <?= $busqueda !== '' ? 'No encontramos clientes con ese dato.' : 'Todavía no hay clientes cargados.' ?>
                </td>
            </tr>
        <?php endif; ?>

        <?php foreach ($clientes as $c): ?>
            <?php $nombre_completo = $c['nombre'] . ' ' . $c['apellido']; ?>
            <tr>
                <td data-label="Nombre"><?= e($c['nombre']) ?></td>
                <td data-label="Apellido"><?= e($c['apellido']) ?></td>
                <td data-label="Teléfono"><?= e(formatear_telefono($c['telefono'])) ?></td>
                <td class="tabla__acciones" data-label="Acciones">
                    <div class="acciones">
                        <button class="boton-icono boton-icono--editar" type="button"
                                aria-label="Editar a <?= e($nombre_completo) ?>"
                                data-abrir-modal="modal-cliente"
                                data-id="<?= (int) $c['id'] ?>"
                                data-nombre="<?= e($c['nombre']) ?>"
                                data-apellido="<?= e($c['apellido']) ?>"
                                data-telefono="<?= e(formatear_telefono($c['telefono'])) ?>"
                                data-titulo="Editar cliente"
                                data-subtitulo="Actualizá los datos de su ficha.">
                            <?= icono('pencil', 16) ?>
                        </button>
                        <button class="boton-icono" type="button"
                                aria-label="Eliminar a <?= e($nombre_completo) ?>"
                                data-abrir-modal="modal-eliminar"
                                data-id="<?= (int) $c['id'] ?>"
                                data-titulo="¿Eliminar a <?= e($nombre_completo) ?>?"
                                data-detalle="Vas a eliminar su ficha de clientes. Los turnos ya registrados se conservarán en el historial."
                                data-boton="Eliminar cliente">
                            <?= icono('trash-2', 16) ?>
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php
    $etiqueta  = 'clientes';
    $mostradas = count($clientes);
    require 'parciales/paginacion.php';
    ?>
</div>

<?php
require 'parciales/modal_cliente.php';
require 'parciales/modal_eliminar.php';
require 'includes/footer.php';
