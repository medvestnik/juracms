<?php
$id = $id ?? 'modal';
$title = $title ?? 'Modal';
$content = $content ?? '';
?>
<div class="jura-modal" id="<?= e($id); ?>" hidden>
    <div class="jura-modal__backdrop" data-close-modal="<?= e($id); ?>"></div>
    <div class="jura-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="<?= e($id); ?>-title">
        <h3 id="<?= e($id); ?>-title"><?= e($title); ?></h3>
        <p><?= e($content); ?></p>
        <button type="button" class="jura-btn jura-btn-secondary" data-close-modal="<?= e($id); ?>">Close</button>
    </div>
</div>
