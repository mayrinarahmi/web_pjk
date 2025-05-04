<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TargetAnggaran extends Model
{
    protected $table = 'target_anggaran';
    
    protected $fillable = [
        'tahun_anggaran_id',
        'kode_rekening_id',
        'jumlah',
    ];
    
    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }
    
    public function kodeRekening()
    {
        return $this->belongsTo(KodeRekening::class);
    }
    
    // Fungsi untuk mendapatkan target anggaran berdasarkan kode rekening dan tahun anggaran
    public static function getTargetAnggaran($kodeRekeningId, $tahunAnggaranId)
    {
        $target = self::where('kode_rekening_id', $kodeRekeningId)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->first();
            
        return $target ? $target->jumlah : 0;
    }
    
    // Fungsi untuk mendapatkan target sampai dengan bulan tertentu
    public static function getTargetSampaiDenganBulan($kodeRekeningId, $tahunAnggaranId, $bulan)
    {
        $target = self::getTargetAnggaran($kodeRekeningId, $tahunAnggaranId);
        $persentase = TargetBulan::getPersentaseSampaiDenganBulan($tahunAnggaranId, $bulan);
        
        return $target * ($persentase / 100);
    }
}

