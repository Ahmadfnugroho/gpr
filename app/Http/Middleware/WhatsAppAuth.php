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

        // Redirect to login form
        return redirect()->route('whatsapp.login.form');
    }
}
