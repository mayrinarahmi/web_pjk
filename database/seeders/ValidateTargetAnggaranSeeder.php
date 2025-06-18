<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TahunAnggaran;
use App\Models\KodeRekening;
use App\Models\TargetAnggaran;
use Illuminate\Support\Facades\DB;

class ValidateTargetAnggaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get all active tahun anggaran
        $tahunAnggarans = TahunAnggaran::where('is_active', true)->get();
        
        foreach ($tahunAnggarans as $tahunAnggaran) {
            $this->command->info("Validating target anggaran for: {$tahunAnggaran->tahun} - {$tahunAnggaran->jenis_anggaran}");
            
            // Get all kode rekening level 5 (yang bisa diinput)
            $kodeRekeningLevel5 = KodeRekening::where('level', 5)
                ->where('is_active', true)
                ->get();
                
            $missingTargets = [];
            
            foreach ($kodeRekeningLevel5 as $kode) {
                // Check if target exists
                $targetExists = TargetAnggaran::where('tahun_anggaran_id', $tahunAnggaran->id)
                    ->where('kode_rekening_id', $kode->id)
                    ->exists();
                    
                if (!$targetExists) {
                    $missingTargets[] = $kode;
                }
            }
            
            if (count($missingTargets) > 0) {
                $this->command->warn("Found {count($missingTargets)} kode rekening without target:");
                
                foreach ($missingTargets as $kode) {
                    $this->command->line("- {$kode->kode} - {$kode->nama}");
                    
                    // Insert default target (0) untuk kode yang belum ada
                    TargetAnggaran::create([
                        'tahun_anggaran_id' => $tahunAnggaran->id,
                        'kode_rekening_id' => $kode->id,
                        'jumlah' => 0
                    ]);
                }
                
                $this->command->info("Created default targets (Rp 0) for missing kode rekening");
            } else {
                $this->command->info("All kode rekening have targets ✓");
            }
            
            // Special check for "Lain-lain Pendapatan Daerah Yang Sah"
            $this->validateLainLainPendapatan($tahunAnggaran);
        }
    }
    
    private function validateLainLainPendapatan($tahunAnggaran)
    {
        // Get kode rekening 4.3 and all its children
        $kodeLainLain = KodeRekening::where('kode', '4.3')->first();
        
        if (!$kodeLainLain) {
            $this->command->error("Kode rekening 4.3 (Lain-lain Pendapatan) not found!");
            return;
        }
        
        // Get all level 5 children of 4.3
        $childrenIds = $this->getAllLevel5Children($kodeLainLain->id);
        
        // Check total target
        $totalTarget = TargetAnggaran::where('tahun_anggaran_id', $tahunAnggaran->id)
            ->whereIn('kode_rekening_id', $childrenIds)
            ->sum('jumlah');
            
        if ($totalTarget == 0) {
            $this->command->warn("WARNING: Total target for 'Lain-lain Pendapatan Daerah Yang Sah' is Rp 0");
            $this->command->warn("This might cause calculation issues if there are realizations");
            
            // Check if there are any realizations
            $realisasi = DB::table('penerimaan')
                ->whereIn('kode_rekening_id', $childrenIds)
                ->whereYear('tanggal', $tahunAnggaran->tahun)
                ->sum('jumlah');
                
            if ($realisasi > 0) {
                $this->command->error("CRITICAL: Found realisasi Rp " . number_format($realisasi) . " but target is Rp 0!");
                $this->command->error("Please update target anggaran for kode rekening under 4.3");
            }
        }
    }
    
    private function getAllLevel5Children($parentId)
    {
        $ids = [];
        
        $children = KodeRekening::where('parent_id', $parentId)->get();
        
        foreach ($children as $child) {
            if ($child->level == 5) {
                $ids[] = $child->id;
            } else {
                $ids = array_merge($ids, $this->getAllLevel5Children($child->id));
            }
        }
        
        return $ids;
    }
}