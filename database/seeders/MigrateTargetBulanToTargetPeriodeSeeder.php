<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TargetBulan;
use App\Models\TargetPeriode;

class MigrateTargetBulanToTargetPeriodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $targetBulan = TargetBulan::all();
        
        foreach ($targetBulan as $target) {
            $bulanArray = json_decode($target->bulan);
            
            if (empty($bulanArray)) {
                continue;
            }
            
            // Sortir bulan untuk mendapatkan awal dan akhir
            sort($bulanArray);
            $bulanAwal = min($bulanArray);
            $bulanAkhir = max($bulanArray);
            
            // Buat target periode baru
            TargetPeriode::create([
                'tahun_anggaran_id' => $target->tahun_anggaran_id,
                'nama_periode' => $target->nama_kelompok,
                'bulan_awal' => $bulanAwal,
                'bulan_akhir' => $bulanAkhir,
                'persentase' => $target->persentase,
            ]);
        }
        
        $this->command->info('Berhasil migrasi data dari Target Bulan ke Target Periode');
    }
}