<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name' => 'Administrator',
                'description' => 'Akses penuh ke semua fitur',
            ],
            [
                'name' => 'Operator',
                'description' => 'Akses ke fitur operasional',
            ],
            [
                'name' => 'Viewer',
                'description' => 'Hanya dapat melihat data',
            ],
        ];
        
        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}

