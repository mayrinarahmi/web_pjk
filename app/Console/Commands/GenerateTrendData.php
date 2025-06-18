<?php
// File: app/Console/Commands/GenerateTrendData.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerateTrendData extends Command
{
    protected $signature = 'trend:generate-sample';
    protected $description = 'Generate sample data for trend analysis testing';
    
    public function handle()
    {
        $this->info('🚀 Generating sample trend data...');
        
        // Get existing kode_rekening level 4 atau 5 dari database
        $categories = DB::table('kode_rekening')
            ->whereIn('level', [4, 5])
            ->where('is_active', 1)
            ->limit(6) // Ambil 6 kategori untuk sample
            ->get();
            
        if ($categories->isEmpty()) {
            $this->error('❌ No kode_rekening found! Please setup master data first.');
            return;
        }
        
        $years = [2023, 2024, 2025];
        $totalInserted = 0;
        
        foreach ($categories as $category) {
            $this->info("Processing: {$category->nama}");
            
            foreach ($years as $year) {
                // Base amount berbeda per kategori
                $baseAmount = $this->getBaseAmount($category->kode);
                
                for ($month = 1; $month <= 12; $month++) {
                    // Untuk 2025, hanya sampai bulan sekarang
                    if ($year == 2025 && $month > date('n')) break;
                    
                    // Random variation per bulan
                    $monthlyAmount = $baseAmount * (1 + (rand(-15, 15) / 100));
                    
                    // Growth trend per kategori
                    $yearMultiplier = $this->getYearMultiplier($category->kode, $year);
                    $finalAmount = $monthlyAmount * $yearMultiplier;
                    
                    // Insert sample data
                    DB::table('penerimaan')->insert([
                        'kode_rekening_id' => $category->id,
                        'tanggal' => Carbon::create($year, $month, rand(1, 28)),
                        'jumlah' => round($finalAmount),
                        'keterangan' => "Sample data trend analysis - {$category->nama} {$year}",
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    $totalInserted++;
                }
            }
        }
        
        $this->info("✅ Generated {$totalInserted} sample records");
        $this->info("🎯 You can now test trend analysis at: /analisis-tren");
        
        // Test views
        $this->info("🔍 Testing views...");
        $yearlyCount = DB::table('v_penerimaan_yearly')->count();
        $this->info("📊 v_penerimaan_yearly: {$yearlyCount} records");
        
        return 0;
    }
    
    private function getBaseAmount($kode)
    {
        // Different base amounts untuk realism
        if (str_contains($kode, 'reklame') || str_contains($kode, '01')) {
            return rand(2000000, 5000000); // 2-5 juta per bulan
        } elseif (str_contains($kode, 'hotel') || str_contains($kode, '02')) {
            return rand(1500000, 3500000); // 1.5-3.5 juta
        } elseif (str_contains($kode, 'restoran') || str_contains($kode, '03')) {
            return rand(1000000, 2500000); // 1-2.5 juta
        } else {
            return rand(800000, 2000000); // 800rb-2 juta
        }
    }
    
    private function getYearMultiplier($kode, $year)
    {
        $baseYear = 2023;
        $yearDiff = $year - $baseYear;
        
        // Different growth patterns per kategori
        if (str_contains($kode, 'reklame') || str_contains($kode, '01')) {
            return 1 + ($yearDiff * 0.25); // 25% growth per year
        } elseif (str_contains($kode, 'hiburan') || str_contains($kode, '04')) {
            return 1 - ($yearDiff * 0.1); // 10% decline per year (dampak pandemi)
        } else {
            return 1 + ($yearDiff * 0.15); // 15% growth per year
        }
    }
}