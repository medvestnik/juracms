<?php
$type = $type ?? 'info';
$message = $message ?? '';
?>
<div class="jura-alert" data-type="<?= e($type); ?>"><?= e($message); ?></div>
