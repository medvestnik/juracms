<?php use App\Core\View; ?>
<section class="jura-card jura-auth-card">
    <h1>Sign in</h1>
    <?php View::component('alert', ['type' => 'info', 'message' => 'TODO: implement authentication']); ?>
    <form method="post" action="#">
        <input type="hidden" name="_token" value="<?= e(csrf_token()); ?>">
        <?php View::component('input', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'admin@example.com']); ?>
        <?php View::component('input', ['name' => 'password', 'label' => 'Password', 'type' => 'password']); ?>
        <div class="jura-actions">
            <?php View::component('button', ['label' => 'Sign in', 'type' => 'submit', 'variant' => 'primary']); ?>
        </div>
    </form>
</section>
