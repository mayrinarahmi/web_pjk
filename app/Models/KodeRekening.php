<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KodeRekening extends Model
{
    use HasFactory;

    protected $table = 'kode_rekening';
    protected $fillable = ['kode', 'nama', 'level', 'parent_id'];

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

    // Mendapatkan semua kode rekening level 5 di bawah kode rekening ini
    public function getAllLevel5Descendants()
    {
        $level5Ids = [];
        
        if ($this->level == 5) {
            $level5Ids[] = $this->id;
        } elseif ($this->level < 5) {
            $children = $this->children;
            foreach ($children as $child) {
                $level5Ids = array_merge($level5Ids, $child->getAllLevel5Descendants());
            }
        }
        
        return $level5Ids;
    }

    // Mendapatkan semua kode rekening level 6 di bawah kode rekening ini
    public function getAllLevel6Descendants()
    {
        $level6Ids = [];
        
        if ($this->level == 6) {
            $level6Ids[] = $this->id;
        } elseif ($this->level < 6) {
            $children = $this->children;
            foreach ($children as $child) {
                $level6Ids = array_merge($level6Ids, $child->getAllLevel6Descendants());
            }
        }
        
        return $level6Ids;
    }

    // Mendapatkan total target anggaran untuk kode rekening ini
    public static function getTargetAnggaran($kodeRekeningId, $tahunAnggaranId)
    {
        $kodeRekening = self::find($kodeRekeningId);
        if (!$kodeRekening) {
            return 0;
        }
        
        // Jika level 5, ambil langsung target anggarannya
        if ($kodeRekening->level == 5) {
            $target = TargetAnggaran::where('kode_rekening_id', $kodeRekeningId)
                ->where('tahun_anggaran_id', $tahunAnggaranId)
                ->first();
            return $target ? $target->jumlah : 0;
        }
        
        // Jika level 6 atau di atas 5, kembalikan 0 (karena target hanya di level 5)
        if ($kodeRekening->level > 5) {
            return 0;
        }
        
        // Jika level di bawah 5, jumlahkan semua target anggaran level 5 di bawahnya
        $level5Ids = $kodeRekening->getAllLevel5Descendants();
        return TargetAnggaran::whereIn('kode_rekening_id', $level5Ids)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->sum('jumlah');
    }
}
