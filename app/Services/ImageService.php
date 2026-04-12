<?php

namespace Services;

use Intervention\Image\ImageManager;

/**
 * Handles recipe image uploads.
 * Validates, resizes (max 1200 px wide), and stores images under public/uploads/recipes/.
 * Requires intervention/image ^3.0 with the GD extension.
 */
class ImageService
{
    private const MAX_BYTES    = 5 * 1024 * 1024; // 5 MB
    private const MAX_WIDTH    = 1200;
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
    private const UPLOAD_REL   = 'uploads/recipes'; // relative to public/

    /**
     * Validate and store an uploaded recipe image from a $_FILES entry.
     * Returns the public URL (e.g. /uploads/recipes/abc123.jpg).
     *
     * @throws \RuntimeException on validation or write failure
     */
    public function storeRecipeImage(array $file): string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($this->uploadErrorMessage($error));
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new \RuntimeException('Image must be 5 MB or smaller.');
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new \RuntimeException('Unsupported image type. Please use JPEG, PNG, or WebP.');
        }

        $uploadDir = $this->publicPath() . '/' . self::UPLOAD_REL;
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new \RuntimeException('Could not create upload directory.');
        }

        $ext      = match ($mime) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest     = $uploadDir . '/' . $filename;

        $manager = ImageManager::gd();
        $image   = $manager->read($file['tmp_name']);

        if ($image->width() > self::MAX_WIDTH) {
            $image->scaleDown(width: self::MAX_WIDTH);
        }

        $image->save($dest);

        return '/' . self::UPLOAD_REL . '/' . $filename;
    }

    /**
     * Delete a locally-stored recipe image.
     * Silently ignores external URLs and missing files.
     */
    public function deleteIfLocal(string $url): void
    {
        if (!str_starts_with($url, '/' . self::UPLOAD_REL . '/')) {
            return;
        }
        $path = $this->publicPath() . $url;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function publicPath(): string
    {
        return defined('APP_ROOT') ? APP_ROOT . '/public' : dirname(__DIR__, 2) . '/public';
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Image exceeds the maximum allowed size.',
            UPLOAD_ERR_PARTIAL                         => 'Image upload was incomplete.',
            UPLOAD_ERR_NO_FILE                         => 'No image was uploaded.',
            default                                    => 'Image upload failed (error ' . $code . ').',
        };
    }
}
