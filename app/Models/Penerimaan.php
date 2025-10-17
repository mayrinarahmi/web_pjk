<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Penerimaan extends Model
{
    protected $table = 'penerimaan';
    
    protected $fillable = [
        'kode_rekening_id',
        'tahun',
        'tanggal',
        'jumlah',
        'keterangan',
        'skpd_id',
        'created_by',
    ];
    
    protected $casts = [
        'tanggal' => 'date',
        'tahun' => 'integer',
    ];
    
    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
    public function kodeRekening()
    {
        return $this->belongsTo(KodeRekening::class);
    }
    
    public function skpd()
    {
        return $this->belongsTo(Skpd::class, 'skpd_id');
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    // ==========================================
    // SCOPE METHODS - FILTERING
    // ==========================================
    
    /**
     * Scope untuk filter berdasarkan SKPD
     * Auto-detect user role dan apply filter sesuai
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $skpdId Optional: Force specific SKPD ID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterBySkpd($query, $skpdId = null)
    {
        $user = auth()->user();
        
        // Jika Super Admin atau Kepala Badan
        if ($user->canViewAllSkpd()) {
            // Jika ada parameter skpdId (dari filter dropdown)
            if ($skpdId) {
                return $query->where('skpd_id', $skpdId);
            }
            // Jika tidak, tampilkan semua
            return $query;
        }
        
        // Jika Operator SKPD - hanya lihat data SKPD sendiri
        if ($user->skpd_id) {
            return $query->where('skpd_id', $user->skpd_id);
        }
        
        // Default: tidak ada data (untuk user tanpa SKPD)
        return $query->where('skpd_id', 0); // Dummy ID
    }
    
    /**
     * Scope untuk filter berdasarkan kode rekening yang accessible
     * Untuk Operator SKPD: hanya kode yang di-assign
     * Untuk Admin: semua kode
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|null $allowedKodeIds Optional: Array of allowed kode_rekening IDs
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByKodeRekeningAccess($query, $allowedKodeIds = null)
    {
        $user = auth()->user();
        
        // Jika Super Admin atau Kepala Badan - no filter
        if ($user->canViewAllSkpd()) {
            return $query;
        }
        
        // Jika ada parameter allowedKodeIds, gunakan itu
        if ($allowedKodeIds !== null && is_array($allowedKodeIds)) {
            if (empty($allowedKodeIds)) {
                return $query->where('kode_rekening_id', 0); // Dummy ID
            }
            return $query->whereIn('kode_rekening_id', $allowedKodeIds);
        }
        
        // Auto-detect dari user SKPD
        if ($user->skpd_id && $user->skpd) {
            $level6Ids = $user->skpd->getLevel6KodeRekeningIds();
            
            if (empty($level6Ids)) {
                return $query->where('kode_rekening_id', 0); // Dummy ID
            }
            
            return $query->whereIn('kode_rekening_id', $level6Ids);
        }
        
        // Default: tidak ada data
        return $query->where('kode_rekening_id', 0); // Dummy ID
    }
    
    /**
     * Scope untuk filter berdasarkan tahun
     */
    public function scopeByTahun($query, $tahun)
    {
        return $query->where('tahun', $tahun);
    }
    
    /**
     * Scope untuk filter berdasarkan rentang tanggal
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereDate('tanggal', '>=', $startDate)
                    ->whereDate('tanggal', '<=', $endDate);
    }
    
    /**
     * Scope untuk filter berdasarkan bulan
     */
    public function scopeByMonth($query, $tahun, $bulan)
    {
        return $query->where('tahun', $tahun)
                    ->whereMonth('tanggal', $bulan);
    }
    
    // ==========================================
    // STATIC HELPER METHODS
    // ==========================================
    
    /**
     * Get total penerimaan berdasarkan kode rekening, tahun, dan rentang tanggal
     */
    public static function getTotalPenerimaan($kodeRekeningId, $tahun, $tanggalMulai, $tanggalSelesai)
    {
        return self::where('kode_rekening_id', $kodeRekeningId)
            ->where('tahun', $tahun)
            ->whereDate('tanggal', '>=', $tanggalMulai)
            ->whereDate('tanggal', '<=', $tanggalSelesai)
            ->sum('jumlah');
    }
    
    /**
     * Get total penerimaan per bulan
     */
    public static function getTotalPenerimaanPerBulan($kodeRekeningId, $tahun, $bulan)
    {
        return self::where('kode_rekening_id', $kodeRekeningId)
            ->where('tahun', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->sum('jumlah');
    }
    
    /**
     * Get total penerimaan sampai dengan bulan tertentu
     */
    public static function getTotalPenerimaanSampaiDenganBulan($kodeRekeningId, $tahun, $bulan)
    {
        $tanggalAkhir = Carbon::create($tahun, $bulan)->endOfMonth();
        
        return self::where('kode_rekening_id', $kodeRekeningId)
            ->where('tahun', $tahun)
            ->whereDate('tanggal', '<=', $tanggalAkhir)
            ->sum('jumlah');
    }
    
    /**
     * Get total by tahun and kode rekening (multiple IDs)
     */
    public static function getTotalByTahunAndKodeRekening($tahun, $kodeRekeningIds)
    {
        return self::where('tahun', $tahun)
            ->whereIn('kode_rekening_id', $kodeRekeningIds)
            ->sum('jumlah');
    }
    
    /**
     * Get total bulanan (multiple kode rekening IDs)
     */
    public static function getTotalBulanan($tahun, $kodeRekeningIds, $bulan)
    {
        return self::where('tahun', $tahun)
            ->whereIn('kode_rekening_id', $kodeRekeningIds)
            ->whereMonth('tanggal', $bulan)
            ->sum('jumlah');
    }
    
    /**
     * Get total penerimaan with SKPD filter
     * NEW METHOD untuk support multi-tenant
     */
    public static function getTotalWithSkpdFilter($tahun, $tanggalMulai = null, $tanggalSelesai = null, $skpdId = null)
    {
        $query = self::where('tahun', $tahun);
        
        if ($tanggalMulai && $tanggalSelesai) {
            $query->whereDate('tanggal', '>=', $tanggalMulai)
                  ->whereDate('tanggal', '<=', $tanggalSelesai);
        }
        
        // Apply SKPD filter
        $user = auth()->user();
        if ($user->canViewAllSkpd()) {
            if ($skpdId) {
                $query->where('skpd_id', $skpdId);
            }
        } elseif ($user->skpd_id) {
            $query->where('skpd_id', $user->skpd_id);
        }
        
        return $query->sum('jumlah');
    }
}