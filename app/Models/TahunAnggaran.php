<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TahunAnggaran extends Model
{
    protected $table = 'tahun_anggaran';
   
    protected $fillable = [
        'tahun',
        'jenis_anggaran',
        'parent_tahun_anggaran_id',
        'tanggal_penetapan',
        'keterangan',
        'is_active',
    ];
    
    protected $casts = [
        'tanggal_penetapan' => 'date',
        'is_active' => 'boolean',
    ];
   
    // Relationship ke parent (APBD Murni)
    public function parent()
    {
        return $this->belongsTo(TahunAnggaran::class, 'parent_tahun_anggaran_id');
    }
    
    // Relationship ke children (APBD Perubahan)
    public function perubahan()
    {
        return $this->hasMany(TahunAnggaran::class, 'parent_tahun_anggaran_id');
    }
   
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
    
    // Helper method untuk mendapatkan tahun anggaran aktif
    public static function getActive()
    {
        return self::where('is_active', true)->first();
    }
    
    // Helper method untuk copy target anggaran dari murni ke perubahan
    public function copyTargetAnggaranFrom($sourceTahunAnggaranId)
    {
        $sourceTargets = TargetAnggaran::where('tahun_anggaran_id', $sourceTahunAnggaranId)->get();
        
        foreach ($sourceTargets as $sourceTarget) {
            TargetAnggaran::updateOrCreate(
                [
                    'tahun_anggaran_id' => $this->id,
                    'kode_rekening_id' => $sourceTarget->kode_rekening_id,
                ],
                [
                    'jumlah' => $sourceTarget->jumlah,
                ]
            );
        }
    }
    
    // Helper method untuk mendapatkan display name
    public function getDisplayNameAttribute()
    {
        $jenisLabel = $this->jenis_anggaran == 'murni' ? 'Murni' : 'Perubahan';
        return $this->tahun . ' (' . $jenisLabel . ')';
    }
}