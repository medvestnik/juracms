<?php use App\Core\View; ?>
<section class="jura-card jura-auth-card" style="padding:2rem">
    <div style="text-align:center;margin-bottom:1.5rem">
        <img src="/install/assets/jura-cms-j-logo.png" alt="Jura CMS" style="width:56px;height:56px;object-fit:contain;margin:0 auto .75rem">
        <h1 style="margin:0;font-size:1.4rem">Вхід в панель JuraCMS</h1>
    </div>
    <?php if (!empty($error)): ?>
        <div style="margin-bottom:1.1rem"><?php View::component('alert', ['type' => 'danger', 'message' => (string) $error]); ?></div>
    <?php endif; ?>
    <?php if ($flash = session_flash('auth_success')): ?>
        <div style="margin-bottom:1.1rem"><?php View::component('alert', ['type' => 'success', 'message' => $flash]); ?></div>
    <?php endif; ?>
    <form method="post" action="/admin/login">
        <?php View::component('input', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'admin@example.com']); ?>
        <?php View::component('input', ['name' => 'password', 'label' => 'Пароль', 'type' => 'password']); ?>
        <button type="submit" class="jura-btn jura-btn-primary" style="width:100%;margin-top:.4rem">Увійти</button>
    </form>
    <div style="text-align:center;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--jura-border,#e2e8f0);font-size:.82rem;color:var(--jura-text-muted,#64748b)">
        <a href="https://juracms.com" target="_blank" rel="noopener" style="color:inherit">juracms.com</a>
        <span style="margin:0 .5rem">·</span>
        <a href="https://github.com/medvestnik/juracms" target="_blank" rel="noopener" style="color:inherit">GitHub</a>
    </div>
</section>
