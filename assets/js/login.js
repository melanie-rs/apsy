/**
 * assets/js/login.js
 * Botón "ojo" del login: muestra u oculta la contraseña.
 * Cambia el type del input entre "password" (puntos) y "text" (legible).
 */
(function () {
    'use strict';

    var boton = document.querySelector('[data-ver-clave]');
    var campo = document.getElementById('clave');

    if (!boton || !campo) { return; }

    var iconoOculta = boton.querySelector('[data-icono-oculta]');
    var iconoVisible = boton.querySelector('[data-icono-visible]');

    boton.addEventListener('click', function () {
        var mostrar = (campo.type === 'password');

        campo.type = mostrar ? 'text' : 'password';
        iconoOculta.hidden = mostrar;       // ojo tachado cuando está oculta
        iconoVisible.hidden = !mostrar;     // ojo abierto cuando se ve
        boton.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
        boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
        campo.focus();
    });
})();
