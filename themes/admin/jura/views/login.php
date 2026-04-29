<?php use App\Core\View; ?>
<section class="jura-card jura-auth-card">
    <h1>Sign in</h1>
    <?php if (!empty($error)): ?>
        <?php View::component('alert', ['type' => 'danger', 'message' => (string) $error]); ?>
    <?php endif; ?>
    <?php if ($flash = session_flash('auth_success')): ?>
        <?php View::component('alert', ['type' => 'success', 'message' => $flash]); ?>
    <?php endif; ?>
    <form method="post" action="/admin/login">
        <?php View::component('input', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'admin@example.com']); ?>
        <?php View::component('input', ['name' => 'password', 'label' => 'Password', 'type' => 'password']); ?>
        <div class="jura-actions">
            <?php View::component('button', ['label' => 'Sign in', 'type' => 'submit', 'variant' => 'primary']); ?>
        </div>
    </form>
</section>
