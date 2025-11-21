<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TargetAnggaranPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('');
        $this->command->info('=================================');
        $this->command->info('🚀 Setting up Target Anggaran Permissions...');
        $this->command->info('=================================');
        $this->command->info('');

        // ==========================================
        // CREATE PERMISSIONS (jika belum ada)
        // ==========================================
        $permissions = [
            'view-target' => 'Melihat target anggaran',
            'create-target' => 'Membuat target anggaran',
            'edit-target' => 'Mengedit target anggaran',
            'delete-target' => 'Menghapus target anggaran',
        ];

        $this->command->info('📝 Creating/Checking Permissions...');
        foreach ($permissions as $name => $description) {
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
            
            if ($permission->wasRecentlyCreated) {
                $this->command->info("   ✓ Created: {$name}");
            } else {
                $this->command->info("   - Exists: {$name}");
            }
        }

        $this->command->info('');
        $this->command->info('👥 Assigning Permissions to Roles...');

        // ==========================================
        // ASSIGN PERMISSIONS TO ROLES
        // ==========================================

        // Super Admin - SEMUA PERMISSION
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions([
                'view-target',
                'create-target',
                'edit-target',
                'delete-target'
            ]);
            $this->command->info('   ✓ Super Admin: All permissions (view, create, edit, delete)');
        } else {
            $this->command->warn('   ⚠ Super Admin role not found');
        }

        // Administrator - SEMUA PERMISSION
        $admin = Role::where('name', 'Administrator')->first();
        if ($admin) {
            $admin->syncPermissions([
                'view-target',
                'create-target',
                'edit-target',
                'delete-target'
            ]);
            $this->command->info('   ✓ Administrator: All permissions (view, create, edit, delete)');
        } else {
            $this->command->warn('   ⚠ Administrator role not found');
        }

        // Kepala Badan - VIEW ONLY
        $kepalaBadan = Role::where('name', 'Kepala Badan')->first();
        if ($kepalaBadan) {
            $currentPermissions = $kepalaBadan->permissions->pluck('name')->toArray();
            $kepalaBadan->givePermissionTo('view-target');
            $this->command->info('   ✓ Kepala Badan: View only');
        } else {
            $this->command->warn('   ⚠ Kepala Badan role not found');
        }

        // Operator - VIEW, CREATE, EDIT
        $operator = Role::where('name', 'Operator')->first();
        if ($operator) {
            $operator->givePermissionTo([
                'view-target',
                'create-target',
                'edit-target'
            ]);
            $this->command->info('   ✓ Operator: View, Create, Edit');
        } else {
            $this->command->warn('   ⚠ Operator role not found');
        }

        // Operator SKPD - VIEW, CREATE, EDIT (PENTING!)
        $operatorSkpd = Role::where('name', 'Operator SKPD')->first();
        if ($operatorSkpd) {
            $operatorSkpd->givePermissionTo([
                'view-target',
                'create-target',
                'edit-target'
            ]);
            $this->command->info('   ✓ Operator SKPD: View, Create, Edit (untuk SKPD sendiri)');
        } else {
            $this->command->warn('   ⚠ Operator SKPD role not found');
        }

        // Viewer - VIEW ONLY
        $viewer = Role::where('name', 'Viewer')->first();
        if ($viewer) {
            $viewer->givePermissionTo('view-target');
            $this->command->info('   ✓ Viewer: View only');
        } else {
            $this->command->warn('   ⚠ Viewer role not found');
        }

        $this->command->info('');
        $this->command->info('=================================');
        $this->command->info('✅ Target Anggaran Permissions Setup Complete!');
        $this->command->info('=================================');
        $this->command->info('');
        
        // Show summary
        $this->command->info('📊 Summary:');
        $this->command->info('   - Permissions: 4 (view, create, edit, delete)');
        $this->command->info('   - Roles configured: ' . Role::count());
        $this->command->info('');
        $this->command->info('🔐 Next steps:');
        $this->command->info('   1. Run: php artisan permission:cache-reset');
        $this->command->info('   2. Logout and login again');
        $this->command->info('   3. Test access to /target-anggaran');
        $this->command->info('');
    }
}