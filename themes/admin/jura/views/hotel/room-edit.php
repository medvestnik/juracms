<?php
$r  = $room ?? [];
$id = (int) ($r['id'] ?? 0);
$isCreate = $id === 0;
$formAction = $isCreate ? '/admin/hotel/rooms/create' : '/admin/hotel/rooms/' . $id . '/edit';
$roomImages = $room_images ?? [];
$featuredImg = null;
$galleryImgs = [];
foreach ($roomImages as $img) {
    if ($img['is_featured']) $featuredImg = $img;
    else $galleryImgs[] = $img;
}
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;gap:1rem;flex-wrap:wrap">
  <a href="/admin/hotel/rooms" class="jura-btn jura-btn-secondary">&larr; Назад до списку</a>
  <div style="display:flex;gap:.6rem">
    <a class="jura-btn jura-btn-secondary" href="/admin/hotel/rooms">Скасувати</a>
    <button form="room-main-form" class="jura-btn jura-btn-secondary" type="submit" name="_close" value="1">Зберегти і закрити</button>
    <button form="room-main-form" class="jura-btn jura-btn-primary" type="submit">Зберегти</button>
  </div>
</div>

<form id="room-main-form" method="post" action="<?= e($formAction) ?>">
  <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Основне</h2>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Назва <span style="color:red">*</span></label>
        <input class="jura-input" name="title" value="<?= e($r['title'] ?? '') ?>" required>
      </div>
      <div>
        <label class="jura-label">Slug (URL)</label>
        <input class="jura-input" name="slug" value="<?= e($r['slug'] ?? '') ?>" placeholder="auto-generated">
      </div>
      <div>
        <label class="jura-label">Площа (м²)</label>
        <input class="jura-input" name="area" value="<?= e($r['area'] ?? '') ?>" placeholder="напр. 25">
      </div>
      <div>
        <label class="jura-label">Місткість (осіб)</label>
        <input class="jura-input" name="capacity" value="<?= e($r['capacity'] ?? '') ?>" placeholder="напр. 2">
      </div>
      <div>
        <label class="jura-label">Ціна від</label>
        <input class="jura-input" name="price_from" type="number" step="0.01" value="<?= e($r['price_from'] ?? '0') ?>">
      </div>
      <div>
        <label class="jura-label">Валюта</label>
        <input class="jura-input" name="currency" value="<?= e($r['currency'] ?? 'UAH') ?>">
      </div>
    </div>
    <div style="margin-top:1rem">
      <label class="jura-label">Короткий опис (excerpt)</label>
      <textarea class="jura-input" name="excerpt" rows="3"><?= e($r['excerpt'] ?? '') ?></textarea>
    </div>
    <div style="margin-top:1rem">
      <label class="jura-label">Зручності</label>
      <?php
        $allAmenities   = $all_amenities ?? [];
        $roomAmenityIds = $room_amenity_ids ?? [];
      ?>
      <?php if (empty($allAmenities)): ?>
        <p style="color:#94a3b8;font-size:.875rem">Зручностей ще немає. <a href="/admin/hotel/amenities">Додайте в розділі Зручності →</a></p>
      <?php else: ?>
      <div class="jura-amenity-picker" id="amenity-picker">
        <div class="jap-selected" id="jap-selected">
          <?php foreach ($allAmenities as $a): ?>
            <?php if (in_array((int)$a['id'], $roomAmenityIds, true)): ?>
            <span class="jap-tag" data-id="<?= (int)$a['id'] ?>">
              <?= e($a['icon'] ?? '') ?> <?= e($a['title']) ?>
              <button type="button" class="jap-remove" data-id="<?= (int)$a['id'] ?>">&times;</button>
            </span>
            <?php endif; ?>
          <?php endforeach; ?>
          <span class="jap-placeholder" id="jap-placeholder">Виберіть зручності...</span>
        </div>
        <div class="jap-dropdown" id="jap-dropdown" hidden>
          <?php foreach ($allAmenities as $a): ?>
          <div class="jap-option" data-id="<?= (int)$a['id'] ?>" data-title="<?= e($a['icon'] . ' ' . $a['title']) ?>">
            <?= e($a['icon'] ?? '') ?> <?= e($a['title']) ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php foreach ($allAmenities as $a): ?>
        <input type="checkbox" name="amenity_ids[]" value="<?= (int)$a['id'] ?>"
               id="am-<?= (int)$a['id'] ?>" class="jap-cb" style="display:none"
               <?= in_array((int)$a['id'], $roomAmenityIds, true) ? 'checked' : '' ?>>
        <?php endforeach; ?>
      </div>
      <style>
      .jura-amenity-picker{position:relative;user-select:none}
      .jap-selected{min-height:44px;border:1px solid var(--jura-border,#e2e8f0);border-radius:var(--jura-radius-sm,8px);padding:6px 40px 6px 8px;display:flex;flex-wrap:wrap;gap:6px;align-items:center;cursor:pointer;background:#fff;transition:border-color .15s}
      .jap-selected:focus-within,.jap-selected.open{border-color:var(--jura-primary,#4f46e5);box-shadow:0 0 0 3px var(--jura-primary-ring,rgba(79,70,229,.15))}
      .jap-tag{display:inline-flex;align-items:center;gap:4px;background:var(--jura-primary,#4f46e5);color:#fff;border-radius:20px;padding:3px 10px 3px 8px;font-size:.825rem;font-weight:500}
      .jap-remove{background:none;border:none;color:rgba(255,255,255,.8);cursor:pointer;padding:0 0 0 4px;font-size:14px;line-height:1;display:flex;align-items:center}
      .jap-remove:hover{color:#fff}
      .jap-placeholder{color:#94a3b8;font-size:.875rem;pointer-events:none}
      .jap-chevron{position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;font-size:12px}
      .jap-dropdown{position:absolute;z-index:100;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid var(--jura-border,#e2e8f0);border-radius:var(--jura-radius-sm,8px);box-shadow:0 8px 24px rgba(0,0,0,.1);max-height:220px;overflow-y:auto}
      .jap-option{padding:9px 14px;cursor:pointer;font-size:.875rem;transition:background .1s}
      .jap-option:hover{background:var(--jura-surface-muted,#f1f5f9)}
      .jap-option.selected{color:var(--jura-primary,#4f46e5);font-weight:600}
      .jap-option.selected::after{content:'✓';float:right;opacity:.7}
      </style>
      <script>
      (function(){
        var picker=document.getElementById('amenity-picker'),sel=document.getElementById('jap-selected'),drop=document.getElementById('jap-dropdown'),ph=document.getElementById('jap-placeholder');
        var chev=document.createElement('span');chev.className='jap-chevron';chev.textContent='▼';picker.appendChild(chev);
        function updatePlaceholder(){ph.style.display=picker.querySelectorAll('.jap-tag').length?'none':'';}
        function updateOption(id){var cb=document.getElementById('am-'+id),opt=drop.querySelector('[data-id="'+id+'"]');if(opt)opt.classList.toggle('selected',cb&&cb.checked);}
        sel.addEventListener('click',function(e){if(e.target.classList.contains('jap-remove'))return;var isOpen=!drop.hidden;drop.hidden=isOpen;sel.classList.toggle('open',!isOpen);});
        document.addEventListener('click',function(e){if(!picker.contains(e.target)){drop.hidden=true;sel.classList.remove('open');}});
        drop.querySelectorAll('.jap-option').forEach(function(opt){
          var id=opt.dataset.id,title=opt.dataset.title,cb=document.getElementById('am-'+id);
          updateOption(id);
          opt.addEventListener('click',function(e){e.stopPropagation();cb.checked=!cb.checked;
            if(cb.checked){var tag=document.createElement('span');tag.className='jap-tag';tag.dataset.id=id;tag.innerHTML=title+' <button type="button" class="jap-remove" data-id="'+id+'">&times;</button>';tag.querySelector('.jap-remove').addEventListener('click',function(){removeTag(id);});sel.insertBefore(tag,ph);}
            else{removeTag(id);}updateOption(id);updatePlaceholder();});
        });
        sel.querySelectorAll('.jap-remove').forEach(function(btn){btn.addEventListener('click',function(){removeTag(btn.dataset.id);});});
        function removeTag(id){var cb=document.getElementById('am-'+id);if(cb)cb.checked=false;var tag=sel.querySelector('.jap-tag[data-id="'+id+'"]');if(tag)tag.remove();updateOption(id);updatePlaceholder();}
        updatePlaceholder();
      })();
      </script>
      <?php endif; ?>
    </div>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Таблиця тарифів</h2>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem">Тарифи для різної кількості гостей (наприклад: «Без сніданку» на 1 особу — 1500 грн).</p>
    <table style="width:100%;border-collapse:collapse;margin-bottom:.75rem">
      <thead><tr style="font-size:.8rem;color:#64748b;text-align:left">
        <th style="padding:.3rem .5rem;font-weight:600">Назва тарифу</th>
        <th style="padding:.3rem .5rem;font-weight:600;width:110px">Гостей</th>
        <th style="padding:.3rem .5rem;font-weight:600;width:140px">Ціна (UAH/доб)</th>
        <th style="padding:.3rem .5rem;width:36px"></th>
      </tr></thead>
      <tbody id="rates-body">
      <?php foreach ($room_rates ?? [] as $i => $rt): ?>
        <tr class="rate-row">
          <td style="padding:.3rem .4rem"><input class="jura-input" name="rates[<?= $i ?>][tariff]" value="<?= e($rt['tariff']) ?>" placeholder="напр. Без сніданку" required style="width:100%"></td>
          <td style="padding:.3rem .4rem"><select class="jura-input" name="rates[<?= $i ?>][guests]"><?php for($g=1;$g<=4;$g++): ?><option value="<?=$g?>" <?=(int)$rt['guests']===$g?'selected':''?>><?=$g?> ос.</option><?php endfor; ?></select></td>
          <td style="padding:.3rem .4rem"><input class="jura-input" type="number" step="0.01" name="rates[<?= $i ?>][price]" value="<?= e($rt['price']) ?>" placeholder="0"></td>
          <td style="padding:.3rem .4rem"><button type="button" onclick="this.closest('tr').remove();reindexRates()" style="background:#fff1f2;color:#9f1239;border:1px solid #fecdd3;border-radius:6px;padding:.2rem .45rem;cursor:pointer">✕</button></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <button type="button" onclick="addRateRow()" class="jura-btn jura-btn-secondary" style="padding:.3rem .75rem">+ Додати тариф</button>
    <script>
    var _rateIdx=<?= count($room_rates ?? []) ?>;
    function addRateRow(){var i=_rateIdx++;var row=document.createElement('tr');row.className='rate-row';row.innerHTML='<td style="padding:.3rem .4rem"><input class="jura-input" name="rates['+i+'][tariff]" placeholder="напр. Без сніданку" required style="width:100%"></td><td style="padding:.3rem .4rem"><select class="jura-input" name="rates['+i+'][guests]"><option value="1">1 ос.</option><option value="2">2 ос.</option><option value="3">3 ос.</option><option value="4">4 ос.</option></select></td><td style="padding:.3rem .4rem"><input class="jura-input" type="number" step="0.01" name="rates['+i+'][price]" placeholder="0"></td><td style="padding:.3rem .4rem"><button type="button" onclick="this.closest(\'tr\').remove();reindexRates()" style="background:#fff1f2;color:#9f1239;border:1px solid #fecdd3;border-radius:6px;padding:.2rem .45rem;cursor:pointer">✕</button></td>';document.getElementById('rates-body').appendChild(row);}
    function reindexRates(){document.querySelectorAll('#rates-body .rate-row').forEach(function(row,i){row.querySelectorAll('[name]').forEach(function(el){el.name=el.name.replace(/rates\[\d+\]/,'rates['+i+']');});});_rateIdx=document.querySelectorAll('#rates-body .rate-row').length;}
    </script>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">Опис</h2>
    <label class="jura-label">Повний опис</label>
    <textarea class="jura-input" name="description" data-editor="simple-js-editor" rows="12"><?= e($r['description'] ?? '') ?></textarea>
  </section>

  <section class="jura-card" style="margin-bottom:1rem">
    <h2 style="margin-top:0">SEO та публікація</h2>
    <div class="jura-grid jura-grid-2" style="gap:1rem">
      <div>
        <label class="jura-label">Meta title</label>
        <input class="jura-input" name="meta_title" value="<?= e($r['meta_title'] ?? '') ?>">
      </div>
      <div>
        <label class="jura-label">Порядок сортування</label>
        <input class="jura-input" name="sort_order" type="number" value="<?= e($r['sort_order'] ?? '0') ?>">
      </div>
    </div>
    <div style="margin-top:1rem">
      <label class="jura-label">Meta description</label>
      <textarea class="jura-input" name="meta_description" rows="2"><?= e($r['meta_description'] ?? '') ?></textarea>
    </div>
    <div style="margin-top:1rem;display:flex;gap:2rem;align-items:center;flex-wrap:wrap">
      <div>
        <label class="jura-label">Статус</label>
        <select class="jura-input" name="status" style="max-width:200px">
          <?php foreach (['draft' => 'Чернетка', 'published' => 'Опублікований'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($r['status'] ?? 'draft') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="margin-top:1.4rem">
        <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
          <input type="checkbox" name="show_similar_rooms" value="1" <?= ($r['show_similar_rooms'] ?? 1) ? 'checked' : '' ?>>
          <span>Виводити схожі номери внизу сторінки</span>
        </label>
      </div>
    </div>
  </section>

</form>

<?php if (!$isCreate): ?>
<!-- Головне фото (окрема форма) -->
<section class="jura-card" style="margin-bottom:1rem">
  <h2 style="margin-top:0">Головне фото</h2>
  <?php if ($featuredImg): ?>
  <div style="display:flex;gap:1rem;align-items:flex-start;margin-bottom:1rem">
    <img src="/public/userfiles/rooms/<?= rawurlencode($featuredImg['title']) ?>" alt=""
         style="width:200px;height:140px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0">
    <form method="post" action="/admin/hotel/rooms/<?= $id ?>/image/<?= $featuredImg['id'] ?>/delete" style="margin:0">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <button class="jura-btn" type="submit" onclick="return confirm('Видалити головне фото?')"
        style="background:#fff1f2;color:#9f1239;border:1px solid #fecdd3;padding:.3rem .7rem">✕ Видалити фото</button>
    </form>
  </div>
  <?php else: ?>
  <p style="color:#94a3b8;font-size:.875rem;margin-bottom:.75rem">Головне фото не завантажено.</p>
  <?php endif; ?>
  <form method="post" action="/admin/hotel/rooms/<?= $id ?>/upload" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="is_featured" value="1">
    <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap">
      <input type="file" name="featured" accept="image/jpeg,image/png,image/webp" class="jura-input" style="flex:1;min-width:200px">
      <button class="jura-btn jura-btn-primary" type="submit">Завантажити</button>
    </div>
    <small style="color:#94a3b8">JPG, PNG або WebP. Замінить поточне головне фото.</small>
  </form>
</section>

<!-- Фотогалерея номера -->
<section class="jura-card">
  <h2 style="margin-top:0">Фотогалерея номера</h2>
  <?php if (!empty($galleryImgs)): ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.75rem;margin-bottom:1rem">
    <?php foreach ($galleryImgs as $img): ?>
    <div style="position:relative;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden">
      <img src="/public/userfiles/rooms/<?= rawurlencode($img['title']) ?>" alt="<?= e($img['alt'] ?? '') ?>"
           style="width:100%;aspect-ratio:4/3;object-fit:cover;display:block">
      <form method="post" action="/admin/hotel/rooms/<?= $id ?>/image/<?= $img['id'] ?>/delete"
            style="position:absolute;top:5px;right:5px;margin:0">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <button type="submit" onclick="return confirm('Видалити фото?')"
          style="background:rgba(0,0,0,.55);border:none;border-radius:50%;width:24px;height:24px;cursor:pointer;color:#fff;font-size:13px;line-height:1">&times;</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <p style="color:#94a3b8;font-size:.875rem;margin-bottom:.75rem">Фото галереї ще немає.</p>
  <?php endif; ?>
  <form method="post" action="/admin/hotel/rooms/<?= $id ?>/upload" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="is_featured" value="0">
    <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap">
      <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple class="jura-input" style="flex:1;min-width:200px">
      <button class="jura-btn jura-btn-primary" type="submit">Завантажити</button>
    </div>
    <small style="color:#94a3b8">Можна вибрати кілька фото одночасно (Ctrl+клік).</small>
  </form>
</section>
<?php else: ?>
<div class="jura-card" style="color:#94a3b8;font-size:.875rem">
  ☝ Спочатку збережіть номер — після цього стане доступне завантаження фото.
</div>
<?php endif; ?>

<div style="display:flex;gap:.75rem;margin-top:1.5rem;flex-wrap:wrap">
  <button form="room-main-form" class="jura-btn jura-btn-primary" type="submit">Зберегти</button>
  <button form="room-main-form" class="jura-btn jura-btn-secondary" type="submit" name="_close" value="1">Зберегти і закрити</button>
  <a class="jura-btn jura-btn-secondary" href="/admin/hotel/rooms">Скасувати</a>
</div>
