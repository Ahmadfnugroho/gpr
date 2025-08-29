<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserPhoto;
use App\Models\UserPhoneNumber;
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
            'source_info' => 'required|in:Instagram,Teman,Google,Lainnya',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'address' => 'required|string',
            'phone1' => 'required|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'job' => 'nullable|string|max:255',
            'office_address' => 'nullable|string',
            'instagram_username' => 'nullable|string|max:255',
            'ktp_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_number' => 'required|string|max:20',
        ], [
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'source_info.required' => 'Silakan pilih dari mana Anda mengetahui Global Photo Rental',
            'name.required' => 'Nama lengkap wajib diisi',
            'gender.required' => 'Jenis kelamin wajib dipilih',
            'address.required' => 'Alamat tinggal wajib diisi',
            'phone1.required' => 'Nomor HP 1 wajib diisi',
            'ktp_photo.required' => 'Foto KTP wajib diunggah',
            'ktp_photo.image' => 'File harus berupa gambar',
            'ktp_photo.mimes' => 'Format foto harus jpeg, png, atau jpg',
            'ktp_photo.max' => 'Ukuran foto maksimal 2MB',
            'emergency_contact_name.required' => 'Nama kontak emergency wajib diisi',
            'emergency_contact_number.required' => 'Nomor HP kontak emergency wajib diisi',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Buat user baru
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make('default123'), // Password default
                'gender' => $request->gender,
                'address' => $request->address,
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

            // Trigger event untuk verifikasi email
            event(new Registered($user));

            // Kirim notifikasi ke admin
            $this->sendAdminNotification($user);

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
        $adminEmail = 'dissamustika96@gmail.com';
        $editUrl = url('/admin/users/' . $user->id . '/edit'); // Sesuaikan dengan URL admin panel Anda

        $subject = 'Registrasi User Baru - Global Photo Rental';
        $message = "
            <h2>Registrasi User Baru</h2>
            <p>User baru telah mendaftar dengan detail:</p>
            <ul>
                <li><strong>Nama:</strong> {$user->name}</li>
                <li><strong>Email:</strong> {$user->email}</li>
                <li><strong>Status:</strong> {$user->status}</li>
                <li><strong>Sumber Info:</strong> {$user->source_info}</li>
            </ul>
            <p><a href='{$editUrl}' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Edit User</a></p>
        ";

        try {
            Mail::html($message, function ($mail) use ($adminEmail, $subject) {
                $mail->to($adminEmail)
                    ->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error('Failed to send admin notification: ' . $e->getMessage());
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
