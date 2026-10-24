<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $cashierRole = Role::firstOrCreate(['name' => 'cashier']);

        // Create standard admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@kassa.local'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'), // Add a default password
            ]
        );
        $admin->assignRole($adminRole);

        // Create standard cashier user
        $cashier = User::firstOrCreate(
            ['email' => 'kassa1@kassa.local'],
            [
                'name' => 'Kassa 1',
                'password' => bcrypt('password'),
            ]
        );
        $cashier->assignRole($cashierRole);
    }
}
