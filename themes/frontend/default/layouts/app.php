<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Jura CMS'); ?></title>
    <?php foreach (($assets['css'] ?? []) as $css): ?>
        <link rel="stylesheet" href="<?= e(asset_url($css)); ?>">
    <?php endforeach; ?>
    <style>
        .frontend-wrap{max-width:860px;margin:0 auto;padding:2rem}
        .frontend-nav{display:flex;gap:1rem;margin-bottom:1rem}
    </style>
</head>
<body>
<div class="frontend-wrap">
    <nav class="frontend-nav">
        <a href="/">Home</a>
        <a href="/admin">Admin</a>
        <a href="/install">Install</a>
    </nav>
    <?php include $viewFile; ?>
</div>
<?php foreach (($assets['js'] ?? []) as $js): ?>
    <script src="<?= e(asset_url($js)); ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
