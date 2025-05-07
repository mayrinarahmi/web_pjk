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
    
    public static function getPersentaseSampaiDenganBulan($tahunAnggaranId, $bulan)
    {
        // Ambil semua target bulan
        $targetBulan = self::where('tahun_anggaran_id', $tahunAnggaranId)->get();
        
        // Cari persentase untuk bulan yang diminta secara langsung
        foreach ($targetBulan as $target) {
            $bulanArray = json_decode($target->bulan);
            
            // Jika bulan yang diminta ada dalam array bulan target ini
            if (in_array($bulan, $bulanArray)) {
                \Log::info("Target bulan $bulan ditemukan: " . $target->persentase . "%");
                return $target->persentase;
            }
        }
        
        \Log::info("Target bulan $bulan tidak ditemukan, mengembalikan 0%");
        return 0;
    }
}