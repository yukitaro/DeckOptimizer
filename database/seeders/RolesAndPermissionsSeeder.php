<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissionGroups = config('permissions');

        // Flatten and create all permissions
        $allPermissions = collect($permissionGroups)
            ->flatten()
            ->unique()
            ->map(fn($name) => Permission::firstOrCreate(['name' => $name]));

        // Create roles
        $roles = [
            'admin' => ['*'], // gets all permissions
            'editor' => [
                'view_issues', 'edit_issues', 'create_issues',
                'view_users', 'view_roles',
            ],
            'viewer' => [
                'view_issues', 'view_users', 'view_roles',
            ],
        ];

        foreach ($roles as $roleName => $grantedPermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            $permissionIds = $grantedPermissions === ['*']
                ? Permission::pluck('id')
                : Permission::whereIn('name', $grantedPermissions)->pluck('id');

            $role->permissions()->sync($permissionIds);
        }
    }
}
