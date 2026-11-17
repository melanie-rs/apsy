<?php
/**
 * includes/topbar.php
 * Barra superior: botón de menú (solo celular), ruta de navegación y datos
 * del usuario logueado. Usa $usuario y $titulo_pagina definidos en header.php.
 */
?>
<header class="topbar">
    <button class="topbar__menu" type="button" aria-label="Abrir menú"
            aria-controls="menu-lateral" aria-expanded="false" data-menu-abrir>
        <?= icono('menu', 22) ?>
    </button>

    <nav class="ruta" aria-label="Ruta de navegación">
        <span class="ruta__salon">Estudio Apsy</span>
        <?= icono('chevron-right', 13) ?>
        <span class="ruta__actual"><?= e($titulo_pagina) ?></span>
    </nav>

    <div class="perfil">
        <?= icono('bell', 19, 'perfil__campana') ?>
        <span class="avatar"><?= e(iniciales($usuario['nombre'], $usuario['apellido'])) ?></span>
        <div class="perfil__datos">
            <p class="perfil__nombre"><?= e($usuario['nombre'] . ' ' . $usuario['apellido']) ?></p>
            <p class="perfil__rol"><?= e($usuario['rol']) ?></p>
        </div>
        <?= icono('chevron-down', 14, 'perfil__flecha') ?>
    </div>
</header>
