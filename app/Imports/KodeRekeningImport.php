<?php

namespace App\Imports;

use App\Models\KodeRekening;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KodeRekeningImport implements 
    ToModel, 
    WithHeadingRow, 
    WithValidation, 
    SkipsEmptyRows, // Tambahan ini untuk skip baris kosong
    SkipsOnError, 
    WithBatchInserts, 
    WithChunkReading
{
    use Importable, SkipsErrors;
    
    private $processedKodes = [];
    
    /**
     * Transform setiap row Excel menjadi model
     */
    public function model(array $row)
    {
        try {
            // Skip jika semua kolom kosong
            if (empty($row['kode']) && empty($row['nama']) && empty($row['level'])) {
                return null;
            }
            
            // Skip jika kode sudah diproses
            if (in_array($row['kode'], $this->processedKodes)) {
                return null;
            }
            
            // Log untuk debugging
            Log::info('Processing row:', $row);
            
            // Cek apakah kode sudah ada di database
            $existing = KodeRekening::where('kode', $row['kode'])->first();
            if ($existing) {
                // Update data yang sudah ada
                $existing->update([
                    'nama' => $row['nama'],
                    'level' => $row['level'],
                    'is_active' => 1,
                ]);
                
                $this->processedKodes[] = $row['kode'];
                return null;
            }
            
            // Tentukan parent_id berdasarkan kode
            $parentId = $this->findParentId($row['kode'], $row['level']);
            
            if ($parentId === false) {
                throw new \Exception("Parent kode tidak ditemukan untuk kode: {$row['kode']}");
            }
            
            $this->processedKodes[] = $row['kode'];
            
            return new KodeRekening([
                'kode' => $row['kode'],
                'nama' => $row['nama'],
                'level' => $row['level'],
                'parent_id' => $parentId,
                'is_active' => 1,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error processing row: ' . $e->getMessage(), ['row' => $row]);
            throw $e;
        }
    }
    
    /**
     * Cari parent_id berdasarkan struktur kode
     */
    private function findParentId($kode, $level)
    {
        if ($level == 1) {
            return null;
        }
        
        // Pecah kode berdasarkan titik
        $parts = explode('.', $kode);
        
        // Buat struktur parent
        $parentParts = array_slice($parts, 0, -1);
        $parentKode = implode('.', $parentParts);
        
        // Cari parent di database
        $parent = KodeRekening::where('kode', $parentKode)->first();
        
        if (!$parent) {
            Log::warning("Parent kode '{$parentKode}' tidak ditemukan untuk kode '{$kode}'");
            return false; // Return false jika parent tidak ada
        }
        
        return $parent->id;
    }
    
    /**
     * Aturan validasi - hanya untuk row yang tidak kosong
     */
    public function rules(): array
    {
        return [
            'kode' => 'required|string|max:50',
            'nama' => 'required|string|max:255',
            'level' => 'required|integer|between:1,6',
        ];
    }
    
    /**
     * Pesan error custom
     */
    public function customValidationMessages()
    {
        return [
            'kode.required' => 'Kolom kode harus diisi',
            'kode.max' => 'Kode maksimal 50 karakter',
            'nama.required' => 'Kolom nama harus diisi',
            'nama.max' => 'Nama maksimal 255 karakter',
            'level.required' => 'Kolom level harus diisi',
            'level.integer' => 'Level harus berupa angka',
            'level.between' => 'Level harus antara 1-6',
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
     * Skip row on error
     */
    public function onError(\Throwable $e)
    {
        Log::error('Import error: ' . $e->getMessage());
    }
}