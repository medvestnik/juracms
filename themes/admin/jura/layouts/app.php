<?php use App\Core\View; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Jura CMS'); ?></title>
    <?php foreach (($assets['css'] ?? []) as $css): ?>
        <link rel="stylesheet" href="<?= e(asset_url($css)); ?>">
    <?php endforeach; ?>
</head>
<body class="jura-admin">
<div class="jura-layout">
    <aside class="jura-sidebar">
        <h2>Jura CMS</h2>
        <nav>
            <a href="/admin">Dashboard</a>
            <a href="/admin/pages">Pages</a>
            <a href="/admin/media">Media</a>
            <a href="/admin/settings">Settings</a>
            <a href="/admin/system/updates">Updates</a>
        </nav>
    </aside>

    <main class="jura-main">
        <header class="jura-topbar">
            <strong><?= e($title ?? ''); ?></strong>
            <span class="jura-badge">MVP</span>
        </header>

        <?php if (installer_warning()): ?>
            <section class="jura-content" style="padding-bottom: 0;">
                <?php View::component('alert', ['type' => 'warning', 'message' => installer_warning()]); ?>
            </section>
        <?php endif; ?>

        <section class="jura-content">
            <?php include $viewFile; ?>
        </section>
    </main>
</div>
<?php View::component('modal', [
    'id' => 'mvp-modal',
    'title' => 'MVP Notice',
    'content' => 'This section is in progress.',
]); ?>
<?php foreach (($assets['js'] ?? []) as $js): ?>
    <script src="<?= e(asset_url($js)); ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
