<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('');
        $this->command->info('=================================');
        $this->command->info('🚀 Setting up Roles & Permissions');
        $this->command->info('=================================');
        $this->command->info('');

        // STEP 1: Buat Permissions
        $this->command->info('📝 Creating Permissions...');
        
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
            'import-kode-rekening',
            
            // Master Data - Target Periode
            'view-target-periode',
            'create-target-periode',
            'edit-target-periode',
            'delete-target-periode',
            
            // Master Data - Target Anggaran / Pagu Anggaran (✅ TAMBAHAN BARU!)
            'view-target',
            'create-target',
            'edit-target',
            'delete-target',
            'import-target',
            
            // Transaksi - Penerimaan
            'view-penerimaan',
            'create-penerimaan',
            'edit-penerimaan',
            'delete-penerimaan',
            'import-penerimaan',
            
            // Laporan
            'view-laporan',
            'export-laporan',
            
            // User Management
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            
            // SKPD Management
            'manage-skpd',
            'assign-kode-rekening',
            
            // Backup
            'manage-backup',
        ];

        foreach ($permissions as $permission) {
            $perm = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            if ($perm->wasRecentlyCreated) {
                $this->command->info("   ✓ Created: {$permission}");
            } else {
                $this->command->info("   - Exists: {$permission}");
            }
        }

        $this->command->info('');
        $this->command->info('👥 Assigning Permissions to Roles...');

        // STEP 2: Buat/Update Roles dengan permissions yang sesuai
        
        // ==========================================
        // 1. SUPER ADMIN - Full Access
        // ==========================================
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminRole->syncPermissions(Permission::all());
        $this->command->info('   ✓ Super Admin: ALL permissions (' . Permission::count() . ' permissions)');
        
        // ==========================================
        // 2. ADMINISTRATOR - Full Access (backward compatibility)
        // ==========================================
        $adminRole = Role::firstOrCreate(['name' => 'Administrator']);
        $adminRole->syncPermissions(Permission::all());
        $this->command->info('   ✓ Administrator: ALL permissions (' . Permission::count() . ' permissions)');
        
        // ==========================================
        // 3. KEPALA BADAN - View Only untuk semua
        // ==========================================
        $kepalaBadanRole = Role::firstOrCreate(['name' => 'Kepala Badan']);
        $kepalaBadanRole->syncPermissions([
            'view-dashboard',
            'view-trend-analysis',
            'view-tahun-anggaran',
            'view-kode-rekening',
            'view-target-periode',
            'view-target',          // ✅ Target Anggaran - VIEW ONLY
            'view-penerimaan',
            'view-laporan',
            'export-laporan',
        ]);
        $this->command->info('   ✓ Kepala Badan: VIEW permissions only (9 permissions)');
        
        // ==========================================
        // 4. OPERATOR SKPD - Pagu Anggaran + Penerimaan ONLY (✅ PENTING!)
        // ==========================================
        $operatorSkpdRole = Role::firstOrCreate(['name' => 'Operator SKPD']);
        $operatorSkpdRole->syncPermissions([
            'view-dashboard',
            // TIDAK ADA permission untuk Master Data lain
            // TIDAK ADA view-trend-analysis
            // ✅ Target Anggaran / Pagu Anggaran - FULL CRUD
            'view-target',
            'create-target',
            'edit-target',
            'import-target',
            // Penerimaan - FULL CRUD untuk SKPD sendiri
            'view-penerimaan',
            'create-penerimaan',
            'edit-penerimaan',
            'delete-penerimaan',
            'import-penerimaan',
            // Laporan
            'view-laporan',
            'export-laporan',
        ]);
        $this->command->info('   ✓ Operator SKPD: Pagu Anggaran + Penerimaan (12 permissions)');
        
        // ==========================================
        // 5. OPERATOR - Limited Access (backward compatibility)
        // ==========================================
        $operatorRole = Role::firstOrCreate(['name' => 'Operator']);
        $operatorRole->syncPermissions([
            'view-dashboard',
            'view-trend-analysis',
            // Master Data - VIEW + CREATE + EDIT (tanpa DELETE)
            'view-tahun-anggaran',
            'create-tahun-anggaran',
            'edit-tahun-anggaran',
            'view-kode-rekening',
            'create-kode-rekening',
            'edit-kode-rekening',
            'import-kode-rekening',
            'view-target-periode',
            'create-target-periode',
            'edit-target-periode',
            // ✅ Target Anggaran - CREATE + EDIT (tanpa DELETE)
            'view-target',
            'create-target',
            'edit-target',
            'import-target',
            // Penerimaan - FULL CRUD
            'view-penerimaan',
            'create-penerimaan',
            'edit-penerimaan',
            'import-penerimaan',
            // Laporan
            'view-laporan',
            'export-laporan',
            // Settings
            'manage-backup',
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
        ]);
        $this->command->info('   ✓ Operator: View Master + CRUD Transaksi (26 permissions)');
        
        // ==========================================
        // 6. VIEWER - Read Only (backward compatibility)
        // ==========================================
        $viewerRole = Role::firstOrCreate(['name' => 'Viewer']);
        $viewerRole->syncPermissions([
            'view-dashboard',
            'view-trend-analysis',
            'view-tahun-anggaran',
            'view-kode-rekening',
            'view-target-periode',
            'view-target',          // ✅ Target Anggaran - VIEW ONLY
            'view-penerimaan',
            'view-laporan',
            'export-laporan',
        ]);
        $this->command->info('   ✓ Viewer: VIEW permissions only (9 permissions)');

        $this->command->info('');
        $this->command->info('=================================');
        $this->command->info('✅ Roles & Permissions Setup Complete!');
        $this->command->info('=================================');
        $this->command->info('');

        // STEP 3: Clear existing roles and reassign
        $this->reassignRoles();
        
        // Summary
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('   - Total Permissions: ' . Permission::count());
        $this->command->info('   - Total Roles: ' . Role::count());
        $this->command->info('');
        
        // Detailed role permissions count
        $roles = Role::with('permissions')->get();
        $this->command->info('📋 Permissions per Role:');
        foreach ($roles as $role) {
            $this->command->info('   - ' . $role->name . ': ' . $role->permissions->count() . ' permissions');
        }
        
        $this->command->info('');
        $this->command->info('🔐 Next steps:');
        $this->command->info('   1. Run: php artisan permission:cache-reset');
        $this->command->info('   2. Run: php artisan cache:clear');
        $this->command->info('   3. Logout and login again for all users');
        $this->command->info('   4. Test menu access for each role');
        $this->command->info('');

        Log::info('RolePermissionSeeder completed successfully', [
            'total_permissions' => Permission::count(),
            'total_roles' => Role::count(),
        ]);
        
        echo "Role Permission Seeder completed successfully!\n";
    }

    private function reassignRoles()
    {
        $this->command->info('👤 Reassigning roles to users...');
        
        // Get specific users by NIP
        
        // 1. Super Admin
        $superAdmin = User::where('nip', '198001011990031001')->first();
        if ($superAdmin) {
            $superAdmin->syncRoles(['Super Admin']);
            $this->command->info("   ✓ {$superAdmin->name} => Super Admin");
        }
        
        // 2. Kepala BPKPAD
        $kepalaBadan = User::where('nip', '196901121993031004')->first();
        if ($kepalaBadan) {
            $kepalaBadan->syncRoles(['Kepala Badan']);
            $this->command->info("   ✓ {$kepalaBadan->name} => Kepala Badan");
        }
        
        // 3. Operator Dinas Kesehatan
        $operatorDinkes = User::where('nip', '199001011990031001')->first();
        if ($operatorDinkes) {
            $operatorDinkes->syncRoles(['Operator SKPD']);
            $this->command->info("   ✓ {$operatorDinkes->name} => Operator SKPD (Dinkes)");
        }
        
        // 4. Operator Dinas PU
        $operatorPU = User::where('nip', '199101011991031001')->first();
        if ($operatorPU) {
            $operatorPU->syncRoles(['Operator SKPD']);
            $this->command->info("   ✓ {$operatorPU->name} => Operator SKPD (PU)");
        }
        
        // 5. Viewer
        $viewer = User::where('nip', '200001012000031001')->first();
        if ($viewer) {
            $viewer->syncRoles(['Viewer']);
            $this->command->info("   ✓ {$viewer->name} => Viewer");
        }
        
        // 6. Handle other users based on their role_id and skpd_id
        $otherUsers = User::whereNotIn('nip', [
            '198001011990031001',
            '196901121993031004', 
            '199001011990031001',
            '199101011991031001',
            '200001012000031001'
        ])->get();
        
        foreach ($otherUsers as $user) {
            if ($user->role_id == 1 && $user->skpd_id == null) {
                $user->syncRoles(['Administrator']);
                $this->command->info("   ✓ {$user->name} => Administrator");
            } elseif ($user->role_id == 2 && $user->skpd_id == null) {
                $user->syncRoles(['Operator']);
                $this->command->info("   ✓ {$user->name} => Operator");
            } elseif ($user->role_id == 3) {
                $user->syncRoles(['Viewer']);
                $this->command->info("   ✓ {$user->name} => Viewer");
            }
        }
        
        $this->command->info('   ✓ Role reassignment completed!');
    }
}