<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles; // TAMBAHKAN INI

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles; // TAMBAHKAN HasRoles

    // Relasi ke role lama TETAP DIPERTAHANKAN untuk sementara
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id', // tetap ada untuk backward compatibility
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}