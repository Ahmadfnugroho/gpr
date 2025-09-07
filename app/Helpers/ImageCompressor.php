<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageCompressor
{
    /**
     * Kompresi gambar jika ukurannya melebihi batas tertentu
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int $threshold Ukuran threshold dalam KB (default 1024 KB = 1MB)
     * @param int $quality Kualitas kompresi (0-100, default 75)
     * @return \Illuminate\Http\UploadedFile File yang sudah dikompresi
     */
    public static function compressIfNeeded($file, $threshold = 1024, $quality = 75)
    {
        // Cek ukuran file dalam KB
        $fileSize = $file->getSize() / 1024;
        
        // Jika ukuran file lebih kecil dari threshold, kembalikan file asli
        if ($fileSize <= $threshold) {
            return $file;
        }
        
        try {
            // Log informasi sebelum kompresi
            Log::info('Compressing image', [
                'original_size' => $fileSize . ' KB',
                'filename' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType()
            ]);
            
            // Buat objek Image dari file
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getPathname());
            
            // Simpan ke temporary file
            $tempPath = sys_get_temp_dir() . '/' . uniqid('compressed_') . '.' . $file->getClientOriginalExtension();
            
            // Kompresi dan simpan berdasarkan format
            $extension = strtolower($file->getClientOriginalExtension());
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image->toJpeg($quality)->save($tempPath);
                    break;
                case 'png':
                    $image->toPng()->save($tempPath);
                    break;
                case 'webp':
                    $image->toWebp($quality)->save($tempPath);
                    break;
                default:
                    $image->toJpeg($quality)->save($tempPath);
                    break;
            }
            
            // Buat file baru dari hasil kompresi
            $compressedFile = new \Illuminate\Http\UploadedFile(
                $tempPath,
                $file->getClientOriginalName(),
                $file->getMimeType(),
                null,
                true // Menandakan ini adalah test file
            );
            
            // Log informasi setelah kompresi
            $compressedSize = $compressedFile->getSize() / 1024;
            Log::info('Image compressed successfully', [
                'original_size' => $fileSize . ' KB',
                'compressed_size' => $compressedSize . ' KB',
                'reduction' => round((($fileSize - $compressedSize) / $fileSize) * 100, 2) . '%'
            ]);
            
            return $compressedFile;
        } catch (\Exception $e) {
            Log::error('Failed to compress image', ['error' => $e->getMessage()]);
            return $file; // Kembalikan file asli jika gagal kompresi
        }
    }
}