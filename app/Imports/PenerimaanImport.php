<?php

namespace App\Imports;

use App\Models\Penerimaan;
use App\Models\KodeRekening;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PenerimaanImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnFailure
{
    use Importable, SkipsFailures;
    
    private $tahun;
    private $skpdId;      // TAMBAHAN
    private $createdBy;   // TAMBAHAN
    private $processedCount = 0;
    private $skippedCount = 0;
    
    public function __construct($tahun, $skpdId = null, $createdBy = null)
    {
        $this->tahun = $tahun;
        $this->skpdId = $skpdId;        // TAMBAHAN
        $this->createdBy = $createdBy;  // TAMBAHAN
    }
    
    /**
     * Transform setiap row Excel menjadi model
     */
    public function model(array $row)
    {
        try {
            // Skip jika data kosong
            if (empty($row['kode']) || empty($row['tanggal']) || empty($row['jumlah'])) {
                return null;
            }
            
            // Cari kode rekening berdasarkan kode (filter by tahun berlaku)
            $kodeQuery = KodeRekening::where('kode', $row['kode'])
                ->where('is_active', true);

            if ($this->tahun) {
                $kodeQuery->forTahun($this->tahun);
            }

            $kodeRekening = $kodeQuery->first();
            
            if (!$kodeRekening) {
                Log::warning("Kode rekening '{$row['kode']}' tidak ditemukan");
                $this->skippedCount++;
                return null;
            }
            
            // TAMBAHAN: Validasi kode rekening harus level 6
            if ($kodeRekening->level != 6) {
                Log::warning("Kode rekening '{$row['kode']}' bukan level 6, skip import");
                $this->skippedCount++;
                return null;
            }
            
            // Parse tanggal - handle berbagai format
            $tanggal = $this->parseDate($row['tanggal']);
            if (!$tanggal) {
                Log::warning("Format tanggal tidak valid: {$row['tanggal']}");
                $this->skippedCount++;
                return null;
            }
            
            // Clean jumlah value - bisa terima nilai minus
            $jumlah = $this->cleanCurrencyValue($row['jumlah']);
            
            // Validasi tahun dari tanggal harus sesuai dengan tahun yang dipilih
            if ($tanggal->year != $this->tahun) {
                Log::warning("Tanggal {$tanggal->format('d-m-Y')} tidak sesuai dengan tahun {$this->tahun}");
                $this->skippedCount++;
                return null;
            }
            
            $this->processedCount++;
            
            // Create penerimaan dengan SKPD ID
            return new Penerimaan([
                'kode_rekening_id' => $kodeRekening->id,
                'tahun' => $this->tahun,
                'tanggal' => $tanggal,
                'jumlah' => $jumlah,
                'keterangan' => $row['keterangan'] ?? null,
                'skpd_id' => $this->skpdId,        // TAMBAHAN
                'created_by' => $this->createdBy,  // TAMBAHAN
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error processing row: ' . $e->getMessage(), ['row' => $row]);
            throw $e;
        }
    }
    
    /**
     * Parse tanggal dari berbagai format
     */
    private function parseDate($value)
    {
        try {
            // Jika numeric (Excel date)
            if (is_numeric($value)) {
                return Carbon::instance(Date::excelToDateTimeObject($value));
            }
            
            // Jika string, coba parse
            if (is_string($value)) {
                // Coba beberapa format umum
                $formats = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'd-M-Y'];
                foreach ($formats as $format) {
                    try {
                        return Carbon::createFromFormat($format, $value);
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
            
            // Jika sudah Carbon/DateTime
            if ($value instanceof \DateTime) {
                return Carbon::instance($value);
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Clean currency format dari value
     * Support nilai minus/negatif
     */
    private function cleanCurrencyValue($value)
    {
        // Handle jika sudah numeric
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        // Convert to string first
        $valueStr = strval($value);
        
        // Check if negative (bisa pakai tanda minus atau dalam kurung)
        $isNegative = false;
        if (strpos($valueStr, '-') !== false || 
            (strpos($valueStr, '(') !== false && strpos($valueStr, ')') !== false)) {
            $isNegative = true;
        }
        
        // Hapus semua karakter non-numeric kecuali koma dan titik
        $cleaned = preg_replace('/[^0-9,.]/', '', $valueStr);
        
        // Ganti koma dengan titik untuk desimal
        $cleaned = str_replace(',', '.', $cleaned);
        
        // Convert ke float
        $numericValue = floatval($cleaned);
        
        // Apply negative if needed
        if ($isNegative && $numericValue > 0) {
            $numericValue = -$numericValue;
        }
        
        return $numericValue;
    }
    
    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'kode' => 'required',
            'tanggal' => 'required',
            'jumlah' => 'required|numeric', // PERUBAHAN: Hapus min:0 untuk support nilai minus
        ];
    }
    
    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'kode.required' => 'Kode rekening tidak boleh kosong',
            'tanggal.required' => 'Tanggal tidak boleh kosong',
            'jumlah.required' => 'Jumlah tidak boleh kosong',
            'jumlah.numeric' => 'Jumlah harus berupa angka',
            // PERUBAHAN: Hapus message untuk min karena sudah dihapus rule-nya
        ];
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