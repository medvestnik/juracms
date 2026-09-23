<?php

declare(strict_types=1);

// ── Schema ───────────────────────────────────────────────────────────────
function git_deploy_ensure_schema(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('gitdeploy_log') . " (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(20) NOT NULL DEFAULT 'pull',
        name VARCHAR(255) NOT NULL DEFAULT '',
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        success TINYINT(1) NOT NULL DEFAULT 1,
        output TEXT NULL,
        changed_files TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Tracks completed migrations from migrations/ -- see the "── Migrations ──"
    // section below. Separate from core's own jura_migrations table (an
    // unrelated, no-longer-actively-used leftover of a removed core feature).
    $pdo->exec('CREATE TABLE IF NOT EXISTS ' . jura_table('gitdeploy_migrations') . " (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        output TEXT NULL,
        UNIQUE KEY migration (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ── Settings ─────────────────────────────────────────────────────────────
function git_deploy_settings(PDO $pdo): array
{
    $defaults = [
        'gitdeploy_repo_dir' => BASE_PATH,
        'gitdeploy_auth_type' => 'none',
        'gitdeploy_git_token' => '',
        'gitdeploy_author_name' => 'Jura CMS Git Deploy',
        'gitdeploy_author_email' => '',
        'gitdeploy_git_bin' => '',
        'gitdeploy_migration_timeout' => '',
    ];
    $all = cms_settings($pdo);
    foreach ($defaults as $key => $default) {
        $defaults[$key] = $all[$key] ?? $default;
    }
    return $defaults;
}

function git_deploy_save_settings(PDO $pdo, array $data): void
{
    $repoDir = trim((string) ($data['repo_dir'] ?? ''));
    if ($repoDir !== '' && is_dir($repoDir)) {
        save_setting($pdo, 'gitdeploy_repo_dir', rtrim($repoDir, '/'), 'gitdeploy');
    }
    if (isset($data['author_name'])) {
        save_setting($pdo, 'gitdeploy_author_name', trim((string) $data['author_name']), 'gitdeploy');
    }
    if (isset($data['author_email'])) {
        save_setting($pdo, 'gitdeploy_author_email', trim((string) $data['author_email']), 'gitdeploy');
    }
    if (isset($data['git_bin'])) {
        save_setting($pdo, 'gitdeploy_git_bin', trim((string) $data['git_bin']), 'gitdeploy');
    }
    if (isset($data['migration_timeout'])) {
        $timeout = trim((string) $data['migration_timeout']);
        save_setting($pdo, 'gitdeploy_migration_timeout', $timeout !== '' ? (string) max(0, (int) $timeout) : '', 'gitdeploy');
    }
}

function git_deploy_repo_dir(PDO $pdo): string
{
    $s = git_deploy_settings($pdo);
    $dir = rtrim((string) $s['gitdeploy_repo_dir'], '/');
    return is_dir($dir) ? $dir : BASE_PATH;
}

// ── Storage (SSH keys, behind-count cache) ──────────────────────────────
function git_deploy_storage_dir(): string
{
    $dir = BASE_PATH . '/storage/gitdeploy';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir;
}

function git_deploy_ssh_key_path(): string
{
    return git_deploy_storage_dir() . '/deploy_key';
}

function git_deploy_known_hosts_path(): string
{
    return git_deploy_storage_dir() . '/known_hosts';
}

function git_deploy_behind_cache_path(PDO $pdo): string
{
    return git_deploy_storage_dir() . '/behind_' . md5(git_deploy_repo_dir($pdo)) . '.txt';
}

// ── Git binary / command building ───────────────────────────────────────
function git_deploy_find_git(PDO $pdo): string
{
    $saved = trim((string) git_deploy_settings($pdo)['gitdeploy_git_bin']);
    if ($saved !== '' && is_executable($saved)) {
        return $saved;
    }
    foreach (['/usr/bin/git', '/usr/local/bin/git', '/opt/local/bin/git', '/opt/homebrew/bin/git', '/snap/bin/git'] as $p) {
        if (is_executable($p)) {
            return $p;
        }
    }
    $out = trim(safe_shell_exec('which git 2>/dev/null'));
    if ($out !== '' && is_executable($out)) {
        return $out;
    }
    return 'git';
}

function git_deploy_cmd(PDO $pdo): string
{
    $dir = git_deploy_repo_dir($pdo);
    $s = git_deploy_settings($pdo);
    $cmd = git_deploy_find_git($pdo) . ' -c safe.directory=' . escapeshellarg($dir) . ' -C ' . escapeshellarg($dir);

    $name = trim((string) $s['gitdeploy_author_name']) ?: 'Jura CMS Git Deploy';
    $email = trim((string) $s['gitdeploy_author_email']) ?: ('git-deploy@' . preg_replace('/[^a-z0-9.-]/i', '', $_SERVER['SERVER_NAME'] ?? 'localhost'));
    $cmd .= ' -c user.name=' . escapeshellarg($name) . ' -c user.email=' . escapeshellarg($email);

    if ($s['gitdeploy_auth_type'] === 'https_token' && trim((string) $s['gitdeploy_git_token']) !== '') {
        $header = base64_encode('x-access-token:' . $s['gitdeploy_git_token']);
        $cmd .= ' -c http.extraheader=' . escapeshellarg('AUTHORIZATION: basic ' . $header);
    } elseif ($s['gitdeploy_auth_type'] === 'ssh_key' && is_file(git_deploy_ssh_key_path())) {
        $sshCmd = 'ssh -i ' . escapeshellarg(git_deploy_ssh_key_path())
            . ' -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new'
            . ' -o UserKnownHostsFile=' . escapeshellarg(git_deploy_known_hosts_path());
        $cmd .= ' -c core.sshCommand=' . escapeshellarg($sshCmd);
    }

    return $cmd;
}

function git_deploy_is_repo(PDO $pdo): bool
{
    return is_dir(git_deploy_repo_dir($pdo) . '/.git');
}

// ── Activity log ─────────────────────────────────────────────────────────
function git_deploy_write_log(PDO $pdo, string $type, string $name, bool $success, string $output, string $changedFiles = ''): void
{
    $pdo->prepare('INSERT INTO ' . jura_table('gitdeploy_log') . ' (type,name,executed_at,success,output,changed_files) VALUES (?,?,NOW(),?,?,?)')
        ->execute([$type, $name, $success ? 1 : 0, $output, $changedFiles]);
}

function git_deploy_get_logs(PDO $pdo, int $limit = 20, int $offset = 0): array
{
    $stmt = $pdo->prepare('SELECT * FROM ' . jura_table('gitdeploy_log') . ' ORDER BY id DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset);
    $stmt->execute();
    return $stmt->fetchAll();
}

function git_deploy_count_logs(PDO $pdo): int
{
    try {
        return (int) $pdo->query('SELECT COUNT(*) FROM ' . jura_table('gitdeploy_log'))->fetchColumn();
    } catch (\Throwable) {
        return 0;
    }
}

// ── Connection status ────────────────────────────────────────────────────
function git_deploy_get_info(PDO $pdo, bool $forceFetch = false): array
{
    $git = git_deploy_cmd($pdo);
    $info = ['no_commits' => false, 'branch' => '', 'commit' => '', 'remote' => '', 'behind' => 0, 'last_pull_changed' => '', 'last_pull_at' => ''];

    $branchOut = trim(safe_shell_exec($git . ' rev-parse --abbrev-ref HEAD 2>&1'));
    $noCommits = str_contains($branchOut, 'fatal');
    $info['no_commits'] = $noCommits;

    if ($noCommits) {
        $symbolic = trim(safe_shell_exec($git . ' symbolic-ref --short HEAD 2>&1'));
        $info['branch'] = str_contains($symbolic, 'fatal') ? '' : $symbolic;
        $info['remote'] = trim(safe_shell_exec($git . ' remote get-url origin 2>&1'));
        return $info;
    }

    $info['branch'] = $branchOut;
    $info['commit'] = trim(safe_shell_exec($git . ' log -1 --format="%h - %s (%ai)" 2>&1'));
    $info['remote'] = trim(safe_shell_exec($git . ' remote get-url origin 2>&1'));

    $cacheFile = git_deploy_behind_cache_path($pdo);
    $ttl = 300;
    if ($forceFetch || !file_exists($cacheFile) || (time() - filemtime($cacheFile)) > $ttl) {
        safe_shell_exec($git . ' fetch origin 2>&1');
        $behind = trim(safe_shell_exec($git . ' rev-list HEAD..@{u} --count 2>&1'));
        @file_put_contents($cacheFile, ctype_digit($behind) ? $behind : '0');
    } else {
        $behind = (string) @file_get_contents($cacheFile);
    }
    $info['behind'] = ctype_digit(trim($behind)) ? (int) trim($behind) : 0;

    $stmt = $pdo->prepare('SELECT changed_files,executed_at FROM ' . jura_table('gitdeploy_log') . " WHERE type='pull' AND success=1 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    $info['last_pull_changed'] = $row ? (string) $row['changed_files'] : '';
    $info['last_pull_at'] = $row ? (string) $row['executed_at'] : '';

    return $info;
}

// ── Pull ─────────────────────────────────────────────────────────────────
function git_deploy_pull(PDO $pdo): array
{
    $git = git_deploy_cmd($pdo);
    $headBefore = trim(safe_shell_exec($git . ' rev-parse HEAD 2>&1'));

    $branch = trim(safe_shell_exec($git . ' symbolic-ref --short HEAD 2>&1'));
    if ($branch === '' || str_contains($branch, 'fatal')) {
        return ['success' => false, 'error' => 'Не вдалося визначити поточну гілку', 'output' => $branch];
    }

    $output = safe_shell_exec($git . ' pull origin ' . escapeshellarg($branch) . ' 2>&1');

    if (str_contains($output, 'reconcile divergent branches') || str_contains($output, 'refusing to merge unrelated histories')) {
        $output = safe_shell_exec($git . ' pull origin ' . escapeshellarg($branch) . ' --no-rebase --allow-unrelated-histories 2>&1');
    }

    $hasConflict = str_contains($output, 'CONFLICT') || str_contains($output, 'Automatic merge failed');
    $success = !$hasConflict && !str_contains($output, 'error') && !str_contains($output, 'fatal');

    // A conflicted merge leaves conflict markers (<<<<<<< HEAD / ======= /
    // >>>>>>>) written directly into whatever files collided -- if one of
    // those happens to be a module's bootstrap.php (or any file the CMS
    // require()s), the site breaks on the very next request with a fatal
    // parse error, on top of the pull already having failed. Reporting the
    // conflict is not enough; restore the pre-pull state immediately so the
    // live site keeps running on the last-known-good commit while the
    // conflict gets resolved manually (via SSH, as the error below says).
    if ($hasConflict) {
        $abortOutput = trim(safe_shell_exec($git . ' merge --abort 2>&1'));
        $output .= "\n\n=== git merge --abort (автоматично, щоб не лишати сайт з конфліктами у файлах) ===\n" . ($abortOutput !== '' ? $abortOutput : 'виконано');
    }

    $changedFiles = '';
    if ($success) {
        $headAfter = trim(safe_shell_exec($git . ' rev-parse HEAD 2>&1'));
        if ($headAfter !== '' && $headBefore !== '' && $headAfter !== $headBefore) {
            $changedFiles = trim(safe_shell_exec($git . ' diff --name-status ' . escapeshellarg($headBefore) . '..' . escapeshellarg($headAfter) . ' 2>&1'));
        } else {
            $changedFiles = 'Вже актуальна версія.';
        }
    }

    // If the merge left the local branch ahead of origin (e.g. a prior
    // commit that couldn't push because origin had unrelated commits),
    // push automatically so nothing is left stranded locally.
    $pushFailed = false;
    if ($success) {
        $ahead = trim(safe_shell_exec($git . ' rev-list --count ' . escapeshellarg('origin/' . $branch) . '..HEAD 2>&1'));
        if (ctype_digit($ahead) && (int) $ahead > 0) {
            $pushOut = trim(safe_shell_exec($git . ' push origin ' . escapeshellarg($branch) . ' 2>&1'));
            $output .= "\n\n=== git push origin {$branch} ===\n{$pushOut}";
            if (str_contains($pushOut, 'error') || str_contains($pushOut, 'fatal')) {
                $success = false;
                $pushFailed = true;
            }
        }
    }

    git_deploy_write_log($pdo, 'pull', 'git pull', $success, $output, $changedFiles);

    if ($success) {
        @unlink(git_deploy_behind_cache_path($pdo));
        return ['success' => true, 'output' => $output, 'changed_files' => $changedFiles];
    }

    $error = $hasConflict
        ? 'Злиття завершилось конфліктом файлів — потрібне ручне вирішення (через SSH/термінал).'
        : ($pushFailed ? 'Злиття пройшло успішно, але push після нього не вдався.' : 'git pull не вдався.');
    return ['success' => false, 'error' => $error, 'output' => $output];
}

// ── Status / diff ────────────────────────────────────────────────────────
function git_deploy_get_status(PDO $pdo): array
{
    $git = git_deploy_cmd($pdo);
    $labels = [
        'M ' => 'модифіковано', ' M' => 'модифіковано', 'MM' => 'модифіковано',
        'A ' => 'додано', ' A' => 'додано', 'AM' => 'додано',
        'D ' => 'видалено', ' D' => 'видалено',
        'R ' => 'перейменовано', ' R' => 'перейменовано', 'RM' => 'перейменовано',
        '??' => 'untracked', '!!' => 'ігнорується',
    ];

    $output = safe_shell_exec($git . ' status --porcelain -z 2>&1');
    if ($output === '') {
        return [];
    }

    $files = [];
    $records = explode("\0", $output);
    $skipNext = false;
    foreach ($records as $rec) {
        if ($skipNext) {
            // The previous record was a rename/copy — this segment is its
            // old path (no "XY " prefix of its own), already consumed.
            $skipNext = false;
            continue;
        }
        if (strlen($rec) < 4) {
            continue;
        }
        $xy = substr($rec, 0, 2);
        $path = trim(substr($rec, 3));
        if ($path === '' || trim($xy) === '') {
            continue;
        }
        if (in_array($xy[0], ['R', 'C'], true) || $xy[1] === 'R' || $xy[1] === 'C') {
            $skipNext = true;
        }
        $files[] = ['status' => trim($xy), 'label' => $labels[$xy] ?? trim($xy), 'file' => $path];
    }
    return $files;
}

function git_deploy_get_diff(PDO $pdo, string $file): string
{
    $git = git_deploy_cmd($pdo);
    $safe = escapeshellarg($file);
    $diff = trim(safe_shell_exec($git . ' diff HEAD -- ' . $safe . ' 2>&1'));
    if ($diff === '') {
        $diff = trim(safe_shell_exec($git . ' diff --cached -- ' . $safe . ' 2>&1'));
    }
    if ($diff === '') {
        $path = git_deploy_repo_dir($pdo) . '/' . ltrim($file, '/');
        if (is_file($path) && filesize($path) < 200000) {
            $diff = "(untracked — вміст файлу)\n" . (string) file_get_contents($path);
        }
    }
    return $diff !== '' ? $diff : '(немає diff)';
}

// ── Commit & push ────────────────────────────────────────────────────────
// A sensible default so a forgotten commit message doesn't block the whole
// commit (or, before this, was simply rejected with "введіть повідомлення").
// Names a few of the selected files plus the total count, e.g.
// "Оновлено: index.php, styles.css та ще 9 файл(ів)".
function git_deploy_auto_commit_message(array $files): string
{
    $files = array_values(array_filter(array_map('trim', $files)));
    if (!$files) {
        return '';
    }
    $names = array_map(static fn(string $f): string => basename($f), $files);
    $shown = array_slice($names, 0, 3);
    $rest = count($names) - count($shown);
    return 'Оновлено: ' . implode(', ', $shown) . ($rest > 0 ? ' та ще ' . $rest . ' файл(ів)' : '');
}

function git_deploy_commit_and_push(PDO $pdo, array $files, string $message): array
{
    $git = git_deploy_cmd($pdo);
    $files = array_values(array_filter(array_map('trim', $files)));
    if (!$files) {
        return ['success' => false, 'error' => 'Не вибрано жодного файлу', 'output' => ''];
    }

    // A pathspec file avoids exceeding the shell's argument-length limit
    // when many files are selected at once (git add/commit both support
    // reading the list from a file instead of the command line).
    $pathspecFile = git_deploy_storage_dir() . '/pathspec_' . bin2hex(random_bytes(6)) . '.txt';
    if (@file_put_contents($pathspecFile, implode("\0", $files)) === false) {
        return ['success' => false, 'error' => 'Не вдалося створити тимчасовий файл списку файлів', 'output' => ''];
    }
    $pathspecArgs = ' --pathspec-from-file=' . escapeshellarg($pathspecFile) . ' --pathspec-file-nul';

    $addOut = trim(safe_shell_exec($git . ' add' . $pathspecArgs . ' 2>&1'));
    $commitOut = trim(safe_shell_exec($git . ' commit -m ' . escapeshellarg($message) . $pathspecArgs . ' 2>&1'));
    @unlink($pathspecFile);

    if (str_contains($commitOut, 'nothing to commit') || str_contains($commitOut, 'nothing added')) {
        return ['success' => false, 'error' => 'Немає змін для коміту', 'output' => $commitOut];
    }
    if ($commitOut === '' || str_contains($commitOut, 'fatal') || str_contains($commitOut, 'error:')) {
        $fullOut = "=== git add ===\n{$addOut}\n\n=== git commit ===\n" . ($commitOut !== '' ? $commitOut : '(порожній вивід — коміт не виконався)');
        git_deploy_write_log($pdo, 'push', $message, false, $fullOut);
        return ['success' => false, 'error' => 'Коміт не вдався', 'output' => $fullOut];
    }

    $branch = trim(safe_shell_exec($git . ' rev-parse --abbrev-ref HEAD 2>&1'));
    $pushOut = trim(safe_shell_exec($git . ' push origin ' . escapeshellarg($branch) . ' 2>&1'));
    $success = !str_contains($pushOut, 'error') && !str_contains($pushOut, 'fatal');

    $fullOut = "=== git commit ===\n{$commitOut}\n\n=== git push origin {$branch} ===\n{$pushOut}";
    git_deploy_write_log($pdo, 'push', $message, $success, $fullOut, implode("\n", $files));

    if ($success) {
        @unlink(git_deploy_behind_cache_path($pdo));
        return ['success' => true, 'output' => $fullOut];
    }

    // The raw git output already explains what happened, but it's aimed at
    // someone reading a terminal, not an admin panel -- point at the actual
    // next action for the handful of causes seen in practice, instead of
    // leaving "push не вдався" + a git hint in English as the only guidance.
    $error = 'push не вдався';
    if (str_contains($pushOut, 'non-fast-forward') || str_contains($pushOut, '[rejected]')) {
        $error = 'push не вдався: на репозиторії вже є коміти, яких немає локально (наприклад, хтось інший запушив зміни, або їх додав інший процес). Натисніть «⬇ git pull» вище, а потім спробуйте commit & push ще раз.';
    } elseif (stripos($pushOut, 'permission denied') !== false || stripos($pushOut, 'publickey') !== false) {
        $error = 'push не вдався: доступ відхилено. Якщо використовуєте SSH-ключ — перевірте, що деплой-ключ додано в GitHub з правом «Allow write access»; якщо токен — що в нього є право на запис (repo scope).';
    } elseif (stripos($pushOut, 'could not read username') !== false) {
        $error = 'push не вдався: автентифікація не спрацювала для поточного способу підключення. Перевірте налаштування нижче — можливо, адреса репозиторію і обраний тип автентифікації (SSH/HTTPS) не узгоджені.';
    }
    return ['success' => false, 'error' => $error, 'output' => $fullOut];
}

// ── Init / connect wizard ────────────────────────────────────────────────
// core.sshCommand / http.extraheader (set in git_deploy_cmd()) only take
// effect when the remote itself uses the matching transport -- an
// https:// remote always uses HTTPS regardless of core.sshCommand, and a
// git@host:path (SSH shorthand) remote always uses SSH regardless of
// http.extraheader. So switching the selected auth type without also
// switching the remote's own URL scheme leaves the new auth silently
// inert and push keeps failing, just with a different error each time.
// Converts in whichever direction the newly selected auth type needs;
// leaves the URL alone if it doesn't recognize the shape (e.g. a
// ssh://host:port/path form, or an https URL with an explicit port --
// the git@host:path shorthand has no room for a port number, so
// converting one would produce a broken "git@host:port:path" remote).
function git_deploy_normalize_remote_for_auth(string $remoteUrl, string $authType): array
{
    if ($authType === 'ssh_key' && preg_match('#^https?://([^:/]+)/(.+?)(?:\.git)?/?$#i', $remoteUrl, $m)) {
        $converted = 'git@' . $m[1] . ':' . $m[2] . '.git';
        return [$converted, "Адресу репозиторію автоматично переведено на SSH-формат (потрібно для core.sshCommand): {$remoteUrl} → {$converted}"];
    }
    if ($authType !== 'ssh_key' && preg_match('#^git@([^:/]+):(.+?)(?:\.git)?/?$#i', $remoteUrl, $m)) {
        $converted = 'https://' . $m[1] . '/' . $m[2] . '.git';
        return [$converted, "Адресу репозиторію автоматично переведено на HTTPS-формат: {$remoteUrl} → {$converted}"];
    }
    return [$remoteUrl, null];
}

function git_deploy_init_repo(PDO $pdo, array $data): array
{
    $remoteUrl = trim((string) ($data['remote_url'] ?? ''));
    $branch = trim((string) ($data['branch'] ?? '')) !== '' ? trim((string) $data['branch']) : 'main';
    $authType = in_array($data['auth_type'] ?? 'none', ['none', 'https_token', 'ssh_key'], true) ? $data['auth_type'] : 'none';
    $token = trim((string) ($data['token'] ?? ''));
    $sshKey = (string) ($data['ssh_key'] ?? '');
    $makeIgnore = !empty($data['create_gitignore']);

    if ($remoteUrl === '') {
        return ['success' => false, 'error' => 'Вкажіть адресу репозиторію'];
    }

    $log = [];
    [$remoteUrl, $conversionLog] = git_deploy_normalize_remote_for_auth($remoteUrl, $authType);
    if ($conversionLog !== null) {
        $log[] = $conversionLog;
    }

    save_setting($pdo, 'gitdeploy_auth_type', $authType, 'gitdeploy');
    if ($authType === 'https_token') {
        // A blank token on a reconnect (auth type already was https_token,
        // just changing the remote URL/branch or re-saving other settings)
        // means "keep the previously saved token" — only a brand-new
        // https_token setup, with nothing saved yet, requires typing one in.
        $existingToken = trim((string) (git_deploy_settings($pdo)['gitdeploy_git_token'] ?? ''));
        if ($token === '' && $existingToken === '') {
            return ['success' => false, 'error' => 'Вкажіть токен доступу'];
        }
        if ($token !== '') {
            save_setting($pdo, 'gitdeploy_git_token', $token, 'gitdeploy');
        }
    } elseif ($authType === 'ssh_key') {
        if (trim($sshKey) !== '') {
            file_put_contents(git_deploy_ssh_key_path(), rtrim($sshKey) . "\n");
            @chmod(git_deploy_ssh_key_path(), 0600);
        } elseif (!is_file(git_deploy_ssh_key_path())) {
            return ['success' => false, 'error' => 'Спершу згенеруйте SSH-ключ або вставте власний приватний ключ.'];
        }
    }

    $git = git_deploy_cmd($pdo);

    if (!git_deploy_is_repo($pdo)) {
        $log[] = "git init:\n" . trim(safe_shell_exec($git . ' init 2>&1'));
    } else {
        $log[] = 'Каталог вже є git-репозиторієм — ініціалізацію пропущено.';
    }

    safe_shell_exec($git . ' checkout -B ' . escapeshellarg($branch) . ' 2>&1');

    if ($makeIgnore) {
        $log[] = git_deploy_write_default_gitignore($pdo);
    }

    $verifyHead = trim(safe_shell_exec($git . ' rev-parse --verify HEAD 2>&1'));
    if (str_contains($verifyHead, 'fatal')) {
        $gitignorePath = git_deploy_repo_dir($pdo) . '/.gitignore';
        if (is_file($gitignorePath)) {
            safe_shell_exec($git . ' add .gitignore 2>&1');
            $log[] = "Перший коміт (.gitignore):\n" . trim(safe_shell_exec($git . ' commit -m ' . escapeshellarg('Initial commit: add .gitignore') . ' 2>&1'));
        } else {
            $log[] = 'Репозиторій ще без жодного коміту — зробіть перший коміт на вкладці «Commit & Push».';
        }
    }

    $remotes = trim(safe_shell_exec($git . ' remote 2>&1'));
    if (str_contains($remotes, 'origin')) {
        safe_shell_exec($git . ' remote set-url origin ' . escapeshellarg($remoteUrl) . ' 2>&1');
        $log[] = 'origin оновлено: ' . $remoteUrl;
    } else {
        safe_shell_exec($git . ' remote add origin ' . escapeshellarg($remoteUrl) . ' 2>&1');
        $log[] = 'origin додано: ' . $remoteUrl;
    }

    $fetchOut = trim(safe_shell_exec($git . ' fetch origin 2>&1'));
    $log[] = "git fetch origin:\n{$fetchOut}";
    $ok = !str_contains($fetchOut, 'fatal') && !stripos($fetchOut, 'permission denied') && !stripos($fetchOut, 'authentication failed');

    if ($ok) {
        $remoteHasBranch = trim(safe_shell_exec($git . ' ls-remote --heads origin ' . escapeshellarg($branch) . ' 2>&1'));
        if ($remoteHasBranch !== '') {
            $log[] = "Увага: на репозиторії вже є гілка «{$branch}» з власною історією. Перед першим Commit & Push зробіть Git Pull, інакше push буде відхилено.";
        }
    }

    git_deploy_write_log($pdo, 'init', $remoteUrl, $ok, implode("\n\n", $log));

    return ['success' => $ok, 'output' => implode("\n\n", $log)];
}

function git_deploy_write_default_gitignore(PDO $pdo): string
{
    $path = git_deploy_repo_dir($pdo) . '/.gitignore';
    $existing = is_file($path) ? (string) file_get_contents($path) : '';
    $existingLines = $existing !== '' ? preg_split('/\r\n|\r|\n/', $existing) : [];

    $defaults = ['/config.php', '/storage/', '/uploads/', '/cache/', '/logs/', '*.log', '.DS_Store', 'Thumbs.db'];
    $toAdd = array_values(array_diff($defaults, $existingLines));
    if (!$toAdd) {
        return '.gitignore: типові виключення вже присутні.';
    }

    $newContent = rtrim($existing);
    $newContent .= ($newContent !== '' ? "\n\n" : '') . "# Jura CMS Git Deploy — типові виключення\n" . implode("\n", $toAdd) . "\n";
    file_put_contents($path, $newContent);
    return '.gitignore: додано типові виключення (' . implode(', ', $toAdd) . ').';
}

function git_deploy_add_to_gitignore(PDO $pdo, array $files): array
{
    $path = git_deploy_repo_dir($pdo) . '/.gitignore';
    $existing = is_file($path) ? (string) file_get_contents($path) : '';
    $lines = $existing !== '' ? explode("\n", rtrim($existing, "\n")) : [];
    $git = git_deploy_cmd($pdo);

    $added = [];
    $uncached = [];
    foreach ($files as $file) {
        $file = trim((string) $file);
        if ($file === '') {
            continue;
        }
        $entry = ltrim($file, '/');
        $pattern = '/' . $entry;
        if (!in_array($pattern, $lines, true) && !in_array($entry, $lines, true)) {
            $lines[] = $pattern;
            $added[] = $pattern;
        }
        $tracked = trim(safe_shell_exec($git . ' ls-files -- ' . escapeshellarg($entry) . ' 2>&1'));
        if ($tracked !== '') {
            safe_shell_exec($git . ' rm --cached -- ' . escapeshellarg($entry) . ' 2>&1');
            $uncached[] = $entry;
        }
    }

    if ($added) {
        file_put_contents($path, implode("\n", $lines) . "\n");
    }

    $log = $added ? 'Додано до .gitignore: ' . implode(', ', $added) : 'Всі файли вже у .gitignore';
    if ($uncached) {
        $log .= "\nЗнято з відстеження: " . implode(', ', $uncached);
    }
    return ['success' => true, 'output' => $log];
}

// ── Reset (danger zone) ──────────────────────────────────────────────────
function git_deploy_reset_repo(PDO $pdo): array
{
    $gitDir = git_deploy_repo_dir($pdo) . '/.git';
    if (!is_dir($gitDir)) {
        return ['success' => false, 'error' => 'Репозиторій ще не ініціалізовано — нічого скидати.'];
    }
    $git = git_deploy_cmd($pdo);

    $remoteUrl = trim(safe_shell_exec($git . ' remote get-url origin 2>&1'));
    if ($remoteUrl === '' || str_contains($remoteUrl, 'fatal')) {
        return ['success' => false, 'error' => 'Не вдалося визначити remote — скидання скасовано.', 'output' => $remoteUrl];
    }
    $branch = trim(safe_shell_exec($git . ' symbolic-ref --short HEAD 2>&1'));
    if ($branch === '' || str_contains($branch, 'fatal')) {
        $branch = 'main';
    }

    $log = ["Поточний remote: {$remoteUrl}", "Поточна гілка: {$branch}"];

    git_deploy_rrmdir($gitDir);
    $log[] = '.git видалено.';

    $gitignorePath = git_deploy_repo_dir($pdo) . '/.gitignore';
    if (is_file($gitignorePath)) {
        $backupPath = $gitignorePath . '.bak-' . date('Ymd-His');
        rename($gitignorePath, $backupPath);
        $log[] = '.gitignore збережено як ' . basename($backupPath) . '.';
    }

    $log[] = "git init:\n" . trim(safe_shell_exec($git . ' init 2>&1'));
    safe_shell_exec($git . ' checkout -B ' . escapeshellarg($branch) . ' 2>&1');
    $log[] = git_deploy_write_default_gitignore($pdo);
    safe_shell_exec($git . ' add .gitignore 2>&1');
    $log[] = "Перший коміт:\n" . trim(safe_shell_exec($git . ' commit -m ' . escapeshellarg('Initial commit: add .gitignore') . ' 2>&1'));
    safe_shell_exec($git . ' remote add origin ' . escapeshellarg($remoteUrl) . ' 2>&1');
    $log[] = 'origin додано: ' . $remoteUrl;
    $log[] = "git fetch origin:\n" . trim(safe_shell_exec($git . ' fetch origin 2>&1'));

    @unlink(git_deploy_behind_cache_path($pdo));
    git_deploy_write_log($pdo, 'reset', $remoteUrl, true, implode("\n\n", $log));

    return ['success' => true, 'output' => implode("\n\n", $log)];
}

function git_deploy_rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($dir);
}

// ── SSH key generation ───────────────────────────────────────────────────
function git_deploy_find_ssh_keygen(): string
{
    // `which` alone is unreliable here: PHP-FPM's shell_exec() often runs
    // with a much narrower PATH than an interactive root shell, so a
    // package being installed (confirmed via `rpm -q`/`dpkg -l` on the
    // server) doesn't mean `which ssh-keygen` finds it from PHP. Same
    // hardcoded-paths-first strategy as git_deploy_find_git().
    foreach (['/usr/bin/ssh-keygen', '/usr/local/bin/ssh-keygen', '/opt/local/bin/ssh-keygen', '/opt/homebrew/bin/ssh-keygen'] as $p) {
        if (is_executable($p)) {
            return $p;
        }
    }
    $out = trim(safe_shell_exec('which ssh-keygen 2>/dev/null'));
    return ($out !== '' && is_executable($out)) ? $out : '';
}

function git_deploy_generate_ssh_key(): array
{
    $sshKeygen = git_deploy_find_ssh_keygen();
    if ($sshKeygen === '') {
        return ['success' => false, 'error' => 'На сервері не знайдено ssh-keygen (перевірено /usr/bin, /usr/local/bin та PATH). Згенеруйте пару ключів на своєму комп’ютері (ssh-keygen -t ed25519) і вставте приватний ключ у поле нижче.'];
    }

    $dir = git_deploy_storage_dir();
    if (!is_dir($dir) || !is_writable($dir)) {
        return ['success' => false, 'error' => "Немає прав на запис у {$dir} — перевірте власника/права директорії storage/ на хостингу."];
    }

    $keyPath = git_deploy_ssh_key_path();
    $pubPath = $keyPath . '.pub';
    @unlink($keyPath);
    @unlink($pubPath);

    $comment = 'jura-gitdeploy@' . preg_replace('/[^a-z0-9.-]/i', '', $_SERVER['SERVER_NAME'] ?? 'site');
    $cmd = escapeshellarg($sshKeygen) . ' -t ed25519 -f ' . escapeshellarg($keyPath) . ' -N ' . escapeshellarg('') . ' -C ' . escapeshellarg($comment) . ' 2>&1';
    $output = trim(safe_shell_exec($cmd));

    if (!is_file($keyPath) || !is_file($pubPath)) {
        // Surface the actual command output instead of a generic message --
        // this is the only way to tell apart the several very different
        // reasons this can fail (disable_functions silently ignoring the
        // call, ssh-keygen erroring on a read-only/full disk, an
        // open_basedir restriction, etc.) without SSH access to the server.
        $detail = $output !== '' ? $output : '(команда не повернула жодного виводу — можливо, exec()/shell_exec() частково обмежені хостингом попри те, що загальна перевірка shell_exec пройшла)';
        return ['success' => false, 'error' => "Не вдалося згенерувати ключ.\n\nВивід команди:\n{$detail}"];
    }
    @chmod($keyPath, 0600);

    return ['success' => true, 'public_key' => trim((string) file_get_contents($pubPath))];
}

// ── DB schema export ─────────────────────────────────────────────────────
function git_deploy_save_schema_to_repo(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT c.TABLE_NAME, c.COLUMN_NAME, c.COLUMN_TYPE, c.IS_NULLABLE, c.COLUMN_DEFAULT, c.COLUMN_KEY, c.EXTRA, c.ORDINAL_POSITION
        FROM INFORMATION_SCHEMA.COLUMNS c
        WHERE c.TABLE_SCHEMA = DATABASE()
        ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION
    ");
    $tables = [];
    foreach ($stmt->fetchAll() as $row) {
        $tables[$row['TABLE_NAME']][] = $row;
    }

    $md = "# Database Schema\n\n> Generated: " . date('Y-m-d H:i:s') . "  \n> Tables: " . count($tables) . "\n\n---\n\n";
    foreach ($tables as $name => $columns) {
        $md .= "## `{$name}`\n\n| # | Field | Type | NULL | Key | Default | Extra |\n|---|-------|------|:----:|:---:|---------|-------|\n";
        foreach ($columns as $i => $col) {
            $default = $col['COLUMN_DEFAULT'] !== null ? '`' . $col['COLUMN_DEFAULT'] . '`' : 'NULL';
            $md .= '| ' . ($i + 1) . ' | `' . $col['COLUMN_NAME'] . '` | `' . $col['COLUMN_TYPE'] . '` | ' . ($col['IS_NULLABLE'] === 'YES' ? 'YES' : 'NO') . ' | ' . $col['COLUMN_KEY'] . ' | ' . $default . ' | ' . $col['EXTRA'] . " |\n";
        }
        $md .= "\n";
    }

    $path = git_deploy_repo_dir($pdo) . '/db_schema.md';
    $ok = @file_put_contents($path, $md) !== false;
    return ['success' => $ok, 'path' => $path, 'tables' => count($tables)];
}

// ── Migrations ───────────────────────────────────────────────────────────
// Ported from the OpenCart Git Deploy module this was based on -- lets an
// admin (or an AI agent working through git) drop a small PHP file with a
// $pdo->query(...) call into migrations/, then run it from this admin page
// instead of needing shell/DB console access. migrations/ is a real,
// version-controlled directory (unlike storage/, which the default
// .gitignore excludes), so files placed there survive a commit/push and a
// later git pull on this or another site.
function git_deploy_migrations_dir(): string
{
    $dir = BASE_PATH . '/migrations';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

// Naming convention: YYYYMMDD_HHMMSS_query_description.php marks a
// read-only migration meant to report data back (e.g. for an AI agent to
// read after it's committed) rather than change schema -- purely a
// filename convention the UI uses to show a hint; nothing enforces it.
function git_deploy_is_query_migration(string $filename): bool
{
    return (bool) preg_match('/^\d{8}_\d{6}_query_/', $filename);
}

function git_deploy_get_migrations(PDO $pdo): array
{
    $executed = [];
    try {
        $stmt = $pdo->query('SELECT migration, executed_at FROM ' . jura_table('gitdeploy_migrations') . ' ORDER BY migration');
        foreach ($stmt->fetchAll() as $row) {
            $executed[$row['migration']] = $row['executed_at'];
        }
    } catch (\Throwable) {
        // table not migrated yet on this request
    }

    $files = [];
    foreach (glob(git_deploy_migrations_dir() . '/*.php') ?: [] as $path) {
        $name = basename($path);
        $files[] = [
            'name' => $name,
            'executed' => isset($executed[$name]),
            'executed_at' => $executed[$name] ?? null,
            'is_query' => git_deploy_is_query_migration($name),
        ];
    }
    // Filenames start with YYYYMMDD_HHMMSS, so a plain string compare is
    // already a chronological sort -- reversed so the newest migrations
    // (most likely just pulled in and still pending) show up first.
    usort($files, static fn(array $a, array $b): int => strcmp($b['name'], $a['name']));
    return $files;
}

function git_deploy_run_migration(PDO $pdo, string $filename): array
{
    $filename = basename($filename);
    if (!preg_match('/^[A-Za-z0-9_.-]+\.php$/', $filename)) {
        return ['success' => false, 'error' => 'Некоректна назва файлу міграції.'];
    }
    $path = git_deploy_migrations_dir() . '/' . $filename;
    if (!is_file($path)) {
        return ['success' => false, 'error' => 'Файл міграції не знайдено: ' . $filename];
    }
    $check = $pdo->prepare('SELECT id FROM ' . jura_table('gitdeploy_migrations') . ' WHERE migration=?');
    $check->execute([$filename]);
    if ($check->fetchColumn()) {
        return ['success' => false, 'error' => 'Міграція вже виконана: ' . $filename];
    }

    // A migration that runs long enough to hit the PHP execution-time limit
    // dies with an uncatchable fatal error partway through `include` -- no
    // exception, no return value, just a broken response, and the
    // migration is left neither marked done nor reported as failed
    // (silently re-runnable, but with no visible explanation why it
    // "didn't work"). Set an explicit, known budget -- configurable in
    // Settings, since hosts vary widely in their own default -- and use a
    // shutdown handler (the only way to observe this specific failure
    // mode) to log it clearly instead of leaving it ambiguous.
    $timeoutSetting = (int) (git_deploy_settings($pdo)['gitdeploy_migration_timeout'] ?? 0);
    if ($timeoutSetting > 0) {
        @set_time_limit($timeoutSetting);
    }

    $finished = false;
    register_shutdown_function(static function () use (&$finished, $pdo, $filename, $timeoutSetting): void {
        if ($finished) {
            return;
        }
        $lastError = error_get_last();
        $isTimeout = $lastError && stripos($lastError['message'], 'maximum execution time') !== false;
        $partial = (string) @ob_get_clean();
        $message = $isTimeout
            ? 'Перевищено ліміт часу виконання (' . ($timeoutSetting > 0 ? $timeoutSetting : (int) ini_get('max_execution_time')) . 'с) — міграція не завершилась. Дані могли застосуватися частково. Збільште ліміт у Налаштуваннях або розбийте міграцію на менші частини, потім запустіть її ще раз.'
            : 'Міграція перервалась несподівано' . ($lastError ? (': ' . $lastError['message']) : '.');
        try {
            git_deploy_write_log($pdo, 'migration', $filename, false, $message . ($partial !== '' ? "\n\n" . $partial : ''));
        } catch (\Throwable) {
        }
    });

    ob_start();
    try {
        // Available to the migration file: $pdo (query the site's DB) and
        // $repoDir (e.g. to write a query_ migration's result into a file
        // for later commit).
        $repoDir = git_deploy_repo_dir($pdo);
        include $path;
        $output = (string) ob_get_clean();
        $pdo->prepare('INSERT INTO ' . jura_table('gitdeploy_migrations') . ' (migration,output) VALUES (?,?)')->execute([$filename, $output]);
        $finished = true;
        return ['success' => true, 'output' => $output !== '' ? $output : 'OK', 'is_query' => git_deploy_is_query_migration($filename)];
    } catch (\Throwable $e) {
        $output = (string) @ob_get_clean();
        $finished = true;
        git_deploy_write_log($pdo, 'migration', $filename, false, $e->getMessage() . ($output !== '' ? "\n\n" . $output : ''));
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function git_deploy_rerun_migration(PDO $pdo, string $filename): array
{
    $pdo->prepare('DELETE FROM ' . jura_table('gitdeploy_migrations') . ' WHERE migration=?')->execute([basename($filename)]);
    return git_deploy_run_migration($pdo, $filename);
}

function git_deploy_run_pending_migrations(PDO $pdo): array
{
    $results = [];
    foreach (git_deploy_get_migrations($pdo) as $m) {
        if ($m['executed']) {
            continue;
        }
        $result = git_deploy_run_migration($pdo, $m['name']);
        $results[] = [
            'migration' => $m['name'],
            'success' => $result['success'],
            'output' => $result['output'] ?? ($result['error'] ?? ''),
            'is_query' => $m['is_query'],
        ];
    }
    return $results;
}

// ── Config info (masked — safe to show an AI working from the repo) ────
function git_deploy_config_info(PDO $pdo): array
{
    $config = cms_config('database', []);
    return [
        'JURA_VERSION' => trim((string) @file_get_contents(BASE_PATH . '/VERSION')),
        'PHP_VERSION' => PHP_VERSION,
        'BASE_PATH' => BASE_PATH,
        'DB_HOST' => (string) ($config['host'] ?? ''),
        'DB_NAME' => (string) ($config['database'] ?? ''),
        'DB_PREFIX' => (string) ($config['prefix'] ?? 'jura_'),
        'DB_PASSWORD' => (($config['password'] ?? '') !== '') ? '•••••••• (приховано)' : '(порожній)',
    ];
}
