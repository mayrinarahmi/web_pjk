<?php

// File: app/Console/Commands/UpdateHierarchiTargetAnggaran.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use Illuminate\Support\Facades\DB;

class UpdateHierarchiTargetAnggaran extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'update:hierarki-target 
                           {--tahun-anggaran-id= : Specific tahun anggaran ID}
                           {--all : Update all tahun anggaran}
                           {--verify : Only verify hierarchy without updating}
                           {--force : Force update even if looks correct}';

    /**
     * The console command description.
     */
    protected $description = 'Update hierarki target anggaran dari level 5 ke level parent';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Hierarki Target Anggaran Update...');
        
        // Determine which tahun anggaran to process
        $tahunAnggaranIds = $this->getTahunAnggaranIds();
        
        if (empty($tahunAnggaranIds)) {
            $this->error('❌ No tahun anggaran found to process');
            return Command::FAILURE;
        }
        
        $this->info("📋 Processing " . count($tahunAnggaranIds) . " tahun anggaran...");
        
        foreach ($tahunAnggaranIds as $tahunAnggaranId) {
            $tahunAnggaran = TahunAnggaran::find($tahunAnggaranId);
            $this->line("Processing: {$tahunAnggaran->display_name}");
            
            if ($this->option('verify')) {
                $this->verifyHierarchi($tahunAnggaranId);
            } else {
                $this->updateHierarchi($tahunAnggaranId);
            }
        }
        
        $this->info('✅ Hierarki Target Anggaran update completed!');
        return Command::SUCCESS;
    }
    
    private function getTahunAnggaranIds()
    {
        if ($this->option('tahun-anggaran-id')) {
            return [$this->option('tahun-anggaran-id')];
        }
        
        if ($this->option('all')) {
            return TahunAnggaran::pluck('id')->toArray();
        }
        
        // Default: hanya tahun anggaran aktif
        $active = TahunAnggaran::where('is_active', true)->first();
        return $active ? [$active->id] : [];
    }
    
    private function updateHierarchi($tahunAnggaranId)
    {
        $tahunAnggaran = TahunAnggaran::find($tahunAnggaranId);
        
        $this->line("🔄 Updating hierarki for {$tahunAnggaran->display_name}...");
        
        // Get level 5 targets yang sudah ada
        $level5Targets = TargetAnggaran::where('tahun_anggaran_id', $tahunAnggaranId)
            ->whereHas('kodeRekening', function($q) {
                $q->where('level', 5);
            })
            ->count();
            
        $this->line("   📊 Found {$level5Targets} level 5 targets");
        
        if ($level5Targets == 0) {
            $this->warn("   ⚠️  No level 5 targets found, skipping...");
            return;
        }
        
        // Update hierarki dari level 4 ke atas
        $updatedCount = 0;
        
        for ($level = 4; $level >= 1; $level--) {
            $kodeRekeningList = KodeRekening::where('level', $level)
                ->where('is_active', true)
                ->orderBy('kode')
                ->get();
            
            $this->line("   🔢 Processing level {$level}: " . $kodeRekeningList->count() . " items");
            
            foreach ($kodeRekeningList as $kode) {
                $calculatedTarget = $kode->calculateHierarchiTarget($tahunAnggaranId);
                
                if ($calculatedTarget > 0) {
                    // Update atau create target anggaran untuk parent
                    $targetAnggaran = TargetAnggaran::updateOrCreate(
                        [
                            'kode_rekening_id' => $kode->id,
                            'tahun_anggaran_id' => $tahunAnggaranId
                        ],
                        [
                            'jumlah' => $calculatedTarget
                        ]
                    );
                    
                    $updatedCount++;
                    
                    if ($this->output->isVerbose()) {
                        $this->line("      ✓ {$kode->kode}: Rp " . number_format($calculatedTarget, 0, ',', '.'));
                    }
                }
            }
        }
        
        $this->info("   ✅ Updated {$updatedCount} parent targets");
        
        // Verify hasil
        $this->verifyHierarchi($tahunAnggaranId, false);
    }
    
    private function verifyHierarchi($tahunAnggaranId, $showDetails = true)
    {
        $tahunAnggaran = TahunAnggaran::find($tahunAnggaranId);
        
        if ($showDetails) {
            $this->line("🔍 Verifying hierarki for {$tahunAnggaran->display_name}...");
        }
        
        $inconsistencies = [];
        
        for ($level = 1; $level <= 4; $level++) {
            $kodeRekeningList = KodeRekening::where('level', $level)
                ->where('is_active', true)
                ->get();
            
            foreach ($kodeRekeningList as $kode) {
                $manualTarget = TargetAnggaran::where('kode_rekening_id', $kode->id)
                    ->where('tahun_anggaran_id', $tahunAnggaranId)
                    ->value('jumlah') ?? 0;
                    
                $calculatedTarget = $kode->calculateHierarchiTarget($tahunAnggaranId);
                
                if (abs($manualTarget - $calculatedTarget) > 1) {
                    $inconsistencies[] = [
                        'kode' => $kode->kode,
                        'nama' => $kode->nama,
                        'level' => $level,
                        'manual' => $manualTarget,
                        'calculated' => $calculatedTarget,
                        'difference' => $manualTarget - $calculatedTarget
                    ];
                }
            }
        }
        
        if (empty($inconsistencies)) {
            if ($showDetails) {
                $this->info("   ✅ All hierarchy targets are consistent");
            }
        } else {
            if ($showDetails) {
                $this->warn("   ⚠️  Found " . count($inconsistencies) . " inconsistencies:");
                
                $headers = ['Kode', 'Level', 'Manual Target', 'Calculated Target', 'Difference'];
                $rows = array_map(function($item) {
                    return [
                        $item['kode'],
                        $item['level'],
                        'Rp ' . number_format($item['manual'], 0, ',', '.'),
                        'Rp ' . number_format($item['calculated'], 0, ',', '.'),
                        'Rp ' . number_format($item['difference'], 0, ',', '.')
                    ];
                }, array_slice($inconsistencies, 0, 10)); // Show max 10
                
                $this->table($headers, $rows);
                
                if (count($inconsistencies) > 10) {
                    $this->line("   ... and " . (count($inconsistencies) - 10) . " more");
                }
            }
        }
        
        return count($inconsistencies);
    }
}