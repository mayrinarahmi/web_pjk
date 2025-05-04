<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TahunAnggaran extends Model
{
    protected $table = 'tahun_anggaran';
    
    protected $fillable = [
        'tahun',
        'is_active',
    ];
    
    public function targetBulan()
    {
        return $this->hasMany(TargetBulan::class);
    }
    
    public function targetAnggaran()
    {
        return $this->hasMany(TargetAnggaran::class);
    }
    
    public function penerimaan()
    {
        return $this->hasMany(Penerimaan::class);
    }
}

