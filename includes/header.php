<?php
/**
 * includes/header.php
 * Abre la estructura común de las pantallas internas:
 *   <html> → <head> → <body> → menú lateral → barra superior → <main>
 * Lo cierra includes/footer.php.
 *
 * Cada página debe definir ANTES de incluir este archivo:
 *   $titulo_pagina  texto para el <title> y la ruta ("Inicio", "Clientes"...)
 *   $pagina_activa  clave del menú a resaltar: inicio | clientes | profesionales | turnos
 */
$usuario = usuario_actual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo_pagina) ?> · Apsy</title>

    <!-- Tipografía Inter del diseño. Sin internet se usa la fuente del sistema. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap">

    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/componentes.css">
</head>
<body>
<div class="app">

    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- Fondo oscuro que aparece detrás del menú en el celular -->
    <div class="menu-fondo" data-menu-cerrar></div>

    <div class="principal">
        <?php require __DIR__ . '/topbar.php'; ?>

        <main class="contenido">
