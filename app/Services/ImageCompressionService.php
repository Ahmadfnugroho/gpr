<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;
use RuntimeException;

class ImageCompressionService
{
    const MAX_FILE_SIZE = 2097152; // 2MB
    const MAX_WIDTH = 1920;
    const MAX_HEIGHT = 1080;
    const JPEG_QUALITY = 85;

    /**
     * Compress and store an uploaded image.
     *
     * @throws RuntimeException when the file is not a supported image or storage fails.
     */
    public function compressAndStore(UploadedFile $file, string $directory = 'product-photos'): string
    {
        if (!$this->isValidImage($file)) {
            throw new RuntimeException('File is not a valid image');
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getPathname());

        $dimensions = $this->calculateNewDimensions($image->width(), $image->height());
        if ($dimensions['resize']) {
            $image = $image->resize($dimensions['width'], $dimensions['height']);
        }

        $extension = $this->getOptimalExtension($file);
        $path = $directory . '/' . $this->generateUniqueFilename($extension);

        $payload = $this->compressImage($image, $extension);

        // Shrink in memory before writing: the media disk may be S3, where
        // rewriting an object in place via path() is not possible.
        if (strlen($payload) > self::MAX_FILE_SIZE) {
            $image = $image->resize(
                (int) ($image->width() * 0.8),
                (int) ($image->height() * 0.8)
            );
            $payload = $this->compressImage($image, $extension, 70);
        }

        if (!Storage::disk('media')->put($path, $payload)) {
            throw new RuntimeException('Failed to store compressed image');
        }

        return $path;
    }

    private function isValidImage(UploadedFile $file): bool
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        return in_array($file->getMimeType(), $allowedTypes);
    }

    private function calculateNewDimensions(int $width, int $height): array
    {
        if ($width <= self::MAX_WIDTH && $height <= self::MAX_HEIGHT) {
            return ['width' => $width, 'height' => $height, 'resize' => false];
        }

        $ratio = min(self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height);

        return [
            'width' => (int) ($width * $ratio),
            'height' => (int) ($height * $ratio),
            'resize' => true,
        ];
    }

    private function getOptimalExtension(UploadedFile $file): string
    {
        return match ($file->getMimeType()) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    private function compressImage($image, string $extension, int $quality = self::JPEG_QUALITY): string
    {
        return match ($extension) {
            'png' => $image->toPng()->toString(),
            'webp' => $image->toWebp($quality)->toString(),
            default => $image->toJpeg($quality)->toString(),
        };
    }

    private function generateUniqueFilename(string $extension): string
    {
        return date('Y/m/d/') . Str::random(40) . '.' . $extension;
    }
}
