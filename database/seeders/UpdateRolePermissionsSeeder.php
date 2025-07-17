<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class UpdateRolePermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Get existing roles
        $adminRole = Role::findByName('Administrator');
        $operatorRole = Role::findByName('Operator');
        $viewerRole = Role::findByName('Viewer');

        // Update permissions untuk Administrator
        // REMOVE user management permissions
        $adminRole->syncPermissions([
            'view-dashboard',
            'view-trend-analysis',
            'view-tahun-anggaran',
            'create-tahun-anggaran',
            'edit-tahun-anggaran',
            'delete-tahun-anggaran',
            'view-kode-rekening',
            'create-kode-rekening',
            'edit-kode-rekening',
            'delete-kode-rekening',
            'view-target',
            'create-target',
            'edit-target',
            'delete-target',
            'view-penerimaan',
            'create-penerimaan',
            'edit-penerimaan',
            'delete-penerimaan',
            'view-laporan',
            'export-laporan',
            'manage-backup',
            // REMOVED: view-users, create-users, edit-users, delete-users
        ]);
        
        // Update permissions untuk Operator
        // GIVE ALL PERMISSIONS including user management
        $operatorRole->syncPermissions(Permission::all());
        
        // Update permissions untuk Viewer  
        // ONLY VIEW permissions, no create/edit/delete
        $viewerRole->syncPermissions([
            'view-dashboard',
            'view-trend-analysis',
            'view-tahun-anggaran',
            'view-kode-rekening',
            'view-target',
            'view-penerimaan',
            'view-laporan',
            'export-laporan', // bisa export tapi tidak bisa edit
            // NO create, edit, delete permissions
        ]);

        echo "Role permissions updated successfully!\n";
        echo "- Administrator: Full access EXCEPT user management\n";
        echo "- Operator: FULL ACCESS including user management\n";
        echo "- Viewer: View only access (no create/edit/delete)\n";
    }
}