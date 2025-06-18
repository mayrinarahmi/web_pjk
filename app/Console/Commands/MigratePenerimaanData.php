<?php

// File: database/seeders/MigratePenerimaanDataSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigratePenerimaanDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info('Starting penerimaan data migration...');
        
        // Cek apakah kolom tahun_anggaran_id masih ada
        $hasOldColumn = DB::getSchemaBuilder()->hasColumn('penerimaan', 'tahun_anggaran_id');
        $hasNewColumn = DB::getSchemaBuilder()->hasColumn('penerimaan', 'tahun');
        
        if ($hasOldColumn && $hasNewColumn) {
            Log::info('Both columns exist, migrating data...');
            
            // Update data penerimaan dengan tahun dari tahun_anggaran
            $affected = DB::statement("
                UPDATE penerimaan p 
                JOIN tahun_anggaran ta ON p.tahun_anggaran_id = ta.id 
                SET p.tahun = ta.tahun
                WHERE p.tahun IS NULL OR p.tahun = 0
            ");
            
            Log::info("Updated {$affected} penerimaan records with tahun from tahun_anggaran");
            
            // Verifikasi data
            $nullTahunCount = DB::table('penerimaan')
                ->whereNull('tahun')
                ->orWhere('tahun', 0)
                ->count();
                
            if ($nullTahunCount > 0) {
                Log::warning("Still have {$nullTahunCount} records with null/zero tahun");
                
                // Set default tahun berdasarkan tanggal
                DB::statement("
                    UPDATE penerimaan 
                    SET tahun = YEAR(tanggal) 
                    WHERE tahun IS NULL OR tahun = 0
                ");
                
                Log::info("Set tahun based on tanggal for remaining records");
            }
            
        } elseif (!$hasOldColumn && $hasNewColumn) {
            Log::info('Migration already completed, tahun_anggaran_id column not found');
            
        } elseif ($hasOldColumn && !$hasNewColumn) {
            Log::error('tahun column not found, please run migration first');
            
        } else {
            Log::error('Both columns missing, check migration status');
        }
        
        // Validate final data
        $totalRecords = DB::table('penerimaan')->count();
        $validRecords = DB::table('penerimaan')
            ->whereNotNull('tahun')
            ->where('tahun', '>', 0)
            ->count();
            
        Log::info("Migration completed: {$validRecords}/{$totalRecords} records have valid tahun");
        
        if ($totalRecords !== $validRecords) {
            Log::error('Some records still have invalid tahun values');
        }
    }
}

// ========================================
// File: app/Console/Commands/MigratePenerimaanData.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Penerimaan;
use App\Models\TahunAnggaran;

class MigratePenerimaanData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'migrate:penerimaan-data 
                           {--verify : Only verify data without making changes}
                           {--force : Force migration even if data looks good}';

    /**
     * The console command description.
     */
    protected $description = 'Migrate penerimaan data from tahun_anggaran_id to tahun column';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Penerimaan Data Migration...');
        
        // Check column existence
        $hasOldColumn = DB::getSchemaBuilder()->hasColumn('penerimaan', 'tahun_anggaran_id');
        $hasNewColumn = DB::getSchemaBuilder()->hasColumn('penerimaan', 'tahun');
        
        $this->info("📋 Column Status:");
        $this->line("   - tahun_anggaran_id: " . ($hasOldColumn ? '✅ EXISTS' : '❌ NOT FOUND'));
        $this->line("   - tahun: " . ($hasNewColumn ? '✅ EXISTS' : '❌ NOT FOUND'));
        
        if (!$hasNewColumn) {
            $this->error('❌ tahun column not found. Please run migration first:');
            $this->line('   php artisan migrate');
            return Command::FAILURE;
        }
        
        // Get current data status
        $totalRecords = Penerimaan::count();
        $invalidTahun = Penerimaan::whereNull('tahun')->orWhere('tahun', 0)->count();
        
        $this->info("📊 Current Data Status:");
        $this->line("   - Total Records: {$totalRecords}");
        $this->line("   - Records with invalid tahun: {$invalidTahun}");
        $this->line("   - Records with valid tahun: " . ($totalRecords - $invalidTahun));
        
        if ($invalidTahun === 0 && !$this->option('force')) {
            $this->info('✅ All records already have valid tahun values. Migration not needed.');
            return Command::SUCCESS;
        }
        
        if ($this->option('verify')) {
            $this->info('🔍 Verification mode - no changes will be made');
            $this->showDataPreview();
            return Command::SUCCESS;
        }
        
        // Confirm before proceeding
        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to proceed with the migration?')) {
                $this->info('Migration cancelled.');
                return Command::SUCCESS;
            }
        }
        
        $this->info('🔄 Migrating data...');
        
        // Step 1: Migrate from tahun_anggaran if column exists
        if ($hasOldColumn) {
            $this->line('   Step 1: Copying tahun from tahun_anggaran relation...');
            
            $affected = DB::table('penerimaan as p')
                ->join('tahun_anggaran as ta', 'p.tahun_anggaran_id', '=', 'ta.id')
                ->whereNull('p.tahun')
                ->orWhere('p.tahun', 0)
                ->update(['p.tahun' => DB::raw('ta.tahun')]);
                
            $this->line("   ✅ Updated {$affected} records from tahun_anggaran relation");
        }
        
        // Step 2: Set tahun based on tanggal for remaining records
        $remainingInvalid = Penerimaan::whereNull('tahun')->orWhere('tahun', 0)->count();
        
        if ($remainingInvalid > 0) {
            $this->line('   Step 2: Setting tahun based on tanggal for remaining records...');
            
            $affected = DB::table('penerimaan')
                ->whereNull('tahun')
                ->orWhere('tahun', 0)
                ->update(['tahun' => DB::raw('YEAR(tanggal)')]);
                
            $this->line("   ✅ Updated {$affected} records based on tanggal");
        }
        
        // Step 3: Validate results
        $this->line('   Step 3: Validating results...');
        
        $finalInvalid = Penerimaan::whereNull('tahun')->orWhere('tahun', 0)->count();
        $finalValid = $totalRecords - $finalInvalid;
        
        $this->info("📊 Final Results:");
        $this->line("   - Total Records: {$totalRecords}");
        $this->line("   - Valid Records: {$finalValid}");
        $this->line("   - Invalid Records: {$finalInvalid}");
        
        if ($finalInvalid === 0) {
            $this->info('✅ Migration completed successfully!');
            
            // Show sample of migrated data
            $this->showDataPreview();
            
            return Command::SUCCESS;
        } else {
            $this->error("❌ Migration completed with {$finalInvalid} invalid records remaining");
            return Command::FAILURE;
        }
    }
    
    private function showDataPreview()
    {
        $this->info('📋 Sample of current data:');
        
        $samples = DB::table('penerimaan')
            ->join('kode_rekening', 'penerimaan.kode_rekening_id', '=', 'kode_rekening.id')
            ->select([
                'penerimaan.id',
                'penerimaan.tanggal',
                'penerimaan.tahun',
                'kode_rekening.kode',
                'penerimaan.jumlah'
            ])
            ->limit(5)
            ->get();
            
        $headers = ['ID', 'Tanggal', 'Tahun', 'Kode', 'Jumlah'];
        $rows = $samples->map(function ($item) {
            return [
                $item->id,
                $item->tanggal,
                $item->tahun ?: 'NULL',
                $item->kode,
                number_format($item->jumlah, 0, ',', '.')
            ];
        });
        
        $this->table($headers, $rows);
    }
}