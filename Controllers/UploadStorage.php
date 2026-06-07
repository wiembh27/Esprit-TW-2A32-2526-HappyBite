<?php

declare(strict_types=1);

/**
 * Centralized image storage under projetweb/uploads/{subdir}/.
 * DB should store relative paths like "uploads/stories/abc.jpg".
 */
final class UploadStorage
{
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    private const MAX_BYTES = 5 * 1024 * 1024;

    public static function projectRoot(): string
    {
        return dirname(__DIR__);
    }

    public static function ensureSubdir(string $subDir): string
    {
        $dir = self::projectRoot() . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . trim($subDir, '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Upload directory could not be created: ' . $subDir);
        }

        return $dir;
    }

    /**
     * @param array<string,mixed> $file $_FILES entry
     * @return array{success:bool,path?:string,message_key?:string}
     */
    public static function saveUploadedImage(array $file, string $subDir): array
    {
        if (empty($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
            return ['success' => false, 'message_key' => 'common.upload_invalid'];
        }

        $tmp = (string) $file['tmp_name'];
        $mime = mime_content_type($tmp) ?: '';
        if (!isset(self::ALLOWED_MIME[$mime])) {
            return ['success' => false, 'message_key' => 'common.upload_format'];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size > self::MAX_BYTES) {
            return ['success' => false, 'message_key' => 'common.upload_too_large'];
        }

        try {
            $uploadDir = self::ensureSubdir($subDir);
        } catch (RuntimeException $e) {
            return ['success' => false, 'message_key' => 'common.upload_dir'];
        }

        $ext = self::ALLOWED_MIME[$mime];
        $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($tmp, $destination)) {
            return ['success' => false, 'message_key' => 'common.upload_save_failed'];
        }

        $relative = 'uploads/' . trim(str_replace('\\', '/', $subDir), '/') . '/' . $filename;

        return ['success' => true, 'path' => $relative];
    }

    /**
     * @return array{success:bool,path?:string,message?:string}
     */
    public static function saveBinaryImage(string $binary, string $subDir, string $basenamePrefix = 'file'): array
    {
        if ($binary === '') {
            return ['success' => false, 'message' => 'Empty image data.'];
        }

        try {
            $uploadDir = self::ensureSubdir($subDir);
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => 'Upload directory inaccessible.'];
        }

        $filename = $basenamePrefix . '-' . bin2hex(random_bytes(8)) . '.png';
        $destination = $uploadDir . $filename;

        if (file_put_contents($destination, $binary) === false) {
            return ['success' => false, 'message' => 'Could not write image file.'];
        }

        $relative = 'uploads/' . trim(str_replace('\\', '/', $subDir), '/') . '/' . $filename;

        return ['success' => true, 'path' => $relative];
    }

    public static function serverPath(string $relativePath): string
    {
        return self::projectRoot() . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
    }

    /** Web URL from FrontOffice pages (prefix ../). Supports legacy filename-only values. */
    public static function publicUrl(?string $stored, string $webPrefix = '../'): string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $stored)) {
            return str_replace(' ', '%20', $stored);
        }

        $normalized = str_replace('\\', '/', $stored);
        if (!str_contains($normalized, '/')) {
            $normalized = 'uploads/' . $normalized;
        }

        return $webPrefix . ltrim(str_replace(' ', '%20', $normalized), '/');
    }
}
