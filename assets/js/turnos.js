/**
 * assets/js/turnos.js
 * Comportamiento de la pantalla de turnos:
 *   1) Los filtros se aplican solos al cambiar cualquiera de ellos.
 *   2) En el formulario de turno, muestra en vivo si el horario está disponible
 *      consultando a disponibilidad.php (que usa la misma regla que el servidor).
 * Es solo un aviso: al guardar, el servidor vuelve a validar todo.
 */
(function () {
    'use strict';

    /* ---- 1) Filtros de la agenda ------------------------------------------ */
    var filtros = document.querySelector('[data-autoenvio]');
    if (filtros) {
        filtros.addEventListener('change', function () { filtros.submit(); });
    }

    /* ---- 2) Disponibilidad dentro del modal ------------------------------ */
    var modal = document.getElementById('modal-turno');
    if (!modal) { return; }

    var aviso = modal.querySelector('[data-disponibilidad]');
    var texto = modal.querySelector('[data-disponibilidad-texto]');
    var campo = function (nombre) { return modal.querySelector('[name="' + nombre + '"]'); };
    var ultimoPedido = 0;     // para ignorar respuestas viejas si el usuario cambia rápido

    function mostrar(tipo, mensaje) {
        aviso.classList.remove('disponibilidad--ok', 'disponibilidad--error');
        if (tipo === 'ok') { aviso.classList.add('disponibilidad--ok'); }
        if (tipo === 'error') { aviso.classList.add('disponibilidad--error'); }
        texto.textContent = mensaje;
    }

    function actualizar() {
        var selServicio = campo('servicio');
        var opcion = selServicio.options[selServicio.selectedIndex];
        var duracion = opcion ? opcion.getAttribute('data-duracion') : null;

        var profesional = campo('profesional').value;
        var servicio = selServicio.value;
        var fecha = campo('fecha').value;
        var hora = campo('hora').value;

        if (campo('estado').value === 'cancelado') {
            mostrar('neutro', 'Un turno cancelado no ocupa horario en la agenda.');
            return;
        }

        if (!profesional || !servicio || !fecha || !hora) {
            mostrar('neutro', duracion
                ? 'Duración del servicio: ' + duracion + ' min. Completá los demás datos para ver la disponibilidad.'
                : 'Completá profesional, servicio, fecha y hora para ver la disponibilidad.');
            return;
        }

        var pedido = ++ultimoPedido;
        var url = 'disponibilidad.php?' + new URLSearchParams({
            profesional: profesional, servicio: servicio, fecha: fecha, hora: hora,
            excluir: campo('id').value || '0'
        }).toString();

        fetch(url, { credentials: 'same-origin' })
            .then(function (respuesta) { return respuesta.json(); })
            .then(function (datos) {
                if (pedido !== ultimoPedido) { return; }          // llegó tarde: ya hay otro pedido más nuevo
                mostrar(datos.disponible ? 'ok' : 'error', datos.mensaje);
            })
            .catch(function () {
                if (pedido !== ultimoPedido) { return; }
                mostrar('neutro', 'No se pudo comprobar la disponibilidad ahora. Se verificará igual al guardar.');
            });
    }

    ['profesional', 'servicio', 'fecha', 'hora', 'estado'].forEach(function (nombre) {
        campo(nombre).addEventListener('change', actualizar);
    });

    // modales.js avisa con este evento cada vez que el modal se abre (ya con los datos cargados)
    modal.addEventListener('modal:abierto', actualizar);

    // Si el servidor devolvió errores, el modal ya viene abierto desde que carga la página:
    // el evento anterior se disparó ANTES de que este script existiera, así que se consulta ahora.
    if (modal.hasAttribute('open')) { actualizar(); }
})();
