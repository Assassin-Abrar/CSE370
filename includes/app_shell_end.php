    </div>
  </div>
</div>
<?php $flashes = getFlashes(); ?>
<script id="flashData" type="application/json"><?= json_encode($flashes) ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?= assetUrl('assets/js/app.js') ?>"></script>
<script src="<?= assetUrl('assets/js/ajax.js') ?>"></script>
<script src="<?= assetUrl('assets/js/charts.js') ?>"></script>
</body>
</html>
