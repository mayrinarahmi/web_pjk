<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Cek apakah user sudah login
        if (!$request->user()) {
            return redirect('/')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Method 1: Cek dengan role lama (backward compatibility)
        if ($request->user()->role && in_array($request->user()->role->name, $roles)) {
            return $next($request);
        }

        // Method 2: Cek dengan Spatie Permission
        if ($request->user()->hasAnyRole($roles)) {
            return $next($request);
        }

        // Jika tidak punya akses, redirect ke dashboard dengan pesan error
        return redirect('/dashboard')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
    }
}