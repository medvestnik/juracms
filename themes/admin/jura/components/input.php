<?php
$name = $name ?? 'field';
$label = $label ?? ucfirst($name);
$type = $type ?? 'text';
$value = $value ?? '';
$placeholder = $placeholder ?? '';
?>
<div class="jura-form-group" style="margin-bottom:1.1rem">
    <label class="jura-label" for="<?= e($name); ?>"><?= e($label); ?></label>
    <input
        class="jura-input"
        id="<?= e($name); ?>"
        name="<?= e($name); ?>"
        type="<?= e($type); ?>"
        value="<?= e($value); ?>"
        placeholder="<?= e($placeholder); ?>"
    >
</div>
