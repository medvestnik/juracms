<?php $s = $s ?? []; ?>

<?php if (!empty($success)): ?>
<div class="jura-alert" style="margin-bottom:1rem"><?= e($success) ?></div>
<?php endif; ?>

<p style="color:#64748b;font-size:.9rem;margin:0 0 1.5rem">
  Коди виджетів Exely (booking-engine). Файли беруться з архіву Exely, розділ файлів для потрібної мови (наприклад, <code>-uk</code> для української).
  Плейсхолдери <code>{{ booking-search-form }}</code> та <code>{{ booking-page-form }}</code> можна вставляти у вміст будь-якої сторінки — вони підставляться автоматично.
</p>

<form method="post">
  <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0;font-size:1rem">Exely: head_script</h2>
    <p style="color:#94a3b8;font-size:.85rem;margin:0 0 .75rem">Вставляється у <code>&lt;head&gt;</code> на всіх сторінках сайту. Файл <code>head_script-uk</code> з папки <code>head_script</code> архіву Exely.</p>
    <textarea class="jura-input" name="booking_widget_head" rows="6" placeholder="<!-- start head script --> ... <!-- end head script -->" style="font-family:monospace;font-size:.82rem"><?= e($s['booking_widget_head'] ?? '') ?></textarea>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0;font-size:1rem">Exely: форма пошуку номерів</h2>
    <p style="color:#94a3b8;font-size:.85rem;margin:0 0 .75rem">Вставте плейсхолдер <code>{{ booking-search-form }}</code> у вміст потрібної сторінки (наприклад, головної) — тут буде підставлено цей код. Файл <code>search_form-uk</code> з папки <code>search_form.2.0</code>.</p>
    <textarea class="jura-input" name="booking_search_form" rows="6" placeholder="<!-- start Search form script --> ... </div>" style="font-family:monospace;font-size:.82rem"><?= e($s['booking_search_form'] ?? '') ?></textarea>
  </section>

  <section class="jura-card" style="margin-bottom:1.5rem">
    <h2 style="margin-top:0;font-size:1rem">Exely: форма бронювання</h2>
    <p style="color:#94a3b8;font-size:.85rem;margin:0 0 .75rem">Вставте плейсхолдер <code>{{ booking-page-form }}</code> у вміст сторінки бронювання. Файл <code>reservation_form-uk</code> з папки <code>booking_engine.2.0</code>.</p>
    <textarea class="jura-input" name="booking_page_form" rows="6" placeholder="<!-- start Booking form script --> ... </div>" style="font-family:monospace;font-size:.82rem"><?= e($s['booking_page_form'] ?? '') ?></textarea>
  </section>

  <button type="submit" class="jura-btn jura-btn-primary">Зберегти</button>
</form>
