<?php $users = $users ?? []; ?>
<?php if (!empty($flash_success)): ?>
<div class="jura-alert" style="margin-bottom:1rem"><?= e($flash_success) ?></div>
<?php endif; ?>
<?php if (!empty($flash_error)): ?>
<div class="jura-alert" style="margin-bottom:1rem;background:#fff1f2;border-color:#fecdd3;color:#9f1239"><?= e($flash_error) ?></div>
<?php endif; ?>

<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Додати користувача</h2>
  <form method="post" action="/admin/users/create" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <div>
      <label class="jura-label">Email</label>
      <input class="jura-input" type="email" name="email" placeholder="user@example.com" style="margin:0" required>
    </div>
    <div>
      <label class="jura-label">Пароль</label>
      <input class="jura-input" type="password" name="password" placeholder="мінімум 6 символів" style="margin:0" required minlength="6">
    </div>
    <div>
      <label class="jura-label">Роль</label>
      <select class="jura-input" name="role" style="margin:0">
        <option value="editor">Редактор</option>
        <option value="admin">Адміністратор</option>
      </select>
    </div>
    <button class="jura-btn jura-btn-primary" type="submit">Додати</button>
  </form>
</section>

<section class="jura-card">
  <h2 style="margin-top:0">Користувачі</h2>
  <?php if (empty($users)): ?>
  <p style="color:#888">Користувачів ще немає.</p>
  <?php else: ?>
  <table class="jura-table">
    <thead><tr><th>ID</th><th>Email</th><th>Роль</th><th>Статус</th><th>Створено</th><th>Дії</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= e($u['id']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= e($u['role'] === 'admin' ? 'Адміністратор' : 'Редактор') ?></td>
        <td>
          <?php if ($u['status'] === 'active'): ?>
          <span style="font-size:.72rem;background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;border-radius:20px;padding:.1rem .5rem">Активний</span>
          <?php else: ?>
          <span style="font-size:.72rem;background:#f3f4f6;color:#6b7280;border:1px solid #d1d5db;border-radius:20px;padding:.1rem .5rem">Неактивний</span>
          <?php endif; ?>
        </td>
        <td><?= e($u['created_at'] ?? '') ?></td>
        <td style="white-space:nowrap;display:flex;gap:.4rem;align-items:center">
          <?php if ((int) $u['id'] !== (int) ($_SESSION['admin_user_id'] ?? 0)): ?>
          <form method="post" action="/admin/users/<?= (int) $u['id'] ?>/toggle" style="margin:0">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <button class="jura-btn jura-btn-secondary" type="submit" style="padding:.3rem .6rem;font-size:.8rem">
              <?= $u['status'] === 'active' ? 'Вимкнути' : 'Увімкнути' ?>
            </button>
          </form>
          <?php endif; ?>
          <button class="jura-btn jura-btn-secondary" type="button" style="padding:.3rem .6rem;font-size:.8rem"
            onclick="var f=document.getElementById('pw-<?= (int) $u['id'] ?>'); f.hidden=!f.hidden;">Пароль</button>
          <?php if ((int) $u['id'] !== (int) ($_SESSION['admin_user_id'] ?? 0)): ?>
          <form method="post" action="/admin/users/<?= (int) $u['id'] ?>/delete" style="margin:0">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <button class="jura-btn" type="submit" style="padding:.3rem .6rem;font-size:.8rem;background:#fff1f2;color:#9f1239;border:1px solid #fecdd3"
              onclick="return confirm('Видалити користувача «<?= e($u['email']) ?>»?')">✕</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <tr id="pw-<?= (int) $u['id'] ?>" hidden>
        <td colspan="6">
          <form method="post" action="/admin/users/<?= (int) $u['id'] ?>/password" style="display:flex;gap:.6rem;align-items:center;margin:0">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <input class="jura-input" type="password" name="password" placeholder="Новий пароль (мінімум 6 символів)" style="margin:0;max-width:260px" required minlength="6">
            <button class="jura-btn jura-btn-primary" type="submit" style="padding:.3rem .8rem;font-size:.85rem">Змінити пароль</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
