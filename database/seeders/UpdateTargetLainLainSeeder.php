<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use App\Models\KodeRekening;
use App\Models\Penerimaan;

class UpdateTargetLainLainSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('=== UPDATING TARGET LAIN-LAIN PENDAPATAN ===');
        
        // Get tahun anggaran
        $tahunAnggarans = TahunAnggaran::where('tahun', 2025)
            ->where('is_active', true)
            ->get();
            
        foreach ($tahunAnggarans as $tahunAnggaran) {
            $this->command->info("\nProcessing: {$tahunAnggaran->tahun} - {$tahunAnggaran->jenis_anggaran}");
            
            // Get Lain-lain Pendapatan code
            $lainLainKode = KodeRekening::where('kode', '4.3')->first();
            if (!$lainLainKode) {
                continue;
            }
            
            // Get all children
            $allChildIds = $this->getAllChildIds($lainLainKode->id);
            $allChildIds[] = $lainLainKode->id;
            
            // Check total realisasi for the year
            $totalRealisasi = Penerimaan::whereIn('kode_rekening_id', $allChildIds)
                ->whereYear('tanggal', $tahunAnggaran->tahun)
                ->sum('jumlah');
                
            $this->command->info("Total Realisasi 2025: Rp " . number_format($totalRealisasi));
            
            // Calculate reasonable target (realisasi YTD projected to full year)
            $currentMonth = date('n');
            $projectedAnnual = ($totalRealisasi / $currentMonth) * 12;
            
            $this->command->info("Projected Annual (based on {$currentMonth} months): Rp " . number_format($projectedAnnual));
            
            // Set target to projected + 20% buffer
            $newTarget = $projectedAnnual * 1.2;
            
            $this->command->info("New Target (projected + 20%): Rp " . number_format($newTarget));
            
            // Update targets for level 5 children
            $level5Children = KodeRekening::whereIn('id', $allChildIds)
                ->where('level', 5)
                ->get();
                
            $targetPerChild = $newTarget / $level5Children->count();
            
            foreach ($level5Children as $child) {
                $target = TargetAnggaran::where('tahun_anggaran_id', $tahunAnggaran->id)
                    ->where('kode_rekening_id', $child->id)
                    ->first();
                    
                if ($target) {
                    $oldAmount = $target->jumlah;
                    $target->jumlah = $targetPerChild;
                    $target->save();
                    
                    $this->command->line("Updated {$child->kode}: Rp " . number_format($oldAmount) . " → Rp " . number_format($targetPerChild));
                } else {
                    TargetAnggaran::create([
                        'tahun_anggaran_id' => $tahunAnggaran->id,
                        'kode_rekening_id' => $child->id,
                        'jumlah' => $targetPerChild
                    ]);
                    
                    $this->command->line("Created {$child->kode}: Rp " . number_format($targetPerChild));
                }
            }
            
            // Verify new total
            $newTotalPagu = TargetAnggaran::where('tahun_anggaran_id', $tahunAnggaran->id)
                ->whereIn('kode_rekening_id', $allChildIds)
                ->sum('jumlah');
                
            $this->command->info("New Total Pagu: Rp " . number_format($newTotalPagu));
            
            // Calculate new percentage
            $target40Percent = $newTotalPagu * 0.4;
            $newPercentage = ($totalRealisasi / $target40Percent) * 100;
            
            $this->command->info("Expected percentage with 40% target: " . number_format($newPercentage, 2) . "%");
        }
        
        $this->command->info("\n✓ Target updated successfully!");
        $this->command->info("Please refresh the dashboard to see the changes.");
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