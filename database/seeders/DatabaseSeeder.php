<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
           // 1. TAHUN ANGGARAN - Harus pertama (diperlukan oleh data lain)
            TahunAnggaranSeeder::class,
            
            // 2. SKPD - Sebelum user (karena user bisa punya skpd_id)
            SkpdSeeder::class,
            
            // 3. ROLES & PERMISSIONS - Sebelum user (karena user butuh role)
            RolePermissionSeeder::class,
            
            // 4. USERS - Terakhir (depends on roles & skpd)
            UserSeeder::class,
        ]);
    }
}
