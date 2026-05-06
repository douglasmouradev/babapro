<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class AvatarStorage
{
    private const MAX_BYTES = 2097152;

    /** @var array<string, string> */
    private const MIME_EXT = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @param array<string, mixed> $file elemento $_FILES['campo']
     */
    public static function assertValidUpload(array $file): void
    {
        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Envie uma foto valida (JPG, PNG ou WebP, ate 2 MB).');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Upload invalido.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        if ($mime === false || !isset(self::MIME_EXT[$mime])) {
            throw new RuntimeException('Formato aceito: JPG, PNG ou WebP.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size > self::MAX_BYTES || $size < 32) {
            throw new RuntimeException('Foto entre alguns bytes e 2 MB.');
        }
    }

    /**
     * Salva upload em public/uploads/avatars/{id}.{ext}; remove extensao anterior se existir.
     *
     * @param array<string, mixed> $file elemento $_FILES['campo']
     */
    public static function store(int $userId, array $file): string
    {
        self::assertValidUpload($file);

        $tmp = (string) ($file['tmp_name'] ?? '');
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        $ext = self::MIME_EXT[$mime];
        $dir = dirname(__DIR__, 2) . '/public/uploads/avatars';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Nao foi possivel criar pasta de uploads.');
        }

        foreach (['jpg', 'png', 'webp'] as $e) {
            $old = $dir . '/' . $userId . '.' . $e;
            if ($e !== $ext && is_file($old)) {
                unlink($old);
            }
        }

        $basename = $userId . '.' . $ext;
        $dest = $dir . '/' . $basename;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('Nao foi possivel salvar a foto.');
        }

        return '/uploads/avatars/' . $basename;
    }

    public static function deleteForUser(int $userId): void
    {
        $dir = dirname(__DIR__, 2) . '/public/uploads/avatars';
        foreach (['jpg', 'png', 'webp'] as $e) {
            $p = $dir . '/' . $userId . '.' . $e;
            if (is_file($p)) {
                unlink($p);
            }
        }
    }
}
