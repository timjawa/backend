<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserAuth;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Create default admin user for development.
     */
    public function run(): void
    {
        // Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@jembersiaga.go.id'],
            [
                'name'      => 'Super Admin',
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );

        UserAuth::firstOrCreate(
            ['user_id' => $superAdmin->id, 'provider' => 'local'],
            ['password' => Hash::make('password')]
        );

        // Admin BMKG
        $adminBmkg = User::firstOrCreate(
            ['email' => 'admin@jembersiaga.go.id'],
            [
                'name'      => 'Admin BMKG',
                'role'      => 'admin_bmkg',
                'is_active' => true,
            ]
        );

        UserAuth::firstOrCreate(
            ['user_id' => $adminBmkg->id, 'provider' => 'local'],
            ['password' => Hash::make('password')]
        );

        // Masyarakat (test user)
        $masyarakat = User::firstOrCreate(
            ['email' => 'user@jembersiaga.go.id'],
            [
                'name'      => 'Warga Jember',
                'role'      => 'masyarakat',
                'is_active' => true,
            ]
        );

        UserAuth::firstOrCreate(
            ['user_id' => $masyarakat->id, 'provider' => 'local'],
            ['password' => Hash::make('password')]
        );

        $this->command->info('✅ Admin seeder berhasil! Akun default:');
        $this->command->table(
            ['Email', 'Password', 'Role'],
            [
                ['superadmin@jembersiaga.go.id', 'password', 'super_admin'],
                ['admin@jembersiaga.go.id', 'password', 'admin_bmkg'],
                ['user@jembersiaga.go.id', 'password', 'masyarakat'],
            ]
        );
    }
}
