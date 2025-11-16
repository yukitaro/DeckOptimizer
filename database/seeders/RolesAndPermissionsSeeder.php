<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = collect(config('permissions'))
            ->flatten()
            ->unique()
            ->map(fn($name) => Permission::firstOrCreate(['name' => $name]));

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $editor = Role::firstOrCreate(['name' => 'editor']);
        $viewer = Role::firstOrCreate(['name' => 'viewer']);

        $admin->permissions()->sync(Permission::pluck('id'));
        $editor->permissions()->sync(Permission::whereIn('name', [
            'view_issues', 'edit_issues', 'create_issues'
        ])->pluck('id'));
        $viewer->permissions()->sync(Permission::where('name', 'like', 'view_%')->pluck('id'));
    }
}
