<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Penerimaan extends Model
{
    protected $table = 'penerimaan';
    
    protected $fillable = [
        'kode_rekening_id',
        'tahun', // Ubah dari tahun_anggaran_id ke tahun
        'tanggal',
        'jumlah',
        'keterangan',
    ];
    
    protected $casts = [
        'tanggal' => 'date',
        'tahun' => 'integer',
    ];
    
    public function kodeRekening()
    {
        return $this->belongsTo(KodeRekening::class);
    }
    
    // Hapus relasi ke tahunAnggaran karena tidak diperlukan lagi
    // public function tahunAnggaran() - DIHAPUS
    
    // Fungsi untuk mendapatkan total penerimaan berdasarkan kode rekening, tahun, dan rentang tanggal
    public static function getTotalPenerimaan($kodeRekeningId, $tahun, $tanggalMulai, $tanggalSelesai)
    {
        return self::where('kode_rekening_id', $kodeRekeningId)
            ->where('tahun', $tahun)
            ->whereDate('tanggal', '>=', $tanggalMulai)
            ->whereDate('tanggal', '<=', $tanggalSelesai)
            ->sum('jumlah');
    }
    
    // Fungsi untuk mendapatkan total penerimaan per bulan
    public static function getTotalPenerimaanPerBulan($kodeRekeningId, $tahun, $bulan)
    {
        return self::where('kode_rekening_id', $kodeRekeningId)
            ->where('tahun', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->sum('jumlah');
    }
    
    // Fungsi untuk mendapatkan total penerimaan sampai dengan bulan tertentu
    public static function getTotalPenerimaanSampaiDenganBulan($kodeRekeningId, $tahun, $bulan)
    {
        $tanggalAkhir = Carbon::create($tahun, $bulan)->endOfMonth();
        
        return self::where('kode_rekening_id', $kodeRekeningId)
            ->where('tahun', $tahun)
            ->whereDate('tanggal', '<=', $tanggalAkhir)
            ->sum('jumlah');
    }
    
    // Helper method untuk mendapatkan total penerimaan per tahun untuk kode rekening tertentu
    public static function getTotalByTahunAndKodeRekening($tahun, $kodeRekeningIds)
    {
        return self::where('tahun', $tahun)
            ->whereIn('kode_rekening_id', $kodeRekeningIds)
            ->sum('jumlah');
    }
    
    // Helper method untuk mendapatkan total penerimaan bulanan
    public static function getTotalBulanan($tahun, $kodeRekeningIds, $bulan)
    {
        return self::where('tahun', $tahun)
            ->whereIn('kode_rekening_id', $kodeRekeningIds)
            ->whereMonth('tanggal', $bulan)
            ->sum('jumlah');
    }
    
    // Scope untuk filter berdasarkan tahun
    public function scopeByTahun($query, $tahun)
    {
        return $query->where('tahun', $tahun);
    }
    
    // Scope untuk filter berdasarkan rentang tanggal
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereDate('tanggal', '>=', $startDate)
                    ->whereDate('tanggal', '<=', $endDate);
    }
}