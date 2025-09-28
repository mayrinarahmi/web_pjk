<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Skpd;

class UpdateUserSkpdSeeder extends Seeder
{
    public function run()
    {
        // Get SKPD
        $bpkpad = Skpd::where('kode_opd', '5.02.0.00.0.00.05.0000')->first();
        $dinkes = Skpd::where('kode_opd', '1.02.0.00.0.00.01.0000')->first();
        $pu = Skpd::where('kode_opd', '1.03.0.00.0.00.01.0000')->first();
        
        if (!$bpkpad || !$dinkes || !$pu) {
            $this->command->error('SKPD tidak ditemukan! Pastikan SkpdSeeder sudah dijalankan.');
            return;
        }
        
        // Update Kepala BPKPAD
        $updated = User::where('nip', '196901121993031004')->update([
            'skpd_id' => $bpkpad->id
        ]);
        if ($updated) {
            $this->command->info('Updated Kepala BPKPAD dengan SKPD: ' . $bpkpad->nama_opd);
        }
        
        // Update Operator Dinas Kesehatan
        $updated = User::where('nip', '199001011990031001')->update([
            'skpd_id' => $dinkes->id
        ]);
        if ($updated) {
            $this->command->info('Updated Operator Dinkes dengan SKPD: ' . $dinkes->nama_opd);
        }
        
        // Update Operator Dinas PU
        $updated = User::where('nip', '199101011991031001')->update([
            'skpd_id' => $pu->id
        ]);
        if ($updated) {
            $this->command->info('Updated Operator Dinas PU dengan SKPD: ' . $pu->nama_opd);
        }
        
        // Super Admin tetap NULL (tidak punya SKPD)
        User::where('nip', '198001011990031001')->update([
            'skpd_id' => null
        ]);
        $this->command->info('Super Admin skpd_id tetap NULL (bisa akses semua)');
        
        // Viewer juga NULL
        User::where('nip', '200001012000031001')->update([
            'skpd_id' => null
        ]);
        
        $this->command->info('✅ Update SKPD ID selesai!');
    }
}