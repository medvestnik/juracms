<?php
global $pdo;
$blocks = $blocks ?? [];
?>
<?php if (empty($blocks)): ?>
<section class="section">
  <div class="site-container">
    <div class="empty-state">Ця сторінка ще не має жодного блоку. Додайте їх у редакторі сторінки в адмінці.</div>
  </div>
</section>
<?php else: ?>
<?php foreach ($blocks as $block): ?>
<?= \App\Core\BlockRegistry::render((string) $block['block_type'], $block['settings'], $pdo) ?>
<?php endforeach; ?>
<?php endif; ?>
