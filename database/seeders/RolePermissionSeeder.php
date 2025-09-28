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

        // STEP 1: Buat Permissions
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
            Permission::firstOrCreate(['name' => $permission]);
        }

        // STEP 2: Buat/Update Roles dengan permissions yang sesuai
        
        // 1. SUPER ADMIN - Full Access
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminRole->syncPermissions(Permission::all());
        
        // 2. ADMINISTRATOR (backward compatibility) - Sama seperti Super Admin
        $adminRole = Role::firstOrCreate(['name' => 'Administrator']);
        $adminRole->syncPermissions(Permission::all());
        
        // 3. KEPALA BADAN - View Only untuk semua, tidak bisa create/edit/delete
        $kepalaBadanRole = Role::firstOrCreate(['name' => 'Kepala Badan']);
        $kepalaBadanRole->syncPermissions([
            'view-dashboard',
            'view-trend-analysis',
            'view-tahun-anggaran',
            'view-kode-rekening',
            'view-target',
            'view-penerimaan',
            'view-laporan',
            'export-laporan', // Kepala Badan boleh export laporan
        ]);
        
        // 4. OPERATOR SKPD - Input penerimaan, TANPA akses Master Data
        $operatorSkpdRole = Role::firstOrCreate(['name' => 'Operator SKPD']);
        $operatorSkpdRole->syncPermissions([
            'view-dashboard',
            // TIDAK ADA permission untuk Master Data (Total Hidden)
            // TIDAK ADA view-trend-analysis
            // Penerimaan - full akses untuk SKPD sendiri
            'view-penerimaan',
            'create-penerimaan',
            'edit-penerimaan',
            'delete-penerimaan',
            'import-penerimaan',
            // Laporan
            'view-laporan',
            'export-laporan',
        ]);
        
        // 5. OPERATOR (backward compatibility) - Limited Access  
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
            'manage-backup',
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
        ]);
        
        // 6. VIEWER (backward compatibility) - Read Only
        $viewerRole = Role::firstOrCreate(['name' => 'Viewer']);
        $viewerRole->syncPermissions([
            'view-dashboard',
            'view-trend-analysis',
            'view-laporan',
        ]);

        // STEP 3: Clear existing roles and reassign
        $this->reassignRoles();
        
        echo "Role Permission Seeder completed successfully!\n";
    }

    private function reassignRoles()
    {
        echo "Reassigning roles to users...\n";
        
        // Get specific users by NIP
        
        // 1. Super Admin
        $superAdmin = User::where('nip', '198001011990031001')->first();
        if ($superAdmin) {
            $superAdmin->syncRoles(['Super Admin']);
            echo "User {$superAdmin->name} => Super Admin\n";
        }
        
        // 2. Kepala BPKPAD
        $kepalaBadan = User::where('nip', '196901121993031004')->first();
        if ($kepalaBadan) {
            $kepalaBadan->syncRoles(['Kepala Badan']);
            echo "User {$kepalaBadan->name} => Kepala Badan\n";
        }
        
        // 3. Operator Dinas Kesehatan
        $operatorDinkes = User::where('nip', '199001011990031001')->first();
        if ($operatorDinkes) {
            $operatorDinkes->syncRoles(['Operator SKPD']);
            echo "User {$operatorDinkes->name} => Operator SKPD (Dinkes)\n";
        }
        
        // 4. Operator Dinas PU
        $operatorPU = User::where('nip', '199101011991031001')->first();
        if ($operatorPU) {
            $operatorPU->syncRoles(['Operator SKPD']);
            echo "User {$operatorPU->name} => Operator SKPD (PU)\n";
        }
        
        // 5. Viewer
        $viewer = User::where('nip', '200001012000031001')->first();
        if ($viewer) {
            $viewer->syncRoles(['Viewer']);
            echo "User {$viewer->name} => Viewer\n";
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
                echo "User {$user->name} => Administrator\n";
            } elseif ($user->role_id == 2 && $user->skpd_id == null) {
                $user->syncRoles(['Operator']);
                echo "User {$user->name} => Operator\n";
            } elseif ($user->role_id == 3) {
                $user->syncRoles(['Viewer']);
                echo "User {$user->name} => Viewer\n";
            }
        }
        
        echo "Role reassignment completed!\n";
    }
}