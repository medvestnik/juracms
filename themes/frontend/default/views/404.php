<section class="section">
  <div class="site-container">
    <div class="empty-state">
      <h1 style="margin-bottom:.5rem">404</h1>
      <p>Сторінку не знайдено.</p>
      <div class="hero__actions" style="margin-top:1.25rem">
        <a class="btn btn-primary" href="/">На головну</a>
        <?php if (!empty($_SESSION['admin_user_id'])): ?>
        <a class="btn btn-secondary" href="/admin">Адмінка</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
