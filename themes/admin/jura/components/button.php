<?php
$label = $label ?? 'Button';
$type = $type ?? 'button';
$variant = $variant ?? 'primary';
$class = 'jura-btn ' . ($variant === 'secondary' ? 'jura-btn-secondary' : 'jura-btn-primary');
?>
<button type="<?= e($type); ?>" class="<?= e($class); ?>"><?= e($label); ?></button>
