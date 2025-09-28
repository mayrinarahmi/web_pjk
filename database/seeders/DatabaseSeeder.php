<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UserSeeder::class,
            TahunAnggaranSeeder::class,
             RolePermissionSeeder::class,
              SkpdSeeder::class,  
        ]);
    }
}
