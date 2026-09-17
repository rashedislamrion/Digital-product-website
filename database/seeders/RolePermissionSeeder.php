<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define all permissions from Section 4.3
        $permissions = [
            'catalog.create_update',
            'catalog.publish_version',
            'orders.view_financials',
            'orders.issue_refund',
            'licenses.view_keys',
            'licenses.reset_revoke',
            'downloads.reset_limit',
            'reviews.moderate_publish',
            'support.manage_tickets',
            'webhooks.view_replay',
            'system.manage_settings',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 2. Define Roles and assign permission matrix
        $matrix = [
            'Super Admin' => $permissions,
            'Finance Manager' => [
                'orders.view_financials',
                'orders.issue_refund',
            ],
            'Catalog Editor' => [
                'catalog.create_update',
                'catalog.publish_version',
            ],
            'Support Agent' => [
                'licenses.view_keys',
                'licenses.reset_revoke',
                'downloads.reset_limit',
                'support.manage_tickets',
            ],
            'Review Moderator' => [
                'reviews.moderate_publish',
            ],
        ];

        $roles = [];
        foreach ($matrix as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
            $roles[$roleName] = $role;
        }

        // Also ensure backward compatibility if anything looked for super_admin
        $legacySuperAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $legacySuperAdmin->syncPermissions($permissions);

        // 3. Create seeded staff users (password: password)
        $staffUsers = [
            [
                'name' => 'Store Administrator',
                'email' => 'admin@example.com',
                'roles' => ['Super Admin', 'super_admin'],
            ],
            [
                'name' => 'Finance Director',
                'email' => 'finance@example.com',
                'roles' => ['Finance Manager'],
            ],
            [
                'name' => 'Catalog Lead',
                'email' => 'catalog@example.com',
                'roles' => ['Catalog Editor'],
            ],
            [
                'name' => 'Support Desk Lead',
                'email' => 'support@example.com',
                'roles' => ['Support Agent'],
            ],
            [
                'name' => 'Review Specialist',
                'email' => 'reviewer@example.com',
                'roles' => ['Review Moderator'],
            ],
        ];

        foreach ($staffUsers as $staff) {
            $user = User::firstOrCreate(
                ['email' => $staff['email']],
                [
                    'name' => $staff['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles($staff['roles']);
        }
    }
}
