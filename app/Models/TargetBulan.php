<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TargetBulan extends Model
{
    protected $table = 'target_bulan';
    
    protected $fillable = [
        'tahun_anggaran_id',
        'nama_kelompok',
        'bulan',
        'persentase',
    ];
    
    protected $casts = [
        'bulan' => 'array',
    ];
    
    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }
    
    // Fungsi untuk mendapatkan persentase target sampai bulan tertentu
    public static function getPersentaseSampaiDenganBulan($tahunAnggaranId, $bulan)
    {
        $targetBulan = self::where('tahun_anggaran_id', $tahunAnggaranId)->get();
        $totalPersentase = 0;
        
        foreach ($targetBulan as $target) {
            $bulanArray = json_decode($target->bulan);
            foreach ($bulanArray as $b) {
                if ($b <= $bulan) {
                    $totalPersentase += $target->persentase / count($bulanArray);
                }
            }
        }
        
        return $totalPersentase;
    }
}
