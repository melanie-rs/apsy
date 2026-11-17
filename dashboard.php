<?php
/**
 * dashboard.php  ·  VERSIÓN PROVISORIA
 * Solo sirve para comprobar el login y el layout. Se reemplaza por completo
 * en la etapa "Dashboard" (con métricas, próximos turnos y equipo de hoy).
 */
require_once 'includes/auth.php';
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

requerir_login();                       // sin sesión → vuelve al login

$titulo_pagina = 'Inicio';              // texto de <title> y de la ruta
$pagina_activa = 'inicio';              // ítem resaltado del menú
$usuario       = usuario_actual();

require 'includes/header.php';
?>
<div class="encabezado-pagina">
    <div>
        <h1><?= e(saludo_segun_hora()) ?>, <?= e($usuario['nombre']) ?></h1>
        <p><?= e(fecha_larga()) ?> · Así viene tu día en el salón.</p>
    </div>
</div>

<div class="tarjeta">
    <p class="tarjeta__titulo">
        <span class="texto-ok"><?= icono('circle-check', 19) ?></span>
        Sesión iniciada correctamente
    </p>
    <p class="texto-suave">
        Estás logueada como <strong><?= e($usuario['usuario']) ?></strong> (<?= e($usuario['rol']) ?>).
        El dashboard completo se desarrolla en una etapa posterior.
    </p>
</div>
<?php require 'includes/footer.php'; ?>
