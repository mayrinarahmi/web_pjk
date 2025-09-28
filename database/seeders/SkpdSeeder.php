<?php
// ===============================================
// 1. Seeder untuk SKPD
// File: database/seeders/SkpdSeeder.php
// ===============================================

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Skpd;

class SkpdSeeder extends Seeder
{
    public function run()
    {
        $skpdData = [
            [
                'kode_opd' => '1.02.0.00.0.00.01.0000',
                'nama_opd' => 'DINAS KESEHATAN',
                'status' => 'aktif'
            ],
            [
                'kode_opd' => '1.03.0.00.0.00.01.0000',
                'nama_opd' => 'DINAS PEKERJAAN UMUM DAN PENATAAN RUANG',
                'status' => 'aktif'
            ],
            [
                'kode_opd' => '1.04.2.10.0.00.30.0000',
                'nama_opd' => 'DINAS PERUMAHAN RAKYAT DAN KAWASAN PERMUKIMAN',
                'status' => 'aktif'
            ],
            [
                'kode_opd' => '2.09.3.27.3.25.03.0000',
                'nama_opd' => 'DINAS KETAHANAN PANGAN PERTANIAN DAN PERIKANAN',
                'status' => 'aktif'
            ],
            [
                'kode_opd' => '2.11.0.00.0.00.01.0000',
                'nama_opd' => 'DINAS LINGKUNGAN HIDUP',
                'status' => 'aktif'
            ],
            [
                'kode_opd' => '2.15.0.00.0.00.01.0000',
                'nama_opd' => 'DINAS PERHUBUNGAN',
                'status' => 'aktif'
            ],
            [
                'kode_opd' => '2.17.2.07.0.00.17.0000',
                'nama_opd' => 'DINAS KOPERASI USAHA MIKRO DAN TENAGA KERJA',
                'status' => 'aktif'
            ],
            [
                'kode_opd' => '2.22.3.26.2.19.03.0000',
                'nama_opd' => 'DINAS KEBUDAYAAN, KEPEMUDAAN, OLAHRAGA DAN PARIWISATA',
                'status' => 'aktif'
            ],
            [
                'kode_opd' => '3.30.3.31.0.00.08.0000',
                'nama_opd' => 'DINAS PERDAGANGAN DAN PERINDUSTRIAN',
                'status' => 'aktif'
            ],
            [
                'kode_opd' => '4.01.0.00.0.00.01.0000',
                'nama_opd' => 'SEKRETARIAT DAERAH',
                'status' => 'aktif'
            ],
            [
                'kode_opd' => '5.02.0.00.0.00.05.0000',
                'nama_opd' => 'BADAN PENGELOLAAN KEUANGAN, PENDAPATAN DAN ASET DAERAH',
                'status' => 'aktif'
            ],
        ];

        foreach ($skpdData as $data) {
            Skpd::create($data);
        }
    }
}
