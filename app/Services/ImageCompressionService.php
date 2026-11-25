<?php

namespace App\Services;

use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;

class ImageCompressionService
{
    const MAX_FILE_SIZE = 2097152; // 2MB
    const QUALITY = 85;

    /**
     * Wire a FileUpload to the WebP pipeline: media disk, size caps enforced in
     * the browser, and a server-side encode that guarantees .webp output.
     */
    public static function apply(FileUpload $field, string $directory, int $maxWidth, int $quality = self::QUALITY): FileUpload
    {
        return $field
            ->disk('media')
            ->imageResizeMode('contain')
            ->imageResizeTargetWidth((string) $maxWidth)
            ->imageResizeUpscale(false)
            ->saveUploadedFileUsing(fn (UploadedFile $file) => (new self())->compressAndStore($file, $directory, $maxWidth, $quality));
    }

    /**
     * @throws RuntimeException when the file is not a supported image or storage fails.
     */
    public function compressAndStore(UploadedFile $file, string $directory, int $maxWidth = 1200, int $quality = self::QUALITY): string
    {
        if (! $this->isValidImage($file)) {
            throw new RuntimeException('File is not a valid image');
        }

        $image = (new ImageManager(new Driver()))->read($file->getPathname());

        // scaleDown never upscales, so small originals keep their pixels.
        if ($image->width() > $maxWidth) {
            $image->scaleDown(width: $maxWidth);
        }

        $payload = $image->toWebp($quality)->toString();

        if (strlen($payload) > self::MAX_FILE_SIZE) {
            $image->scaleDown(width: (int) ($maxWidth * 0.8));
            $payload = $image->toWebp(70)->toString();
        }

        $path = $directory.'/'.date('Y/m/d/').Str::random(40).'.webp';

        if (! Storage::disk('media')->put($path, $payload)) {
            throw new RuntimeException('Failed to store compressed image');
        }

        return $path;
    }

    private function isValidImage(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'], true);
    }
}