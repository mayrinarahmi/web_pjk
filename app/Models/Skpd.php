<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Skpd extends Model
{
    use HasFactory;

    protected $table = 'skpd';

    protected $fillable = [
        'kode_opd',
        'nama_opd',
        'status',
        'kode_rekening_access'
    ];

    // REMOVE cast temporarily - kita handle manual
    // protected $casts = [
    //     'kode_rekening_access' => 'array'
    // ];

    /**
     * Accessor untuk kode_rekening_access
     * Handle both string (JSON) and array format
     */
    public function getKodeRekeningAccessAttribute($value)
    {
        // Jika null atau kosong, return array kosong
        if (empty($value)) {
            return [];
        }
        
        // Jika sudah array, langsung return
        if (is_array($value)) {
            return $value;
        }
        
        // Jika string, decode JSON
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        // Default return empty array
        return [];
    }

    /**
     * Mutator untuk kode_rekening_access
     * Always save as JSON string
     */
    public function setKodeRekeningAccessAttribute($value)
    {
        // Jika null atau bukan array, set ke array kosong
        if (!is_array($value)) {
            $value = [];
        }
        
        // Simpan sebagai JSON string
        $this->attributes['kode_rekening_access'] = json_encode($value);
    }

    /**
     * Relasi dengan User
     */
    public function users()
    {
        return $this->hasMany(User::class, 'skpd_id');
    }

    /**
     * Relasi dengan Penerimaan
     */
    public function penerimaan()
    {
        return $this->hasMany(Penerimaan::class, 'skpd_id');
    }

    /**
     * Helper method untuk cek apakah SKPD bisa akses kode rekening tertentu
     */
    public function canAccessKodeRekening($kodeRekeningId)
    {
        $access = $this->kode_rekening_access;
        
        if (empty($access) || !is_array($access)) {
            return false;
        }
        
        return in_array($kodeRekeningId, $access);
    }
    
    /**
     * Get count of assigned kode rekening
     */
    public function getAssignmentCountAttribute()
    {
        $access = $this->kode_rekening_access;
        return is_array($access) ? count($access) : 0;
    }
    
    /**
     * Get accessible kode rekening models
     */
    public function getAccessibleKodeRekening()
    {
        $access = $this->kode_rekening_access;
        
        if (empty($access) || !is_array($access)) {
            return collect([]);
        }
        
        return KodeRekening::whereIn('id', $access)
                          ->where('is_active', true)
                          ->get();
    }
}