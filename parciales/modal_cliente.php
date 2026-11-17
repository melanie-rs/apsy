<?php
/**
 * parciales/modal_cliente.php
 * Ventana (modal) para AGREGAR y EDITAR un cliente: es la misma, y cambia
 * el título y los datos según qué botón la abra (ver assets/js/modales.js).
 *
 * Variables que espera: $datos (id, nombre, apellido, telefono), $errores, $abrir_modal.
 * Si el servidor encontró errores, la ventana se vuelve a abrir sola con
 * lo que escribió el usuario y los mensajes debajo de cada campo.
 */
$editando = ($datos['id'] !== '');
?>
<dialog class="modal" id="modal-cliente" aria-labelledby="modal-cliente-titulo"
        <?= $abrir_modal ? 'data-abrir-al-cargar' : '' ?>>
    <form class="modal__cuerpo" method="post" action="<?= e(url_con([])) ?>" novalidate>
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= e($datos['id']) ?>" data-rellenar="id">

        <div class="modal__encabezado">
            <div>
                <h2 id="modal-cliente-titulo" data-rellenar="titulo"><?= $editando ? 'Editar cliente' : 'Agregar cliente' ?></h2>
                <p data-rellenar="subtitulo"><?= $editando ? 'Actualizá los datos de su ficha.' : 'Completá los datos para crear su ficha.' ?></p>
            </div>
            <button class="modal__cerrar" type="button" data-cerrar-modal aria-label="Cerrar">
                <?= icono('x', 18) ?>
            </button>
        </div>

        <div class="modal__fila">
            <div class="campo<?= isset($errores['nombre']) ? ' campo--error' : '' ?>">
                <label class="campo__etiqueta" for="cliente-nombre">Nombre *</label>
                <input class="campo__control" type="text" id="cliente-nombre" name="nombre" maxlength="60"
                       placeholder="Ingresá el nombre" value="<?= e($datos['nombre']) ?>"
                       data-rellenar="nombre" autocomplete="off" autofocus>
                <?php if (isset($errores['nombre'])): ?><p class="campo__error"><?= e($errores['nombre']) ?></p><?php endif; ?>
            </div>

            <div class="campo<?= isset($errores['apellido']) ? ' campo--error' : '' ?>">
                <label class="campo__etiqueta" for="cliente-apellido">Apellido *</label>
                <input class="campo__control" type="text" id="cliente-apellido" name="apellido" maxlength="60"
                       placeholder="Ingresá el apellido" value="<?= e($datos['apellido']) ?>"
                       data-rellenar="apellido" autocomplete="off">
                <?php if (isset($errores['apellido'])): ?><p class="campo__error"><?= e($errores['apellido']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="campo<?= isset($errores['telefono']) ? ' campo--error' : '' ?>">
            <label class="campo__etiqueta" for="cliente-telefono">Teléfono *</label>
            <input class="campo__control" type="tel" id="cliente-telefono" name="telefono" maxlength="20"
                   placeholder="Ingresá el teléfono" value="<?= e($datos['telefono']) ?>"
                   data-rellenar="telefono" inputmode="tel" autocomplete="off">
            <?php if (isset($errores['telefono'])): ?><p class="campo__error"><?= e($errores['telefono']) ?></p><?php endif; ?>
        </div>

        <p class="modal__nota">* Campos obligatorios</p>

        <div class="modal__acciones">
            <button class="boton boton--secundario" type="button" data-cerrar-modal>Cancelar</button>
            <button class="boton boton--primario" type="submit">Guardar</button>
        </div>
    </form>
</dialog>
