<?php $entity = $entity ?? 'page'; $editor = editor_config(); ?>
<h1><?= e(ucfirst($entity)); ?> editor</h1>
<form class="jura-card" method="post" action="#">
    <label>
        Title
        <input type="text" name="title" value="" placeholder="Enter title">
    </label>

    <label>
        Content
        <textarea
            id="content-editor"
            name="content"
            rows="12"
            data-editor="<?= e((string) ($editor['default_editor'] ?? 'simple-js-editor')); ?>"
            data-upload-url="<?= e((string) ($editor['upload_url'] ?? '')); ?>"
            data-media-url="<?= e((string) ($editor['media_manager_url'] ?? '')); ?>"
        ></textarea>
    </label>

    <div class="jura-actions">
        <button class="jura-btn jura-btn-primary" type="submit">Save <?= e($entity); ?></button>
    </div>
</form>
