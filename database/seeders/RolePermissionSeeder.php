<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // STEP 1: Buat Permissions (skip jika sudah ada)
        $permissions = [
            // Dashboard
            'view-dashboard',
            
            // Trend Analysis
            'view-trend-analysis',
            
            // Master Data - Tahun Anggaran
            'view-tahun-anggaran',
            'create-tahun-anggaran',
            'edit-tahun-anggaran',
            'delete-tahun-anggaran',
            
            // Master Data - Kode Rekening
            'view-kode-rekening',
            'create-kode-rekening',
            'edit-kode-rekening',
            'delete-kode-rekening',
            
            // Master Data - Target
            'view-target',
            'create-target',
            'edit-target',
            'delete-target',
            
            // Transaksi - Penerimaan
            'view-penerimaan',
            'create-penerimaan',
            'edit-penerimaan',
            'delete-penerimaan',
            
            // Laporan
            'view-laporan',
            'export-laporan',
            
            // User Management
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            
            // Backup
            'manage-backup',
        ];

        foreach ($permissions as $permission) {
            // Gunakan firstOrCreate untuk avoid duplicate
            Permission::firstOrCreate(['name' => $permission]);
        }

        // STEP 2: Buat Roles (skip jika sudah ada)
        
        // Administrator - Full Access
        $adminRole = Role::firstOrCreate(['name' => 'Administrator']);
        $adminRole->syncPermissions(Permission::all());
        
        // Operator - Limited Access
        $operatorRole = Role::firstOrCreate(['name' => 'Operator']);
        $operatorRole->syncPermissions([
            'view-dashboard',
            'view-trend-analysis',
            'view-tahun-anggaran',
            'create-tahun-anggaran',
            'edit-tahun-anggaran',
            'view-kode-rekening',
            'create-kode-rekening',
            'edit-kode-rekening',
            'view-target',
            'create-target',
            'edit-target',
            'view-penerimaan',
            'create-penerimaan',
            'edit-penerimaan',
            'view-laporan',
            'export-laporan',
        ]);
        
        // Viewer - Read Only
        $viewerRole = Role::firstOrCreate(['name' => 'Viewer']);
        $viewerRole->syncPermissions([
            'view-dashboard',
            'view-trend-analysis',
            'view-laporan',
        ]);

        // STEP 3: Migrate existing users ke Spatie roles
        $this->migrateExistingUsers();
        
        echo "Seeding completed successfully!\n";
    }

    private function migrateExistingUsers()
    {
        echo "Migrasi user existing ke Spatie Roles...\n";
        
        $users = User::with('role')->get();
        
        foreach ($users as $user) {
            if ($user->role) {
                // Assign Spatie role berdasarkan role lama
                switch ($user->role->name) {
                    case 'Administrator':
                        $user->syncRoles(['Administrator']);
                        echo "User {$user->name} => Administrator\n";
                        break;
                    case 'Operator':
                        $user->syncRoles(['Operator']);
                        echo "User {$user->name} => Operator\n";
                        break;
                    case 'Viewer':
                        $user->syncRoles(['Viewer']);
                        echo "User {$user->name} => Viewer\n";
                        break;
                }
            }
        }
        
        echo "Migrasi selesai!\n";
    }
}