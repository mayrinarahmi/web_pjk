<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\TahunAnggaran;
use App\Models\TargetPeriode;
use App\Models\TargetAnggaran;
use App\Models\KodeRekening;

class CheckAndFixDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('=== CHECKING AND FIXING DATA ===');
        
        // 1. Check current month and quarter
        $currentMonth = date('n');
        $currentQuarter = ceil($currentMonth / 3);
        $this->command->info("Current Month: {$currentMonth} (Quarter {$currentQuarter})");
        
        // 2. Check tahun anggaran
        $tahunAnggaran = TahunAnggaran::where('tahun', 2025)
            ->where('jenis_anggaran', 'murni')
            ->where('is_active', true)
            ->first();
            
        if (!$tahunAnggaran) {
            $this->command->error('No active APBD Murni for 2025!');
            return;
        }
        
        $this->command->info("Using: {$tahunAnggaran->tahun} - {$tahunAnggaran->jenis_anggaran}");
        
        // 3. Check and fix target periode
        $this->command->info("\n--- Checking Target Periode ---");
        $targetPeriodes = TargetPeriode::where('tahun_anggaran_id', $tahunAnggaran->id)->get();
        
        if ($targetPeriodes->count() != 4) {
            $this->command->warn("Found {$targetPeriodes->count()} periods, should be 4. Fixing...");
            
            // Delete existing
            TargetPeriode::where('tahun_anggaran_id', $tahunAnggaran->id)->delete();
            
            // Insert correct data
            $periods = [
                ['nama' => 'Triwulan I', 'start' => 1, 'end' => 3, 'pct' => 15],
                ['nama' => 'Triwulan II', 'start' => 4, 'end' => 6, 'pct' => 25],
                ['nama' => 'Triwulan III', 'start' => 7, 'end' => 9, 'pct' => 30],
                ['nama' => 'Triwulan IV', 'start' => 10, 'end' => 12, 'pct' => 30],
            ];
            
            foreach ($periods as $period) {
                TargetPeriode::create([
                    'tahun_anggaran_id' => $tahunAnggaran->id,
                    'nama_periode' => $period['nama'],
                    'bulan_awal' => $period['start'],
                    'bulan_akhir' => $period['end'],
                    'persentase' => $period['pct']
                ]);
            }
            
            $this->command->info("✓ Created 4 target periods");
        }
        
        // Show current periods
        $targetPeriodes = TargetPeriode::where('tahun_anggaran_id', $tahunAnggaran->id)
            ->orderBy('bulan_awal')
            ->get();
            
        foreach ($targetPeriodes as $tp) {
            $this->command->line("- {$tp->nama_periode}: {$tp->persentase}% (Month {$tp->bulan_awal}-{$tp->bulan_akhir})");
        }
        
        // Calculate cumulative percentage
        $cumulativePercentage = $targetPeriodes
            ->where('bulan_akhir', '<=', $currentMonth)
            ->sum('persentase');
            
        $this->command->info("Cumulative percentage for current period: {$cumulativePercentage}%");
        
        // 4. Check Lain-lain Pendapatan target
        $this->command->info("\n--- Checking Lain-lain Pendapatan Target ---");
        
        $lainLainKode = KodeRekening::where('kode', '4.3')->first();
        if ($lainLainKode) {
            // Get all level 5 children
            $level5Children = KodeRekening::where('kode', 'like', '4.3%')
                ->where('level', 5)
                ->where('is_active', true)
                ->get();
                
            $this->command->info("Found {$level5Children->count()} level 5 children under 4.3");
            
            $totalTarget = 0;
            foreach ($level5Children as $child) {
                $target = TargetAnggaran::where('tahun_anggaran_id', $tahunAnggaran->id)
                    ->where('kode_rekening_id', $child->id)
                    ->first();
                    
                if (!$target) {
                    $this->command->warn("No target for: {$child->kode} - {$child->nama}");
                    
                    // Create default target
                    TargetAnggaran::create([
                        'tahun_anggaran_id' => $tahunAnggaran->id,
                        'kode_rekening_id' => $child->id,
                        'jumlah' => 1000000000 // Default 1 Milyar
                    ]);
                    
                    $this->command->info("✓ Created default target Rp 1.000.000.000");
                    $totalTarget += 1000000000;
                } else {
                    $totalTarget += $target->jumlah;
                }
            }
            
            $this->command->info("Total target for Lain-lain: Rp " . number_format($totalTarget));
        }
        
        // 5. Summary
        $this->command->info("\n=== SUMMARY ===");
        $this->command->info("Current Period: Quarter {$currentQuarter} (Month {$currentMonth})");
        $this->command->info("Expected Target Percentage: {$cumulativePercentage}%");
        $this->command->info("\nIf percentage is still wrong, the issue might be in the calculation logic.");
        $this->command->info("Expected calculation:");
        $this->command->info("- Target = Pagu × {$cumulativePercentage}%");
        $this->command->info("- Percentage = (Realisasi ÷ Target) × 100%");
    }
}