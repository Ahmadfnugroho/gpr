<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPhoto;
use App\Models\CustomerPhoneNumber;
use App\Services\WAHAService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

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
            'email.unique' => 'Email ini sudah terdaftar dalam sistem kami. Jika Anda sudah memiliki akun, silakan hubungi customer service di WhatsApp +62 812-1234-9564 atau email global.photorental@gmail.com untuk bantuan.',
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
                    $this->storeUploadedImage($request->file('ktp_photo'), $customer->id, 'ktp', null);
                } catch (\Exception $e) {
                    Log::error('Registration: Failed to upload KTP photo', [
                        'error' => $e->getMessage(),
                        'customer_id' => $customer->id
                    ]);
                    throw new \Exception('Gagal mengunggah foto KTP: ' . $e->getMessage());
                }
            }

            // Upload dan simpan foto ID tambahan 1
            if ($request->hasFile('id_photo')) {
                try {
                    $this->storeUploadedImage($request->file('id_photo'), $customer->id, 'additional_id_1', $request->id_type);
                } catch (\Exception $e) {
                    Log::error('Registration: Failed to upload ID photo 1', [
                        'error' => $e->getMessage(),
                        'customer_id' => $customer->id
                    ]);
                    throw new \Exception('Gagal mengunggah foto ID tambahan 1: ' . $e->getMessage());
                }
            }

            // Upload dan simpan foto ID tambahan 2
            if ($request->hasFile('id_photo_2')) {
                try {
                    $this->storeUploadedImage($request->file('id_photo_2'), $customer->id, 'additional_id_2', $request->id_type_2);
                } catch (\Exception $e) {
                    Log::error('Registration: Failed to upload ID photo 2', [
                        'error' => $e->getMessage(),
                        'customer_id' => $customer->id
                    ]);
                    throw new \Exception('Gagal mengunggah foto ID tambahan 2: ' . $e->getMessage());
                }
            }

            // Trigger event untuk verifikasi email
            event(new Registered($customer));

            // Kirim notifikasi ke admin (email + WhatsApp)
            $this->sendAdminNotification($customer);
            $this->sendAdminWhatsAppNotification($customer);

            return redirect()->route('registration.success')
                ->with('success', 'Registrasi berhasil! Silakan cek email Anda untuk verifikasi.');
        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['ktp_photo', 'id_photo', 'id_photo_2']) // Exclude file data from logs
            ]);
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat registrasi: ' . $e->getMessage())
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
            Log::error('Registration: Failed to send admin email notification', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
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

            $wahaService->sendMessage($adminPhone, $message);
        } catch (\Exception $e) {
            Log::error('Registration: Failed to send admin WhatsApp notification', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function success()
    {
        return view('registration.success');
    }

    public function verifyEmail(Request $request)
    {
        $customer = Customer::findOrFail($request->route('id'));

        if ($customer->hasVerifiedEmail()) {
            return redirect()->route('registration.success')
                ->with('message', 'Email sudah terverifikasi sebelumnya.');
        }

        if ($customer->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($customer));
        }

        return redirect()->route('registration.success')
            ->with('success', 'Email berhasil diverifikasi!');
    }

    /**
     * Resend verification email for customer
     */
    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:customers,email'
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if ($customer->hasVerifiedEmail()) {
            return redirect()->back()
                ->with('message', 'Email sudah terverifikasi.');
        }

        // Trigger the Registered event to send queued verification email
        event(new Registered($customer));

        return redirect()->back()
            ->with('success', 'Email verifikasi telah dikirim ulang.');
    }

    /**
     * Validate, compress, and persist a registration photo.
     *
     * @throws \RuntimeException when the upload is invalid or storage fails.
     */
    private function storeUploadedImage(UploadedFile $file, int $customerId, string $photoType, ?string $idType): void
    {
        if (!$file->isValid()) {
            throw new \RuntimeException('File tidak valid atau rusak');
        }

        CustomerPhoto::create([
            'customer_id' => $customerId,
            'photo_type' => $photoType,
            'photo' => $this->compressImage($file),
            'id_type' => $idType,
        ]);
    }

    /**
     * Kompres gambar ke < 1MB dan simpan di disk public.
     * Format WebP dipakai bila browser mendukung, selain itu JPEG.
     */
    private function compressImage(UploadedFile $file): string
    {
        $manager = new ImageManager(extension_loaded('imagick') ? new ImagickDriver() : new GdDriver());
        $image = $manager->read($file->getPathname());

        if ($image->width() > 800) {
            $image->scale(width: 800);
        }

        $format = $this->browserSupportsWebP() ? 'webp' : 'jpg';
        $targetSize = 1024 * 1024;

        $encoded = null;
        foreach ([85, 75, 65, 55, 45, 35, 25, 15] as $quality) {
            $encoded = $format === 'webp' ? $image->toWebp($quality) : $image->toJpeg($quality);

            if ($encoded->size() < $targetSize) {
                break;
            }
        }

        $path = 'customer_photos/' . Str::random(40) . '.' . $format;

        if (!Storage::disk('public')->put($path, $encoded->toString())) {
            throw new \RuntimeException('Gagal menyimpan foto.');
        }

        return $path;
    }

    /**
     * Deteksi apakah browser mendukung format WebP.
     */
    protected function browserSupportsWebP(): bool
    {
        $accept = request()->header('Accept', '');
        if (str_contains($accept, 'image/webp')) {
            return true;
        }

        $userAgent = request()->header('User-Agent', '');

        return (bool) preg_match('/Chrome\/[1-9][0-9]+|Firefox\/(?:[7-9][0-9]|[6-9][5-9])|Edg\/[1-9][0-9]+|Version\/1[4-9].*Safari/', $userAgent);
    }
}
