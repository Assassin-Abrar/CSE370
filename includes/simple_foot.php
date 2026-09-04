<?php $flashes = getFlashes(); ?>
<script id="flashData" type="application/json"><?= json_encode($flashes) ?></script>
<script src="<?= assetUrl('assets/js/app.js') ?>"></script>
<script src="<?= assetUrl('assets/js/ajax.js') ?>"></script>
</body>
</html>
