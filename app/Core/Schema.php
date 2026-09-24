<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\SettingModel;
use PDO;

/**
 * Core CMS database schema -- creates/patches the tables and default
 * settings that index.php's admin and frontend dispatch depend on. Mirrors
 * ModuleLoader's own ensureTable/autoMigrate, just for core tables instead
 * of a module's. Bump VERSION whenever a table/column/default changes below.
 */
final class Schema
{
    public const VERSION = '6';

    public static function ensure(PDO $pdo): void
    {
        static $ensuredThisRequest = false;
        if ($ensuredThisRequest) {
            return;
        }
        // This function used to run its full set of SHOW/CREATE/ALTER/INSERT
        // IGNORE checks on every single request (frontend and admin alike),
        // which is a lot of avoidable round-trips once the schema is already
        // up to date. Short-circuit via a file marker; bump VERSION whenever
        // a table/column/default changes below so it re-runs once.
        //
        // The marker lives on the filesystem and can outlive the database it
        // describes (a clean_install/reinstall wipes tables but not storage/,
        // and a site can get pointed at a different database entirely) --
        // trusting it blindly then means core tables never get (re)created and
        // every admin page needing them fatals. Cheaply confirm jura_locales
        // (one of the tables this function is responsible for) actually exists
        // before trusting a matching marker; a stale marker self-heals here
        // instead of requiring a manual reinstall.
        $marker = BASE_PATH . '/storage/schema-version.txt';
        if (is_file($marker) && trim((string) @file_get_contents($marker)) === self::VERSION) {
            $localesTable = str_replace('`', '', jura_table('locales'));
            $exists = false;
            try {
                $exists = (bool) $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($localesTable))->fetch();
            } catch (\Throwable) {
                $exists = false;
            }
            if ($exists) {
                $ensuredThisRequest = true;
                return;
            }
        }

        $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('locales') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,code VARCHAR(16) UNIQUE,name VARCHAR(191),native_name VARCHAR(191),is_default TINYINT(1) DEFAULT 0,is_active TINYINT(1) DEFAULT 1,sort_order INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('form_submissions') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,form_code VARCHAR(120),locale VARCHAR(16) NULL,source_url VARCHAR(255) NULL,name VARCHAR(191) NULL,email VARCHAR(191) NULL,phone VARCHAR(80) NULL,message TEXT NULL,payload_json JSON NULL,status VARCHAR(40) DEFAULT 'new',ip_address VARCHAR(45) NULL,user_agent VARCHAR(255) NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('migrations') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,migration VARCHAR(191) UNIQUE,batch INT UNSIGNED DEFAULT 1,executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('settings') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,setting_key VARCHAR(191) UNIQUE,setting_value TEXT NULL,setting_type VARCHAR(40) DEFAULT 'string',group_name VARCHAR(80) DEFAULT 'system',updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Page-builder blocks (see App\Core\BlockRegistry) -- one row per block
        // placed on a page, in display order. settings_json holds whatever
        // fields that block type's admin_form/save_settings pair defined.
        $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('page_blocks') . " (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,page_id INT UNSIGNED NOT NULL,block_type VARCHAR(60) NOT NULL,settings_json JSON NULL,status VARCHAR(20) NOT NULL DEFAULT 'active',sort_order INT NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX page_order (page_id,sort_order)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        foreach ([
            ['settings', 'setting_type', "VARCHAR(40) DEFAULT 'string'"],
            ['settings', 'group_name', "VARCHAR(80) DEFAULT 'system'"],
            ['pages', 'canonical_path', 'VARCHAR(191) NULL'],
            ['pages', 'og_title', 'VARCHAR(191) NULL'],
            ['pages', 'og_description', 'TEXT NULL'],
            ['posts', 'canonical_path', 'VARCHAR(191) NULL'],
            ['posts', 'og_title', 'VARCHAR(191) NULL'],
            ['posts', 'og_description', 'TEXT NULL'],
            ['posts', 'featured_image', 'VARCHAR(255) NULL'],
            ['posts', 'sort_order', 'INT NOT NULL DEFAULT 0'],
            ['redirects', 'notes', 'TEXT NULL'],
            ['redirects', 'hit_count', 'INT UNSIGNED DEFAULT 0'],
            ['redirects', 'last_hit_at', 'TIMESTAMP NULL'],
            ['media_files', 'folder', 'VARCHAR(191) NULL'],
            ['users', 'role', "VARCHAR(40) NOT NULL DEFAULT 'admin'"],
            ['pages', 'locale', "VARCHAR(16) NOT NULL DEFAULT ''"],
            ['pages', 'translation_of', 'INT UNSIGNED NULL'],
            ['posts', 'locale', "VARCHAR(16) NOT NULL DEFAULT ''"],
            ['posts', 'translation_of', 'INT UNSIGNED NULL'],
            // Empty locale = shown for every locale (matches pages/posts/hotel
            // tables' fallback), so existing menu items keep showing up on every
            // language without an admin having to tag them.
            ['menu_items', 'locale', "VARCHAR(16) NOT NULL DEFAULT ''"],
        ] as [$table, $column, $definition]) {
            $tableName = str_replace('`', '', jura_table($table));
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$tableName}` LIKE " . $pdo->quote($column));
            if (!$stmt->fetch()) {
                try {
                    $pdo->exec("ALTER TABLE `{$tableName}` ADD `{$column}` {$definition}");
                } catch (\PDOException $e) {
                    // Concurrent requests can both see the column missing and both
                    // try to add it; the loser gets "Duplicate column" (42S21) —
                    // that just means another request already added it, not an
                    // error worth failing the whole request over.
                    if ($e->getCode() !== '42S21') {
                        throw $e;
                    }
                }
            }
        }
        $defaults = [
            ['site_name', 'Jura CMS', 'system'],
            ['site_url', '', 'system'],
            ['default_locale', 'uk', 'localization'],
            ['default_locale_has_prefix', '0', 'localization'],
            ['active_locales', 'uk,ru,en', 'localization'],
            ['contact_phone', '', 'contacts'],
            ['contact_phone2', '', 'contacts'],
            ['contact_email', '', 'contacts'],
            ['contact_address', '', 'contacts'],
            ['social_facebook', '', 'contacts'],
            ['social_instagram', '', 'contacts'],
            ['google_maps_embed', '', 'integrations'],
            ['gtm_id', '', 'integrations'],
            ['ga4_id', '', 'integrations'],
            ['google_ads_id', '', 'integrations'],
            ['fb_pixel_id', '', 'integrations'],
            ['fb_access_token', '', 'integrations'],
            ['menu_header', 'main', 'system'],
            ['menu_footer', 'main', 'system'],
            ['telegram_bot_token', '', 'notifications'],
            ['telegram_chat_id', '', 'notifications'],
            ['notification_email_to', '', 'notifications'],
            ['thankyou_page', '/thankyou', 'forms'],
            ['blog_per_page', '12', 'system'],
        ];
        foreach ($defaults as [$key, $value, $group]) {
            $pdo->prepare('INSERT IGNORE INTO ' . jura_table('settings') . ' (setting_key,setting_value,setting_type,group_name) VALUES (?,?,?,?)')
                ->execute([$key, $value, 'string', $group]);
        }
        // default_locale (settings) is the authoritative "what locale is this
        // site in" value — the installer can set it to anything (e.g. 'ru').
        // jura_locales.is_default must always agree with it; seed new rows
        // against it (not a hardcoded 'uk') and reconcile existing rows every
        // time this runs, since a mismatch here means current_locale()
        // resolves a different locale than the content was backfilled onto
        // below, silently emptying every locale-filtered listing (e.g. /blog).
        $defaultLocale = (string) SettingModel::get($pdo, 'default_locale', 'uk');
        foreach ([['uk', 'Ukrainian', 'Українська', 1], ['ru', 'Russian', 'Русский', 2], ['en', 'English', 'English', 3]] as [$code, $name, $native, $sort]) {
            $pdo->prepare('INSERT IGNORE INTO ' . jura_table('locales') . ' (code,name,native_name,is_default,sort_order) VALUES (?,?,?,?,?)')
                ->execute([$code, $name, $native, $code === $defaultLocale ? 1 : 0, $sort]);
        }
        $pdo->prepare('UPDATE ' . jura_table('locales') . ' SET is_default=(code=?)')->execute([$defaultLocale]);

        // Existing pages/posts predate the locale column — backfill them onto
        // the site's default locale rather than leaving it blank, so they keep
        // rendering exactly as before until an admin adds real translations.
        $pdo->prepare('UPDATE ' . jura_table('pages') . " SET locale=? WHERE locale=''")->execute([$defaultLocale]);
        $pdo->prepare('UPDATE ' . jura_table('posts') . " SET locale=? WHERE locale=''")->execute([$defaultLocale]);

        // Any admin user works as the author here -- this just needs to exist,
        // not belong to anyone in particular.
        $anyAdminId = (int) $pdo->query('SELECT id FROM ' . jura_table('users') . ' ORDER BY id LIMIT 1')->fetchColumn();
        if ($anyAdminId) {
            jura_seed_thankyou_page($pdo, $anyAdminId);
        }

        $dir = dirname($marker);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($marker, self::VERSION);
        $ensuredThisRequest = true;
    }
}
