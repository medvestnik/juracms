<h1>System updates</h1>

<?php if (!empty($flash ?? null)): ?>
    <div class="jura-card"><p><?= e($flash); ?></p></div>
<?php endif; ?>

<?php if (!empty($requiresFinalize ?? false)): ?>
    <div class="jura-card">
        <h2>Требуется завершить обновление</h2>
        <p>Файлы CMS уже обновлены вручную. Нажмите кнопку, чтобы выполнить миграции, очистить кэш и зафиксировать installed_version.</p>
        <form method="post" action="/admin/system/updates/run">
            <button class="jura-btn jura-btn-primary" type="submit">Запустить обновление</button>
        </form>
    </div>
<?php endif; ?>

<div class="jura-card">
    <h2>Автоматическое обновление</h2>
    <p>Текущая версия: <strong><?= e((string) ($update['current_version'] ?? 'n/a')); ?></strong></p>
    <p>Целевая версия: <strong><?= e((string) ($update['target_version'] ?? 'n/a')); ?></strong></p>
    <div class="jura-actions">
        <a href="/admin/system/updates" class="jura-btn jura-btn-secondary">Проверить обновления</a>
        <form method="post" action="/admin/system/updates/run" style="display:inline;">
            <button class="jura-btn jura-btn-primary" type="submit">Обновить</button>
        </form>
    </div>
</div>
