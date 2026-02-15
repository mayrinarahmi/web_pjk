<?php
// Reset password semua Operator SKPD ke "banjarmasin"
// Jalankan: php artisan tinker reset_password_operator.php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$users = User::whereHas('roles', function($q) {
    $q->where('name', 'Operator SKPD');
})->get();

echo "Operator SKPD ditemukan: " . $users->count() . " akun\n";

foreach ($users as $user) {
    $user->password = Hash::make('banjarmasin');
    $user->save();
    echo "  - {$user->name} ({$user->email}) ✓\n";
}

echo "\nSelesai! Semua password Operator SKPD direset ke 'banjarmasin'\n";
