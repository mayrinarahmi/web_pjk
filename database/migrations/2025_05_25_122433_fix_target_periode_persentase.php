<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixTargetPeriodePersentase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update atau insert target periode dengan persentase yang benar
        $tahunAnggarans = DB::table('tahun_anggaran')
            ->where('is_active', true)
            ->get();
            
        foreach ($tahunAnggarans as $tahunAnggaran) {
            // Hapus target periode lama jika ada
            DB::table('target_periode')
                ->where('tahun_anggaran_id', $tahunAnggaran->id)
                ->delete();
                
            // Insert target periode baru dengan persentase yang benar
            $targetPeriodes = [
                [
                    'tahun_anggaran_id' => $tahunAnggaran->id,
                    'nama_periode' => 'Triwulan I',
                    'bulan_awal' => 1,
                    'bulan_akhir' => 3,
                    'persentase' => 15,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'tahun_anggaran_id' => $tahunAnggaran->id,
                    'nama_periode' => 'Triwulan II',
                    'bulan_awal' => 4,
                    'bulan_akhir' => 6,
                    'persentase' => 25,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'tahun_anggaran_id' => $tahunAnggaran->id,
                    'nama_periode' => 'Triwulan III',
                    'bulan_awal' => 7,
                    'bulan_akhir' => 9,
                    'persentase' => 30,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'tahun_anggaran_id' => $tahunAnggaran->id,
                    'nama_periode' => 'Triwulan IV',
                    'bulan_awal' => 10,
                    'bulan_akhir' => 12,
                    'persentase' => 30,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ];
            
            DB::table('target_periode')->insert($targetPeriodes);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Tidak perlu rollback karena ini fix data
    }
}