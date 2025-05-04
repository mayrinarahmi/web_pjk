<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Penerimaan extends Model
{
    protected $table = 'penerimaan';
    
    protected $fillable = [
        'kode_rekening_id',
        'tahun_anggaran_id',
        'tanggal',
        'jumlah',
        'keterangan',
    ];
    
    protected $casts = [
        'tanggal' => 'date',
    ];
    
    public function kodeRekening()
    {
        return $this->belongsTo(KodeRekening::class);
    }
    
    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }
    
    // Fungsi untuk mendapatkan total penerimaan berdasarkan kode rekening, tahun anggaran, dan rentang tanggal
    public static function getTotalPenerimaan($kodeRekeningId, $tahunAnggaranId, $tanggalMulai, $tanggalSelesai)
    {
        return self::where('kode_rekening_id', $kodeRekeningId)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->whereDate('tanggal', '>=', $tanggalMulai)
            ->whereDate('tanggal', '<=', $tanggalSelesai)
            ->sum('jumlah');
    }
    
    // Fungsi untuk mendapatkan total penerimaan per bulan
    public static function getTotalPenerimaanPerBulan($kodeRekeningId, $tahunAnggaranId, $tahun, $bulan)
    {
        return self::where('kode_rekening_id', $kodeRekeningId)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->sum('jumlah');
    }
    
    // Fungsi untuk mendapatkan total penerimaan sampai dengan bulan tertentu
    public static function getTotalPenerimaanSampaiDenganBulan($kodeRekeningId, $tahunAnggaranId, $tahun, $bulan)
    {
        $tanggalAkhir = Carbon::create($tahun, $bulan)->endOfMonth();
        
        return self::where('kode_rekening_id', $kodeRekeningId)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->whereYear('tanggal', $tahun)
            ->whereDate('tanggal', '<=', $tanggalAkhir)
            ->sum('jumlah');
    }
}

