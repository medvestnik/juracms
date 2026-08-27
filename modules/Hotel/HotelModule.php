<?php

declare(strict_types=1);

function hotel_ensure_schema(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('hotel_rooms') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,slug VARCHAR(191) UNIQUE,title VARCHAR(191),excerpt TEXT NULL,description MEDIUMTEXT NULL,status VARCHAR(20) DEFAULT 'draft',area VARCHAR(80) NULL,capacity VARCHAR(80) NULL,price_from DECIMAL(12,2) DEFAULT 0,currency VARCHAR(10) DEFAULT 'UAH',amenities TEXT NULL,featured_image_id INT UNSIGNED NULL,meta_title VARCHAR(191) NULL,meta_description TEXT NULL,sort_order INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('hotel_room_images') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,room_id INT UNSIGNED,media_file_id INT UNSIGNED,alt VARCHAR(191) NULL,title VARCHAR(191) NULL,sort_order INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('hotel_amenities') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,title VARCHAR(191) NOT NULL,icon VARCHAR(20) NULL,sort_order INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('hotel_room_amenities') . " (room_id INT UNSIGNED NOT NULL,amenity_id INT UNSIGNED NOT NULL,PRIMARY KEY(room_id,amenity_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('hotel_promotions') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,slug VARCHAR(191) UNIQUE,title VARCHAR(191),excerpt TEXT NULL,content MEDIUMTEXT NULL,status VARCHAR(20) DEFAULT 'draft',featured_image_id INT UNSIGNED NULL,meta_title VARCHAR(191) NULL,meta_description TEXT NULL,sort_order INT DEFAULT 0,published_at TIMESTAMP NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('hotel_room_rates') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,room_id INT UNSIGNED NOT NULL,tariff VARCHAR(191) NOT NULL,guests TINYINT UNSIGNED DEFAULT 1,price DECIMAL(12,2) DEFAULT 0,sort_order INT DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // hotel_galleries must exist before the column-backfill loop below,
    // since that loop ALTERs it (locale column) -- on a brand-new install
    // (no tables yet at all) creating it after the loop instead meant the
    // loop's SHOW COLUMNS FROM hotel_galleries threw an uncaught
    // PDOException on every single request (this function doubles as the
    // ensure_schema hook, which runs on every admin page), a permanent
    // white-page crash the moment the module was first installed.
    $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('hotel_galleries') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,slug VARCHAR(191) UNIQUE,title VARCHAR(191),description TEXT NULL,status VARCHAR(20) DEFAULT 'active',meta_title VARCHAR(191) NULL,meta_description TEXT NULL,sort_order INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('hotel_gallery_images') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,gallery_id INT UNSIGNED,media_file_id INT UNSIGNED,alt VARCHAR(191) NULL,title VARCHAR(191) NULL,sort_order INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Add featured_image and show_similar_rooms columns if missing
    foreach ([
        ['hotel_rooms', 'featured_image', 'VARCHAR(255) NULL'],
        ['hotel_rooms', 'show_similar_rooms', 'TINYINT(1) NOT NULL DEFAULT 1'],
        ['hotel_room_images', 'is_featured', 'TINYINT(1) NOT NULL DEFAULT 0'],
        // Empty locale = shown for every locale (matches the fallback used
        // for pages/posts) -- lets existing rooms/promotions/galleries
        // keep showing up without an admin having to tag them.
        ['hotel_rooms', 'locale', "VARCHAR(16) NOT NULL DEFAULT ''"],
        ['hotel_promotions', 'locale', "VARCHAR(16) NOT NULL DEFAULT ''"],
        ['hotel_galleries', 'locale', "VARCHAR(16) NOT NULL DEFAULT ''"],
    ] as [$tbl, $col, $def]) {
        $tn = str_replace('`', '', jura_table($tbl));
        if (!$pdo->query("SHOW COLUMNS FROM `{$tn}` LIKE " . $pdo->quote($col))->fetch()) {
            $pdo->exec("ALTER TABLE `{$tn}` ADD `{$col}` {$def}");
        }
    }
    // Tourist tax default settings + page-content shortcode placeholders (see hotel_filter_page_content)
    foreach ([
        ['hotel_tourist_tax_ua_enabled',      '0',           'hotel'],
        ['hotel_tourist_tax_ua_rate',         '43,24',       'hotel'],
        ['hotel_tourist_tax_ua_note',         'ставка без змін', 'hotel'],
        ['hotel_tourist_tax_foreign_enabled', '0',           'hotel'],
        ['hotel_tourist_tax_foreign_rate',    '86,47',       'hotel'],
        ['hotel_tourist_tax_foreign_badge',   '',            'hotel'],
        ['hotel_tourist_tax_foreign_note',    '',            'hotel'],
        ['hotel_tourist_tax_extra_note',      '',            'hotel'],
        ['booking_page_form',                 '',            'hotel'],
        ['booking_search_form',               '',            'hotel'],
        ['booking_widget_head',               '',            'hotel'],
    ] as [$key, $val, $group]) {
        $pdo->prepare('INSERT IGNORE INTO ' . jura_table('settings') . ' (setting_key,setting_value,setting_type,group_name) VALUES (?,?,?,?)')
            ->execute([$key, $val, 'string', $group]);
    }
    hotel_seed_pages($pdo);
}

// Creates editable jura_pages/routes for /rooms and /booking (if missing) so
// they show up in Сторінки/Меню like any other page, and can carry admin-
// written intro content above the module's own listing/booking-form markup
// -- see hotel_render_page_template() and the render_page_template hook.
function hotel_seed_pages(PDO $pdo): void
{
    $authorId = (int) $pdo->query('SELECT id FROM ' . jura_table('users') . ' ORDER BY id LIMIT 1')->fetchColumn();
    foreach ([
        ['rooms', 'Номери', 'hotel_rooms', ''],
        ['booking', 'Бронювання', 'hotel_booking', '{{ booking-page-form }}'],
    ] as [$slug, $title, $template, $content]) {
        $existing = $pdo->prepare('SELECT id FROM ' . jura_table('pages') . ' WHERE slug=? LIMIT 1');
        $existing->execute([$slug]);
        $pageId = (int) $existing->fetchColumn();
        if (!$pageId) {
            $ins = $pdo->prepare('INSERT INTO ' . jura_table('pages') . ' (author_id,title,slug,content,status,template,meta_title,sort_order,published_at) VALUES (?,?,?,?,?,?,?,?,NOW())');
            $ins->execute([$authorId, $title, $slug, $content, 'published', $template, $title, 0]);
            $pageId = (int) $pdo->lastInsertId();
        }
        $pdo->prepare('INSERT IGNORE INTO ' . jura_table('routes') . ' (path,entity_type,entity_id,status) VALUES (?,?,?,?)')
            ->execute(['/' . $slug, 'page', $pageId, 'active']);
    }
}

function hotel_save_rates(PDO $pdo, int $roomId, array $rates): void
{
    $pdo->prepare('DELETE FROM ' . jura_table('hotel_room_rates') . ' WHERE room_id=?')->execute([$roomId]);
    $sort = 0;
    foreach ($rates as $rt) {
        $tariff = trim((string)($rt['tariff'] ?? ''));
        $guests = (int)($rt['guests'] ?? 1);
        $price  = (float)($rt['price'] ?? 0);
        if ($tariff === '' || $guests < 1) continue;
        $pdo->prepare('INSERT INTO ' . jura_table('hotel_room_rates') . ' (room_id,tariff,guests,price,sort_order) VALUES (?,?,?,?,?)')
            ->execute([$roomId, $tariff, $guests, $price, $sort++]);
    }
}

// ── Hook: admin_stats ───────────────────────────────────────────────────────
// Contributes hotel counters to the admin dashboard stat row via
// ModuleLoader::hookCollect('admin_stats', $pdo) — see index.php admin_stats().
function hotel_admin_stats(PDO $pdo): array
{
    $stats = [];
    foreach (['hotel_rooms', 'hotel_promotions', 'hotel_amenities'] as $table) {
        try {
            $stats[$table] = (int) $pdo->query('SELECT COUNT(*) FROM ' . jura_table($table))->fetchColumn();
        } catch (\Throwable $e) {
            $stats[$table] = 0;
        }
    }
    return $stats;
}

// ── Hook: dashboard_widgets ──────────────────────────────────────────────────
// Renders a hotel stat-card row + quick actions into the admin dashboard via
// ModuleLoader::hookRender('dashboard_widgets', $stats) — see
// themes/admin/jura/views/dashboard.php.
function hotel_dashboard_widgets(array $stats): string
{
    $rooms = (int) ($stats['hotel_rooms'] ?? 0);
    $leads = (int) ($stats['leads'] ?? 0);

    ob_start();
    ?>
    <div class="jura-grid jura-grid-4" style="margin-bottom:1.5rem">
      <a href="/admin/hotel/rooms" class="jura-card jura-card-hover" style="text-decoration:none;display:block">
        <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--jura-text-muted)">Номери</div>
        <span class="jura-stat-value"><?= $rooms ?></span>
      </a>
      <a href="/admin/hotel/leads" class="jura-card jura-card-hover" style="text-decoration:none;display:block">
        <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--jura-text-muted)">Заявки (готель)</div>
        <span class="jura-stat-value"><?= $leads ?></span>
      </a>
    </div>

    <div class="jura-grid jura-grid-2" style="margin-bottom:1.5rem">
      <section class="jura-card">
        <h2 class="jura-card-title" style="margin-bottom:1rem">Готель — швидкі дії</h2>
        <div style="display:flex;flex-wrap:wrap;gap:.6rem">
          <a href="/admin/hotel/rooms/create" class="jura-btn jura-btn-primary">+ Новий номер</a>
          <a href="/admin/hotel/promotions/create" class="jura-btn jura-btn-secondary">+ Нова акція</a>
          <a href="/admin/hotel/amenities" class="jura-btn jura-btn-secondary">Зручності</a>
          <a href="/admin/hotel/galleries" class="jura-btn jura-btn-secondary">Галереї</a>
        </div>
      </section>
      <section class="jura-card">
        <h2 class="jura-card-title" style="margin-bottom:1rem">Сторінки готелю</h2>
        <div style="display:grid;gap:.6rem">
          <a href="/rooms" target="_blank" rel="noopener" style="font-size:.875rem">↗ Номери</a>
        </div>
      </section>
    </div>
    <?php
    return (string) ob_get_clean();
}

// ── Hook: home_data ──────────────────────────────────────────────────────────
// Feeds featured rooms/promotions into the home template via
// ModuleLoader::hookCollect('home_data', $pdo) — see index.php's home-template
// branch and themes/frontend/default/views/home.php.
function hotel_home_data(PDO $pdo, string $locale = ''): array
{
    $featuredRooms = [];
    $featuredPromotions = [];
    try {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('hotel_rooms') . " WHERE status='published' AND (locale=? OR locale='') ORDER BY sort_order,id LIMIT 3");
        $stmt->execute([$locale]);
        $featuredRooms = $stmt->fetchAll();
    } catch (\Throwable $e) {
        $featuredRooms = [];
    }
    try {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('hotel_promotions') . " WHERE status='published' AND (locale=? OR locale='') ORDER BY sort_order,id DESC LIMIT 3");
        $stmt->execute([$locale]);
        $featuredPromotions = $stmt->fetchAll();
    } catch (\Throwable $e) {
        $featuredPromotions = [];
    }
    return ['featured_rooms' => $featuredRooms, 'featured_promotions' => $featuredPromotions];
}

// ── Hook: filter_page_content ────────────────────────────────────────────────
// Replaces the hotel booking shortcodes in page content via
// ModuleLoader::hookFilter('filter_page_content', $content, $settings) — see
// index.php's page-render branch.
function hotel_filter_page_content(string $content, array $settings): string
{
    return strtr($content, [
        '{{ booking-page-form }}'   => $settings['booking_page_form']   ?? '',
        '{{ booking-search-form }}' => $settings['booking_search_form'] ?? '',
    ]);
}

// ── Hook: head_scripts ───────────────────────────────────────────────────────
// Injects the Exely head_script snippet (head_script-uk from the Exely
// booking-engine archive) into <head> on every frontend page, via
// ModuleLoader::hookRender('head_scripts', $settings) — see
// themes/frontend/default/layouts/app.php.
function hotel_head_scripts(array $settings): string
{
    return (string) ($settings['booking_widget_head'] ?? '');
}

function hotel_handle_admin(string $path, string $method, PDO $pdo): bool
{
    // --- Amenities CRUD ---
    if ($path === '/admin/hotel/amenities') {
        if ($method === 'POST' && ($_POST['_action'] ?? '') === 'create') {
            $pdo->prepare('INSERT INTO ' . jura_table('hotel_amenities') . ' (title,icon,sort_order) VALUES (?,?,?)')
                ->execute([trim($_POST['title'] ?? ''), trim($_POST['icon'] ?? ''), (int)($_POST['sort_order'] ?? 0)]);
        }
        if ($method === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
            $aid = (int)($_POST['id'] ?? 0);
            $pdo->prepare('DELETE FROM ' . jura_table('hotel_room_amenities') . ' WHERE amenity_id=?')->execute([$aid]);
            $pdo->prepare('DELETE FROM ' . jura_table('hotel_amenities') . ' WHERE id=?')->execute([$aid]);
        }
        if ($method === 'POST' && ($_POST['_action'] ?? '') === 'update') {
            $aid = (int)($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE ' . jura_table('hotel_amenities') . ' SET title=?,icon=?,sort_order=? WHERE id=?')
                ->execute([trim($_POST['title'] ?? ''), trim($_POST['icon'] ?? ''), (int)($_POST['sort_order'] ?? 0), $aid]);
        }
        if ($method === 'POST') { redirect('/admin/hotel/amenities'); }
        $amenities = $pdo->query('SELECT * FROM ' . jura_table('hotel_amenities') . ' ORDER BY sort_order,id')->fetchAll();
        view_admin('hotel/amenities', ['title' => 'Зручності', 'amenities' => $amenities]);
        return true;
    }

    // --- Rooms ---
    if ($path === '/admin/hotel/rooms') {
        view_admin('hotel/rooms', ['title' => 'Rooms', 'rooms' => $pdo->query('SELECT * FROM ' . jura_table('hotel_rooms') . ' ORDER BY sort_order,id')->fetchAll()]);
        return true;
    }

    if ($path === '/admin/hotel/rooms/create') {
        if ($method === 'POST') {
            $slug = trim((string) ($_POST['slug'] ?: slugify((string) $_POST['title'])));
            $pdo->prepare('INSERT INTO ' . jura_table('hotel_rooms') . ' (slug,title,excerpt,description,status,area,capacity,price_from,currency,meta_title,meta_description,sort_order,locale) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$slug, $_POST['title'], $_POST['excerpt'] ?? '', $_POST['description'] ?? '', $_POST['status'] ?? 'draft', $_POST['area'] ?? '', $_POST['capacity'] ?? '', (float)($_POST['price_from'] ?? 0), $_POST['currency'] ?? 'UAH', $_POST['meta_title'] ?? '', $_POST['meta_description'] ?? '', (int)($_POST['sort_order'] ?? 0), (string)($_POST['locale'] ?? '')]);
            $roomId = (int)$pdo->lastInsertId();
            $pdo->prepare('DELETE FROM ' . jura_table('hotel_room_amenities') . ' WHERE room_id=?')->execute([$roomId]);
            foreach (array_map('intval', $_POST['amenity_ids'] ?? []) as $aid) {
                if ($aid > 0) $pdo->prepare('INSERT IGNORE INTO ' . jura_table('hotel_room_amenities') . ' (room_id,amenity_id) VALUES (?,?)')->execute([$roomId, $aid]);
            }
            hotel_save_rates($pdo, $roomId, $_POST['rates'] ?? []);
            redirect(isset($_POST['_close']) ? '/admin/hotel/rooms' : '/admin/hotel/rooms/' . $roomId . '/edit');
        }
        $allAmenities = $pdo->query('SELECT * FROM ' . jura_table('hotel_amenities') . ' ORDER BY sort_order,id')->fetchAll();
        $allLocales = jura_available_locales($pdo, 'code,native_name');
        view_admin('hotel/room-edit', ['title' => 'Новий номер', 'room' => [], 'all_amenities' => $allAmenities, 'room_amenity_ids' => [], 'room_rates' => [], 'all_locales' => $allLocales]);
        return true;
    }

    if (preg_match('#^/admin/hotel/rooms/(\d+)/edit$#', $path, $matches)) {
        $id = (int)$matches[1];
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('hotel_rooms') . ' WHERE id=?');
        $stmt->execute([$id]);
        $room = $stmt->fetch();
        if ($method === 'POST') {
            $slug = trim((string)($_POST['slug'] ?: slugify((string)$_POST['title'])));
            $pdo->prepare('UPDATE ' . jura_table('hotel_rooms') . ' SET slug=?,title=?,excerpt=?,description=?,status=?,area=?,capacity=?,price_from=?,currency=?,meta_title=?,meta_description=?,sort_order=?,show_similar_rooms=?,locale=?,updated_at=NOW() WHERE id=?')
                ->execute([$slug, $_POST['title'], $_POST['excerpt'] ?? '', $_POST['description'] ?? '', $_POST['status'] ?? 'draft', $_POST['area'] ?? '', $_POST['capacity'] ?? '', (float)($_POST['price_from'] ?? 0), $_POST['currency'] ?? 'UAH', $_POST['meta_title'] ?? '', $_POST['meta_description'] ?? '', (int)($_POST['sort_order'] ?? 0), isset($_POST['show_similar_rooms']) ? 1 : 0, (string)($_POST['locale'] ?? ''), $id]);
            $pdo->prepare('DELETE FROM ' . jura_table('hotel_room_amenities') . ' WHERE room_id=?')->execute([$id]);
            foreach (array_map('intval', $_POST['amenity_ids'] ?? []) as $aid) {
                if ($aid > 0) $pdo->prepare('INSERT IGNORE INTO ' . jura_table('hotel_room_amenities') . ' (room_id,amenity_id) VALUES (?,?)')->execute([$id, $aid]);
            }
            hotel_save_rates($pdo, $id, $_POST['rates'] ?? []);
            redirect(isset($_POST['_close']) ? '/admin/hotel/rooms' : '/admin/hotel/rooms/' . $id . '/edit');
        }
        $allAmenities = $pdo->query('SELECT * FROM ' . jura_table('hotel_amenities') . ' ORDER BY sort_order,id')->fetchAll();
        $roomAmenityIds = $pdo->query('SELECT amenity_id FROM ' . jura_table('hotel_room_amenities') . ' WHERE room_id=' . $id)->fetchAll(PDO::FETCH_COLUMN, 0);
        $roomRates = $pdo->query('SELECT * FROM ' . jura_table('hotel_room_rates') . ' WHERE room_id=' . $id . ' ORDER BY sort_order,id')->fetchAll();
        $roomImages = $pdo->query('SELECT * FROM ' . jura_table('hotel_room_images') . ' WHERE room_id=' . $id . ' ORDER BY is_featured DESC,sort_order,id')->fetchAll();
        $allLocales = jura_available_locales($pdo, 'code,native_name');
        view_admin('hotel/room-edit', ['title' => 'Редагувати номер', 'room' => $room, 'all_amenities' => $allAmenities, 'room_amenity_ids' => array_map('intval', $roomAmenityIds), 'room_rates' => $roomRates, 'room_images' => $roomImages, 'all_locales' => $allLocales]);
        return true;
    }

    if (preg_match('#^/admin/hotel/rooms/(\d+)/upload$#', $path, $matches) && $method === 'POST') {
        $id = (int)$matches[1];
        $uploadDir = BASE_PATH . '/public/userfiles/rooms/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
        $isFeatured = (int)(($_POST['is_featured'] ?? '0') === '1');
        $filesKey = $isFeatured ? 'featured' : 'images';
        $files = $_FILES[$filesKey] ?? [];
        if ($isFeatured && isset($files['tmp_name']) && is_uploaded_file($files['tmp_name'])) {
            $ext = strtolower(pathinfo((string)$files['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'], true)) {
                $filename = 'room_' . $id . '_featured_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($files['tmp_name'], $uploadDir . $filename)) {
                    // Remove old featured
                    $old = $pdo->query('SELECT id,title FROM ' . jura_table('hotel_room_images') . ' WHERE room_id=' . $id . ' AND is_featured=1')->fetchAll();
                    foreach ($old as $o) {
                        @unlink(BASE_PATH . '/public/userfiles/rooms/' . $o['title']);
                        $pdo->prepare('DELETE FROM ' . jura_table('hotel_room_images') . ' WHERE id=?')->execute([$o['id']]);
                    }
                    $pdo->prepare('INSERT INTO ' . jura_table('hotel_room_images') . ' (room_id,is_featured,alt,title,sort_order) VALUES (?,1,?,?,0)')
                        ->execute([$id, $room['title'] ?? '', $filename]);
                    // Also update hotel_rooms.featured_image
                    $pdo->prepare('UPDATE ' . jura_table('hotel_rooms') . ' SET featured_image=? WHERE id=?')->execute([$filename, $id]);
                }
            }
        } else {
            $sort = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM ' . jura_table('hotel_room_images') . ' WHERE room_id=' . $id . ' AND is_featured=0')->fetchColumn();
            foreach ($files['tmp_name'] ?? [] as $i => $tmp) {
                if (!is_uploaded_file($tmp)) continue;
                $ext = strtolower(pathinfo((string)($files['name'][$i] ?? ''), PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) continue;
                $filename = 'room_' . $id . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($tmp, $uploadDir . $filename)) {
                    $pdo->prepare('INSERT INTO ' . jura_table('hotel_room_images') . ' (room_id,is_featured,alt,title,sort_order) VALUES (?,0,?,?,?)')
                        ->execute([$id, pathinfo((string)($files['name'][$i] ?? ''), PATHINFO_FILENAME), $filename, $sort++]);
                }
            }
        }
        redirect('/admin/hotel/rooms/' . $id . '/edit');
        return true;
    }

    if (preg_match('#^/admin/hotel/rooms/(\d+)/image/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
        $roomId = (int)$matches[1]; $imgId = (int)$matches[2];
        $row = $pdo->prepare('SELECT title,is_featured FROM ' . jura_table('hotel_room_images') . ' WHERE id=? AND room_id=?');
        $row->execute([$imgId, $roomId]);
        $img = $row->fetch();
        if ($img) {
            @unlink(BASE_PATH . '/public/userfiles/rooms/' . $img['title']);
            if ($img['is_featured']) {
                $pdo->prepare('UPDATE ' . jura_table('hotel_rooms') . ' SET featured_image=NULL WHERE id=?')->execute([$roomId]);
            }
            $pdo->prepare('DELETE FROM ' . jura_table('hotel_room_images') . ' WHERE id=? AND room_id=?')->execute([$imgId, $roomId]);
        }
        redirect('/admin/hotel/rooms/' . $roomId . '/edit');
        return true;
    }

    if (preg_match('#^/admin/hotel/rooms/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
        $id = (int)$matches[1];
        // Clean up image files
        $imgs = $pdo->query('SELECT title FROM ' . jura_table('hotel_room_images') . ' WHERE room_id=' . $id)->fetchAll();
        foreach ($imgs as $img) { @unlink(BASE_PATH . '/public/userfiles/rooms/' . $img['title']); }
        $pdo->prepare('DELETE FROM ' . jura_table('hotel_room_images') . ' WHERE room_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM ' . jura_table('hotel_room_amenities') . ' WHERE room_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM ' . jura_table('hotel_room_rates') . ' WHERE room_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM ' . jura_table('hotel_rooms') . ' WHERE id=?')->execute([$id]);
        redirect('/admin/hotel/rooms');
        return true;
    }

    if (preg_match('#^/admin/hotel/rooms/(\d+)/toggle$#', $path, $matches) && $method === 'POST') {
        $id = (int)$matches[1];
        $row = $pdo->prepare('SELECT status FROM ' . jura_table('hotel_rooms') . ' WHERE id=?');
        $row->execute([$id]);
        $cur = (string)($row->fetchColumn() ?: 'draft');
        $next = $cur === 'published' ? 'draft' : 'published';
        $pdo->prepare('UPDATE ' . jura_table('hotel_rooms') . ' SET status=?,updated_at=NOW() WHERE id=?')->execute([$next, $id]);
        redirect('/admin/hotel/rooms');
        return true;
    }

    if ($path === '/admin/hotel/promotions') {
        view_admin('hotel/promotions', ['title' => 'Promotions', 'promotions' => $pdo->query('SELECT * FROM ' . jura_table('hotel_promotions') . ' ORDER BY sort_order,id DESC')->fetchAll()]);
        return true;
    }

    if ($path === '/admin/hotel/promotions/create') {
        if ($method === 'POST') {
            $slug = trim((string) ($_POST['slug'] ?: slugify((string) $_POST['title'])));
            $status = (string) ($_POST['status'] ?? 'draft');
            $pdo->prepare('INSERT INTO ' . jura_table('hotel_promotions') . ' (slug,title,excerpt,content,status,meta_title,meta_description,sort_order,locale,published_at) VALUES (?,?,?,?,?,?,?,?,?,CASE WHEN ?="published" THEN NOW() ELSE NULL END)')
                ->execute([$slug, $_POST['title'], $_POST['excerpt'] ?? '', $_POST['content'] ?? '', $status, $_POST['meta_title'] ?? '', $_POST['meta_description'] ?? '', (int) ($_POST['sort_order'] ?? 0), (string) ($_POST['locale'] ?? ''), $status]);
            redirect('/admin/hotel/promotions');
        }
        $allLocales = jura_available_locales($pdo, 'code,native_name');
        view_admin('hotel/promotion-edit', ['title' => 'Нова акція', 'promotion' => [], 'all_locales' => $allLocales]);
        return true;
    }

    if (preg_match('#^/admin/hotel/promotions/(\d+)/edit$#', $path, $matches)) {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('hotel_promotions') . ' WHERE id=?');
        $stmt->execute([(int) $matches[1]]);
        $promo = $stmt->fetch();
        if ($method === 'POST') {
            $id = (int) $matches[1];
            $slug = trim((string) ($_POST['slug'] ?: slugify((string) $_POST['title'])));
            $status = (string) ($_POST['status'] ?? 'draft');
            $pdo->prepare('UPDATE ' . jura_table('hotel_promotions') . ' SET slug=?,title=?,excerpt=?,content=?,status=?,meta_title=?,meta_description=?,sort_order=?,locale=?,updated_at=NOW() WHERE id=?')
                ->execute([$slug, $_POST['title'], $_POST['excerpt'] ?? '', $_POST['content'] ?? '', $status, $_POST['meta_title'] ?? '', $_POST['meta_description'] ?? '', (int) ($_POST['sort_order'] ?? 0), (string) ($_POST['locale'] ?? ''), $id]);
            redirect('/admin/hotel/promotions');
        }
        $allLocales = jura_available_locales($pdo, 'code,native_name');
        view_admin('hotel/promotion-edit', ['title' => 'Редагувати акцію', 'promotion' => $promo, 'all_locales' => $allLocales]);
        return true;
    }

    if ($path === '/admin/hotel/galleries') {
        view_admin('hotel/galleries', ['title' => 'Galleries', 'galleries' => $pdo->query('SELECT * FROM ' . jura_table('hotel_galleries') . ' ORDER BY sort_order,id')->fetchAll()]);
        return true;
    }

    if ($path === '/admin/hotel/galleries/create') {
        if ($method === 'POST') {
            $slug = trim((string) ($_POST['slug'] ?: slugify((string) $_POST['title'])));
            $pdo->prepare('INSERT INTO ' . jura_table('hotel_galleries') . ' (slug,title,description,status,meta_title,meta_description,sort_order,locale) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$slug, $_POST['title'], $_POST['description'] ?? '', $_POST['status'] ?? 'active', $_POST['meta_title'] ?? '', $_POST['meta_description'] ?? '', (int) ($_POST['sort_order'] ?? 0), (string) ($_POST['locale'] ?? '')]);
            redirect('/admin/hotel/galleries');
        }
        $allLocales = jura_available_locales($pdo, 'code,native_name');
        view_admin('hotel/gallery-edit', ['title' => 'Нова галерея', 'gallery' => [], 'all_locales' => $allLocales]);
        return true;
    }

    if (preg_match('#^/admin/hotel/galleries/(\d+)/edit$#', $path, $matches)) {
        $id = (int) $matches[1];
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('hotel_galleries') . ' WHERE id=?');
        $stmt->execute([$id]);
        $gal = $stmt->fetch();
        if ($method === 'POST') {
            $slug = trim((string) ($_POST['slug'] ?: slugify((string) $_POST['title'])));
            $pdo->prepare('UPDATE ' . jura_table('hotel_galleries') . ' SET slug=?,title=?,description=?,status=?,meta_title=?,meta_description=?,sort_order=?,locale=?,updated_at=NOW() WHERE id=?')
                ->execute([$slug, $_POST['title'], $_POST['description'] ?? '', $_POST['status'] ?? 'active', $_POST['meta_title'] ?? '', $_POST['meta_description'] ?? '', (int) ($_POST['sort_order'] ?? 0), (string) ($_POST['locale'] ?? ''), $id]);
            redirect('/admin/hotel/galleries/' . $id . '/edit');
        }
        $imgStmt = $pdo->prepare('SELECT * FROM ' . jura_table('hotel_gallery_images') . ' WHERE gallery_id=? ORDER BY sort_order,id');
        $imgStmt->execute([$id]);
        $allLocales = jura_available_locales($pdo, 'code,native_name');
        view_admin('hotel/gallery-edit', ['title' => 'Редагувати галерею', 'gallery' => $gal, 'gallery_images' => $imgStmt->fetchAll(), 'all_locales' => $allLocales]);
        return true;
    }

    if (preg_match('#^/admin/hotel/galleries/(\d+)/upload$#', $path, $matches) && $method === 'POST') {
        $id = (int) $matches[1];
        $uploadDir = BASE_PATH . '/public/userfiles/gallery/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
        $sort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM ' . jura_table('hotel_gallery_images') . ' WHERE gallery_id=' . $id)->fetchColumn();
        foreach ($_FILES['images']['tmp_name'] ?? [] as $i => $tmp) {
            if (!is_uploaded_file($tmp)) { continue; }
            $origName = (string) ($_FILES['images']['name'][$i] ?? '');
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) { continue; }
            $filename = uniqid('g_') . '.' . $ext;
            if (move_uploaded_file($tmp, $uploadDir . $filename)) {
                $pdo->prepare('INSERT INTO ' . jura_table('hotel_gallery_images') . ' (gallery_id,media_file_id,alt,sort_order) VALUES (?,NULL,?,?)')
                    ->execute([$id, pathinfo($origName, PATHINFO_FILENAME), $sort++]);
                // store filename in alt temporarily via title col - use a workaround: store in alt
                $lastId = (int) $pdo->lastInsertId();
                $pdo->prepare('UPDATE ' . jura_table('hotel_gallery_images') . ' SET title=? WHERE id=?')->execute([$filename, $lastId]);
            }
        }
        redirect('/admin/hotel/galleries/' . $id . '/edit');
    }

    if (preg_match('#^/admin/hotel/galleries/(\d+)/image/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
        $galleryId = (int) $matches[1];
        $imgId = (int) $matches[2];
        $row = $pdo->prepare('SELECT title FROM ' . jura_table('hotel_gallery_images') . ' WHERE id=? AND gallery_id=?');
        $row->execute([$imgId, $galleryId]);
        $img = $row->fetch();
        if ($img && $img['title']) {
            $f = BASE_PATH . '/public/userfiles/gallery/' . $img['title'];
            if (is_file($f)) { unlink($f); }
        }
        $pdo->prepare('DELETE FROM ' . jura_table('hotel_gallery_images') . ' WHERE id=? AND gallery_id=?')->execute([$imgId, $galleryId]);
        redirect('/admin/hotel/galleries/' . $galleryId . '/edit');
    }

    if (preg_match('#^/admin/hotel/galleries/image/(\d+)/alt$#', $path, $matches) && $method === 'POST') {
        $pdo->prepare('UPDATE ' . jura_table('hotel_gallery_images') . ' SET alt=? WHERE id=?')->execute([$_POST['alt'] ?? '', (int) $matches[1]]);
        http_response_code(200);
        echo 'ok';
        exit;
    }

    if ($path === '/admin/hotel/leads') {
        view_admin('hotel/leads', ['title' => 'Leads', 'leads' => $pdo->query('SELECT * FROM ' . jura_table('form_submissions') . ' ORDER BY id DESC')->fetchAll()]);
        return true;
    }

    if ($path === '/admin/hotel/tax') {
        $taxKeys = [
            'hotel_tourist_tax_ua_enabled', 'hotel_tourist_tax_ua_rate', 'hotel_tourist_tax_ua_note',
            'hotel_tourist_tax_foreign_enabled', 'hotel_tourist_tax_foreign_rate',
            'hotel_tourist_tax_foreign_badge', 'hotel_tourist_tax_foreign_note',
            'hotel_tourist_tax_extra_note',
        ];
        if ($method === 'POST') {
            foreach ($taxKeys as $key) {
                $val = match($key) {
                    'hotel_tourist_tax_ua_enabled', 'hotel_tourist_tax_foreign_enabled'
                        => isset($_POST[$key]) ? '1' : '0',
                    default => trim((string)($_POST[$key] ?? '')),
                };
                $pdo->prepare('INSERT INTO ' . jura_table('settings') . ' (setting_key,setting_value,setting_type,group_name) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')
                    ->execute([$key, $val, 'string', 'hotel']);
            }
            session_flash('tax_success', 'Збережено');
            redirect('/admin/hotel/tax');
        }
        $s = cms_settings($pdo);
        view_admin('hotel/tax', ['title' => 'Туристичний збір', 's' => $s, 'success' => session_flash('tax_success')]);
        return true;
    }

    if ($path === '/admin/hotel/booking') {
        $bookingKeys = ['booking_widget_head', 'booking_search_form', 'booking_page_form'];
        if ($method === 'POST') {
            foreach ($bookingKeys as $key) {
                $pdo->prepare('INSERT INTO ' . jura_table('settings') . ' (setting_key,setting_value,setting_type,group_name) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')
                    ->execute([$key, (string) ($_POST[$key] ?? ''), 'string', 'hotel']);
            }
            session_flash('booking_success', 'Збережено');
            redirect('/admin/hotel/booking');
        }
        $s = cms_settings($pdo);
        view_admin('hotel/booking', ['title' => 'Exely / Бронювання', 's' => $s, 'success' => session_flash('booking_success')]);
        return true;
    }

    return false;
}

function hotel_handle_frontend(string $path, PDO $pdo): bool
{
    // current_locale() is defined earlier in index.php and already loaded
    // by the time module hooks run -- strip the /en, /ru style prefix the
    // same way core page/post routing does, so /en/rooms works too.
    [$locale, $path] = current_locale($pdo, $path);

    // Note: /rooms itself is no longer hardcoded here -- it's a real,
    // editable jura_pages row (template=hotel_rooms, seeded by
    // hotel_seed_pages()) rendered via the render_page_template hook
    // (see hotel_render_page_template() below), so it shows up in
    // Сторінки/Меню like any other page.
    if (preg_match('#^/rooms/([^/]+)$#', $path, $matches)) {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('hotel_rooms') . " WHERE slug=? AND status='published' AND (locale=? OR locale='') LIMIT 1");
        $stmt->execute([$matches[1], $locale]);
        $room = $stmt->fetch();
        if ($room) {
            $room['amenity_list'] = $pdo->query(
                'SELECT a.title,a.icon FROM ' . jura_table('hotel_amenities') . ' a JOIN ' . jura_table('hotel_room_amenities') . ' ra ON ra.amenity_id=a.id WHERE ra.room_id=' . (int)$room['id'] . ' ORDER BY a.sort_order,a.id'
            )->fetchAll();
            $room['rates'] = $pdo->query(
                'SELECT * FROM ' . jura_table('hotel_room_rates') . ' WHERE room_id=' . (int)$room['id'] . ' ORDER BY sort_order,id'
            )->fetchAll();
            $room['room_images'] = $pdo->query(
                'SELECT * FROM ' . jura_table('hotel_room_images') . ' WHERE room_id=' . (int)$room['id'] . ' ORDER BY is_featured DESC,sort_order,id'
            )->fetchAll();
            $similarRooms = [];
            if ($room['show_similar_rooms'] ?? 1) {
                $similarStmt = $pdo->prepare(
                    'SELECT * FROM ' . jura_table('hotel_rooms') . " WHERE status='published' AND (locale=? OR locale='') AND id<>? ORDER BY sort_order,id"
                );
                $similarStmt->execute([$locale, (int) $room['id']]);
                $similarRooms = $similarStmt->fetchAll();
                foreach ($similarRooms as &$sr) {
                    $fi = $pdo->query('SELECT title FROM ' . jura_table('hotel_room_images') . ' WHERE room_id=' . (int)$sr['id'] . ' AND is_featured=1 LIMIT 1')->fetch();
                    $sr['_feat_img'] = $fi ? $fi['title'] : null;
                }
                unset($sr);
            }
            view_frontend('hotel/room', ['title' => $room['meta_title'] ?: $room['title'], 'meta_description' => $room['meta_description'], 'room' => $room, 'similar_rooms' => $similarRooms, 'settings' => cms_settings($pdo), 'locale' => $locale]);
            return true;
        }
    }
    if ($path === '/promotions') {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('hotel_promotions') . " WHERE status='published' AND (locale=? OR locale='') ORDER BY sort_order,id DESC");
        $stmt->execute([$locale]);
        view_frontend('hotel/promotions', ['title' => 'Акції', 'promotions' => $stmt->fetchAll(), 'settings' => cms_settings($pdo), 'locale' => $locale]);
        return true;
    }
    if (preg_match('#^/promotions/([^/]+)$#', $path, $matches)) {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('hotel_promotions') . " WHERE slug=? AND status='published' AND (locale=? OR locale='') LIMIT 1");
        $stmt->execute([$matches[1], $locale]);
        $promotion = $stmt->fetch();
        if ($promotion) {
            view_frontend('hotel/promotion', ['title' => $promotion['meta_title'] ?: $promotion['title'], 'meta_description' => $promotion['meta_description'], 'promotion' => $promotion, 'settings' => cms_settings($pdo), 'locale' => $locale]);
            return true;
        }
    }
    if ($path === '/gallery') {
        $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('hotel_galleries') . " WHERE status='active' AND (locale=? OR locale='') ORDER BY sort_order,id");
        $stmt->execute([$locale]);
        $galleries = $stmt->fetchAll();
        // Load images for each gallery
        foreach ($galleries as &$gal) {
            $imgQ = $pdo->prepare('SELECT * FROM ' . jura_table('hotel_gallery_images') . ' WHERE gallery_id=? ORDER BY sort_order,id');
            $imgQ->execute([$gal['id']]);
            $gal['images'] = $imgQ->fetchAll();
        }
        unset($gal);
        view_frontend('hotel/gallery', ['title' => 'Галерея', 'galleries' => $galleries, 'settings' => cms_settings($pdo), 'locale' => $locale]);
        return true;
    }
    return false;
}

// ── Hook: page_templates ─────────────────────────────────────────────────────
// Lets the "Шаблон" dropdown on /admin/pages offer this module's templates,
// so editing the auto-created Номери/Бронювання pages doesn't silently
// reset their template back to the generic "page" on save -- see
// ModuleLoader::hookCollect('page_templates') in index.php / pages.php.
function hotel_page_templates(): array
{
    return [
        'hotel_rooms'   => 'Готель: Номери',
        'hotel_booking' => 'Готель: Бронювання',
    ];
}

// ── Hook: render_page_template ──────────────────────────────────────────────
// Renders a jura_pages row whose template is one of this module's own
// (hotel_rooms, hotel_booking) via ModuleLoader::hookFirst('render_page_template', ...)
// -- see index.php's page-render branch. Returning false means "not mine",
// so core falls back to the generic 'page' view.
function hotel_render_page_template(array $page, array $settings, string $locale, array $common, PDO $pdo): bool
{
    $template = $page['template'] ?? '';
    if ($template === 'hotel_rooms') {
        $stmt = $pdo->prepare('SELECT r.*,fi.title as feat_img_file FROM ' . jura_table('hotel_rooms') . ' r LEFT JOIN ' . jura_table('hotel_room_images') . ' fi ON fi.room_id=r.id AND fi.is_featured=1' . " WHERE r.status='published' AND (r.locale=? OR r.locale='') ORDER BY r.sort_order,r.id");
        $stmt->execute([$locale]);
        view_frontend('hotel/rooms', array_merge(['title' => $page['meta_title'] ?: $page['title'], 'meta_description' => $page['meta_description'], 'page' => $page, 'rooms' => $stmt->fetchAll(), 'settings' => $settings], $common));
        return true;
    }
    if ($template === 'hotel_booking') {
        view_frontend('hotel/booking', array_merge(['title' => $page['meta_title'] ?: $page['title'], 'meta_description' => $page['meta_description'], 'page' => $page, 'settings' => $settings], $common));
        return true;
    }
    return false;
}
