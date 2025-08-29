<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class WhatsAppAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if already authenticated
        if ($request->session()->get('whatsapp_authenticated')) {
            return $next($request);
        }

        // Check for login attempt
        if ($request->isMethod('post') && $request->has(['username', 'password'])) {
            $username = $request->input('username');
            $password = $request->input('password');

            if ($username === 'wahaadmin' && $password === 'Infrasglobal@100') {
                $request->session()->put('whatsapp_authenticated', true);
                return redirect()->route('whatsapp.dashboard');
            } else {
                return redirect()->back()->withErrors(['Invalid credentials']);
            }
        }

        // Show login form
        return response()->view('admin.whatsapp.login');
    }
}
