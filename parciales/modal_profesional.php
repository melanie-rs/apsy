<?php
/**
 * parciales/modal_profesional.php
 * Ventana para AGREGAR y EDITAR un profesional (misma lógica que modal_cliente.php).
 * Variables que espera: $datos (id, nombre, apellido, especialidad), $errores, $abrir_modal.
 */
$editando = ($datos['id'] !== '');
?>
<dialog class="modal" id="modal-profesional" aria-labelledby="modal-profesional-titulo"
        <?= $abrir_modal ? 'data-abrir-al-cargar' : '' ?>>
    <form class="modal__cuerpo" method="post" action="<?= e(url_con([])) ?>" novalidate>
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= e($datos['id']) ?>" data-rellenar="id">

        <div class="modal__encabezado">
            <div>
                <h2 id="modal-profesional-titulo" data-rellenar="titulo"><?= $editando ? 'Editar profesional' : 'Agregar profesional' ?></h2>
                <p data-rellenar="subtitulo"><?= $editando ? 'Actualizá los datos de esta persona del equipo.' : 'Sumá una persona a tu equipo de trabajo.' ?></p>
            </div>
            <button class="modal__cerrar" type="button" data-cerrar-modal aria-label="Cerrar">
                <?= icono('x', 18) ?>
            </button>
        </div>

        <div class="modal__fila">
            <div class="campo<?= isset($errores['nombre']) ? ' campo--error' : '' ?>">
                <label class="campo__etiqueta" for="profesional-nombre">Nombre *</label>
                <input class="campo__control" type="text" id="profesional-nombre" name="nombre" maxlength="60"
                       placeholder="Ingresá el nombre" value="<?= e($datos['nombre']) ?>"
                       data-rellenar="nombre" autocomplete="off" autofocus>
                <?php if (isset($errores['nombre'])): ?><p class="campo__error"><?= e($errores['nombre']) ?></p><?php endif; ?>
            </div>

            <div class="campo<?= isset($errores['apellido']) ? ' campo--error' : '' ?>">
                <label class="campo__etiqueta" for="profesional-apellido">Apellido *</label>
                <input class="campo__control" type="text" id="profesional-apellido" name="apellido" maxlength="60"
                       placeholder="Ingresá el apellido" value="<?= e($datos['apellido']) ?>"
                       data-rellenar="apellido" autocomplete="off">
                <?php if (isset($errores['apellido'])): ?><p class="campo__error"><?= e($errores['apellido']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="campo<?= isset($errores['especialidad']) ? ' campo--error' : '' ?>">
            <label class="campo__etiqueta" for="profesional-especialidad">Especialidad *</label>
            <select class="campo__control" id="profesional-especialidad" name="especialidad" data-rellenar="especialidad">
                <option value="">Seleccioná una especialidad</option>
                <?php foreach (especialidades() as $opcion): ?>
                    <option value="<?= e($opcion) ?>"<?= $datos['especialidad'] === $opcion ? ' selected' : '' ?>><?= e($opcion) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errores['especialidad'])): ?><p class="campo__error"><?= e($errores['especialidad']) ?></p><?php endif; ?>
        </div>

        <p class="modal__nota">* Campos obligatorios</p>

        <div class="modal__acciones">
            <button class="boton boton--secundario" type="button" data-cerrar-modal>Cancelar</button>
            <button class="boton boton--primario" type="submit">Guardar</button>
        </div>
    </form>
</dialog>
