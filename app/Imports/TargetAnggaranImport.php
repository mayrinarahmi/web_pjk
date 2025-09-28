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
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TargetAnggaranImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;
    
    protected $tahunAnggaranId;
    protected $processedCount = 0;
    protected $skippedCount = 0;
    protected $zeroValueCount = 0;
    protected $skippedDetails = [];
    
    public function __construct($tahunAnggaranId)
    {
        $this->tahunAnggaranId = $tahunAnggaranId;
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
        
        // Find kode rekening
        $kodeRekening = $this->findKodeRekening($kode);
        
        if (!$kodeRekening) {
            Log::warning("Kode rekening not found: {$kode}");
            $this->skippedCount++;
            $this->skippedDetails[] = "Kode '{$kode}' tidak ditemukan di master data";
            return null;
        }
        
        // Clean pagu value - PERBAIKAN UTAMA DI SINI
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
            'original_value' => $pagu
        ]);
        
        $this->processedCount++;
        
        try {
            return TargetAnggaran::updateOrCreate(
                [
                    'kode_rekening_id' => $kodeRekening->id,
                    'tahun_anggaran_id' => $this->tahunAnggaranId
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
                // Return nilai meskipun 0
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
        // Method 1: Exact match
        $kodeRekening = KodeRekening::where('kode', $kode)
            ->where('is_active', true)
            ->first();
            
        if ($kodeRekening) {
            return $kodeRekening;
        }
        
        // Method 2: Case insensitive
        $kodeRekening = KodeRekening::whereRaw('LOWER(kode) = LOWER(?)', [$kode])
            ->where('is_active', true)
            ->first();
            
        if ($kodeRekening) {
            return $kodeRekening;
        }
        
        // Method 3: Try with trimmed spaces in database
        $kodeRekening = KodeRekening::whereRaw('TRIM(kode) = ?', [$kode])
            ->where('is_active', true)
            ->first();
            
        if ($kodeRekening) {
            return $kodeRekening;
        }
        
        // Log untuk debugging
        Log::warning("Kode not found in any method: '{$kode}'");
        
        // Cari kode yang mirip untuk debugging
        $similar = KodeRekening::where('kode', 'LIKE', '%' . substr($kode, -4) . '%')
            ->where('is_active', true)
            ->limit(3)
            ->pluck('kode');
            
        if ($similar->count() > 0) {
            Log::info("Similar codes for '{$kode}':", $similar->toArray());
        }
        
        return null;
    }
    
    /**
     * PERBAIKAN UTAMA: Clean currency value dengan handle desimal yang benar
     */
    protected function cleanCurrencyValue($value)
    {
        // Handle null atau empty string sebagai 0
        if ($value === null || $value === '') {
            return 0;
        }
        
        // Jika sudah numeric, langsung return
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        $value = (string) $value;
        
        // Log nilai asli untuk debugging
        Log::info("Original currency value: '{$value}'");
        
        // Hapus currency symbols dan spasi
        $value = preg_replace('/[Rp\s]/i', '', $value);
        $value = str_replace(['IDR', 'idr'], '', $value);
        
        // PENTING: Deteksi format desimal
        // Jika ada titik dengan 1-2 digit di belakangnya di akhir string, itu adalah desimal
        // Contoh: 103410956098.16 -> titik adalah desimal
        
        // Cek apakah ada pola desimal (titik diikuti 1-2 digit di akhir)
        if (preg_match('/\.(\d{1,2})$/', $value)) {
            // Ini format dengan titik sebagai desimal
            // JANGAN hapus titik terakhir
            
            // Hapus semua titik KECUALI yang terakhir (jika ada titik sebagai thousand separator)
            $parts = explode('.', $value);
            if (count($parts) > 2) {
                // Ada multiple titik, yang terakhir adalah desimal
                $decimal = array_pop($parts);
                $integer = implode('', $parts);
                $value = $integer . '.' . $decimal;
            }
            // Jika hanya 1 titik, biarkan apa adanya (sudah benar)
            
        } else if (strpos($value, ',') !== false) {
            // Handle format Indonesia (koma sebagai desimal)
            if (strpos($value, '.') !== false) {
                // Ada titik dan koma, asumsi: titik = ribuan, koma = desimal
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                // Hanya ada koma
                $parts = explode(',', $value);
                if (isset($parts[1]) && strlen($parts[1]) <= 2) {
                    // Koma sebagai desimal
                    $value = str_replace(',', '.', $value);
                } else {
                    // Koma sebagai ribuan
                    $value = str_replace(',', '', $value);
                }
            }
        }
        // Jika tidak ada titik atau koma di posisi desimal, nilai sudah benar
        
        // Hapus karakter non-numeric kecuali titik dan minus
        $value = preg_replace('/[^0-9.\-]/', '', $value);
        
        // Convert ke float
        $result = (float) $value;
        
        // Log hasil untuk debugging
        Log::info("Cleaned currency value: {$result}");
        
        return $result;
    }
    
    public function rules(): array
    {
        return [];
    }
    
    public function onError(\Throwable $e)
    {
        Log::error('Import error: ' . $e->getMessage());
        $this->skippedDetails[] = 'Error: ' . $e->getMessage();
    }
    
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $row = $failure->row();
            $errors = implode(', ', $failure->errors());
            $this->skippedDetails[] = "Baris {$row}: {$errors}";
        }
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