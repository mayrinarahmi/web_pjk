<?php

namespace App\Imports;

use App\Models\KodeRekening;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KodeRekeningImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $errors = [];
    protected $processedCount = 0;
    protected $skippedCount = 0;
    protected $defaultBerlakuMulai;

    /**
     * @param int|null $berlakuMulai Default berlaku_mulai jika tidak ada di Excel
     */
    public function __construct($berlakuMulai = null)
    {
        $this->defaultBerlakuMulai = $berlakuMulai ?? 2022;
    }

    /**
     * Process the entire collection at once to handle parent-child relationships
     */
    public function collection(Collection $rows)
    {
        // Clean and prepare data first
        $cleanedRows = $rows->map(function ($row) {
            return [
                'kode' => $this->cleanKode($row['kode'] ?? ''),
                'nama' => $this->cleanNama($row['nama'] ?? ''),
                'level' => $this->cleanLevel($row['level'] ?? ''),
                'berlaku_mulai' => isset($row['berlaku_mulai']) && !empty($row['berlaku_mulai'])
                    ? (int) $row['berlaku_mulai']
                    : $this->defaultBerlakuMulai,
            ];
        });
        
        // Group rows by level for ordered processing
        $groupedByLevel = $cleanedRows->groupBy('level')->sortKeys();
        
        DB::beginTransaction();
        
        try {
            // Process each level in order (1, 2, 3, 4, 5, 6)
            foreach ($groupedByLevel as $level => $levelRows) {
                foreach ($levelRows as $row) {
                    $this->processRow($row, $level);
                }
            }
            
            DB::commit();
            
            Log::info("Import completed. Processed: {$this->processedCount}, Skipped: {$this->skippedCount}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Clean kode value - handle numeric and string formats
     */
    protected function cleanKode($value)
    {
        // Convert to string first
        $value = (string) $value;
        
        // Remove any whitespace
        $value = trim($value);
        
        // Remove any quotes
        $value = trim($value, '"\'');
        
        // Handle scientific notation (jika ada)
        if (stripos($value, 'E+') !== false || stripos($value, 'E-') !== false) {
            $value = number_format((float)$value, 0, '', '');
        }
        
        // Ensure dots are preserved (not converted to comma)
        $value = str_replace(',', '.', $value);
        
        return $value;
    }
    
    /**
     * Clean nama value
     */
    protected function cleanNama($value)
    {
        // Convert to string
        $value = (string) $value;
        
        // Trim whitespace
        $value = trim($value);
        
        // Remove any extra spaces
        $value = preg_replace('/\s+/', ' ', $value);
        
        return $value;
    }
    
    /**
     * Clean level value - ensure it's numeric
     */
    protected function cleanLevel($value)
    {
        // Convert to integer
        $value = (int) $value;
        
        // Validate range
        if ($value < 1 || $value > 6) {
            return 0; // Invalid level
        }
        
        return $value;
    }
    
    protected function processRow($row, $level)
    {
        try {
            $kode = $row['kode'];
            $nama = $row['nama'];
            $berlakuMulai = $row['berlaku_mulai'] ?? $this->defaultBerlakuMulai;

            // Skip if empty
            if (empty($kode) || empty($nama)) {
                $this->errors[] = "Data kosong ditemukan, dilewati";
                $this->skippedCount++;
                return;
            }

            // Validate level
            if ($level < 1 || $level > 6) {
                $this->errors[] = "Level tidak valid untuk kode {$kode}: {$level}";
                $this->skippedCount++;
                return;
            }

            // Validate level matches kode format
            $expectedLevel = $this->calculateLevelFromKode($kode);
            if ($expectedLevel != $level) {
                $this->errors[] = "Kode {$kode} tidak sesuai dengan level {$level}. Seharusnya level {$expectedLevel}";
                $this->skippedCount++;
                return;
            }

            // Check if already exists (unique: kode + berlaku_mulai)
            $existing = KodeRekening::where('kode', $kode)
                ->where('berlaku_mulai', $berlakuMulai)
                ->first();
            if ($existing) {
                // Update existing if needed
                $existing->update([
                    'nama' => $nama,
                    'is_active' => true
                ]);
                Log::info("Kode {$kode} (berlaku_mulai: {$berlakuMulai}) sudah ada, diupdate");
                $this->skippedCount++;
                return;
            }

            // Find parent (for level > 1)
            $parentId = null;
            if ($level > 1) {
                $parentKode = $this->getParentKode($kode);
                // Cari parent dengan berlaku_mulai yang sama
                $parent = KodeRekening::where('kode', $parentKode)
                    ->where('berlaku_mulai', $berlakuMulai)
                    ->first();

                if (!$parent) {
                    $this->errors[] = "Parent kode tidak ditemukan untuk kode: {$kode} (berlaku_mulai: {$berlakuMulai}). Parent yang dicari: {$parentKode}";
                    $this->skippedCount++;
                    return;
                }

                $parentId = $parent->id;
            }

            // Create kode rekening
            KodeRekening::create([
                'kode' => $kode,
                'nama' => $nama,
                'level' => $level,
                'parent_id' => $parentId,
                'is_active' => true,
                'berlaku_mulai' => $berlakuMulai,
            ]);
            
            $this->processedCount++;
            
            Log::info("Successfully imported: {$kode} - {$nama} (Level {$level})");
            
        } catch (\Exception $e) {
            $this->errors[] = "Error processing kode {$row['kode']}: " . $e->getMessage();
            $this->skippedCount++;
            Log::error("Error importing row: " . $e->getMessage());
        }
    }
    
    /**
     * Calculate level from kode format
     */
    protected function calculateLevelFromKode($kode)
    {
        // Count dots in kode to determine level
        $parts = explode('.', $kode);
        return count($parts);
    }
    
    /**
     * Get parent kode from current kode
     */
    protected function getParentKode($kode)
    {
        $parts = explode('.', $kode);
        array_pop($parts); // Remove last part
        return implode('.', $parts);
    }
    
    /**
     * Validation rules - UPDATED to be more flexible
     */
    public function rules(): array
    {
        return [
            // Remove strict string validation, we'll handle conversion
            '*.kode' => 'required',
            '*.nama' => 'required',
            '*.level' => 'required|numeric|min:1|max:6',
            '*.berlaku_mulai' => 'nullable|numeric|min:2020|max:2099',
        ];
    }
    
    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            '*.kode.required' => 'Kolom kode wajib diisi',
            '*.nama.required' => 'Kolom nama wajib diisi', 
            '*.level.required' => 'Kolom level wajib diisi',
            '*.level.numeric' => 'Level harus berupa angka',
            '*.level.min' => 'Level minimal adalah 1',
            '*.level.max' => 'Level maksimal adalah 6',
        ];
    }
    
    /**
     * Get import errors
     */
    public function errors()
    {
        return $this->errors;
    }
    
    /**
     * Get processed count
     */
    public function getProcessedCount()
    {
        return $this->processedCount;
    }
    
    /**
     * Get skipped count
     */
    public function getSkippedCount()
    {
        return $this->skippedCount;
    }
}