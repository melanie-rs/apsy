<?php
/**
 * parciales/modal_turno.php
 * Ventana para CREAR y EDITAR un turno.
 * Variables que espera: $datos, $errores, $abrir_modal, $clientes_op,
 * $profesionales_op, $servicios_op y $estados.
 * El aviso de disponibilidad del pie lo actualiza assets/js/turnos.js.
 */
$editando = ($datos['id'] !== '');
?>
<dialog class="modal modal--ancho" id="modal-turno" aria-labelledby="modal-turno-titulo"
        <?= $abrir_modal ? 'data-abrir-al-cargar' : '' ?>>
    <form class="modal__cuerpo" method="post" action="<?= e(url_con([])) ?>" novalidate>
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= e($datos['id']) ?>" data-rellenar="id">

        <div class="modal__encabezado">
            <div>
                <h2 id="modal-turno-titulo" data-rellenar="titulo"><?= $editando ? 'Editar turno' : 'Nuevo turno' ?></h2>
                <p data-rellenar="subtitulo"><?= $editando
                    ? 'Actualizá los datos de la reserva y guardá los cambios.'
                    : 'Elegí los datos de la reserva y guardá el turno en tu agenda.' ?></p>
            </div>
            <button class="modal__cerrar" type="button" data-cerrar-modal aria-label="Cerrar">
                <?= icono('x', 18) ?>
            </button>
        </div>

        <div class="modal__fila">
            <div class="campo<?= isset($errores['cliente']) ? ' campo--error' : '' ?>">
                <label class="campo__etiqueta" for="turno-cliente">Cliente *</label>
                <select class="campo__control" id="turno-cliente" name="cliente" data-rellenar="cliente" autofocus>
                    <option value="">Seleccioná un cliente</option>
                    <?php foreach ($clientes_op as $op): ?>
                        <option value="<?= (int) $op['id'] ?>"<?= $datos['cliente'] === (string) $op['id'] ? ' selected' : '' ?>><?= e($op['texto']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errores['cliente'])): ?><p class="campo__error"><?= e($errores['cliente']) ?></p><?php endif; ?>
            </div>

            <div class="campo<?= isset($errores['profesional']) ? ' campo--error' : '' ?>">
                <label class="campo__etiqueta" for="turno-profesional">Profesional *</label>
                <select class="campo__control" id="turno-profesional" name="profesional" data-rellenar="profesional">
                    <option value="">Seleccioná un profesional</option>
                    <?php foreach ($profesionales_op as $op): ?>
                        <option value="<?= (int) $op['id'] ?>"<?= $datos['profesional'] === (string) $op['id'] ? ' selected' : '' ?>><?= e($op['texto']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errores['profesional'])): ?><p class="campo__error"><?= e($errores['profesional']) ?></p><?php endif; ?>
            </div>

            <div class="campo<?= isset($errores['servicio']) ? ' campo--error' : '' ?>">
                <label class="campo__etiqueta" for="turno-servicio">Servicio *</label>
                <select class="campo__control" id="turno-servicio" name="servicio" data-rellenar="servicio">
                    <option value="">Seleccioná un servicio</option>
                    <?php foreach ($servicios_op as $op): ?>
                        <option value="<?= (int) $op['id'] ?>" data-duracion="<?= (int) $op['duracion_min'] ?>"<?= $datos['servicio'] === (string) $op['id'] ? ' selected' : '' ?>><?= e($op['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errores['servicio'])): ?><p class="campo__error"><?= e($errores['servicio']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="modal__fila">
            <div class="campo<?= isset($errores['fecha']) ? ' campo--error' : '' ?>">
                <label class="campo__etiqueta" for="turno-fecha">Fecha *</label>
                <input class="campo__control" type="date" id="turno-fecha" name="fecha"
                       value="<?= e($datos['fecha']) ?>" data-rellenar="fecha">
                <?php if (isset($errores['fecha'])): ?><p class="campo__error"><?= e($errores['fecha']) ?></p><?php endif; ?>
            </div>

            <div class="campo<?= isset($errores['hora']) ? ' campo--error' : '' ?>">
                <label class="campo__etiqueta" for="turno-hora">Hora *</label>
                <input class="campo__control" type="time" id="turno-hora" name="hora"
                       value="<?= e($datos['hora']) ?>" data-rellenar="hora">
                <?php if (isset($errores['hora'])): ?><p class="campo__error"><?= e($errores['hora']) ?></p><?php endif; ?>
            </div>

            <div class="campo<?= isset($errores['estado']) ? ' campo--error' : '' ?>">
                <label class="campo__etiqueta" for="turno-estado">Estado *</label>
                <select class="campo__control" id="turno-estado" name="estado" data-rellenar="estado">
                    <?php foreach ($estados as $clave => $etiqueta): ?>
                        <option value="<?= e($clave) ?>"<?= $datos['estado'] === $clave ? ' selected' : '' ?>><?= e($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errores['estado'])): ?><p class="campo__error"><?= e($errores['estado']) ?></p><?php endif; ?>
            </div>
        </div>

        <p class="modal__nota">* Campos obligatorios</p>

        <div class="modal__pie">
            <p class="disponibilidad" data-disponibilidad role="status">
                <?= icono('circle-check', 16) ?>
                <span data-disponibilidad-texto>Completá profesional, servicio, fecha y hora para ver la disponibilidad.</span>
            </p>
            <div class="modal__acciones">
                <button class="boton boton--secundario" type="button" data-cerrar-modal>Cancelar</button>
                <button class="boton boton--primario" type="submit" data-rellenar="boton"><?= $editando ? 'Guardar cambios' : 'Guardar turno' ?></button>
            </div>
        </div>
    </form>
</dialog>
