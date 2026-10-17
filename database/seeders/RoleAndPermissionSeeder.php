<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            'view_dashboard',
            'manage_members',
            'manage_subscriptions',
            'scan_attendance',
            'pos_cashier',
            'pos_void',
            'manage_pt_sessions',
            'view_reports',
            'manage_users',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // 1. Role: Admin (Full Access)
        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions(Permission::all());

        // 2. Role: Supervisor (Operational, Attendance, Void, Reports)
        $supervisorRole = Role::findOrCreate('supervisor', 'web');
        $supervisorRole->syncPermissions([
            'view_dashboard',
            'manage_members',
            'manage_subscriptions',
            'scan_attendance',
            'pos_cashier',
            'pos_void',
            'view_reports',
        ]);

        // 3. Role: CS (Frontliner: Member registration, Attendance kiosk, POS cashier)
        $csRole = Role::findOrCreate('cs', 'web');
        $csRole->syncPermissions([
            'view_dashboard',
            'manage_members',
            'manage_subscriptions',
            'scan_attendance',
            'pos_cashier',
        ]);

        // 4. Role: PT (Personal Trainer: PT sessions, Activity logs)
        $ptRole = Role::findOrCreate('pt', 'web');
        $ptRole->syncPermissions([
            'view_dashboard',
            'manage_pt_sessions',
        ]);

        // Create Seed Users for each persona

        // Admin User
        $admin = User::updateOrCreate(
            ['email' => 'admin@fitcore.com'],
            [
                'name' => 'Alexander Pierce (Admin)',
                'password' => Hash::make('password'),
            ]
        );
        $admin->syncRoles([$adminRole]);

        // Supervisor User
        $supervisor = User::updateOrCreate(
            ['email' => 'supervisor@fitcore.com'],
            [
                'name' => 'Bambang Supriyadi (Supervisor)',
                'password' => Hash::make('password'),
            ]
        );
        $supervisor->syncRoles([$supervisorRole]);

        // CS Frontliner User
        $cs = User::updateOrCreate(
            ['email' => 'cs@fitcore.com'],
            [
                'name' => 'Siti Nurhaliza (Frontliner)',
                'password' => Hash::make('password'),
            ]
        );
        $cs->syncRoles([$csRole]);

        // Personal Trainer User
        $pt = User::updateOrCreate(
            ['email' => 'pt@fitcore.com'],
            [
                'name' => 'Coach Dimas (Personal Trainer)',
                'password' => Hash::make('password'),
            ]
        );
        $pt->syncRoles([$ptRole]);
    }
}
