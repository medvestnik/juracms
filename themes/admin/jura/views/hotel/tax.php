<?php $s = $s ?? []; ?>

<?php if (!empty($success)): ?>
<div class="jura-alert" style="margin-bottom:1rem"><?= e($success) ?></div>
<?php endif; ?>

<form method="post">
  <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

  <!-- Громадяни України -->
  <section class="jura-card" style="margin-bottom:1rem">
    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem">
      <h2 style="margin:0;font-size:1rem">🇺🇦 Громадяни України</h2>
      <label style="display:flex;align-items:center;gap:.4rem;font-size:.88rem;cursor:pointer;margin-left:auto">
        <input type="checkbox" name="hotel_tourist_tax_ua_enabled" value="1"
          <?= ($s['hotel_tourist_tax_ua_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
        Показувати на сторінці номера
      </label>
    </div>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Ставка збору (грн/особа/добу)</label>
        <input class="jura-input" name="hotel_tourist_tax_ua_rate"
          value="<?= e($s['hotel_tourist_tax_ua_rate'] ?? '43,24') ?>"
          placeholder="43,24" style="max-width:160px">
        <small style="color:#94a3b8;display:block;margin-top:.3rem">Відображається як є — можна писати «43,24 грн» або просто «43,24»</small>
      </div>
      <div>
        <label class="jura-label">Примітка під ставкою</label>
        <input class="jura-input" name="hotel_tourist_tax_ua_note"
          value="<?= e($s['hotel_tourist_tax_ua_note'] ?? '') ?>"
          placeholder="ставка без змін">
        <small style="color:#94a3b8;display:block;margin-top:.3rem">Відображається дрібним шрифтом під сумою</small>
      </div>
    </div>
  </section>

  <!-- Іноземні громадяни -->
  <section class="jura-card" style="margin-bottom:1rem">
    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem">
      <h2 style="margin:0;font-size:1rem">🌍 Іноземні громадяни</h2>
      <label style="display:flex;align-items:center;gap:.4rem;font-size:.88rem;cursor:pointer;margin-left:auto">
        <input type="checkbox" name="hotel_tourist_tax_foreign_enabled" value="1"
          <?= ($s['hotel_tourist_tax_foreign_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
        Показувати на сторінці номера
      </label>
    </div>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Ставка збору (грн/особа/добу)</label>
        <input class="jura-input" name="hotel_tourist_tax_foreign_rate"
          value="<?= e($s['hotel_tourist_tax_foreign_rate'] ?? '86,47') ?>"
          placeholder="86,47" style="max-width:160px">
      </div>
      <div>
        <label class="jura-label">Бейдж «нова ставка» (необов'язково)</label>
        <input class="jura-input" name="hotel_tourist_tax_foreign_badge"
          value="<?= e($s['hotel_tourist_tax_foreign_badge'] ?? '') ?>"
          placeholder="НОВА СТАВКА">
        <small style="color:#94a3b8;display:block;margin-top:.3rem">Якщо заповнено — показується червоний бейдж</small>
      </div>
      <div style="grid-column:1/-1">
        <label class="jura-label">Примітка (наприклад, дата введення)</label>
        <input class="jura-input" name="hotel_tourist_tax_foreign_note"
          value="<?= e($s['hotel_tourist_tax_foreign_note'] ?? '') ?>"
          placeholder="діє з 01.01.2026">
      </div>
    </div>
  </section>

  <!-- Загальна примітка -->
  <section class="jura-card" style="margin-bottom:1.5rem">
    <h2 style="margin-top:0;font-size:1rem">Загальна примітка</h2>
    <div>
      <label class="jura-label">Текст під блоком туристичного збору</label>
      <input class="jura-input" name="hotel_tourist_tax_extra_note"
        value="<?= e($s['hotel_tourist_tax_extra_note'] ?? '') ?>"
        placeholder="Туристичний збір сплачується при заселенні готівкою або карткою">
      <small style="color:#94a3b8;display:block;margin-top:.3rem">Необов'язково. Відображається курсивом під блоком.</small>
    </div>
  </section>

  <!-- Preview -->
  <?php
  $uaEnabled = ($s['hotel_tourist_tax_ua_enabled'] ?? '0') === '1';
  $foreignEnabled = ($s['hotel_tourist_tax_foreign_enabled'] ?? '0') === '1';
  if ($uaEnabled || $foreignEnabled):
  ?>
  <section class="jura-card" style="margin-bottom:1.5rem;background:#f8fafc">
    <h2 style="margin-top:0;font-size:.9rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Попередній перегляд</h2>
    <div style="max-width:480px">
      <?php if ($uaEnabled): ?>
      <div style="border:1.5px solid #fbbf24;border-radius:10px;padding:.75rem 1rem;margin-bottom:.75rem;display:flex;align-items:center;gap:.75rem">
        <span style="font-size:1.4rem">🇺🇦</span>
        <div>
          <div style="font-weight:600;font-size:.92rem">
            Громадяни України —
            <span style="color:#c4922a"><?= e($s['hotel_tourist_tax_ua_rate'] ?? '43,24') ?> грн</span>
            з особи
          </div>
          <?php if (!empty($s['hotel_tourist_tax_ua_note'])): ?>
          <div style="font-size:.78rem;color:#64748b">(<?= e($s['hotel_tourist_tax_ua_note']) ?>)</div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($foreignEnabled): ?>
      <div style="border:1.5px solid #93c5fd;border-radius:10px;padding:.75rem 1rem;margin-bottom:.75rem;display:flex;align-items:center;gap:.75rem">
        <span style="font-size:1.4rem">🌍</span>
        <div>
          <div style="font-weight:600;font-size:.92rem;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
            Іноземні громадяни —
            <span style="color:#2563eb"><?= e($s['hotel_tourist_tax_foreign_rate'] ?? '86,47') ?> грн</span>
            з особи
            <?php if (!empty($s['hotel_tourist_tax_foreign_badge'])): ?>
            <span style="background:#dc2626;color:#fff;font-size:.68rem;font-weight:700;padding:.1rem .4rem;border-radius:3px"><?= e($s['hotel_tourist_tax_foreign_badge']) ?></span>
            <?php endif; ?>
          </div>
          <?php if (!empty($s['hotel_tourist_tax_foreign_note'])): ?>
          <div style="font-size:.78rem;color:#64748b">(<?= e($s['hotel_tourist_tax_foreign_note']) ?>)</div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if (!empty($s['hotel_tourist_tax_extra_note'])): ?>
      <p style="font-size:.8rem;color:#64748b;font-style:italic;margin:.25rem 0 0"><?= e($s['hotel_tourist_tax_extra_note']) ?></p>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <button type="submit" class="jura-btn jura-btn-primary">Зберегти</button>
</form>
