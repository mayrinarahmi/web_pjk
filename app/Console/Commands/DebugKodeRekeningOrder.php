<?php

// File: app/Console/Commands/DebugKodeRekeningOrder.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KodeRekening;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DebugKodeRekeningOrder extends Command
{
    protected $signature = 'debug:kode-rekening-order';
    protected $description = 'Debug ordering kode rekening';

    public function handle()
    {
        $this->info('🔍 Testing Hierarchical Ordering...');
        
        // Test current data
        $this->info('📋 Current data in database:');
        $current = KodeRekening::where('is_active', true)
            ->orderBy('kode')
            ->get(['kode', 'nama', 'level'])
            ->take(15);
            
        $headers = ['Kode', 'Nama', 'Level'];
        $rows = $current->map(function($item) {
            return [$item->kode, Str::limit($item->nama, 40), $item->level];
        })->toArray();
        
        $this->table($headers, $rows);
        
        $this->info('🔧 With StringHierarchicalOrder:');
        $ordered = KodeRekening::where('is_active', true)
            ->stringHierarchicalOrder()
            ->get(['kode', 'nama', 'level'])
            ->take(15);
            
        $orderedRows = $ordered->map(function($item) {
            return [$item->kode, Str::limit($item->nama, 40), $item->level];
        })->toArray();
        
        $this->table($headers, $orderedRows);
        
        // Show expected order
        $this->info('✅ Expected order should be:');
        $expected = [
            ['4', 'PENDAPATAN DAERAH', '1'],
            ['4.1', 'PENDAPATAN ASLI DAERAH (PAD)', '2'],
            ['4.1.01', 'Pajak Daerah', '3'],
            ['4.1.01.09', 'Pajak Reklame', '4'],
            ['4.1.01.09.01.0001', 'Pajak Reklame Papan/Billboard', '5'],
            ['4.2', 'PENDAPATAN TRANSFER', '2'],
            ['4.2.01', 'Pendapatan Transfer Pemerintah Pusat', '3'],
            ['4.2.01.06', 'Dana Insentif Fiskal', '4'],
            ['4.2.01.06.01.0001', 'Dana Insentif Fiskal', '5'],
            ['4.3', 'LAIN-LAIN PENDAPATAN DAERAH YANG SAH', '2'],
            ['4.3.01', 'Pendapatan Hibah', '3'],
            ['4.3.01.01.01.0001', 'Pendapatan Hibah dari Pemerintah Pusat', '5'],
            ['4.3.01.05.01.0001', 'Sumbangan Pihak Ketiga/Sejenis', '5'],
        ];
        
        $this->table(['Expected Kode', 'Expected Nama', 'Expected Level'], $expected);
        
        // Check for wrong codes
        $this->info('🚨 Checking for wrong codes:');
        $wrongCodes = KodeRekening::where('kode', 'like', '4.11%')->get(['kode', 'nama', 'level']);
        
        if ($wrongCodes->count() > 0) {
            $this->error('Found wrong codes:');
            $wrongRows = $wrongCodes->map(function($item) {
                return [$item->kode, $item->nama, $item->level];
            })->toArray();
            $this->table(['Wrong Kode', 'Nama', 'Level'], $wrongRows);
            
            $this->info('💡 These should be fixed to 4.1.xx.xx format');
        } else {
            $this->info('✅ No wrong codes found');
        }
        
        // Test hierarchical calculation
        $this->info('🧮 Testing hierarchical calculation:');
        $level1 = KodeRekening::where('kode', '4')->where('level', 1)->first();
        if ($level1) {
            $this->info("Level 1 - {$level1->kode}: {$level1->nama}");
            $this->info("Children count: " . $level1->children->count());
        }
        
        return Command::SUCCESS;
    }
}