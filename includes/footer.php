<?php
/**
 * includes/footer.php
 * Cierra lo que abrió header.php y carga los scripts.
 * Una página puede pedir scripts extra definiendo antes:
 *   $scripts_extra = ['assets/js/modales.js'];
 */
?>
        </main>
    </div><!-- /.principal -->
</div><!-- /.app -->

<script src="assets/js/layout.js"></script>
<?php foreach (($scripts_extra ?? []) as $script): ?>
<script src="<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
