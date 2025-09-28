<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Skpd;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Get SKPD yang dibutuhkan
        $bpkpad = Skpd::where('kode_opd', '5.02.0.00.0.00.05.0000')->first();
        $dinasKesehatan = Skpd::where('kode_opd', '1.02.0.00.0.00.01.0000')->first();
        $dinasPU = Skpd::where('kode_opd', '1.03.0.00.0.00.01.0000')->first();

        // Super Admin
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'nip' => '198001011990031001',
                'password' => Hash::make('password'),
                'role_id' => 1,
                'skpd_id' => null,
            ]
        );

        // Kepala Badan BPKPAD
        User::updateOrCreate(
            ['email' => 'kepala.bpkpad@example.com'],
            [
                'name' => 'Kepala BPKPAD',
                'nip' => '196901121993031004',
                'password' => Hash::make('password'),
                'role_id' => 1,
                'skpd_id' => $bpkpad ? $bpkpad->id : null,
            ]
        );

        // Operator Dinas Kesehatan
        User::updateOrCreate(
            ['email' => 'operator.dinkes@example.com'],
            [
                'name' => 'Operator Dinas Kesehatan',
                'nip' => '199001011990031001',
                'password' => Hash::make('password'),
                'role_id' => 2,
                'skpd_id' => $dinasKesehatan ? $dinasKesehatan->id : null,
            ]
        );

        // Operator Dinas PU
        User::updateOrCreate(
            ['email' => 'operator.pu@example.com'],
            [
                'name' => 'Operator Dinas PU',
                'nip' => '199101011991031001',
                'password' => Hash::make('password'),
                'role_id' => 2,
                'skpd_id' => $dinasPU ? $dinasPU->id : null,
            ]
        );

        // Viewer
        User::updateOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Viewer',
                'nip' => '200001012000031001',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'skpd_id' => null,
            ]
        );
    }
}
