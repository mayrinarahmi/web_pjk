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

    // ==========================================
    // HELPER METHODS - ROLE CHECKING
    // ==========================================
    
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

    // ==========================================
    // NEW HELPER METHODS - SKPD ACCESS
    // ==========================================
    
    /**
     * Check if user can view all SKPD data
     * Super Admin dan Kepala Badan bisa lihat semua
     */
    public function canViewAllSkpd()
    {
        return $this->isSuperAdmin() || $this->isKepalaBadan();
    }
    
    /**
     * Get accessible kode rekening IDs for current user
     * Including parent hierarchy (Level 1-6)
     * 
     * @return array Array of kode_rekening IDs that user can access
     */
    public function getAccessibleKodeRekeningIds()
    {
        // Super Admin dan Kepala Badan - akses semua
        if ($this->canViewAllSkpd()) {
            return []; // Empty array means no restriction
        }
        
        // User tanpa SKPD - tidak ada akses
        if (!$this->skpd_id || !$this->skpd) {
            return [0]; // Return dummy ID to prevent showing all data
        }
        
        // Operator SKPD - ambil dari skpd->kode_rekening_access
        return $this->skpd->getHierarchicalKodeRekeningIds();
    }
    
    /**
     * Get accessible level 6 kode rekening only
     * Used for dropdown selection
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getAccessibleLevel6KodeRekening()
    {
        // Super Admin dan Kepala Badan - semua level 6
        if ($this->canViewAllSkpd()) {
            return KodeRekening::where('level', 6)
                              ->where('is_active', true)
                              ->orderBy('kode')
                              ->get();
        }
        
        // User tanpa SKPD - kosong
        if (!$this->skpd_id || !$this->skpd) {
            return collect([]);
        }
        
        // Operator SKPD - hanya yang di-assign
        $skpdAccess = $this->skpd->kode_rekening_access ?? [];
        
        if (empty($skpdAccess)) {
            return collect([]);
        }
        
        return KodeRekening::whereIn('id', $skpdAccess)
                          ->where('level', 6)
                          ->where('is_active', true)
                          ->orderBy('kode')
                          ->get();
    }

    // ==========================================
    // PERMISSION HELPER METHODS
    // ==========================================
    
    /**
     * Helper untuk filter query berdasarkan SKPD
     * @deprecated Use getAccessibleKodeRekeningIds() instead
     */
    public function scopeFilterBySkpd($query)
    {
        if ($this->canViewAllSkpd()) {
            return $query;
        }
        
        if ($this->skpd_id) {
            return $query->where('skpd_id', $this->skpd_id);
        }
        
        return $query;
    }
    
    /**
     * Helper untuk cek apakah user bisa edit data
     */
    public function canEdit()
    {
        // Kepala Badan tidak bisa edit
        if ($this->isKepalaBadan()) {
            return false;
        }
        
        // Super Admin dan Operator bisa edit
        return $this->isSuperAdmin() || $this->isOperator() || $this->isOperatorSkpd();
    }
    
    /**
     * Helper untuk cek apakah user bisa delete data
     */
    public function canDelete()
    {
        return $this->canEdit();
    }
    
    /**
     * Helper untuk cek apakah user bisa create data
     */
    public function canCreate()
    {
        return $this->canEdit();
    }
}