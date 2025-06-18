<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use App\Models\TargetPeriode;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use Illuminate\Support\Facades\DB;

class DebugDashboardSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('=== DEBUGGING DASHBOARD CALCULATION ===');
        
        // Get current date info
        $currentMonth = date('n'); // 5 (May)
        $currentQuarter = ceil($currentMonth / 3); // 2
        $currentYear = date('Y'); // 2025
        
        $this->command->info("Current: Month {$currentMonth}, Quarter {$currentQuarter}, Year {$currentYear}");
        
        // Get active tahun anggaran
        $tahunAnggaran = TahunAnggaran::where('tahun', $currentYear)
            ->where('jenis_anggaran', 'murni')
            ->where('is_active', true)
            ->first();
            
        if (!$tahunAnggaran) {
            $this->command->error('No active APBD Murni found!');
            return;
        }
        
        $this->command->info("Using: {$tahunAnggaran->tahun} - {$tahunAnggaran->jenis_anggaran} (ID: {$tahunAnggaran->id})");
        
        // Debug Lain-lain Pendapatan
        $this->command->info("\n--- DEBUGGING LAIN-LAIN PENDAPATAN (4.3) ---");
        
        $lainLainKode = KodeRekening::where('kode', '4.3')->first();
        if (!$lainLainKode) {
            $this->command->error('Kode 4.3 not found!');
            return;
        }
        
        $this->command->info("Found: {$lainLainKode->kode} - {$lainLainKode->nama} (ID: {$lainLainKode->id})");
        
        // Get all children IDs
        $allChildIds = $this->getAllChildIds($lainLainKode->id);
        $allChildIds[] = $lainLainKode->id; // Include parent
        
        $this->command->info("Total IDs (including parent): " . count($allChildIds));
        
        // Check pagu (target anggaran)
        $pagu = TargetAnggaran::where('tahun_anggaran_id', $tahunAnggaran->id)
            ->whereIn('kode_rekening_id', $allChildIds)
            ->sum('jumlah');
            
        $this->command->info("Total Pagu: Rp " . number_format($pagu));
        
        // Show breakdown
        $targets = TargetAnggaran::where('tahun_anggaran_id', $tahunAnggaran->id)
            ->whereIn('kode_rekening_id', $allChildIds)
            ->join('kode_rekening', 'target_anggaran.kode_rekening_id', '=', 'kode_rekening.id')
            ->select('kode_rekening.kode', 'kode_rekening.nama', 'target_anggaran.jumlah')
            ->get();
            
        foreach ($targets as $target) {
            $this->command->line("  - {$target->kode}: Rp " . number_format($target->jumlah));
        }
        
        // Check target periode
        $this->command->info("\n--- TARGET PERIODE CALCULATION ---");
        
        // Method 1: Periods that have ended
        $endedPeriods = TargetPeriode::where('tahun_anggaran_id', $tahunAnggaran->id)
            ->where('bulan_akhir', '<=', $currentMonth)
            ->get();
            
        $this->command->info("Ended periods: " . $endedPeriods->count());
        foreach ($endedPeriods as $period) {
            $this->command->line("  - {$period->nama_periode}: {$period->persentase}%");
        }
        
        // Method 2: Current period
        $currentPeriod = TargetPeriode::where('tahun_anggaran_id', $tahunAnggaran->id)
            ->where('bulan_awal', '<=', $currentMonth)
            ->where('bulan_akhir', '>=', $currentMonth)
            ->first();
            
        if ($currentPeriod) {
            $this->command->info("Current period: {$currentPeriod->nama_periode} ({$currentPeriod->persentase}%)");
        }
        
        // Calculate cumulative
        $totalPersentase = 0;
        
        // All periods up to current
        $allRelevantPeriods = TargetPeriode::where('tahun_anggaran_id', $tahunAnggaran->id)
            ->where('bulan_awal', '<=', $currentMonth)
            ->get();
            
        foreach ($allRelevantPeriods as $period) {
            $totalPersentase += $period->persentase;
            $this->command->line("  + {$period->nama_periode}: {$period->persentase}% (Total: {$totalPersentase}%)");
        }
        
        $this->command->info("Total cumulative percentage: {$totalPersentase}%");
        
        // Calculate target
        $target = ($pagu * $totalPersentase) / 100;
        $this->command->info("Calculated Target: Rp " . number_format($target) . " = Rp " . number_format($pagu) . " × {$totalPersentase}%");
        
        // Check realisasi
        $this->command->info("\n--- REALISASI ---");
        
        $realisasi = Penerimaan::whereIn('kode_rekening_id', $allChildIds)
            ->whereYear('tanggal', $tahunAnggaran->tahun)
            ->whereMonth('tanggal', '<=', $currentMonth)
            ->sum('jumlah');
            
        $this->command->info("Total Realisasi (YTD): Rp " . number_format($realisasi));
        
        // Show monthly breakdown
        for ($i = 1; $i <= $currentMonth; $i++) {
            $monthlyRealisasi = Penerimaan::whereIn('kode_rekening_id', $allChildIds)
                ->whereYear('tanggal', $tahunAnggaran->tahun)
                ->whereMonth('tanggal', $i)
                ->sum('jumlah');
                
            if ($monthlyRealisasi > 0) {
                $this->command->line("  - Month {$i}: Rp " . number_format($monthlyRealisasi));
            }
        }
        
        // Calculate percentage
        $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
        
        $this->command->info("\n--- FINAL CALCULATION ---");
        $this->command->info("Realisasi: Rp " . number_format($realisasi));
        $this->command->info("Target: Rp " . number_format($target));
        $this->command->info("Percentage: " . number_format($percentage, 2) . "%");
        
        if ($percentage > 200) {
            $this->command->error("\n!!! PERCENTAGE TOO HIGH !!!");
            $this->command->error("Possible issues:");
            $this->command->error("1. Target (pagu) too low");
            $this->command->error("2. Wrong cumulative percentage");
            $this->command->error("3. Duplicate realisasi entries");
        }
    }
    
    private function getAllChildIds($parentId)
    {
        $ids = [];
        $children = KodeRekening::where('parent_id', $parentId)
            ->where('is_active', true)
            ->get();
            
        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getAllChildIds($child->id));
        }
        
        return $ids;
    }
}