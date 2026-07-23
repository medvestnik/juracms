<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Authentication'); ?></title>
    <?php foreach (($assets['css'] ?? []) as $css): ?>
        <link rel="stylesheet" href="<?= e(asset_url($css)); ?>">
    <?php endforeach; ?>
    <style>
        html,body{height:100%}
        body.jura-auth-page{display:flex!important;align-items:center;justify-content:center;min-height:100vh;margin:0}
        .jura-auth-wrap{width:min(420px,100%)}
    </style>
</head>
<body class="jura-auth-page">
<div class="jura-auth-wrap">
    <?php include $viewFile; ?>
</div>
<?php foreach (($assets['js'] ?? []) as $js): ?>
    <script src="<?= e(asset_url($js)); ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
