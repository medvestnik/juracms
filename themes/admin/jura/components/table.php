<?php
$headers = $headers ?? [];
$rows = $rows ?? [];
?>
<table class="jura-table">
    <thead>
    <tr>
        <?php foreach ($headers as $header): ?>
            <th><?= e($header); ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
        <tr>
            <td colspan="<?= e((string) max(1, count($headers))); ?>">No data yet.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($row as $cell): ?>
                    <td><?= e((string) $cell); ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
