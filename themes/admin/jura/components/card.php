<?php
$title = $title ?? '';
$content = $content ?? '';
?>
<article class="jura-card">
    <?php if ($title !== ''): ?><h3><?= e($title); ?></h3><?php endif; ?>
    <div><?= e($content); ?></div>
</article>
