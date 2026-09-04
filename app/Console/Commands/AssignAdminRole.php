<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class AssignAdminRole extends Command
{
    protected $signature = 'assign:admin-role {emails* : One or more user emails to assign the admin role}';
    protected $description = 'Assign the admin role to one or more users by email';

    public function handle()
    {
        $adminRole = Role::where('name', 'admin')->first();

        if (!$adminRole) {
            $this->error('Admin role not found. Run RolesAndPermissionsSeeder first.');
            return 1;
        }

        foreach ($this->argument('emails') as $email) {
            $user = User::where('email', $email)->first();

            if (!$user) {
                $this->warn("User not found: {$email}");
                continue;
            }

            $user->roles()->syncWithoutDetaching([$adminRole->id]);
            $this->info("Assigned admin role to: {$user->email}");
        }

        return 0;
    }
}
