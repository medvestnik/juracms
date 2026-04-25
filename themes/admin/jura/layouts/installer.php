<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Installer'); ?></title>
    <?php foreach (($assets['css'] ?? []) as $css): ?>
        <link rel="stylesheet" href="<?= e(asset_url($css)); ?>">
    <?php endforeach; ?>
</head>
<body class="jura-installer-page">
<div class="jura-installer-wrap">
    <?php include $viewFile; ?>
</div>
<?php foreach (($assets['js'] ?? []) as $js): ?>
    <script src="<?= e(asset_url($js)); ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
