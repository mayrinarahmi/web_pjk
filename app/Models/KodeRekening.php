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
    
    // Fungsi untuk mendapatkan semua children rekursif
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }
    
    // Fungsi untuk mendapatkan semua kode rekening level 4 di bawah kode ini
    public function getAllLevel4Descendants()
    {
        if ($this->level == 4) {
            return collect([$this->id]);
        }
        
        $ids = collect();
        
        if ($this->level == 3) {
            $ids = $this->children()->where('level', 4)->pluck('id');
        } elseif ($this->level == 2) {
            $level3Ids = $this->children()->where('level', 3)->pluck('id');
            $ids = KodeRekening::whereIn('parent_id', $level3Ids)
                ->where('level', 4)
                ->pluck('id');
        } elseif ($this->level == 1) {
            $level2Ids = $this->children()->where('level', 2)->pluck('id');
            $level3Ids = KodeRekening::whereIn('parent_id', $level2Ids)
                ->where('level', 3)
                ->pluck('id');
            $ids = KodeRekening::whereIn('parent_id', $level3Ids)
                ->where('level', 4)
                ->pluck('id');
        }
        
        return $ids;
    }
}

