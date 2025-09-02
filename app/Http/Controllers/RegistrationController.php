<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPhoto;
use App\Models\CustomerPhoneNumber;
use App\Services\WAHAService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;

class RegistrationController extends Controller
{
    public function showForm()
    {
        return view('registration.form');
    }

    public function register(Request $request)
    {
        // Meningkatkan batas waktu eksekusi untuk upload file besar
        set_time_limit(300); // 5 menit
        ini_set('memory_limit', '256M');

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:customers,email',
            'source_info' => 'required|in:Instagram,Teman,Google,Lainnya,TikTok',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'village' => 'required|string|max:255',
            'address_detail' => 'required|string',
            'phone1' => 'required|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'job' => 'nullable|string|max:255',
            'office_address' => 'nullable|string',
            'instagram_username' => 'nullable|string|max:255',
            'ktp_photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240|max_server_size:1024',
            'id_photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240|max_server_size:1024',
            'id_type' => 'required|string|max:50',
            'id_photo_2' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240|max_server_size:1024',
            'id_type_2' => 'required|string|max:50',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_number' => 'required|string|max:20',
        ], [
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'source_info.required' => 'Silakan pilih dari mana Anda mengetahui Global Photo Rental',
            'name.required' => 'Nama lengkap wajib diisi',
            'gender.required' => 'Jenis kelamin wajib dipilih',
            'province.required' => 'Provinsi wajib dipilih',
            'city.required' => 'Kab/Kota wajib dipilih',
            'district.required' => 'Kecamatan wajib dipilih',
            'village.required' => 'Kelurahan wajib dipilih',
            'address_detail.required' => 'Alamat lengkap wajib diisi',
            'phone1.required' => 'Nomor HP 1 wajib diisi',
            'ktp_photo.required' => 'Foto KTP wajib diunggah',
            'ktp_photo.image' => 'File KTP harus berupa gambar',
            'ktp_photo.mimes' => 'Format foto KTP harus jpeg, png, jpg, gif, atau webp',
            'ktp_photo.max' => 'Ukuran foto KTP maksimal 10MB',
            'id_photo.required' => 'Foto ID tambahan 1 wajib diunggah',
            'id_photo.image' => 'File ID tambahan 1 harus berupa gambar',
            'id_photo.mimes' => 'Format foto ID tambahan 1 harus jpeg, png, jpg, gif, atau webp',
            'id_photo.max' => 'Ukuran foto ID tambahan 1 maksimal 10MB',
            'id_type.required' => 'Jenis ID tambahan 1 wajib dipilih',
            'id_photo_2.required' => 'Foto ID tambahan 2 wajib diunggah',
            'id_photo_2.image' => 'File ID tambahan 2 harus berupa gambar',
            'id_photo_2.mimes' => 'Format foto ID tambahan 2 harus jpeg, png, jpg, gif, atau webp',
            'id_photo_2.max' => 'Ukuran foto ID tambahan 2 maksimal 10MB',
            'id_type_2.required' => 'Jenis ID tambahan 2 wajib dipilih',
            'emergency_contact_name.required' => 'Nama kontak emergency wajib diisi',
            'emergency_contact_number.required' => 'Nomor HP kontak emergency wajib diisi',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Gabungkan alamat lengkap
            $fullAddress = implode(', ', array_filter([
                $request->address_detail,
                $request->village,
                $request->district,
                $request->city,
                $request->province
            ]));

            // Buat customer baru
            $customer = Customer::create([
                'name' => $request->name,
                'email' => $request->email,
                'gender' => $request->gender,
                'address' => $fullAddress,
                'job' => $request->job,
                'office_address' => $request->office_address,
                'instagram_username' => $request->instagram_username,
                'source_info' => $request->source_info,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_number' => $request->emergency_contact_number,
                'status' => Customer::STATUS_BLACKLIST, // Default status
            ]);

            // Simpan nomor HP
            CustomerPhoneNumber::create([
                'customer_id' => $customer->id,
                'phone_number' => $request->phone1,
            ]);

            if ($request->phone2) {
                CustomerPhoneNumber::create([
                    'customer_id' => $customer->id,
                    'phone_number' => $request->phone2,
                ]);
            }

            // Upload dan simpan foto KTP
            if ($request->hasFile('ktp_photo')) {
                try {
                    $ktpFile = $request->file('ktp_photo');
                    Log::info('Registration: Processing KTP photo', ['customer_id' => $customer->id, 'file_size' => $ktpFile->getSize()]);

                    // Simpan file sementara
                    $tempPath = storage_path('app/public/temp/' . Str::random(20) . '.' . $ktpFile->getClientOriginalExtension());
                    $ktpFile->move(dirname($tempPath), basename($tempPath));

                    // Kompresi gambar jika ukurannya > 1MB
                    if ($ktpFile->getSize() > 1024 * 1024) {
                        // Gunakan fungsi compressImage untuk kompresi
                        $compressedFileName = $this->compressImage($tempPath);
                        $compressedPath = storage_path('app/public/' . $compressedFileName);

                        // Hapus file temp asli
                        if (file_exists($tempPath)) {
                            @unlink($tempPath);
                        }

                        $tempPath = $compressedPath;
                    }

                    $ktpFileName = 'ktp_' . $customer->id . '_' . time() . '.' . pathinfo($tempPath, PATHINFO_EXTENSION);

                    // Buat instance UploadedFile dari file yang sudah dikompres
                    $compressedFile = new UploadedFile(
                        $tempPath,
                        $ktpFileName,
                        mime_content_type($tempPath),
                        null,
                        false
                    );

                    // Simpan ke storage
                    $ktpPath = $compressedFile->storeAs('customer_photos', $ktpFileName, 'public');

                    // Hapus file temp
                    if (file_exists($tempPath)) {
                        @unlink($tempPath);
                    }

                    if (!$ktpPath) {
                        throw new \Exception('Gagal menyimpan foto KTP.');
                    }

                    CustomerPhoto::create([
                        'customer_id' => $customer->id,
                        'photo_type' => 'ktp',
                        'photo' => $ktpPath,
                    ]);

                    Log::info('Registration: KTP photo uploaded successfully', ['path' => $ktpPath]);
                } catch (\Exception $e) {
                    Log::error('Registration: Failed to upload KTP photo', [
                        'error' => $e->getMessage(),
                        'customer_id' => $customer->id
                    ]);
                    throw new \Exception('Gagal mengunggah foto KTP: ' . $e->getMessage());
                }
            }

            // Upload dan simpan foto ID tambahan 1


            // ...

            // Upload dan simpan foto ID tambahan 1
            if ($request->hasFile('id_photo')) {
                try {
                    $idFile = $request->file('id_photo');
                    Log::info('Registration: Processing ID photo 1', [
                        'customer_id' => $customer->id,
                        'original_size' => $idFile->getSize(),
                        'id_type' => $request->id_type,
                        'extension' => $idFile->getClientOriginalExtension()
                    ]);

                    // Simpan file sementara
                    $tempPath = storage_path('app/public/temp/' . Str::random(20) . '.' . $idFile->getClientOriginalExtension());
                    $idFile->move(dirname($tempPath), basename($tempPath));

                    // Kompresi gambar jika ukurannya > 1MB
                    if ($idFile->getSize() > 1024 * 1024) {
                        // Gunakan fungsi compressImage untuk kompresi
                        $compressedFileName = $this->compressImage($tempPath);
                        $compressedPath = storage_path('app/public/' . $compressedFileName);

                        // Hapus file temp asli
                        if (file_exists($tempPath)) {
                            @unlink($tempPath);
                        }

                        $tempPath = $compressedPath;
                    }

                    $idFileName = 'id_1_' . $customer->id . '_' . time() . '.' . pathinfo($tempPath, PATHINFO_EXTENSION);

                    // Buat instance UploadedFile dari file yang sudah dikompres
                    $compressedFile = new UploadedFile(
                        $tempPath,
                        $idFileName,
                        mime_content_type($tempPath),
                        null,
                        false
                    );

                    $idPath = $compressedFile->storeAs('customer_photos', $idFileName, 'public');

                    // Hapus file sementara
                    if (file_exists($tempPath)) {
                        @unlink($tempPath);
                    }

                    if (!$idPath) {
                        throw new \Exception('Failed to store ID photo 1');
                    }

                    CustomerPhoto::create([
                        'customer_id' => $customer->id,
                        'photo_type' => 'additional_id_1',
                        'photo' => $idPath,
                        'id_type' => $request->id_type,
                    ]);

                    Log::info('Registration: ID photo 1 uploaded successfully', [
                        'path' => $idPath,
                        'id_type' => $request->id_type
                    ]);
                } catch (\Exception $e) {
                    Log::error('Registration: Failed to upload ID photo 1', [
                        'error' => $e->getMessage(),
                        'customer_id' => $customer->id
                    ]);
                    throw new \Exception('Gagal mengunggah foto ID tambahan 1: ' . $e->getMessage());
                }
            } else {
                Log::warning('Registration: No ID photo 1 provided', ['customer_id' => $customer->id]);
            }

            // Upload dan simpan foto ID tambahan 2
            // Upload dan simpan foto ID tambahan 2
            if ($request->hasFile('id_photo_2')) {
                try {
                    $idFile2 = $request->file('id_photo_2');
                    Log::info('Registration: Processing ID photo 2', [
                        'customer_id' => $customer->id,
                        'original_size' => $idFile2->getSize(),
                        'id_type' => $request->id_type_2,
                        'extension' => $idFile2->getClientOriginalExtension()
                    ]);

                    // Simpan file sementara
                    $tempPath2 = storage_path('app/public/temp/' . Str::random(20) . '.' . $idFile2->getClientOriginalExtension());
                    $idFile2->move(dirname($tempPath2), basename($tempPath2));

                    // Kompresi gambar jika ukurannya > 1MB
                    if ($idFile2->getSize() > 1024 * 1024) {
                        // Gunakan fungsi compressImage untuk kompresi
                        $compressedFileName = $this->compressImage($tempPath2);
                        $compressedPath = storage_path('app/public/' . $compressedFileName);

                        // Hapus file temp asli
                        if (file_exists($tempPath2)) {
                            @unlink($tempPath2);
                        }

                        $tempPath2 = $compressedPath;
                    }

                    $idFileName2 = 'id_2_' . $customer->id . '_' . time() . '.' . pathinfo($tempPath2, PATHINFO_EXTENSION);

                    $compressedFile2 = new UploadedFile(
                        $tempPath2,
                        $idFileName2,
                        mime_content_type($tempPath2),
                        null,
                        false
                    );

                    $idPath2 = $compressedFile2->storeAs('customer_photos', $idFileName2, 'public');

                    if (file_exists($tempPath2)) {
                        @unlink($tempPath2);
                    }

                    if (!$idPath2) {
                        throw new \Exception('Failed to store ID photo 2');
                    }

                    CustomerPhoto::create([
                        'customer_id' => $customer->id,
                        'photo_type' => 'additional_id_2',
                        'photo' => $idPath2,
                        'id_type' => $request->id_type_2,
                    ]);

                    Log::info('Registration: ID photo 2 uploaded successfully', [
                        'path' => $idPath2,
                        'id_type' => $request->id_type_2
                    ]);
                } catch (\Exception $e) {
                    Log::error('Registration: Failed to upload ID photo 2', [
                        'error' => $e->getMessage(),
                        'customer_id' => $customer->id
                    ]);
                    throw new \Exception('Gagal mengunggah foto ID tambahan 2: ' . $e->getMessage());
                }
            } else {
                Log::warning('Registration: No ID photo 2 provided', ['customer_id' => $customer->id]);
            }

            // Trigger event untuk verifikasi email
            event(new Registered($customer));

            // Kirim notifikasi ke admin (email + WhatsApp)
            $this->sendAdminNotification($customer);
            $this->sendAdminWhatsAppNotification($customer);

            return redirect()->route('registration.success')
                ->with('success', 'Registrasi berhasil! Silakan cek email Anda untuk verifikasi.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat registrasi. Silakan coba lagi.')
                ->withInput();
        }
    }

    private function sendAdminNotification($customer)
    {
        $adminEmail = 'global.photorental@gmail.com';
        $editUrl = url('/admin/customers/' . $customer->id . '/edit');

        // Get customer phone numbers
        $phone1 = $customer->customerPhoneNumbers->first()?->phone_number ?? 'Tidak ada';
        $phone2 = $customer->customerPhoneNumbers->skip(1)->first()?->phone_number ?? 'Tidak ada';

        // Create WhatsApp links
        $waLink1 = $phone1 !== 'Tidak ada' ? 'https://wa.me/' . preg_replace('/\D/', '', $phone1) : null;
        $waLink2 = $phone2 !== 'Tidak ada' ? 'https://wa.me/' . preg_replace('/\D/', '', $phone2) : null;

        $subject = 'Registrasi Customer Baru - Global Photo Rental';
        $message = "
            <h2>🆕 Registrasi Customer Baru</h2>
            <p>Customer baru telah mendaftar dengan detail:</p>
            <table style='border-collapse: collapse; width: 100%;'>
                <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Nama:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$customer->name}</td></tr>
                <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Email:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$customer->email}</td></tr>
                <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>HP 1:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$phone1}</td></tr>
                <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>HP 2:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$phone2}</td></tr>
                <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Alamat:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$customer->address}</td></tr>
                <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Status:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'><span style='background: #dc3545; color: white; padding: 4px 8px; border-radius: 4px;'>{$customer->status}</span></td></tr>
                <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Sumber Info:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$customer->source_info}</td></tr>
            </table>
            
            <div style='margin-top: 20px;'>
                <h3>🔗 Quick Actions:</h3>
                <p>
                    <a href='{$editUrl}' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>✏️ Edit Customer</a>";

        if ($waLink1) {
            $message .= "<a href='{$waLink1}' style='background: #25D366; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>📱 Chat HP 1</a>";
        }
        if ($waLink2) {
            $message .= "<a href='{$waLink2}' style='background: #25D366; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📱 Chat HP 2</a>";
        }

        $message .= "
                </p>
                <p style='margin-top: 15px; padding: 10px; background: #f8f9fa; border-left: 4px solid #007bff; font-style: italic;'>
                    ✅ Silakan lakukan verifikasi dan ubah status customer dari <strong>blacklist</strong> menjadi <strong>active</strong>.
                </p>
            </div>
        ";

        try {
            Mail::html($message, function ($mail) use ($adminEmail, $subject) {
                $mail->to($adminEmail)
                    ->subject($subject);
            });
        } catch (\Exception $e) {
        }
    }

    private function sendAdminWhatsAppNotification($customer)
    {
        try {
            $wahaService = new WAHAService();
            $adminPhone = '6281212349564';
            $editUrl = url('/admin/customers/' . $customer->id . '/edit');

            // Get customer phone numbers
            $phone1 = $customer->customerPhoneNumbers->first()?->phone_number ?? 'Tidak ada';
            $phone2 = $customer->customerPhoneNumbers->skip(1)->first()?->phone_number ?? 'Tidak ada';

            // Create WhatsApp links
            $waLink1 = $phone1 !== 'Tidak ada' ? 'https://wa.me/' . preg_replace('/\D/', '', $phone1) : 'Tidak ada';
            $waLink2 = $phone2 !== 'Tidak ada' ? 'https://wa.me/' . preg_replace('/\D/', '', $phone2) : 'Tidak ada';

            $message = "🆕 *CUSTOMER BARU MENDAFTAR*\n\n";
            $message .= "👤 *Nama:* {$customer->name}\n";
            $message .= "📧 *Email:* {$customer->email}\n";
            $message .= "📱 *HP 1:* {$phone1}\n";
            $message .= "📱 *HP 2:* {$phone2}\n";
            $message .= "🏠 *Alamat:* {$customer->address}\n";
            $message .= "💼 *Pekerjaan:* " . ($customer->job ?: 'Tidak diisi') . "\n";
            $message .= "📍 *Sumber Info:* {$customer->source_info}\n";
            $message .= "⚠️ *Status:* " . strtoupper($customer->status) . "\n\n";

            $message .= "🔗 *Quick Actions:*\n";
            $message .= "• Edit Customer: {$editUrl}\n";

            if ($waLink1 !== 'Tidak ada') {
                $message .= "• Chat HP 1: {$waLink1}\n";
            }
            if ($waLink2 !== 'Tidak ada') {
                $message .= "• Chat HP 2: {$waLink2}\n";
            }

            $message .= "\n✅ Silakan lakukan verifikasi dan ubah status customer.";

            $result = $wahaService->sendMessage($adminPhone, $message);

            if ($result) {
            }
        } catch (\Exception $e) {
        }
    }

    public function success()
    {
        return view('registration.success');
    }

    public function verifyEmail(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('registration.success')
                ->with('message', 'Email sudah terverifikasi sebelumnya.');
        }

        if ($user->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($user));
        }

        return redirect()->route('registration.success')
            ->with('success', 'Email berhasil diverifikasi!');
    }

    /**
     * Kompres gambar untuk mengurangi ukuran file
     * Target ukuran: < 1MB (800-900KB)
     * Mendukung format WebP jika browser mendukung
     * 
     * @param string $imagePath Path ke file gambar yang akan dikompresi
     * @return string Path relatif ke file hasil kompresi
     */
    /**
     * Deteksi apakah browser mendukung format WebP
     * 
     * @return bool True jika browser mendukung WebP, false jika tidak
     */
    protected function browserSupportsWebP()
    {
        // Deteksi dukungan WebP berdasarkan header User-Agent
        $userAgent = request()->header('User-Agent');

        // Browser yang mendukung WebP
        $supportsWebP = false;

        // Chrome 9+, Opera 12+, Firefox 65+, Edge 18+, Safari 14+
        if (
            preg_match('/Chrome\/[9-9][0-9]|Chrome\/[1-9][0-9]{2,}/', $userAgent) ||
            preg_match('/Opera\/[1-9][2-9]|Opera\/[2-9][0-9]/', $userAgent) ||
            preg_match('/Firefox\/[6-9][5-9]|Firefox\/[7-9][0-9]/', $userAgent) ||
            preg_match('/Edg\/[1-9][8-9]|Edg\/[2-9][0-9]/', $userAgent) ||
            preg_match('/Version\/1[4-9].*Safari/', $userAgent)
        ) {
            $supportsWebP = true;
        }

        // Jika header Accept tersedia, periksa apakah mencakup image/webp
        if (request()->header('Accept') && strpos(request()->header('Accept'), 'image/webp') !== false) {
            $supportsWebP = true;
        }

        return $supportsWebP;
    }

    private function compressImage($imagePath)
    {
        try {
            // Check if Imagick is available
            $useImagick = extension_loaded('imagick');
            $manager = new ImageManager($useImagick ? new \Intervention\Image\Drivers\Imagick\Driver() : new \Intervention\Image\Drivers\Gd\Driver());

            $originalSize = filesize($imagePath);
            Log::info('Starting image compression', ['original_size' => $originalSize, 'use_imagick' => $useImagick]);

            // Read image
            $image = $manager->read($imagePath);

            // 1. Resize to max width of 800px to reduce file size significantly
            if ($image->width() > 800) {
                $image->scale(width: 800);
                Log::info('Image resized to 800px width');
            }

            // 2. Remove metadata to save space
            $image = $image->removeMetadata();

            // 3. Determine output format: prefer WebP if supported, otherwise JPG
            $supportsWebP = $this->browserSupportsWebP();
            $outputFormat = $supportsWebP ? 'webp' : 'jpg';
            $outputExtension = $supportsWebP ? '.webp' : '.jpg';

            $tempDir = storage_path('app/public/temp');
            if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

            $compressedFileName = 'compressed_' . Str::random(10) . $outputExtension;
            $compressedPath = $tempDir . '/' . $compressedFileName;

            // 4. Apply progressive compression with quality reduction until file < 1MB
            $qualities = [85, 75, 65, 55, 45, 35, 25, 15];
            $targetSize = 1024 * 1024; // 1MB
            $finalQuality = 85;

            foreach ($qualities as $quality) {
                if ($outputFormat === 'webp') {
                    $image->toWebp($quality)->save($compressedPath);
                } else {
                    $image->toJpeg($quality)->save($compressedPath);
                }

                $currentSize = filesize($compressedPath);
                $finalQuality = $quality;

                if ($currentSize < $targetSize) {
                    break;
                }
            }

            // 5. If still > 1MB, try more aggressive resize
            if (filesize($compressedPath) >= $targetSize) {
                Log::info('File still too large, applying aggressive resize');
                $image = $manager->read($imagePath);
                $image->scale(width: 600)->removeMetadata();
                
                if ($outputFormat === 'webp') {
                    $image->toWebp(20)->save($compressedPath);
                } else {
                    $image->toJpeg(20)->save($compressedPath);
                }
                $finalQuality = 20;
            }

            // 6. Apply Imagick optimization if available
            if ($useImagick && class_exists('Imagick')) {
                try {
                    $imagick = new \Imagick($compressedPath);
                    
                    // Strip all metadata and profiles
                    $imagick->stripImage();
                    
                    // Optimize for web
                    $imagick->setImageFormat($outputFormat === 'webp' ? 'WEBP' : 'JPEG');
                    
                    // Apply additional optimization
                    if (method_exists($imagick, 'optimizeImageLayers')) {
                        $imagick->optimizeImageLayers();
                    }
                    
                    // Set compression quality
                    $imagick->setImageCompressionQuality($finalQuality);
                    
                    // Write optimized image
                    $imagick->writeImage($compressedPath);
                    $imagick->destroy();
                    
                    Log::info('Applied Imagick optimization');
                } catch (\Exception $e) {
                    Log::warning('Imagick optimization failed: ' . $e->getMessage());
                }
            }

            $finalSize = filesize($compressedPath);
            $compressionRatio = round(($originalSize - $finalSize) / $originalSize * 100);
            $compressionInfo = [
                'original_size' => $originalSize,
                'final_size' => $finalSize,
                'compression_ratio' => $compressionRatio,
                'format' => $outputFormat,
                'quality' => $finalQuality,
                'final_width' => 'N/A'
            ];

            // Get final width for logging
            try {
                $finalImage = $manager->read($compressedPath);
                $compressionInfo['final_width'] = $finalImage->width();
            } catch (\Exception $e) {
                // Ignore if we can't read final dimensions
            }

            Log::info("Image compression completed", $compressionInfo);

            return 'temp/' . $compressedFileName;
        } catch (\Exception $e) {
            Log::error('Image compression failed: ' . $e->getMessage(), [
                'original_path' => $imagePath,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback: copy original file
            $copyName = 'temp/original_' . Str::random(10) . '.' . pathinfo($imagePath, PATHINFO_EXTENSION);
            $fallbackPath = storage_path('app/public/' . $copyName);
            copy($imagePath, $fallbackPath);
            return $copyName;
        }
    }
    /**
     * Deteksi apakah browser mendukung format WebP
     * 
     * @return bool True jika browser mendukung WebP, false jika tidak
     */
}
