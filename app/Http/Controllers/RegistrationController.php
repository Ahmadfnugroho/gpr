<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserPhoto;
use App\Models\UserPhoneNumber;
use App\Services\WAHAService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
    public function showForm()
    {
        return view('registration.form');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
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
            'ktp_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'id_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'id_type' => 'required|string|max:50',
            'id_photo_2' => 'required|image|mimes:jpeg,png,jpg|max:2048',
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
            'ktp_photo.mimes' => 'Format foto KTP harus jpeg, png, atau jpg',
            'ktp_photo.max' => 'Ukuran foto KTP maksimal 2MB',
            'id_photo.required' => 'Foto ID tambahan 1 wajib diunggah',
            'id_photo.image' => 'File ID tambahan 1 harus berupa gambar',
            'id_photo.mimes' => 'Format foto ID tambahan 1 harus jpeg, png, atau jpg',
            'id_photo.max' => 'Ukuran foto ID tambahan 1 maksimal 2MB',
            'id_type.required' => 'Jenis ID tambahan 1 wajib dipilih',
            'id_photo_2.required' => 'Foto ID tambahan 2 wajib diunggah',
            'id_photo_2.image' => 'File ID tambahan 2 harus berupa gambar',
            'id_photo_2.mimes' => 'Format foto ID tambahan 2 harus jpeg, png, atau jpg',
            'id_photo_2.max' => 'Ukuran foto ID tambahan 2 maksimal 2MB',
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

            // Buat user baru
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make('default123'), // Password default
                'gender' => $request->gender,
                'address' => $fullAddress,
                'job' => $request->job,
                'office_address' => $request->office_address,
                'instagram_username' => $request->instagram_username,
                'source_info' => $request->source_info,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_number' => $request->emergency_contact_number,
                'status' => 'blacklist', // Default status
            ]);

            // Simpan nomor HP
            UserPhoneNumber::create([
                'user_id' => $user->id,
                'phone_number' => $request->phone1,
            ]);

            if ($request->phone2) {
                UserPhoneNumber::create([
                    'user_id' => $user->id,
                    'phone_number' => $request->phone2,
                ]);
            }

            // Upload dan simpan foto KTP
            if ($request->hasFile('ktp_photo')) {
                $ktpFile = $request->file('ktp_photo');
                $ktpFileName = 'ktp_' . $user->id . '_' . time() . '.' . $ktpFile->getClientOriginalExtension();
                $ktpPath = $ktpFile->storeAs('user_photos', $ktpFileName, 'public');

                UserPhoto::create([
                    'user_id' => $user->id,
                    'photo_type' => 'ktp',
                    'photo' => $ktpPath,
                ]);
            }

            // Upload dan simpan foto ID tambahan 1
            if ($request->hasFile('id_photo')) {
                $idFile = $request->file('id_photo');
                $idFileName = 'id_1_' . $user->id . '_' . time() . '.' . $idFile->getClientOriginalExtension();
                $idPath = $idFile->storeAs('user_photos', $idFileName, 'public');

                UserPhoto::create([
                    'user_id' => $user->id,
                    'photo_type' => 'additional_id_1',
                    'photo' => $idPath,
                    'id_type' => $request->id_type,
                ]);
            }

            // Upload dan simpan foto ID tambahan 2
            if ($request->hasFile('id_photo_2')) {
                $idFile2 = $request->file('id_photo_2');
                $idFileName2 = 'id_2_' . $user->id . '_' . time() . '.' . $idFile2->getClientOriginalExtension();
                $idPath2 = $idFile2->storeAs('user_photos', $idFileName2, 'public');

                UserPhoto::create([
                    'user_id' => $user->id,
                    'photo_type' => 'additional_id_2',
                    'photo' => $idPath2,
                    'id_type' => $request->id_type_2,
                ]);
            }

            // Trigger event untuk verifikasi email
            event(new Registered($user));

            // Kirim notifikasi ke admin (email + WhatsApp)
            $this->sendAdminNotification($user);
            $this->sendAdminWhatsAppNotification($user);

            return redirect()->route('registration.success')
                ->with('success', 'Registrasi berhasil! Silakan cek email Anda untuk verifikasi.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat registrasi. Silakan coba lagi.')
                ->withInput();
        }
    }

    private function sendAdminNotification($user)
    {
        $adminEmail = 'global.photorental@gmail.com';
        $editUrl = url('/admin/users/' . $user->id . '/edit');

        // Get user phone numbers
        $phone1 = $user->userPhoneNumbers->first()?->phone_number ?? 'Tidak ada';
        $phone2 = $user->userPhoneNumbers->skip(1)->first()?->phone_number ?? 'Tidak ada';

        // Create WhatsApp links
        $waLink1 = $phone1 !== 'Tidak ada' ? 'https://wa.me/' . preg_replace('/\D/', '', $phone1) : null;
        $waLink2 = $phone2 !== 'Tidak ada' ? 'https://wa.me/' . preg_replace('/\D/', '', $phone2) : null;

        $subject = 'Registrasi User Baru - Global Photo Rental';
        $message = "
            <h2>🆕 Registrasi User Baru</h2>
            <p>User baru telah mendaftar dengan detail:</p>
            <table style='border-collapse: collapse; width: 100%;'>
                <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Nama:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$user->name}</td></tr>
                <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Email:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$user->email}</td></tr>
                <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>HP 1:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$phone1}</td></tr>
                <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>HP 2:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$phone2}</td></tr>
                <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Alamat:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$user->address}</td></tr>
                <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Status:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'><span style='background: #dc3545; color: white; padding: 4px 8px; border-radius: 4px;'>{$user->status}</span></td></tr>
                <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Sumber Info:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$user->source_info}</td></tr>
            </table>
            
            <div style='margin-top: 20px;'>
                <h3>🔗 Quick Actions:</h3>
                <p>
                    <a href='{$editUrl}' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>✏️ Edit User</a>";

        if ($waLink1) {
            $message .= "<a href='{$waLink1}' style='background: #25D366; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>📱 Chat HP 1</a>";
        }
        if ($waLink2) {
            $message .= "<a href='{$waLink2}' style='background: #25D366; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📱 Chat HP 2</a>";
        }

        $message .= "
                </p>
                <p style='margin-top: 15px; padding: 10px; background: #f8f9fa; border-left: 4px solid #007bff; font-style: italic;'>
                    ✅ Silakan lakukan verifikasi dan ubah status user dari <strong>blacklist</strong> menjadi <strong>active</strong>.
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

    private function sendAdminWhatsAppNotification($user)
    {
        try {
            $wahaService = new WAHAService();
            $adminPhone = '6281212349564';
            $editUrl = url('/admin/users/' . $user->id . '/edit');

            // Get user phone numbers
            $phone1 = $user->userPhoneNumbers->first()?->phone_number ?? 'Tidak ada';
            $phone2 = $user->userPhoneNumbers->skip(1)->first()?->phone_number ?? 'Tidak ada';

            // Create WhatsApp links
            $waLink1 = $phone1 !== 'Tidak ada' ? 'https://wa.me/' . preg_replace('/\D/', '', $phone1) : 'Tidak ada';
            $waLink2 = $phone2 !== 'Tidak ada' ? 'https://wa.me/' . preg_replace('/\D/', '', $phone2) : 'Tidak ada';

            $message = "🆕 *USER BARU MENDAFTAR*\n\n";
            $message .= "👤 *Nama:* {$user->name}\n";
            $message .= "📧 *Email:* {$user->email}\n";
            $message .= "📱 *HP 1:* {$phone1}\n";
            $message .= "📱 *HP 2:* {$phone2}\n";
            $message .= "🏠 *Alamat:* {$user->address}\n";
            $message .= "💼 *Pekerjaan:* " . ($user->job ?: 'Tidak diisi') . "\n";
            $message .= "📍 *Sumber Info:* {$user->source_info}\n";
            $message .= "⚠️ *Status:* " . strtoupper($user->status) . "\n\n";

            $message .= "🔗 *Quick Actions:*\n";
            $message .= "• Edit User: {$editUrl}\n";

            if ($waLink1 !== 'Tidak ada') {
                $message .= "• Chat HP 1: {$waLink1}\n";
            }
            if ($waLink2 !== 'Tidak ada') {
                $message .= "• Chat HP 2: {$waLink2}\n";
            }

            $message .= "\n✅ Silakan lakukan verifikasi dan ubah status user.";

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
}
