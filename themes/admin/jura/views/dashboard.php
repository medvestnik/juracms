<?php use App\Core\View; ?>
<h1>Dashboard</h1>
<div class="jura-grid jura-grid-4">
    <?php View::component('card', ['title' => 'Pages', 'content' => '0 items']); ?>
    <?php View::component('card', ['title' => 'Media files', 'content' => '0 files']); ?>
    <?php View::component('card', ['title' => 'Modules', 'content' => '0 enabled']); ?>
    <?php View::component('card', ['title' => 'Themes', 'content' => 'Admin: Jura']); ?>
</div>

<div class="jura-grid jura-grid-2">
    <section class="jura-card">
        <h2>Quick actions</h2>
        <div class="jura-actions">
            <a class="jura-btn jura-btn-primary" href="/admin/pages">Create page</a>
            <a class="jura-btn jura-btn-secondary" href="/admin/media">Upload media</a>
        </div>
    </section>

    <section class="jura-card">
        <h2>System status</h2>
        <?php View::component('alert', ['type' => 'success', 'message' => 'Admin area is protected by authentication.']); ?>
        <ul>
            <li>Frontend theme: default</li>
            <li>Admin theme: jura</li>
            <li>Installer: web installer completed</li>
            <li>Signed in as: <?= e($_SESSION['admin_user_email'] ?? ''); ?></li>
        </ul>
    </section>
</div>
