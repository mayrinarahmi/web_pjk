<?php
namespace App\Services;

use App\Models\KodeRekening;
use App\Models\Penerimaan;
use App\Models\TargetAnggaran;
use App\Models\TahunAnggaran;
use Carbon\Carbon;
use DB;

class LaporanRealisasiService
{
    public function generateLaporan($tahun, $tanggalAwal, $tanggalAkhir)
    {
        $data = [];
        
        // Get tahun_anggaran_id
        $tahunAnggaran = TahunAnggaran::where('tahun', $tahun)
            ->where('jenis_anggaran', 'murni')
            ->first();
            
        $tahunAnggaranId = $tahunAnggaran ? $tahunAnggaran->id : null;
        
        // Get semua kode rekening dengan urutan yang benar (filter by tahun berlaku)
        $query = KodeRekening::where('is_active', 1);
        if ($tahun) {
            $query->forTahun($tahun);
        }
        $kodeRekenings = $query
            ->orderByRaw("CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED),
                         CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1), SUBSTRING_INDEX(kode, '.', 1)), '0') AS UNSIGNED),
                         CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 3), '.', -1), SUBSTRING_INDEX(kode, '.', 2)), '0') AS UNSIGNED),
                         CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 4), '.', -1), SUBSTRING_INDEX(kode, '.', 3)), '0') AS UNSIGNED),
                         CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 5), '.', -1), SUBSTRING_INDEX(kode, '.', 4)), '0') AS UNSIGNED),
                         CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 6), '.', -1), SUBSTRING_INDEX(kode, '.', 5)), '0') AS UNSIGNED)")
            ->get();
            
        foreach ($kodeRekenings as $kode) {
            $target = 0;
            $realisasi = 0;
            
            if ($kode->level == 6) {
                // Level 6: ambil langsung
                if ($tahunAnggaranId) {
                    $target = TargetAnggaran::where('kode_rekening_id', $kode->id)
                        ->where('tahun_anggaran_id', $tahunAnggaranId)
                        ->value('jumlah') ?? 0;
                }
                
                $realisasi = Penerimaan::where('kode_rekening_id', $kode->id)
                    ->where('tahun', $tahun)
                    ->when($tanggalAwal && $tanggalAkhir, function($q) use ($tanggalAwal, $tanggalAkhir) {
                        return $q->whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir]);
                    })
                    ->sum('jumlah');
            } else {
                // Level 1-5: sum dari children level 6
                $childrenIds = $this->getLevel6Descendants($kode->id);
                
                if (!empty($childrenIds)) {
                    if ($tahunAnggaranId) {
                        $target = TargetAnggaran::whereIn('kode_rekening_id', $childrenIds)
                            ->where('tahun_anggaran_id', $tahunAnggaranId)
                            ->sum('jumlah');
                    }
                    
                    $realisasi = Penerimaan::whereIn('kode_rekening_id', $childrenIds)
                        ->where('tahun', $tahun)
                        ->when($tanggalAwal && $tanggalAkhir, function($q) use ($tanggalAwal, $tanggalAkhir) {
                            return $q->whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir]);
                        })
                        ->sum('jumlah');
                }
            }
            
            $persentase = $target > 0 ? ($realisasi / $target * 100) : 0;
            
            $data[] = [
                'kode' => $kode->kode,
                'nama' => $kode->nama,
                'level' => $kode->level,
                'target' => $target,
                'realisasi' => $realisasi,
                'persentase' => round($persentase, 2)
            ];
        }
        
        return $data;
    }
    
    private function getLevel6Descendants($parentId)
    {
        $descendants = [];
        
        // Recursive query untuk dapat semua level 6 descendants
        $children = KodeRekening::where('parent_id', $parentId)->get();
        
        foreach ($children as $child) {
            if ($child->level == 6) {
                $descendants[] = $child->id;
            } else {
                $descendants = array_merge($descendants, $this->getLevel6Descendants($child->id));
            }
        }
        
        return $descendants;
    }
}