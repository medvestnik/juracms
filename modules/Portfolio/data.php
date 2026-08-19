<?php

declare(strict_types=1);

if (!function_exists('portfolio_module_seed_rows')) {
    function portfolio_module_seed_rows(): array
    {
        // No default content — this is a generic module, so a fresh install
        // starts with an empty portfolio/projects list. Add items via
        // /admin/portfolio/items/create and /admin/portfolio/projects/create.
        return [];
    }
}

function portfolio_module_ensure_schema(PDO $pdo): void
{
    $table = jura_table('portfolio_items');
    if (!$pdo->query("SHOW COLUMNS FROM {$table} LIKE 'featured_home'")->fetch()) {
        $pdo->exec("ALTER TABLE {$table} ADD featured_home TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
        $urls = array_column(array_filter(portfolio_module_seed_rows(), static fn(array $row): bool => $row[9] === 1), 4);
        if ($urls) {
            $pdo->prepare("UPDATE {$table} SET featured_home=1 WHERE url IN (" . implode(',', array_fill(0, count($urls), '?')) . ')')->execute($urls);
        }
        portfolio_module_insert_missing_rows($pdo);
    }
}

function portfolio_module_insert_missing_rows(PDO $pdo): void
{
    $table = jura_table('portfolio_items');
    $exists = $pdo->prepare("SELECT 1 FROM {$table} WHERE kind=? AND url=? LIMIT 1");
    $insert = $pdo->prepare("INSERT INTO {$table} (kind,title,category,description,url,link_label,status_label,color,sort_order,featured_home) VALUES (?,?,?,?,?,?,?,?,?,?)");
    foreach (portfolio_module_seed_rows() as $row) {
        $exists->execute([$row[0], $row[4]]);
        if (!$exists->fetchColumn()) $insert->execute($row);
    }
}
