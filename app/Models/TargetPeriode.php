<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TargetPeriode extends Model
{
    protected $table = 'target_periode';
    
    protected $fillable = [
        'tahun_anggaran_id',
        'nama_periode',
        'bulan_awal',
        'bulan_akhir',
        'persentase',
    ];
    
    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }
    
    // Fungsi untuk mendapatkan persentase target untuk bulan tertentu
    public static function getPersentaseForBulan($tahunAnggaranId, $bulan)
    {
        $periode = self::where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('bulan_awal', '<=', $bulan)
            ->where('bulan_akhir', '>=', $bulan)
            ->first();
        
        return $periode ? $periode->persentase : 0;
    }
    
    public static function getPersentaseForWeek($tahunAnggaranId, $tanggalSelesai)
{
    // Hitung berapa minggu di tahun ini sampai tanggal tersebut
    $tanggal = Carbon::parse($tanggalSelesai);
    $bulan = $tanggal->month;
    $mingguDalamBulan = ceil($tanggal->day / 7);
    
    // Cari periode yang mencakup bulan ini
    $periode = self::where('tahun_anggaran_id', $tahunAnggaranId)
        ->where('bulan_awal', '<=', $bulan)
        ->where('bulan_akhir', '>=', $bulan)
        ->first();
    
    if (!$periode) {
        return 0;
    }
    
    // Hitung jumlah total minggu dalam periode
    $totalMinggu = 0;
    for ($i = $periode->bulan_awal; $i <= $periode->bulan_akhir; $i++) {
        $totalMinggu += Carbon::createFromDate($tanggal->year, $i, 1)->daysInMonth / 7;
    }
    
    // Hitung jumlah minggu yang sudah berlalu dalam periode
    $mingguBerlalu = 0;
    for ($i = $periode->bulan_awal; $i < $bulan; $i++) {
        $mingguBerlalu += Carbon::createFromDate($tanggal->year, $i, 1)->daysInMonth / 7;
    }
    $mingguBerlalu += $mingguDalamBulan;
    
    // Hitung persentase secara proporsional
    $proporsi = $mingguBerlalu / $totalMinggu;
    return $periode->persentase * $proporsi;
}

    // Fungsi untuk validasi overlap periode
    public static function isOverlap($tahunAnggaranId, $bulanAwal, $bulanAkhir, $excludeId = null)
    {
        $query = self::where('tahun_anggaran_id', $tahunAnggaranId)
            ->where(function($q) use ($bulanAwal, $bulanAkhir) {
                // Cek jika ada periode lain yang overlap
                $q->where(function($q2) use ($bulanAwal, $bulanAkhir) {
                    // Periode lain mencakup awal periode ini
                    $q2->where('bulan_awal', '<=', $bulanAwal)
                       ->where('bulan_akhir', '>=', $bulanAwal);
                })->orWhere(function($q2) use ($bulanAwal, $bulanAkhir) {
                    // Periode lain mencakup akhir periode ini
                    $q2->where('bulan_awal', '<=', $bulanAkhir)
                       ->where('bulan_akhir', '>=', $bulanAkhir);
                })->orWhere(function($q2) use ($bulanAwal, $bulanAkhir) {
                    // Periode lain berada di dalam periode ini
                    $q2->where('bulan_awal', '>=', $bulanAwal)
                       ->where('bulan_akhir', '<=', $bulanAkhir);
                });
            });
        
        // Exclude ID yang sedang diedit
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}