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

            // Ambil hanya bagian kode (sebelum " - "), untuk handle format "kode - uraian"
            $kode = trim(explode(' - ', $row['kode'])[0]);

            // Cari kode rekening berdasarkan kode (filter by tahun berlaku)
            $kodeQuery = KodeRekening::where('kode', $kode)
                ->where('is_active', true);

            if ($this->tahun) {
                $kodeQuery->forTahun($this->tahun);
            }

            $kodeRekening = $kodeQuery->first();
            
            if (!$kodeRekening) {
                Log::warning("Kode rekening '{$kode}' tidak ditemukan");
                $this->skippedCount++;
                return null;
            }
            
            // TAMBAHAN: Validasi kode rekening harus level 6
            if ($kodeRekening->level != 6) {
                Log::warning("Kode rekening '{$kode}' bukan level 6, skip import");
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
            
            // Save langsung di dalam try-catch agar DB error pun tertangkap
            $penerimaan = new Penerimaan([
                'kode_rekening_id' => $kodeRekening->id,
                'tahun' => $this->tahun,
                'tanggal' => $tanggal,
                'jumlah' => $jumlah,
                'skpd_id' => $this->skpdId,
                'created_by' => $this->createdBy,
            ]);
            $penerimaan->save();
            $this->processedCount++;

            return null; // sudah di-save, package tidak perlu save lagi

        } catch (\Exception $e) {
            Log::error('Error processing row: ' . $e->getMessage(), ['row' => $row]);
            $this->skippedCount++;
            return null;
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
                // Coba beberapa format umum (termasuk tahun 2-digit seperti "02-01-26")
                $formats = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'd-M-Y', 'd-m-y', 'd/m/y'];
                foreach ($formats as $format) {
                    try {
                        $date = Carbon::createFromFormat($format, $value);
                        // Koreksi tahun 2-digit: jika tahun < 100, tambahkan 2000
                        if ($date->year < 100) {
                            $date->addYears(2000);
                        }
                        return $date;
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
     * Clean currency format dari value — support format Indonesia (titik=ribuan, koma=desimal)
     * Contoh: "135.100,00" → 135100 | "2.456.172.889.497" → 2456172889497
     */
    private function cleanCurrencyValue($value)
    {
        // Sudah numeric (Excel simpan sebagai angka)
        if (is_numeric($value)) {
            return (float) $value;
        }

        $valueStr = strval($value);

        // Deteksi nilai negatif
        $isNegative = strpos($valueStr, '-') !== false ||
                      (strpos($valueStr, '(') !== false && strpos($valueStr, ')') !== false);

        // Hapus karakter selain digit, titik, dan koma
        $cleaned = preg_replace('/[^0-9.,]/', '', $valueStr);

        if (strpos($cleaned, ',') !== false) {
            // Format Indonesia: titik = pemisah ribuan, koma = desimal
            // "135.100,00" → hapus titik → "135100,00" → ganti koma → "135100.00"
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } else {
            // Tidak ada koma: titik dianggap pemisah ribuan
            // "2.456.172.889.497" → "2456172889497"
            $cleaned = str_replace('.', '', $cleaned);
        }

        $numericValue = floatval($cleaned);

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
            'jumlah' => 'required',
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