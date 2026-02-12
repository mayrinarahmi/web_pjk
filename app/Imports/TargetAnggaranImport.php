<?php

namespace App\Imports;

use App\Models\KodeRekening;
use App\Models\TargetAnggaran;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TargetAnggaranImport implements 
    ToModel, 
    WithHeadingRow, 
    WithValidation, 
    SkipsOnError, 
    SkipsOnFailure,
    WithBatchInserts,
    WithChunkReading
{
    use SkipsErrors, SkipsFailures;
    
    protected $tahunAnggaranId;
    protected $skpdId;
    protected $tahun;
    protected $processedCount = 0;
    protected $skippedCount = 0;
    protected $zeroValueCount = 0;
    protected $skippedDetails = [];
    
    public function __construct($tahunAnggaranId, $skpdId) // ✅ TAMBAH PARAMETER
    {
        $this->tahunAnggaranId = $tahunAnggaranId;
        $this->skpdId = $skpdId;

        // Resolve tahun from tahunAnggaranId for berlaku_mulai filtering
        $ta = \App\Models\TahunAnggaran::find($tahunAnggaranId);
        $this->tahun = $ta ? $ta->tahun : null;
    }
    
    public function model(array $row)
    {
        // Log raw data untuk debugging
        Log::info('Processing row:', $row);
        
        // Extract kode dan pagu dengan flexible column names
        $kode = $this->extractKode($row);
        $pagu = $this->extractPagu($row);
        
        // Skip jika kode kosong
        if (empty($kode)) {
            Log::warning('Skipping empty row');
            $this->skippedCount++;
            $this->skippedDetails[] = "Baris kosong (kode tidak ada)";
            return null;
        }
        
        // Clean kode
        $kode = $this->cleanKode($kode);
        
        // Find kode rekening - HANYA LEVEL 6 ✅
        $kodeRekening = $this->findKodeRekening($kode);
        
        if (!$kodeRekening) {
            Log::warning("Kode rekening not found: {$kode}");
            $this->skippedCount++;
            $this->skippedDetails[] = "Kode '{$kode}' tidak ditemukan di master data";
            return null;
        }
        
        // Validasi: Hanya Level 6 ✅
        if ($kodeRekening->level != 6) {
            Log::warning("Kode rekening bukan level 6: {$kode} (Level: {$kodeRekening->level})");
            $this->skippedCount++;
            $this->skippedDetails[] = "Kode '{$kode}' bukan level 6 (Level: {$kodeRekening->level})";
            return null;
        }
        
        // Clean pagu value
        $paguAnggaran = $this->cleanCurrencyValue($pagu);
        
        // Tetap proses meskipun nilai 0
        if ($paguAnggaran == 0) {
            $this->zeroValueCount++;
            Log::info("Processing zero value for kode: {$kode}");
        }
        
        Log::info("Successfully processing", [
            'kode' => $kode,
            'kode_id' => $kodeRekening->id,
            'level' => $kodeRekening->level,
            'pagu' => $paguAnggaran,
            'skpd_id' => $this->skpdId, // ✅ LOG SKPD
            'original_value' => $pagu
        ]);
        
        $this->processedCount++;
        
        try {
            return TargetAnggaran::updateOrCreate(
                [
                    'kode_rekening_id' => $kodeRekening->id,
                    'tahun_anggaran_id' => $this->tahunAnggaranId,
                    'skpd_id' => $this->skpdId, // ✅ TAMBAH SKPD_ID
                ],
                [
                    'jumlah' => $paguAnggaran
                ]
            );
        } catch (\Exception $e) {
            Log::error("Failed to save TargetAnggaran: " . $e->getMessage());
            $this->skippedCount++;
            $this->processedCount--;
            $this->skippedDetails[] = "Gagal menyimpan data untuk kode '{$kode}': " . $e->getMessage();
            return null;
        }
    }
    
    private function extractKode($row)
    {
        // Coba berbagai kemungkinan nama kolom
        $possibleKeys = [
            'kode', 'kode_rekening', 'code', 'kode rekening', 
            'KODE', 'Kode', 'KODE_REKENING', 'Kode Rekening'
        ];
        
        foreach ($possibleKeys as $key) {
            if (isset($row[$key]) && !empty($row[$key])) {
                return $row[$key];
            }
        }
        
        // Coba dengan normalisasi key
        foreach ($row as $rowKey => $value) {
            $normalizedKey = strtolower(str_replace([' ', '_', '-'], '', $rowKey));
            if ($normalizedKey === 'kode' || $normalizedKey === 'koderekening') {
                return $value;
            }
        }
        
        return null;
    }
    
    private function extractPagu($row)
    {
        // Coba berbagai kemungkinan nama kolom
        $possibleKeys = [
            'pagu_anggaran', 'pagu', 'jumlah', 'nilai', 'anggaran',
            'pagu anggaran', 'PAGU', 'PAGU_ANGGARAN', 'Pagu Anggaran',
            'JUMLAH', 'NILAI', 'nominal', 'NOMINAL'
        ];
        
        foreach ($possibleKeys as $key) {
            if (isset($row[$key])) {
                return $row[$key];
            }
        }
        
        // Coba dengan normalisasi key
        foreach ($row as $rowKey => $value) {
            $normalizedKey = strtolower(str_replace([' ', '_', '-'], '', $rowKey));
            if (strpos($normalizedKey, 'pagu') !== false || 
                strpos($normalizedKey, 'jumlah') !== false || 
                strpos($normalizedKey, 'nilai') !== false ||
                strpos($normalizedKey, 'anggaran') !== false) {
                return $value;
            }
        }
        
        return null;
    }
    
    private function cleanKode($kode)
    {
        $kode = (string) $kode;
        $kode = trim($kode);
        $kode = str_replace(['"', "'", ' '], '', $kode);
        
        // Handle jika kode menggunakan koma sebagai pemisah
        if (strpos($kode, ',') !== false && strpos($kode, '.') === false) {
            $kode = str_replace(',', '.', $kode);
        }
        
        // Normalize multiple dots
        $kode = preg_replace('/\.+/', '.', $kode);
        $kode = rtrim($kode, '.');
        $kode = ltrim($kode, '.');
        
        return $kode;
    }
    
    private function findKodeRekening($kode)
    {
        // Method 1: Exact match + Level 6 + tahun filter ✅
        $query = KodeRekening::where('kode', $kode)
            ->where('is_active', true)
            ->where('level', 6);

        if ($this->tahun) {
            $query->forTahun($this->tahun);
        }

        $kodeRekening = $query->first();

        if ($kodeRekening) {
            return $kodeRekening;
        }

        // Method 2: Case insensitive + Level 6 + tahun filter ✅
        $query2 = KodeRekening::whereRaw('LOWER(kode) = LOWER(?)', [$kode])
            ->where('is_active', true)
            ->where('level', 6);

        if ($this->tahun) {
            $query2->forTahun($this->tahun);
        }

        $kodeRekening = $query2->first();

        if ($kodeRekening) {
            return $kodeRekening;
        }
        
        // Log untuk debugging
        Log::warning("Kode not found (Level 6): '{$kode}'");
        
        return null;
    }
    
    protected function cleanCurrencyValue($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }
        
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        $value = (string) $value;
        
        Log::info("Original currency value: '{$value}'");
        
        // Hapus currency symbols dan spasi
        $value = preg_replace('/[Rp\s]/i', '', $value);
        $value = str_replace(['IDR', 'idr'], '', $value);
        
        // Deteksi format desimal
        if (preg_match('/\.(\d{1,2})$/', $value)) {
            // Format dengan titik sebagai desimal
            $parts = explode('.', $value);
            if (count($parts) > 2) {
                $decimal = array_pop($parts);
                $integer = implode('', $parts);
                $value = $integer . '.' . $decimal;
            }
        } else if (strpos($value, ',') !== false) {
            // Handle format Indonesia (koma sebagai desimal)
            if (strpos($value, '.') !== false) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $parts = explode(',', $value);
                if (isset($parts[1]) && strlen($parts[1]) <= 2) {
                    $value = str_replace(',', '.', $value);
                } else {
                    $value = str_replace(',', '', $value);
                }
            }
        }
        
        // Hapus karakter non-numeric kecuali titik dan minus
        $value = preg_replace('/[^0-9.\-]/', '', $value);
        
        $result = (float) $value;
        
        Log::info("Cleaned currency value: {$result}");
        
        return $result;
    }
    
    public function rules(): array
    {
        return [];
    }
    
    public function batchSize(): int
    {
        return 100;
    }
    
    public function chunkSize(): int
    {
        return 100;
    }
    
    public function getProcessedCount()
    {
        return $this->processedCount;
    }
    
    public function getSkippedCount()
    {
        return $this->skippedCount;
    }
    
    public function getZeroValueCount()
    {
        return $this->zeroValueCount;
    }
    
    public function getSkippedDetails()
    {
        return $this->skippedDetails;
    }
}