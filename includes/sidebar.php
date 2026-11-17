<?php
/**
 * includes/sidebar.php
 * Menú lateral. Usa $pagina_activa (definida por la página) para resaltar
 * la sección actual.
 */
$menu = [
    'inicio'        => ['dashboard.php',     'Inicio',           'layout-dashboard'],
    'clientes'      => ['clientes.php',      'Clientes',         'users'],
    'profesionales' => ['profesionales.php', 'Profesionales',    'user-round'],
    'turnos'        => ['turnos.php',        'Agenda de turnos', 'calendar-days'],
];
?>
<aside class="sidebar" id="menu-lateral">
    <div class="sidebar__identidad">
        <a class="marca" href="dashboard.php">
            <span class="marca__simbolo"><?= icono('scissors', 22) ?></span>
            <span class="marca__nombre">Apsy</span>
        </a>
        <p class="sidebar__lema">GESTIÓN DE PELUQUERÍA</p>
    </div>

    <nav class="sidebar__nav" aria-label="Navegación principal">
        <?php foreach ($menu as $clave => [$url, $texto, $nombre_icono]): ?>
            <?php $activo = ($clave === $pagina_activa); ?>
            <a class="nav-item<?= $activo ? ' nav-item--activo' : '' ?>"
               href="<?= e($url) ?>"<?= $activo ? ' aria-current="page"' : '' ?>>
                <?= icono($nombre_icono, 19) ?>
                <span><?= e($texto) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar__cuenta">
        <div class="salon">
            <?= icono('store', 20) ?>
            <div>
                <p class="salon__nombre">Estudio Apsy</p>
                <p class="salon__ciudad">Córdoba</p>
            </div>
        </div>
        <a class="cerrar-sesion" href="logout.php">
            <?= icono('log-out', 18) ?>
            <span>Cerrar sesión</span>
        </a>
    </div>
</aside>
