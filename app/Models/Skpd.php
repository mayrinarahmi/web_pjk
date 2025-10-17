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

    // ==========================================
    // ACCESSOR & MUTATOR
    // ==========================================
    
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

    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
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

    // ==========================================
    // HELPER METHODS - KODE REKENING ACCESS
    // ==========================================
    
    /**
     * Helper method untuk cek apakah SKPD bisa akses kode rekening tertentu
     * 
     * @param int $kodeRekeningId
     * @return bool
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
     * 
     * @return int
     */
    public function getAssignmentCountAttribute()
    {
        $access = $this->kode_rekening_access;
        return is_array($access) ? count($access) : 0;
    }
    
    /**
     * Get accessible kode rekening models (Level 6 only)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAccessibleKodeRekening()
    {
        $access = $this->kode_rekening_access;
        
        if (empty($access) || !is_array($access)) {
            return collect([]);
        }
        
        return KodeRekening::whereIn('id', $access)
                          ->where('is_active', true)
                          ->orderBy('kode')
                          ->get();
    }
    
    /**
     * Get hierarchical kode rekening IDs
     * Include Level 6 yang di-assign + semua parent (Level 1-5)
     * 
     * CRITICAL METHOD untuk filter penerimaan
     * 
     * @return array Array of kode_rekening IDs including hierarchy
     */
    public function getHierarchicalKodeRekeningIds()
    {
        $access = $this->kode_rekening_access;
        
        // Jika tidak ada assignment, return array kosong
        if (empty($access) || !is_array($access)) {
            return [];
        }
        
        $allowedIds = [];
        
        // Load Level 6 yang di-assign
        $level6Kodes = KodeRekening::whereIn('id', $access)
                                   ->where('is_active', true)
                                   ->with('parent.parent.parent.parent.parent') // Eager load all parents
                                   ->get();
        
        foreach ($level6Kodes as $kode6) {
            // Tambahkan Level 6 itu sendiri
            $allowedIds[] = $kode6->id;
            
            // Tambahkan semua parent sampai ke root (Level 1)
            $currentKode = $kode6;
            while ($currentKode->parent_id && $currentKode->parent) {
                $allowedIds[] = $currentKode->parent_id;
                $currentKode = $currentKode->parent;
            }
        }
        
        // Remove duplicates
        return array_unique($allowedIds);
    }
    
    /**
     * Get Level 6 IDs only (no parent hierarchy)
     * Used for filtering actual penerimaan data
     * 
     * @return array
     */
    public function getLevel6KodeRekeningIds()
    {
        $access = $this->kode_rekening_access;
        
        if (empty($access) || !is_array($access)) {
            return [];
        }
        
        // Pastikan hanya Level 6
        return KodeRekening::whereIn('id', $access)
                          ->where('level', 6)
                          ->where('is_active', true)
                          ->pluck('id')
                          ->toArray();
    }
    
    /**
     * Get readable kode rekening list for display
     * Format: "4.1.02.01.01.0001 - Retribusi Pelayanan Kesehatan di Puskesmas"
     * 
     * @return array
     */
    public function getReadableKodeRekeningList()
    {
        $kodes = $this->getAccessibleKodeRekening();
        
        return $kodes->map(function($kode) {
            return $kode->kode . ' - ' . $kode->nama;
        })->toArray();
    }
    
    /**
     * Check if SKPD has any kode rekening assigned
     * 
     * @return bool
     */
    public function hasKodeRekeningAssigned()
    {
        $access = $this->kode_rekening_access;
        return !empty($access) && is_array($access) && count($access) > 0;
    }
    
    /**
     * Sync kode rekening access
     * Used when updating assignments from Kelola SKPD
     * 
     * @param array $kodeRekeningIds Array of kode_rekening IDs (Level 6)
     * @return bool
     */
    public function syncKodeRekeningAccess(array $kodeRekeningIds)
    {
        // Validate: pastikan semua ID adalah Level 6
        $validIds = KodeRekening::whereIn('id', $kodeRekeningIds)
                                ->where('level', 6)
                                ->where('is_active', true)
                                ->pluck('id')
                                ->toArray();
        
        $this->kode_rekening_access = $validIds;
        return $this->save();
    }
    
    // ==========================================
    // SCOPE METHODS
    // ==========================================
    
    /**
     * Scope untuk SKPD aktif
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }
    
    /**
     * Scope untuk SKPD yang sudah punya assignment
     */
    public function scopeHasAssignment($query)
    {
        return $query->whereNotNull('kode_rekening_access')
                    ->where('kode_rekening_access', '!=', '[]')
                    ->where('kode_rekening_access', '!=', 'null');
    }
    
    /**
     * Scope untuk SKPD yang belum punya assignment
     */
    public function scopeNoAssignment($query)
    {
        return $query->where(function($q) {
            $q->whereNull('kode_rekening_access')
              ->orWhere('kode_rekening_access', '[]')
              ->orWhere('kode_rekening_access', 'null');
        });
    }
}