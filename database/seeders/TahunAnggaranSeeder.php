<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TahunAnggaran;

class TahunAnggaranSeeder extends Seeder
{
    public function run()
    {
        TahunAnggaran::create([
            'tahun' => 2025,
            'is_active' => true,
        ]);
    }
}
