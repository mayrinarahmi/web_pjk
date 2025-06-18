<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixTargetPeriodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Fixing target periode data...');
        
        // Get active tahun anggaran for 2025
        $tahunAnggarans = DB::table('tahun_anggaran')
            ->where('tahun', 2025)
            ->where('is_active', true)
            ->get();

        if ($tahunAnggarans->isEmpty()) {
            $this->command->error('No active tahun anggaran found for 2025!');
            return;
        }

        foreach ($tahunAnggarans as $tahunAnggaran) {
            $this->command->info("Processing: {$tahunAnggaran->tahun} - {$tahunAnggaran->jenis_anggaran}");
            
            // Check existing data
            $existing = DB::table('target_periode')
                ->where('tahun_anggaran_id', $tahunAnggaran->id)
                ->count();
                
            if ($existing > 0) {
                $this->command->warn("Found {$existing} existing records. Deleting...");
                DB::table('target_periode')
                    ->where('tahun_anggaran_id', $tahunAnggaran->id)
                    ->delete();
            }

            // Insert correct data
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
            $this->command->info("✓ Inserted 4 target periode records");
        }
        
        // Verify results
        $this->command->info("\nVerifying results:");
        $results = DB::table('target_periode as tp')
            ->join('tahun_anggaran as ta', 'tp.tahun_anggaran_id', '=', 'ta.id')
            ->where('ta.tahun', 2025)
            ->select('tp.*', 'ta.jenis_anggaran')
            ->orderBy('ta.jenis_anggaran')
            ->orderBy('tp.bulan_awal')
            ->get();
            
        foreach ($results as $result) {
            $this->command->line("- {$result->jenis_anggaran} | {$result->nama_periode}: {$result->persentase}%");
        }
        
        $this->command->info("\n✓ Target periode data fixed successfully!");
    }
}