<?php

namespace App\Imports;

use App\Models\TargetAnggaran;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Log;

class TargetAnggaranImport implements 
    ToModel, 
    WithHeadingRow, 
    WithValidation, 
    SkipsEmptyRows,
    SkipsOnError, 
    WithBatchInserts, 
    WithChunkReading
{
    use Importable, SkipsErrors;
    
    protected $tahunAnggaranId;
    protected $processedCount = 0;
    protected $skippedCount = 0;
    
    public function __construct($tahunAnggaranId)
    {
        $this->tahunAnggaranId = $tahunAnggaranId;
    }
    
    /**
     * Transform setiap row Excel menjadi model
     */
    public function model(array $row)
    {
        try {
            // Handle both formats: pagu_anggaran or pagu anggaran
            $paguValue = $row['pagu_anggaran'] ?? $row['pagu anggaran'] ?? null;
            
            // Skip jika data kosong
            if (empty($row['kode']) || empty($paguValue)) {
                return null;
            }
            
            // Cari kode rekening berdasarkan kode (ambil yang aktif dan terbaru)
            $kodeRekening = KodeRekening::where('kode', $row['kode'])
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$kodeRekening) {
                Log::warning("Kode rekening '{$row['kode']}' tidak ditemukan");
                $this->skippedCount++;
                return null;
            }
            
            // Clean pagu anggaran value (hapus format currency jika ada)
            $paguAnggaran = $this->cleanCurrencyValue($paguValue);
            
            // Log untuk debugging
            Log::info("Processing: Kode={$row['kode']}, KodeRekeningID={$kodeRekening->id}, Pagu Raw={$paguValue}, Pagu Clean={$paguAnggaran}");
            
            $this->processedCount++;
            
            // Test direct save untuk debug
            if ($row['kode'] == '4.1.02') {
                Log::info("DEBUG 4.1.02: Before save - Pagu={$paguAnggaran}");
                
                // Coba direct update query
                \DB::table('target_anggaran')
                    ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                    ->where('kode_rekening_id', $kodeRekening->id)
                    ->update(['jumlah' => $paguAnggaran]);
                    
                Log::info("DEBUG 4.1.02: After direct DB update");
                
                // Return null agar tidak diproses lagi oleh model
                return null;
            }
            
            // Update atau create target anggaran
            $target = TargetAnggaran::updateOrCreate(
                [
                    'tahun_anggaran_id' => $this->tahunAnggaranId,
                    'kode_rekening_id' => $kodeRekening->id,
                ],
                [
                    'jumlah' => $paguAnggaran,
                ]
            );
            
            // Verify the saved value
            $target->refresh();
            Log::info("Target Anggaran saved: ID={$target->id}, Jumlah={$target->jumlah}, Expected={$paguAnggaran}");
            
            if ($target->jumlah != $paguAnggaran) {
                Log::error("Value mismatch! Expected: {$paguAnggaran}, Saved: {$target->jumlah}");
            }
            
            return $target;
            
        } catch (\Exception $e) {
            Log::error('Error processing row: ' . $e->getMessage(), ['row' => $row]);
            throw $e;
        }
    }
    
    /**
     * Clean currency format dari value
     */
    private function cleanCurrencyValue($value)
    {
        // Log original value
        Log::info("Original value: " . json_encode($value) . " Type: " . gettype($value));
        
        // Handle scientific notation atau number yang terlalu besar
        if (is_numeric($value)) {
            $result = (float) $value;
            Log::info("Direct numeric conversion: " . $result);
            return $result;
        }
        
        // Convert to string first
        $valueStr = strval($value);
        
        // Hapus "Rp", spasi, titik sebagai pemisah ribuan
        $cleaned = preg_replace('/[Rp\s\.]/', '', $valueStr);
        
        // Ganti koma dengan titik untuk desimal
        $cleaned = str_replace(',', '.', $cleaned);
        
        // Convert ke float
        $result = floatval($cleaned);
        
        // Log cleaned value
        Log::info("Cleaned value: " . $result);
        
        return $result;
    }
    
    /**
     * Aturan validasi
     */
    public function rules(): array
    {
        return [
            'kode' => 'required|string',
            'pagu_anggaran' => 'required_without:pagu anggaran',
            'pagu anggaran' => 'required_without:pagu_anggaran',
        ];
    }
    
    /**
     * Pesan error custom
     */
    public function customValidationMessages()
    {
        return [
            'kode.required' => 'Kolom kode harus diisi',
            'pagu_anggaran.required_without' => 'Kolom pagu anggaran harus diisi',
            'pagu anggaran.required_without' => 'Kolom pagu anggaran harus diisi',
        ];
    }
    
    /**
     * Batch size untuk insert
     */
    public function batchSize(): int
    {
        return 100;
    }
    
    /**
     * Chunk size untuk reading
     */
    public function chunkSize(): int
    {
        return 100;
    }
    
    /**
     * Get processed count
     */
    public function getProcessedCount(): int
    {
        return $this->processedCount;
    }
    
    /**
     * Get skipped count
     */
    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }
    
    /**
     * Skip row on error
     */
    public function onError(\Throwable $e)
    {
        Log::error('Import error: ' . $e->getMessage());
    }
}