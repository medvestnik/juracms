<?php
$amenities = $amenities ?? [];
?>
<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
</div>

<section class="jura-card" style="margin-bottom:1.5rem">
  <h2 style="margin-top:0">Додати нову зручність</h2>
  <form method="post" action="/admin/hotel/amenities" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="_action" value="create">
    <div>
      <label class="jura-label">Назва <span style="color:red">*</span></label>
      <input class="jura-input" name="title" required placeholder="напр. Wi-Fi" style="width:220px">
    </div>
    <div>
      <label class="jura-label">Іконка (emoji)</label>
      <div style="display:flex;gap:.4rem;align-items:center">
        <input class="jura-input" id="new-icon" name="icon" placeholder="📶" style="width:70px;text-align:center;font-size:1.3rem">
        <button type="button" class="jura-btn jura-btn-secondary" onclick="openEmojiPicker('new-icon')" style="padding:.35rem .55rem">☺ Вибрати</button>
      </div>
    </div>
    <div>
      <label class="jura-label">Сортування</label>
      <input class="jura-input" type="number" name="sort_order" value="0" style="width:80px">
    </div>
    <div>
      <button class="jura-btn jura-btn-primary" type="submit">+ Додати</button>
    </div>
  </form>
</section>

<section class="jura-card">
  <h2 style="margin-top:0">Список зручностей (<?= count($amenities) ?>)</h2>
  <?php if (empty($amenities)): ?>
  <p style="color:#94a3b8">Зручностей ще немає. Додайте першу вище.</p>
  <?php else: ?>
  <table class="jura-table">
    <thead>
      <tr><th style="width:50px">Іконка</th><th>Назва</th><th style="width:100px">Сортування</th><th style="width:160px">Дії</th></tr>
    </thead>
    <tbody>
      <?php foreach ($amenities as $a): ?>
      <tr>
        <td style="text-align:center;font-size:1.4rem"><?= e($a['icon'] ?? '') ?></td>
        <td>
          <form method="post" action="/admin/hotel/amenities" style="display:flex;gap:.5rem;align-items:center;margin:0">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="_action" value="update">
            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
            <input class="jura-input" name="icon" id="icon-<?= (int)$a['id'] ?>" value="<?= e($a['icon'] ?? '') ?>" style="width:55px;text-align:center;font-size:1.1rem">
            <button type="button" class="jura-btn jura-btn-secondary" onclick="openEmojiPicker('icon-<?= (int)$a['id'] ?>')" style="padding:.25rem .45rem;font-size:.85rem">☺</button>
            <input class="jura-input" name="title" value="<?= e($a['title']) ?>" style="width:200px" required>
            <input class="jura-input" name="sort_order" type="number" value="<?= (int)$a['sort_order'] ?>" style="width:70px">
            <button class="jura-btn jura-btn-secondary" type="submit" style="padding:.3rem .7rem">✓</button>
          </form>
        </td>
        <td><?= (int)$a['sort_order'] ?></td>
        <td>
          <form method="post" action="/admin/hotel/amenities" style="margin:0;display:inline">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
            <button class="jura-btn" type="submit" onclick="return confirm('Видалити «<?= e($a['title']) ?>»?')"
              style="background:#fff1f2;color:#9f1239;border:1px solid #fecdd3;padding:.3rem .7rem">✕ Видалити</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>

<!-- Emoji Picker Modal -->
<div id="emoji-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.4);align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:1.25rem;max-width:460px;width:95%;box-shadow:0 8px 32px rgba(0,0,0,.2)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem">
      <strong>Вибрати іконку</strong>
      <button type="button" onclick="closeEmojiPicker()" style="background:none;border:none;cursor:pointer;font-size:1.2rem;color:#6b7280">✕</button>
    </div>
    <input id="emoji-search" class="jura-input" placeholder="Пошук..." style="margin-bottom:.75rem" oninput="filterEmojis(this.value)">
    <div id="emoji-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(42px,1fr));gap:4px;max-height:320px;overflow-y:auto"></div>
  </div>
</div>

<script>
var _emojiTarget = null;
var _allEmojis = [
  // Зв'язок і техніка
  ['📶','Wi-Fi'],['📡','Антена/супутник'],['📺','Телевізор'],['📻','Радіо'],['💻','Ноутбук/стіл'],['🖥️','Монітор'],['📱','Телефон'],['📞','Стаціонарний телефон'],['🔌','USB/розетки'],['🎮','ТВ-приставка'],['🔋','Зарядка'],['📷','Камера'],
  // Клімат
  ['❄️','Кондиціонер'],['🌡️','Опалення'],['🌬️','Вентиляція/вентилятор'],['♨️','Тепла підлога'],['🌤️','Свіже повітря'],
  // Безпека
  ['🔒','Сейф'],['🔐','Замок'],['🔑','Ключ/картка'],['🛡️','Охорона'],['🚨','Сигналізація'],['📹','Відеоспостереження'],
  // Ванна кімната
  ['🛁','Ванна'],['🚿','Душ'],['🪥','Фен'],['🧴','Гель/шампунь'],['🧼','Мило'],['🪒','Бритва'],['🪞','Дзеркало'],['🧻','Рушники'],['🚽','Туалет'],['🛀','Джакузі'],
  // Спальня
  ['🛏️','Ліжко'],['🪑','Крісло'],['🛋️','Диван'],['🪟','Балкон/вікно'],['🌅','Вид на море/місто'],['🌃','Нічний вид'],['💤','Тихий номер'],
  // Їжа і напої
  ['☕','Кава'],['🍵','Чай'],['🫖','Чайник'],['🍳','Сніданок'],['🍽️','Ресторан'],['🍸','Міні-бар'],['🧃','Напої'],['🍷','Вино'],['🥐','Випічка'],['🍾','Шампанське'],
  // Холодильник і кухня
  ['🗄️','Холодильник'],['🥶','Морозилка'],['🫙','Мікрохвильовка'],['🍴','Кухня/кухнетка'],['🔪','Посуд'],
  // Прання
  ['🧺','Прання'],['👔','Праска/праскування'],['🧵','Швейний набір'],
  // Розваги та відпочинок
  ['🏊','Басейн'],['🧖','СПА/масаж'],['🏋️','Тренажерний зал'],['🎯','Ігрова кімната'],['🎵','Музика/аудіо'],['🎪','Розваги'],['🧘','Йога/медитація'],['🎨','Творчі заняття'],
  // Транспорт
  ['🚗','Парковка'],['🚌','Трансфер'],['🚁','Вертоліт/трансфер'],['🚲','Велосипед'],['🛴','Самокат'],['🚖','Таксі'],
  // Природа і вид
  ['🏖️','Пляж'],['🌊','Море'],['⛰️','Гори'],['🌲','Парк/ліс'],['🌸','Сад'],['🌿','Природа'],['🌴','Тропіки'],
  // Послуги
  ['🛎️','Обслуговування номерів'],['🧹','Прибирання'],['💼','Бізнес-послуги'],['📰','Газети'],['🩺','Аптечка'],['💊','Медична допомога'],['🌐','Консьєрж'],['👨‍💼','Персонал'],
  // Будівля
  ['🛗','Ліфт'],['♿','Доступність'],['🏢','Бізнес-центр'],['🏦','Банкомат'],['🏪','Магазин'],['💈','Перукарня'],['💅','Манікюр'],
  // Тварини
  ['🐾','Тварини дозволені'],['🐕','Собаки'],['🐈','Коти'],
  // Природне освітлення
  ['💡','Освітлення'],['🕯️','Свічки/романтика'],['🌙','Нічне освітлення'],
  // Додаткове
  ['📦','Зберігання'],['🧳','Камера схову'],['🎁','Подарунок/привітання'],['🎂','День народження'],['💐','Квіти'],['🪴','Рослини в номері'],
];

function openEmojiPicker(targetId) {
  _emojiTarget = targetId;
  document.getElementById('emoji-search').value = '';
  renderEmojis(_allEmojis);
  var m = document.getElementById('emoji-modal');
  m.style.display = 'flex';
  document.getElementById('emoji-search').focus();
}
function closeEmojiPicker() {
  document.getElementById('emoji-modal').style.display = 'none';
}
function pickEmoji(em) {
  if (_emojiTarget) document.getElementById(_emojiTarget).value = em;
  closeEmojiPicker();
}
function renderEmojis(list) {
  var g = document.getElementById('emoji-grid');
  g.innerHTML = list.map(function(e){
    return '<button type="button" title="'+e[1]+'" onclick="pickEmoji(\''+e[0]+'\')" style="background:none;border:1px solid #e5e7eb;border-radius:6px;padding:6px;font-size:1.4rem;cursor:pointer;transition:background .1s" onmouseover="this.style.background=\'#f3f4f6\'" onmouseout="this.style.background=\'none\'">'+e[0]+'</button>';
  }).join('');
}
function filterEmojis(q) {
  if (!q) { renderEmojis(_allEmojis); return; }
  q = q.toLowerCase();
  renderEmojis(_allEmojis.filter(function(e){ return e[1].toLowerCase().includes(q) || e[0].includes(q); }));
}
document.getElementById('emoji-modal').addEventListener('click', function(e){
  if (e.target === this) closeEmojiPicker();
});
</script>
