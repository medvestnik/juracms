<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\MediaModel;
use PDO;

final class MediaController
{
    public static function index(PDO $pdo): never
    {
        view_admin('media', ['title' => 'Медіа', 'media' => MediaModel::all($pdo), 'success' => session_flash('success'), 'error' => session_flash('error')]);
        exit;
    }

    public static function upload(PDO $pdo): never
    {
        $stored = MediaModel::storeUpload($_FILES['file'] ?? []);
        if (!$stored) {
            session_flash('error', 'Upload failed.');
            redirect('/admin/media');
        }
        [$relative, $filename, $original, $absolute, $folder] = $stored;
        $mime = function_exists('mime_content_type') ? (string) mime_content_type($absolute) : 'application/octet-stream';
        $size = (int) filesize($absolute);
        [$width, $height] = str_starts_with($mime, 'image/') ? (getimagesize($absolute) ?: [null, null]) : [null, null];
        MediaModel::create($pdo, [
            'user_id' => (int) $_SESSION['admin_user_id'],
            'disk' => 'public',
            'path' => $relative,
            'filename' => $filename,
            'original_name' => $original,
            'mime_type' => $mime,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'alt' => $_POST['alt'] ?? '',
            'title' => $_POST['title'] ?? '',
            'folder' => $folder,
        ]);
        session_flash('success', 'File uploaded.');
        redirect('/admin/media');
    }
}
