<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TahunAnggaran;

class TahunAnggaranSeeder extends Seeder
{
    public function run()
    {
        TahunAnggaran::firstOrCreate(
            [
                'tahun' => 2025,
                // tambahkan field unik lain kalau ada, misal:
                // 'jenis_anggaran' => 'murni',
            ],
            [
                'is_active' => true,
            ]
        );
    }
}
