<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/** Reads/writes jura_media_files, plus the upload-to-disk step that always
 * precedes creating a row. */
final class MediaModel
{
    public static function all(PDO $pdo): array
    {
        return $pdo->query('SELECT * FROM ' . jura_table('media_files') . ' ORDER BY id DESC')->fetchAll();
    }

    public static function create(PDO $pdo, array $data): void
    {
        $pdo->prepare('INSERT INTO ' . jura_table('media_files') . ' (user_id,disk,path,filename,original_name,mime_type,size,width,height,alt,title,folder) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([
                $data['user_id'], $data['disk'], $data['path'], $data['filename'], $data['original_name'],
                $data['mime_type'], $data['size'], $data['width'], $data['height'], $data['alt'], $data['title'], $data['folder'],
            ]);
    }

    /** Moves an uploaded file into uploads/YYYY/MM with a randomized filename;
     * returns [relative path, filename, original name, absolute path, folder] or null on failure. */
    public static function storeUpload(array $file): ?array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $name = (string) ($file['name'] ?? 'file');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $safeBase = slugify(pathinfo($name, PATHINFO_FILENAME)) ?: 'file';
        $filename = $safeBase . '-' . date('His') . '-' . bin2hex(random_bytes(3)) . ($ext ? '.' . $ext : '');
        $folder = 'uploads/' . date('Y/m');
        $absoluteFolder = BASE_PATH . '/' . $folder;
        if (!is_dir($absoluteFolder)) {
            mkdir($absoluteFolder, 0775, true);
        }
        $absolute = $absoluteFolder . '/' . $filename;
        if (!move_uploaded_file((string) $file['tmp_name'], $absolute)) {
            return null;
        }
        return [$folder . '/' . $filename, $filename, $name, $absolute, $folder];
    }
}
