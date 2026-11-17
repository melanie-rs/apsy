<?php
/**
 * parciales/modal_eliminar.php
 * Ventana de confirmación para eliminar. Es genérica: el botón de la fila
 * le entrega el id, el título, el detalle y el texto del botón mediante
 * atributos data-* (ver assets/js/modales.js). La usan Clientes,
 * Profesionales y Turnos.
 */
?>
<dialog class="modal modal--chico" id="modal-eliminar" aria-labelledby="modal-eliminar-titulo">
    <form class="modal__cuerpo" method="post" action="<?= e(url_con([])) ?>">
        <input type="hidden" name="accion" value="eliminar">
        <input type="hidden" name="id" value="" data-rellenar="id">

        <div class="modal__encabezado">
            <div>
                <h2 id="modal-eliminar-titulo" data-rellenar="titulo">¿Eliminar?</h2>
                <p>Esta acción no se puede deshacer.</p>
            </div>
            <button class="modal__cerrar" type="button" data-cerrar-modal aria-label="Cerrar">
                <?= icono('x', 18) ?>
            </button>
        </div>

        <span class="modal__aviso"><?= icono('trash-2', 21) ?></span>

        <p class="modal__detalle" data-rellenar="detalle"></p>

        <div class="modal__acciones">
            <button class="boton boton--secundario" type="button" data-cerrar-modal>Cancelar</button>
            <button class="boton boton--primario" type="submit" data-rellenar="boton">Eliminar</button>
        </div>
    </form>
</dialog>
