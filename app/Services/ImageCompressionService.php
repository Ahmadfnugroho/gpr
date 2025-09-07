<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ImageCompressionService
{
    /**
     * Maximum file size in bytes (2MB)
     */
    const MAX_FILE_SIZE = 2097152; // 2MB
    
    /**
     * Maximum width for product photos
     */
    const MAX_WIDTH = 1920;
    
    /**
     * Maximum height for product photos
     */
    const MAX_HEIGHT = 1080;
    
    /**
     * JPEG quality for compression
     */
    const JPEG_QUALITY = 85;
    
    /**
     * PNG quality for compression (0-9)
     */
    const PNG_QUALITY = 8;

    /**
     * Compress and optimize uploaded image
     *
     * @param UploadedFile $file
     * @param string $directory
     * @return string|null Returns the stored file path or null on failure
     */
    public function compressAndStore(UploadedFile $file, string $directory = 'product-photos'): ?string
    {
        try {
            // Validate file is an image
            if (!$this->isValidImage($file)) {
                throw new \Exception('File is not a valid image');
            }

            // Create image manager instance
            $manager = new ImageManager(new Driver());
            
            // Create image instance
            $image = $manager->read($file->getPathname());
            
            // Get original dimensions
            $originalWidth = $image->width();
            $originalHeight = $image->height();
            
            // Calculate new dimensions while maintaining aspect ratio
            $newDimensions = $this->calculateNewDimensions($originalWidth, $originalHeight);
            
            // Resize image if needed
            if ($newDimensions['resize']) {
                $image = $image->resize($newDimensions['width'], $newDimensions['height']);
            }
            
            // Generate unique filename
            $extension = $this->getOptimalExtension($file);
            $filename = $this->generateUniqueFilename($extension);
            $fullPath = $directory . '/' . $filename;
            
            // Compress based on format
            $compressedImage = $this->compressImage($image, $extension);
            
            // Store the compressed image
            $stored = Storage::disk('public')->put($fullPath, $compressedImage);
            
            if ($stored) {
                // Verify file size is acceptable
                $storedSize = Storage::disk('public')->size($fullPath);
                if ($storedSize > self::MAX_FILE_SIZE) {
                    // If still too large, compress more aggressively
                    $this->compressMoreAggressively($fullPath, $extension);
                }
                
                return $fullPath;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Image compression failed: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]);
            
            return null;
        }
    }

    /**
     * Compress multiple images
     *
     * @param array $files
     * @param string $directory
     * @return array
     */
    public function compressMultiple(array $files, string $directory = 'product-photos'): array
    {
        $compressedPaths = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $path = $this->compressAndStore($file, $directory);
                if ($path) {
                    $compressedPaths[] = $path;
                }
            }
        }
        
        return $compressedPaths;
    }

    /**
     * Check if file is a valid image
     */
    private function isValidImage(UploadedFile $file): bool
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        return in_array($file->getMimeType(), $allowedTypes);
    }

    /**
     * Calculate optimal dimensions
     */
    private function calculateNewDimensions(int $width, int $height): array
    {
        if ($width <= self::MAX_WIDTH && $height <= self::MAX_HEIGHT) {
            return [
                'width' => $width,
                'height' => $height,
                'resize' => false
            ];
        }

        $widthRatio = self::MAX_WIDTH / $width;
        $heightRatio = self::MAX_HEIGHT / $height;
        
        $ratio = min($widthRatio, $heightRatio);
        
        return [
            'width' => (int)($width * $ratio),
            'height' => (int)($height * $ratio),
            'resize' => true
        ];
    }

    /**
     * Get optimal file extension for compression
     */
    private function getOptimalExtension(UploadedFile $file): string
    {
        $mime = $file->getMimeType();
        
        // Convert PNG to JPEG for better compression if it's a photo
        if ($mime === 'image/png') {
            // For simplicity, convert PNG to JPG for better compression
            // unless it's likely to have transparency (we'll keep PNG for now)
            // This is a simplified approach - you could add more sophisticated transparency detection
            return 'png'; // Keep as PNG to preserve potential transparency
        }
        
        return match($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg'
        };
    }

    /**
     * Check if image has transparency (simplified for v3)
     */
    private function hasTransparency($image): bool
    {
        try {
            // Simple check - assume PNG might have transparency
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Compress image based on format
     */
    private function compressImage($image, string $extension): string
    {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return $image->toJpeg(self::JPEG_QUALITY)->toString();
                
            case 'png':
                return $image->toPng()->toString();
                
            case 'webp':
                return $image->toWebp(self::JPEG_QUALITY)->toString();
                
            default:
                return $image->toJpeg(self::JPEG_QUALITY)->toString();
        }
    }

    /**
     * More aggressive compression if file is still too large
     */
    private function compressMoreAggressively(string $path, string $extension): void
    {
        try {
            $fullPath = Storage::disk('public')->path($path);
            $manager = new ImageManager(new Driver());
            $image = $manager->read($fullPath);
            
            // Reduce dimensions by 20%
            $newWidth = (int)($image->width() * 0.8);
            $newHeight = (int)($image->height() * 0.8);
            $image = $image->resize($newWidth, $newHeight);
            
            // Save with lower quality
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image->toJpeg(70)->save($fullPath);
                    break;
                case 'png':
                    $image->toPng()->save($fullPath);
                    break;
                case 'webp':
                    $image->toWebp(70)->save($fullPath);
                    break;
                default:
                    $image->toJpeg(70)->save($fullPath);
                    break;
            }
            
        } catch (\Exception $e) {
            \Log::warning('Aggressive compression failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(string $extension): string
    {
        return date('Y/m/d/') . Str::random(40) . '.' . $extension;
    }

    /**
     * Get human readable file size
     */
    public function getReadableSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
