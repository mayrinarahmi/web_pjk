<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TargetAnggaran extends Model
{
    protected $table = 'target_anggaran';
    
    protected $fillable = [
        'tahun_anggaran_id',
        'kode_rekening_id',
        'skpd_id',
        'jumlah',
    ];
    
    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }
    
    public function kodeRekening()
    {
        return $this->belongsTo(KodeRekening::class);
    }
    
    public function skpd()
    {
        return $this->belongsTo(Skpd::class);
    }
    
    // ==========================================
    // SCOPES
    // ==========================================
    
    /**
     * Scope untuk filter by SKPD
     * Otomatis filter jika user adalah SKPD
     */
    public function scopeFilterBySkpd($query)
    {
        $user = Auth::user();
        
        if ($user && $user->skpd_id && !$user->canViewAllSkpd()) {
            return $query->where('skpd_id', $user->skpd_id);
        }
        
        return $query;
    }
    
    // ==========================================
    // STATIC METHODS - KONSOLIDASI
    // ==========================================
    
    /**
     * Get target anggaran KONSOLIDASI (SUM semua SKPD)
     */
    public static function getTargetAnggaranKonsolidasi($kodeRekeningId, $tahunAnggaranId)
    {
        return self::where('kode_rekening_id', $kodeRekeningId)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->sum('jumlah'); // SUM dari semua SKPD
    }
    
    /**
     * Get target anggaran untuk SKPD tertentu
     */
    public static function getTargetAnggaranBySkpd($kodeRekeningId, $tahunAnggaranId, $skpdId)
    {
        $target = self::where('kode_rekening_id', $kodeRekeningId)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('skpd_id', $skpdId)
            ->first();
            
        return $target ? $target->jumlah : 0;
    }
    
    /**
     * HYBRID METHOD - Untuk backward compatibility
     * Jika skpdId NULL = konsolidasi
     * Jika skpdId ada = per SKPD
     */
   /**
 * Get Target Anggaran (static method)
 * ✅ UPDATED untuk support recursive
 * 
 * @param int $kodeRekeningId
 * @param int $tahunAnggaranId
 * @param int|null $skpdId
 * @return float
 */
public static function getTargetAnggaran($kodeRekeningId, $tahunAnggaranId, $skpdId = null)
{
    if ($skpdId) {
        // Per SKPD
        return self::where('kode_rekening_id', $kodeRekeningId)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('skpd_id', $skpdId)
            ->value('jumlah') ?? 0;
    } else {
        // Konsolidasi - SUM semua SKPD
        return self::where('kode_rekening_id', $kodeRekeningId)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->sum('jumlah');
    }
}
    
     
    /**
     * Get target sampai dengan bulan tertentu
     */
    public static function getTargetSampaiDenganBulan($kodeRekeningId, $tahunAnggaranId, $bulan, $skpdId = null)
    {
        $target = self::getTargetAnggaran($kodeRekeningId, $tahunAnggaranId, $skpdId);
        
        // Cek apakah TargetBulan class exists
        if (class_exists('App\Models\TargetBulan')) {
            $persentase = \App\Models\TargetBulan::getPersentaseSampaiDenganBulan($tahunAnggaranId, $bulan);
            return $target * ($persentase / 100);
        }
        
        return $target;
    }
}