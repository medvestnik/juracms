<?php use App\Core\View; ?>
<h1>Settings</h1>
<form class="jura-card" method="post" action="#">
    <input type="hidden" name="_token" value="<?= e(csrf_token()); ?>">
    <?php View::component('input', ['name' => 'site_name', 'label' => 'Site name', 'value' => 'Jura CMS']); ?>
    <?php View::component('input', ['name' => 'site_url', 'label' => 'Site URL', 'value' => 'https://example.com']); ?>
    <?php View::component('input', ['name' => 'admin_email', 'label' => 'Admin email', 'type' => 'email', 'value' => 'admin@example.com']); ?>
    <?php View::component('input', ['name' => 'default_language', 'label' => 'Default language', 'value' => 'en']); ?>
    <div class="jura-actions">
        <?php View::component('button', ['label' => 'Save settings', 'type' => 'submit', 'variant' => 'primary']); ?>
    </div>
</form>
