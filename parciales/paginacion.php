<?php
/**
 * parciales/paginacion.php
 * Pie de tabla: "Mostrando 1–5 de 128 clientes" + flechas de página.
 * Reutilizable en Clientes, Profesionales y Agenda.
 *
 * Variables que espera:
 *   $pag       resultado de paginar()
 *   $total     total de filas que cumplen el filtro
 *   $mostradas cuántas filas se ven en esta página
 *   $etiqueta  plural para el texto ("clientes")
 */
$desde = $total > 0 ? $pag['offset'] + 1 : 0;
$hasta = $pag['offset'] + $mostradas;
?>
<div class="tabla-pie">
    <?php /* Con {llaves} PHP sabe dónde termina el nombre de la variable (el guion largo "–" no es parte de ella) */ ?>
    <p><?= $total > 0 ? "Mostrando {$desde}–{$hasta} de {$total} " . e($etiqueta) : 'Sin resultados' ?></p>

    <nav class="paginas" aria-label="Paginación">
        <?php if ($pag['pagina'] > 1): ?>
            <a class="paginas__flecha" href="<?= e(url_con(['pagina' => $pag['pagina'] - 1])) ?>" aria-label="Página anterior">
                <?= icono('chevron-left', 14) ?>
            </a>
        <?php else: ?>
            <span class="paginas__flecha paginas__flecha--inactiva" aria-hidden="true"><?= icono('chevron-left', 14) ?></span>
        <?php endif; ?>

        <span class="paginas__actual" aria-current="page"><?= (int) $pag['pagina'] ?></span>

        <?php if ($pag['pagina'] < $pag['total_paginas']): ?>
            <a class="paginas__flecha" href="<?= e(url_con(['pagina' => $pag['pagina'] + 1])) ?>" aria-label="Página siguiente">
                <?= icono('chevron-right', 14) ?>
            </a>
        <?php else: ?>
            <span class="paginas__flecha paginas__flecha--inactiva" aria-hidden="true"><?= icono('chevron-right', 14) ?></span>
        <?php endif; ?>
    </nav>
</div>
