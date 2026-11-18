<?php
/**
 * includes/topbar.php
 * Barra superior: botón de menú (solo celular), ruta de navegación,
 * notificaciones interactivas y perfil de usuario con menú desplegable.
 */

// Consultamos rápidamente los turnos pendientes de hoy para mostrarlos en las notificaciones
$hoy = date('Y-m-d');
$stmt_notif = $conexion->prepare(
    "SELECT t.id, t.hora_inicio, c.nombre, c.apellido 
       FROM turnos t 
      INNER JOIN clientes c ON c.id = t.cliente_id 
      WHERE t.fecha = ? AND t.estado = 'pendiente' 
      ORDER BY t.hora_inicio LIMIT 5"
);
$stmt_notif->bind_param('s', $hoy);
$stmt_notif->execute();
$notificaciones_pendientes = $stmt_notif->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_notif->close();

$total_pendientes = count($notificaciones_pendientes);
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

    <div class="perfil-acciones" style="display: flex; align-items: center; gap: 1rem; position: relative;">
        
        <!-- Contenedor de la Campanita de Notificaciones -->
        <div class="notificaciones-contenedor" style="position: relative;">
            <button type="button" id="btn-campana" aria-label="Notificaciones" 
                    style="background: none; border: none; cursor: pointer; position: relative; display: flex; align-items: center; padding: 0.5rem; color: #4a4a4a;">
                <?= icono('bell', 19, 'perfil__campana') ?>
                <?php if ($total_pendientes > 0): ?>
                    <span style="position: absolute; top: 2px; right: 2px; background: #C86D51; color: #fff; font-size: 0.65rem; font-weight: 600; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <?= $total_pendientes ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Menú desplegable de notificaciones -->
            <div id="panel-notificaciones" style="display: none; position: absolute; right: 0; top: calc(100% + 8px); background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; width: 300px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); z-index: 100; padding: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; border-bottom: 1px solid #f3f4f6; padding-bottom: 0.5rem;">
                    <p style="font-weight: 600; font-size: 0.9rem; color: #1a1a1a; margin: 0;">Notificaciones de hoy</p>
                    <span style="font-size: 0.75rem; background: #f3f4f6; padding: 2px 8px; border-radius: 10px; color: #666;"><?= $total_pendientes ?> pendientes</span>
                </div>

                <div style="max-height: 250px; overflow-y: auto;">
                    <?php if (!$notificaciones_pendientes): ?>
                        <p style="font-size: 0.85rem; color: #666; text-align: center; margin: 1rem 0;">No hay turnos pendientes para hoy. ¡Todo al día!</p>
                    <?php else: ?>
                        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php foreach ($notificaciones_pendientes as $n): ?>
                                <li style="padding: 0.5rem; border-bottom: 1px solid #f9fafb; font-size: 0.85rem;">
                                    <a href="turnos.php?fecha=<?= e($hoy) ?>&estado=pendiente" style="text-decoration: none; color: inherit; display: block;">
                                        <strong style="color: #1a1a1a;"><?= e($n['nombre'] . ' ' . $n['apellido']) ?></strong>
                                        <span style="display: block; color: #666; font-size: 0.75rem;">Turno a las <?= e(substr($n['hora_inicio'], 0, 5)) ?> h · Esperando confirmación</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <?php if ($total_pendientes > 0): ?>
                    <div style="margin-top: 0.75rem; border-top: 1px solid #f3f4f6; padding-top: 0.5rem; text-align: center;">
                        <a href="turnos.php?fecha=<?= e($hoy) ?>&estado=pendiente" style="font-size: 0.8rem; color: #C86D51; text-decoration: none; font-weight: 500;">Ver todos los pendientes →</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contenedor del Perfil con Menú Desplegable -->
        <div class="perfil-contenedor" style="position: relative;">
            <button type="button" id="btn-perfil" aria-label="Menú de usuario" 
                    style="background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.75rem; padding: 0.25rem; text-align: left;">
                <span class="avatar"><?= e(iniciales($usuario['nombre'], $usuario['apellido'])) ?></span>
                <div class="perfil__datos">
                    <p class="perfil__nombre"><?= e($usuario['nombre'] . ' ' . $usuario['apellido']) ?></p>
                    <p class="perfil__rol"><?= e($usuario['rol']) ?></p>
                </div>
                <?= icono('chevron-down', 14, 'perfil__flecha') ?>
            </button>

            <!-- Menú desplegable del perfil -->
            <div id="panel-perfil" style="display: none; position: absolute; right: 0; top: calc(100% + 8px); background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; width: 220px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); z-index: 100; padding: 0.5rem 0;">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6;">
                    <p style="font-weight: 600; font-size: 0.85rem; color: #1a1a1a; margin: 0;"><?= e($usuario['nombre'] . ' ' . $usuario['apellido']) ?></p>
                    <p style="font-size: 0.75rem; color: #666; margin: 0;"><?= e($usuario['rol']) ?></p>
                </div>
                <div style="padding: 0.25rem 0;">
                    <a href="contacto.php" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; font-size: 0.85rem; color: #374151; text-decoration: none;">
                        <?= icono('help-circle', 16) ?> Soporte y contacto
                    </a>
                    <a href="logout.php" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; font-size: 0.85rem; color: #dc2626; text-decoration: none;">
                        <?= icono('log-out', 16) ?> Cerrar sesión
                    </a>
                </div>
            </div>
        </div>

    </div>
</header>

<!-- Script para manejar la apertura y cierre de ambos menús flotantes -->
<script>
    const btnCampana = document.getElementById('btn-campana');
    const panelNotificaciones = document.getElementById('panel-notificaciones');
    
    const btnPerfil = document.getElementById('btn-perfil');
    const panelPerfil = document.getElementById('panel-perfil');

    if (btnCampana && panelNotificaciones) {
        btnCampana.addEventListener('click', (e) => {
            e.stopPropagation();
            if (panelPerfil) panelPerfil.style.display = 'none'; // Cierra perfil si está abierto
            const visible = panelNotificaciones.style.display === 'block';
            panelNotificaciones.style.display = visible ? 'none' : 'block';
        });
    }

    if (btnPerfil && panelPerfil) {
        btnPerfil.addEventListener('click', (e) => {
            e.stopPropagation();
            if (panelNotificaciones) panelNotificaciones.style.display = 'none'; // Cierra campana si está abierta
            const visible = panelPerfil.style.display === 'block';
            panelPerfil.style.display = visible ? 'none' : 'block';
        });
    }

    // Ocultar paneles si se hace clic fuera de ellos
    document.addEventListener('click', (e) => {
        if (panelNotificaciones && !panelNotificaciones.contains(e.target) && btnCampana && !btnCampana.contains(e.target)) {
            panelNotificaciones.style.display = 'none';
        }
        if (panelPerfil && !panelPerfil.contains(e.target) && btnPerfil && !btnPerfil.contains(e.target)) {
            panelPerfil.style.display = 'none';
        }
    });
</script>