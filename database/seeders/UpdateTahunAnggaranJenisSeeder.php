<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TahunAnggaran;

class UpdateTahunAnggaranJenisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update semua tahun anggaran existing menjadi 'murni'
        // karena kolom jenis_anggaran sudah default 'murni', tidak perlu update manual
        
        // Tapi jika perlu set tanggal penetapan untuk data existing:
        TahunAnggaran::whereNull('tanggal_penetapan')->update([
            'tanggal_penetapan' => now(),
            'keterangan' => 'Data awal sebelum implementasi APBD Murni & Perubahan'
        ]);
        
        $this->command->info('Tahun anggaran existing telah diupdate menjadi APBD Murni.');
    }
}