<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KodeRekening extends Model
{
    protected $table = 'kode_rekening';
    
    protected $fillable = [
        'kode',
        'nama',
        'parent_id',
        'level',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    // Relationships
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
    
    // Helper method untuk mendapatkan target anggaran dengan hierarki calculation
    public static function getTargetAnggaran($kodeRekeningId, $tahunAnggaranId)
    {
        $kodeRekening = self::find($kodeRekeningId);
        if (!$kodeRekening) return 0;
        
        // Jika level 6, ambil langsung dari database
        if ($kodeRekening->level == 6) {
            $target = TargetAnggaran::where('kode_rekening_id', $kodeRekeningId)
                ->where('tahun_anggaran_id', $tahunAnggaranId)
                ->first();
            return $target ? $target->jumlah : 0;
        }
        
        // Jika level 1-5, hitung dari SUM children
        return $kodeRekening->calculateHierarchiTarget($tahunAnggaranId);
    }
    
    // Method untuk menghitung target hierarki
    public function calculateHierarchiTarget($tahunAnggaranId)
    {
        // Jika level 6, ambil dari database
        if ($this->level == 6) {
            $target = TargetAnggaran::where('kode_rekening_id', $this->id)
                ->where('tahun_anggaran_id', $tahunAnggaranId)
                ->first();
            return $target ? $target->jumlah : 0;
        }
        
        // Jika level 1-5, sum dari children
        $total = 0;
        foreach ($this->children as $child) {
            $total += $child->calculateHierarchiTarget($tahunAnggaranId);
        }
        
        return $total;
    }
    
    // Helper method untuk mendapatkan semua descendant level 6
    public function getAllLevel6Descendants()
    {
        if ($this->level == 6) {
            return [$this->id];
        }
        
        $descendants = [];
        $this->collectLevel6Descendants($descendants);
        
        return $descendants;
    }
    
    // Method recursive untuk mengumpulkan level 6 descendants
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
    
    // SCOPE ORDERING: Perfect Hierarchical (FIXED)
    public function scopePerfectHierarchicalOrder($query)
    {
        return $query->orderByRaw("
            -- Convert kode parts to proper numeric values for hierarchical sorting
            CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED),
            CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1), SUBSTRING_INDEX(kode, '.', 1)), '0') AS UNSIGNED),
            CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 3), '.', -1), SUBSTRING_INDEX(kode, '.', 2)), '0') AS UNSIGNED),
            CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 4), '.', -1), SUBSTRING_INDEX(kode, '.', 3)), '0') AS UNSIGNED),
            CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 5), '.', -1), SUBSTRING_INDEX(kode, '.', 4)), '0') AS UNSIGNED),
            CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 6), '.', -1), SUBSTRING_INDEX(kode, '.', 5)), '0') AS UNSIGNED)
        ");
    }
    
    // SCOPE ORDERING: Natural String Order (MOST RELIABLE)
    public function scopeNaturalHierarchicalOrder($query)
    {
        return $query->orderByRaw("
            -- Pad each part with zeros for proper string comparison
            CONCAT(
                LPAD(SUBSTRING_INDEX(kode, '.', 1), 3, '0'),
                '.',
                LPAD(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1), SUBSTRING_INDEX(kode, '.', 1)), '0'), 3, '0'),
                '.',
                LPAD(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 3), '.', -1), SUBSTRING_INDEX(kode, '.', 2)), '0'), 3, '0'),
                '.',
                LPAD(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 4), '.', -1), SUBSTRING_INDEX(kode, '.', 3)), '0'), 3, '0'),
                '.',
                LPAD(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 5), '.', -1), SUBSTRING_INDEX(kode, '.', 4)), '0'), 3, '0'),
                '.',
                LPAD(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 6), '.', -1), SUBSTRING_INDEX(kode, '.', 5)), '0'), 5, '0')
            )
        ");
    }
    
    // SCOPE ORDERING: Simple Kode ASC (PALING SEDERHANA & EFEKTIF)
    public function scopeSimpleKodeOrder($query)
    {
        return $query->orderBy('kode', 'ASC');
    }
    
    // SCOPE ORDERING: Level First then Kode (UNTUK DEBUGGING)
    public function scopeLevelFirstOrder($query)
    {
        return $query->orderBy('level', 'ASC')->orderBy('kode', 'ASC');
    }
    
    // SCOPE ORDERING: Custom Hierarchical yang benar-benar tepat
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
    
    // Method untuk mendapatkan data hierarkis dengan urutan yang benar
    public static function getHierarchicalList($visibleLevels = [1,2,3,4,5,6], $search = null)
    {
        $query = self::where('is_active', true);
        
        if (!empty($visibleLevels)) {
            $query->whereIn('level', $visibleLevels);
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', '%' . $search . '%')
                  ->orWhere('nama', 'like', '%' . $search . '%');
            });
        }
        
        // Gunakan simple kode order - paling reliable dan cepat
        return $query->simpleKodeOrder()->get();
    }
    
    // Alternative method for pagination dengan ordering yang benar
    public static function getHierarchicalQuery($visibleLevels = [1,2,3,4,5,6], $search = null)
    {
        $query = self::where('is_active', true);
        
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
    
    // Helper method untuk mendapatkan all children sampai level tertentu
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
    
    // Static method untuk mendapatkan kode rekening berdasarkan pattern
    public static function getByKodePattern($pattern)
    {
        return self::where('kode', 'like', $pattern . '%')
                  ->simpleKodeOrder()
                  ->get();
    }
    
    // Static method untuk mendapatkan kode rekening level 6 berdasarkan parent pattern
    public static function getLevel6ByParentPattern($pattern)
    {
        return self::where('kode', 'like', $pattern . '%')
                  ->where('level', 6)
                  ->pluck('id')
                  ->toArray();
    }
    
    // Method untuk update hierarki target anggaran (updated untuk level 6)
    public static function updateHierarchiTargets($tahunAnggaranId)
    {
        // Update dari level 5 ke atas (bottom-up calculation)
        for ($level = 5; $level >= 1; $level--) {
            $kodeRekeningList = self::where('level', $level)
                ->where('is_active', true)
                ->get();
            
            foreach ($kodeRekeningList as $kode) {
                $calculatedTarget = $kode->calculateHierarchiTarget($tahunAnggaranId);
                
                // Update atau create target anggaran untuk parent
                TargetAnggaran::updateOrCreate(
                    [
                        'kode_rekening_id' => $kode->id,
                        'tahun_anggaran_id' => $tahunAnggaranId
                    ],
                    [
                        'jumlah' => $calculatedTarget
                    ]
                );
            }
        }
    }
    
    // Method untuk mendapatkan path hierarki (breadcrumb)
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
    
    // Method untuk validasi konsistensi hierarki (updated untuk level 6)
    public function validateHierarchi($tahunAnggaranId)
    {
        if ($this->level == 6) {
            return true; // Level 6 selalu valid
        }
        
        $manualTarget = TargetAnggaran::where('kode_rekening_id', $this->id)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->value('jumlah') ?? 0;
            
        $calculatedTarget = $this->calculateHierarchiTarget($tahunAnggaranId);
        
        // Toleransi perbedaan 1 rupiah untuk floating point precision
        return abs($manualTarget - $calculatedTarget) <= 1;
    }
    
    // Method untuk mendapatkan kode parts (split by dot)
    public function getKodeParts()
    {
        return explode('.', $this->kode);
    }
    
    // Method untuk validasi format kode
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
    
    // Method untuk auto-fix parent relationship
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
    
    // Scope untuk kode rekening aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    // Scope untuk level tertentu
    public function scopeLevel($query, $level)
    {
        return $query->where('level', $level);
    }
    
    // Scope untuk kode pattern
    public function scopeKodePattern($query, $pattern)
    {
        return $query->where('kode', 'like', $pattern . '%');
    }
    
    // Accessor untuk format kode dengan indentasi berdasarkan level
    public function getFormattedKodeAttribute()
    {
        $indent = str_repeat('  ', $this->level - 1);
        return $indent . $this->kode;
    }
    
    // Accessor untuk nama dengan indentasi
    public function getFormattedNamaAttribute()
    {
        $indent = str_repeat('  ', $this->level - 1);
        return $indent . $this->nama;
    }
    
    // Accessor untuk display name dengan level info
    public function getDisplayNameAttribute()
    {
        return "({$this->level}) {$this->kode} - {$this->nama}";
    }
    
    // Method untuk debugging hierarchy
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
    
    // Method untuk testing berbagai ordering
    public static function testAllOrderings($limit = 10)
    {
        $results = [];
        
        $results['simple_kode'] = self::active()->simpleKodeOrder()->limit($limit)->pluck('kode')->toArray();
        $results['level_first'] = self::active()->levelFirstOrder()->limit($limit)->pluck('kode')->toArray();
        $results['correct_hierarchical'] = self::active()->correctHierarchicalOrder()->limit($limit)->pluck('kode')->toArray();
        $results['perfect_hierarchical'] = self::active()->perfectHierarchicalOrder()->limit($limit)->pluck('kode')->toArray();
        
        return $results;
    }
}