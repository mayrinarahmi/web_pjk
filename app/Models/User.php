<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    // Relasi ke role lama TETAP DIPERTAHANKAN untuk backward compatibility
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Relasi ke SKPD
    public function skpd()
    {
        return $this->belongsTo(Skpd::class, 'skpd_id');
    }

    protected $fillable = [
        'name',
        'nip',
        'email',
        'password',
        'role_id',    // tetap ada untuk backward compatibility
        'skpd_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Helper methods untuk cek role
    public function isSuperAdmin()
    {
        // Cek dengan Spatie Role
        if ($this->hasRole('Super Admin') || $this->hasRole('Administrator')) {
            return true;
        }
        
        // Fallback ke logic lama
        return $this->role_id == 1 && $this->skpd_id == null;
    }

    public function isKepalaBadan()
    {
        // Cek dengan Spatie Role
        if ($this->hasRole('Kepala Badan')) {
            return true;
        }
        
        // Fallback ke logic lama
        return $this->role_id == 1 && $this->skpd && $this->skpd->kode_opd == '5.02.0.00.0.00.05.0000';
    }

    public function isOperatorSkpd()
    {
        // Cek dengan Spatie Role
        if ($this->hasRole('Operator SKPD')) {
            return true;
        }
        
        // Fallback ke logic lama
        return $this->role_id == 2 && $this->skpd_id != null;
    }
    
    public function isOperator()
    {
        // Operator lama (backward compatibility)
        return $this->hasRole('Operator') || ($this->role_id == 2 && $this->skpd_id == null);
    }
    
    public function isViewer()
    {
        return $this->hasRole('Viewer') || $this->role_id == 3;
    }

    // Helper untuk filter query berdasarkan SKPD
    public function scopeFilterBySkpd($query)
    {
        if ($this->isSuperAdmin() || $this->isKepalaBadan()) {
            // Super Admin dan Kepala Badan bisa lihat semua
            return $query;
        }
        
        if ($this->skpd_id) {
            // Operator SKPD hanya lihat data SKPD sendiri
            return $query->where('skpd_id', $this->skpd_id);
        }
        
        return $query;
    }
    
    // Helper untuk cek apakah user bisa edit data
    public function canEdit()
    {
        // Kepala Badan tidak bisa edit
        if ($this->isKepalaBadan()) {
            return false;
        }
        
        // Super Admin dan Operator bisa edit
        return $this->isSuperAdmin() || $this->isOperator() || $this->isOperatorSkpd();
    }
    
    // Helper untuk cek apakah user bisa delete data
    public function canDelete()
    {
        // Sama dengan canEdit
        return $this->canEdit();
    }
    
    // Helper untuk cek apakah user bisa create data
    public function canCreate()
    {
        // Sama dengan canEdit
        return $this->canEdit();
    }
}