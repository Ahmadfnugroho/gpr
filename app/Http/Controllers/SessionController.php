<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class SessionController extends Controller
{
    function index()
    {
        return view('login');
    }
    function Login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required'
        ], [
            'email.required' => 'Email Harus Diisi',
            'password.required' => 'Password Harus Diisi'
        ]);

        $infologin = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        if (Auth::attempt($infologin)) {
            return redirect()->intended('/admin')->with('success', 'Login Berhasil');
        } else {
            return redirect('')->withErrors('Username Atau Password Salah')->withInput();
        }
    }

    public function showForgotPasswordForm()
    {
        return view('forgot-password');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'email.exists' => 'Email tidak terdaftar dalam sistem'
        ]);

        try {
            // Generate password baru yang random
            $newPassword = Str::random(12);
            
            // Jalankan artisan command untuk reset password
            $exitCode = Artisan::call('admin:reset-password', [
                'email' => $request->email,
                'password' => $newPassword
            ]);

            if ($exitCode === 0) {
                return redirect()->route('forgot.password')
                    ->with('success', "Password berhasil direset! Password baru Anda: <strong>{$newPassword}</strong><br>Silakan login dengan password baru dan segera ubah password Anda.");
            } else {
                return redirect()->route('forgot.password')
                    ->withErrors('Gagal mereset password. Silakan coba lagi.');
            }
        } catch (\Exception $e) {
            return redirect()->route('forgot.password')
                ->withErrors('Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
