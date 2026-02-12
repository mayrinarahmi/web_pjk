<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KodeRekening extends Model
{
    protected $table = 'kode_rekening';
    
    protected $fillable = [
        'kode',
        'nama',
        'parent_id',
        'level',
        'is_active',
        'berlaku_mulai',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'berlaku_mulai' => 'integer',
    ];
    
    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
    public function parent()
    {
        return $this->belongsTo(KodeRekening::class, 'parent_id');
    }
    
    public function children()
    {
        return $this->hasMany(KodeRekening::class, 'parent_id');
    }
    
    public function targetAnggaran()
    {
        return $this->hasMany(TargetAnggaran::class);
    }
    
    public function penerimaan()
    {
        return $this->hasMany(Penerimaan::class);
    }
    
    // ==========================================
    // HIERARKI CALCULATION - UPDATED UNTUK SKPD SYSTEM
    // ==========================================
    
    /**
     * Helper method untuk mendapatkan target anggaran dengan hierarki calculation
     * ✅ Updated untuk support SKPD
     * 
     * @param int $kodeRekeningId
     * @param int $tahunAnggaranId
     * @param int|null $skpdId - NULL = konsolidasi, INT = per SKPD
     * @return float
     */
    public static function getTargetAnggaran($kodeRekeningId, $tahunAnggaranId, $skpdId = null)
    {
        $kodeRekening = self::find($kodeRekeningId);
        if (!$kodeRekening) return 0;
        
        return $kodeRekening->calculateHierarchiTarget($tahunAnggaranId, $skpdId);
    }
    
    /**
     * Method untuk menghitung target hierarki dengan KONSOLIDASI
     * ✅ Updated untuk support SKPD system
     * 
     * @param int $tahunAnggaranId
     * @param int|null $skpdId - NULL = konsolidasi, INT = per SKPD
     * @return float
     */
   public function calculateHierarchiTarget($tahunAnggaranId, $skpdId = null)
{
    // Jika level 6 (leaf node)
    if ($this->level == 6) {
        if ($skpdId) {
            // Untuk SKPD tertentu - ambil dari table
            $target = TargetAnggaran::where('kode_rekening_id', $this->id)
                ->where('tahun_anggaran_id', $tahunAnggaranId)
                ->where('skpd_id', $skpdId)
                ->first();
            return $target ? $target->jumlah : 0;
        } else {
            // Konsolidasi - SUM semua SKPD
            return TargetAnggaran::where('kode_rekening_id', $this->id)
                ->where('tahun_anggaran_id', $tahunAnggaranId)
                ->sum('jumlah');
        }
    }
    
    // ✅ FIXED: Level 1-5 SELALU recursive (tidak pakai stored)
    // Sum dari children untuk SKPD tertentu ATAU konsolidasi
    $total = 0;
    $children = $this->children()->where('is_active', true)->get();
    
    foreach ($children as $child) {
        $total += $child->calculateHierarchiTarget($tahunAnggaranId, $skpdId);
    }
    
    return $total;
}

/**
 * Get Target Anggaran untuk tahun tertentu (RECURSIVE - seperti Penerimaan)
 * ✅ Method baru untuk consistency dengan getTotalPenerimaanForTahun
 * 
 * @param int $tahunAnggaranId
 * @param int|null $skpdId - Jika null, konsolidasi semua SKPD
 * @return float
 */
public function getTargetAnggaranForTahun($tahunAnggaranId, $skpdId = null)
{
    return $this->calculateHierarchiTarget($tahunAnggaranId, $skpdId);
}

/**
 * Validate hierarki konsistensi untuk Target Anggaran
 * ✅ Method baru untuk validasi
 * 
 * @param int $tahunAnggaranId
 * @param int|null $skpdId
 * @return bool
 */
public function validateTargetHierarchi($tahunAnggaranId, $skpdId = null)
{
    if ($this->level == 6) {
        return true; // Level 6 selalu konsisten
    }

    $manualTarget = $this->getTargetAnggaranForTahun($tahunAnggaranId, $skpdId);
    
    // Hitung total dari children
    $children = $this->children()->where('is_active', true)->get();
    $childrenTotal = 0;
    foreach ($children as $child) {
        $childrenTotal += $child->getTargetAnggaranForTahun($tahunAnggaranId, $skpdId);
    }

    // Konsisten jika selisih < 1 (untuk handle floating point)
    return abs($manualTarget - $childrenTotal) < 1;
}
    
    /**
     * Update hierarki targets berdasarkan KONSOLIDASI (SUM semua SKPD)
     * ✅ UNTUK SISTEM SKPD - Level 6 per SKPD, Level 1-5 konsolidasi
     * 
     * @param int $tahunAnggaranId
     * @return bool
     */
    public static function updateHierarchiTargetsKonsolidasi($tahunAnggaranId, $tahun = null)
    {
        DB::beginTransaction();

        try {
            Log::info('Starting hierarki konsolidasi update', [
                'tahun_anggaran_id' => $tahunAnggaranId,
                'tahun' => $tahun
            ]);

            // Update dari level 5 ke atas (bottom-up)
            // Level 6 sudah diinput manual per SKPD
            for ($level = 5; $level >= 1; $level--) {
                $parentQuery = self::where('level', $level)
                    ->where('is_active', true);

                if ($tahun) {
                    $parentQuery->forTahun($tahun);
                }

                $parents = $parentQuery->get();
                
                Log::info("Processing level {$level}", [
                    'count' => $parents->count()
                ]);
                
                foreach ($parents as $parent) {
                    // Get all Level 6 descendants
                    $level6Query = self::where('kode', 'like', $parent->kode . '%')
                        ->where('level', 6)
                        ->where('is_active', true);

                    if ($tahun) {
                        $level6Query->forTahun($tahun);
                    }

                    $level6Ids = $level6Query->pluck('id')->toArray();
                    
                    if (empty($level6Ids)) {
                        continue;
                    }
                    
                    // ✅ SUM konsolidasi dari SEMUA SKPD untuk level 6 ini
                    $totalPagu = TargetAnggaran::where('tahun_anggaran_id', $tahunAnggaranId)
                        ->whereIn('kode_rekening_id', $level6Ids)
                        ->sum('jumlah'); // SUM dari semua SKPD
                    
                    // ✅ Simpan ke parent sebagai konsolidasi (skpd_id = NULL)
                    TargetAnggaran::updateOrCreate(
                        [
                            'tahun_anggaran_id' => $tahunAnggaranId,
                            'kode_rekening_id' => $parent->id,
                            'skpd_id' => null, // ✅ NULL = konsolidasi
                        ],
                        [
                            'jumlah' => $totalPagu,
                        ]
                    );
                    
                    Log::debug("Updated parent", [
                        'kode' => $parent->kode,
                        'level' => $level,
                        'total_pagu' => $totalPagu,
                        'level6_count' => count($level6Ids)
                    ]);
                }
            }
            
            DB::commit();
            
            Log::info('Hierarki konsolidasi updated successfully', [
                'tahun_anggaran_id' => $tahunAnggaranId
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update hierarki konsolidasi', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * BACKWARD COMPATIBILITY - Update hierarki (wrapper method)
     * Method lama masih bisa dipanggil, tapi akan redirect ke method baru
     */
    public static function updateHierarchiTargets($tahunAnggaranId, $tahun = null)
    {
        // Call the new konsolidasi method
        return self::updateHierarchiTargetsKonsolidasi($tahunAnggaranId, $tahun);
    }
    
    // ==========================================
    // HELPER METHODS
    // ==========================================
    
    /**
     * Helper method untuk mendapatkan semua descendant level 6
     */
    public function getAllLevel6Descendants()
    {
        if ($this->level == 6) {
            return [$this->id];
        }
        
        $descendants = [];
        $this->collectLevel6Descendants($descendants);
        
        return $descendants;
    }
    
    /**
     * Method recursive untuk mengumpulkan level 6 descendants
     */
    private function collectLevel6Descendants(&$descendants)
    {
        if ($this->level == 6) {
            $descendants[] = $this->id;
            return;
        }
        
        // Load children dengan eager loading untuk performa
        $this->load('children');
        
        foreach ($this->children as $child) {
            $child->collectLevel6Descendants($descendants);
        }
    }
    
    /**
     * Helper method untuk mendapatkan all children sampai level tertentu
     */
    public function getAllDescendants($maxLevel = null)
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            
            if (!$maxLevel || $child->level < $maxLevel) {
                $descendants = $descendants->merge($child->getAllDescendants($maxLevel));
            }
        }
        
        return $descendants;
    }
    
    /**
     * Method untuk mendapatkan path hierarki (breadcrumb)
     */
    public function getHierarchiPath()
    {
        $path = [];
        $current = $this;
        
        while ($current) {
            array_unshift($path, $current);
            $current = $current->parent;
        }
        
        return $path;
    }
    
    /**
     * Method untuk validasi konsistensi hierarki
     */
    public function validateHierarchi($tahunAnggaranId)
    {
        if ($this->level == 6) {
            return true; // Level 6 selalu valid
        }
        
        $manualTarget = TargetAnggaran::where('kode_rekening_id', $this->id)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('skpd_id', null) // konsolidasi
            ->value('jumlah') ?? 0;
            
        $calculatedTarget = $this->calculateHierarchiTarget($tahunAnggaranId, null);
        
        // Toleransi perbedaan 1 rupiah untuk floating point precision
        return abs($manualTarget - $calculatedTarget) <= 1;
    }
    
    // ==========================================
    // SCOPES - ORDERING
    // ==========================================
    
    /**
     * SCOPE ORDERING: Simple Kode ASC (PALING SEDERHANA & EFEKTIF)
     */
    public function scopeSimpleKodeOrder($query)
    {
        return $query->orderBy('kode', 'ASC');
    }
    
    /**
     * SCOPE ORDERING: Level First then Kode (UNTUK DEBUGGING)
     */
    public function scopeLevelFirstOrder($query)
    {
        return $query->orderBy('level', 'ASC')->orderBy('kode', 'ASC');
    }
    
    /**
     * SCOPE ORDERING: Custom Hierarchical yang benar-benar tepat
     */
    public function scopeCorrectHierarchicalOrder($query)
    {
        return $query->orderByRaw("
            -- Split kode dan sort secara numeric per bagian
            CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED) ASC,
            CASE 
                WHEN LOCATE('.', kode) > 0 THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1) AS UNSIGNED)
                ELSE 0 
            END ASC,
            CASE 
                WHEN CHAR_LENGTH(kode) - CHAR_LENGTH(REPLACE(kode, '.', '')) >= 2 THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 3), '.', -1) AS UNSIGNED)
                ELSE 0 
            END ASC,
            CASE 
                WHEN CHAR_LENGTH(kode) - CHAR_LENGTH(REPLACE(kode, '.', '')) >= 3 THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 4), '.', -1) AS UNSIGNED)
                ELSE 0 
            END ASC,
            CASE 
                WHEN CHAR_LENGTH(kode) - CHAR_LENGTH(REPLACE(kode, '.', '')) >= 4 THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 5), '.', -1) AS UNSIGNED)
                ELSE 0 
            END ASC,
            CASE 
                WHEN CHAR_LENGTH(kode) - CHAR_LENGTH(REPLACE(kode, '.', '')) >= 5 THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 6), '.', -1) AS UNSIGNED)
                ELSE 0 
            END ASC
        ");
    }
    
    // ==========================================
    // STATIC QUERY METHODS
    // ==========================================
    
    /**
     * Method untuk mendapatkan data hierarkis dengan urutan yang benar
     */
    public static function getHierarchicalList($visibleLevels = [1,2,3,4,5,6], $search = null, $tahun = null)
    {
        $query = self::where('is_active', true);

        if ($tahun) {
            $query->forTahun($tahun);
        }

        if (!empty($visibleLevels)) {
            $query->whereIn('level', $visibleLevels);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', '%' . $search . '%')
                  ->orWhere('nama', 'like', '%' . $search . '%');
            });
        }

        return $query->simpleKodeOrder()->get();
    }
    
    /**
     * Alternative method for pagination dengan ordering yang benar
     */
    public static function getHierarchicalQuery($visibleLevels = [1,2,3,4,5,6], $search = null, $tahun = null)
    {
        $query = self::where('is_active', true);

        if ($tahun) {
            $query->forTahun($tahun);
        }

        if (!empty($visibleLevels)) {
            $query->whereIn('level', $visibleLevels);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', '%' . $search . '%')
                  ->orWhere('nama', 'like', '%' . $search . '%');
            });
        }

        return $query->correctHierarchicalOrder();
    }
    
    /**
     * Static method untuk mendapatkan kode rekening berdasarkan pattern
     */
    public static function getByKodePattern($pattern, $tahun = null)
    {
        $query = self::where('kode', 'like', $pattern . '%');

        if ($tahun) {
            $query->forTahun($tahun);
        }

        return $query->simpleKodeOrder()->get();
    }

    /**
     * Static method untuk mendapatkan kode rekening level 6 berdasarkan parent pattern
     */
    public static function getLevel6ByParentPattern($pattern, $tahun = null)
    {
        $query = self::where('kode', 'like', $pattern . '%')
                     ->where('level', 6);

        if ($tahun) {
            $query->forTahun($tahun);
        }

        return $query->pluck('id')->toArray();
    }
    
    // ==========================================
    // SCOPES - FILTERS
    // ==========================================
    
    /**
     * Scope untuk kode rekening aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope untuk level tertentu
     */
    public function scopeLevel($query, $level)
    {
        return $query->where('level', $level);
    }
    
    /**
     * Scope untuk kode pattern
     */
    public function scopeKodePattern($query, $pattern)
    {
        return $query->where('kode', 'like', $pattern . '%');
    }

    /**
     * Scope untuk filter kode rekening berdasarkan tahun berlaku.
     * Mengambil generasi terbaru yang berlaku_mulai <= $tahun.
     * Contoh: tahun 2025 → ambil berlaku_mulai=2022, tahun 2026 → ambil berlaku_mulai=2026
     */
    public function scopeForTahun($query, $tahun)
    {
        if (!$tahun) {
            return $query;
        }

        $maxBerlaku = self::where('berlaku_mulai', '<=', $tahun)
                          ->max('berlaku_mulai');

        if ($maxBerlaku === null) {
            return $query;
        }

        return $query->where('berlaku_mulai', $maxBerlaku);
    }
    
    // ==========================================
    // ACCESSORS
    // ==========================================
    
    /**
     * Accessor untuk format kode dengan indentasi berdasarkan level
     */
    public function getFormattedKodeAttribute()
    {
        $indent = str_repeat('  ', $this->level - 1);
        return $indent . $this->kode;
    }
    
    /**
     * Accessor untuk nama dengan indentasi
     */
    public function getFormattedNamaAttribute()
    {
        $indent = str_repeat('  ', $this->level - 1);
        return $indent . $this->nama;
    }
    
    /**
     * Accessor untuk display name dengan level info
     */
    public function getDisplayNameAttribute()
    {
        return "({$this->level}) {$this->kode} - {$this->nama}";
    }
    
    // ==========================================
    // UTILITY METHODS
    // ==========================================
    
    /**
     * Method untuk mendapatkan kode parts (split by dot)
     */
    public function getKodeParts()
    {
        return explode('.', $this->kode);
    }
    
    /**
     * Method untuk validasi format kode
     */
    public function isValidKodeFormat()
    {
        $parts = $this->getKodeParts();
        $partsCount = count($parts);
        
        // Validasi jumlah parts sesuai level
        if ($partsCount !== $this->level) {
            return false;
        }
        
        // Validasi setiap part adalah numeric
        foreach ($parts as $part) {
            if (!is_numeric($part)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Method untuk auto-fix parent relationship
     */
    public function autoFixParentRelationship()
    {
        if ($this->level <= 1) {
            $this->parent_id = null;
            return;
        }
        
        $parts = $this->getKodeParts();
        array_pop($parts); // Remove last part untuk dapat parent code
        $parentKode = implode('.', $parts);
        
        $parent = self::where('kode', $parentKode)->first();
        if ($parent) {
            $this->parent_id = $parent->id;
        }
    }
    
    /**
     * Method untuk debugging hierarchy
     */
    public function debugHierarchy()
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'level' => $this->level,
            'parent_id' => $this->parent_id,
            'parent_kode' => $this->parent ? $this->parent->kode : null,
            'children_count' => $this->children->count(),
            'is_valid_format' => $this->isValidKodeFormat(),
            'parts' => $this->getKodeParts(),
            'kode_length' => strlen($this->kode),
            'dots_count' => substr_count($this->kode, '.'),
        ];
    }
}