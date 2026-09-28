<?php $jsVer = @filemtime(APP_ROOT . '/assets/js/app.js') ?: 1; ?>
<script src="<?= e(url('assets/vendor/lenis.min.js')) ?>"></script>
<script src="<?= e(url('assets/js/app.js')) ?>?v=<?= $jsVer ?>"></script>
</body>
</html>
