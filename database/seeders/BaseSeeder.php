<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BaseSeeder extends Seeder
{
    /**
     * Seed essential data required for the app to function.
     */
    public function run(): void
    {
        $roles = [
            'admin' => 'Admin',
            'acquisition_agent' => 'Acquisition Agent',
            'disposition_agent' => 'Disposition Agent',
            'field_scout' => 'Field Scout',
            'agent' => 'Agent',
            'listing_agent' => 'Listing Agent',
            'buyers_agent' => 'Buyers Agent',
        ];

        foreach ($roles as $name => $displayName) {
            Role::firstOrCreate(
                ['name' => $name],
                ['display_name' => $displayName, 'is_system' => true]
            );
        }

        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_permission')) {
            return;
        }

        $allPermissions = Permission::query()->pluck('id', 'key');
        if ($allPermissions->isEmpty()) {
            return;
        }

        $rolePermissions = [
            'admin' => $allPermissions->keys()->all(),
            'agent' => [
                'leads.view', 'leads.create', 'leads.edit', 'leads.delete', 'leads.export', 'leads.bulk_actions',
                'properties.view', 'properties.create', 'properties.edit',
                'deals.view', 'deals.create', 'deals.edit', 'deals.export',
                'calendar.view', 'profile.edit',
            ],
            'acquisition_agent' => [
                'leads.view', 'leads.create', 'leads.edit', 'leads.delete', 'leads.export', 'leads.bulk_actions',
                'properties.view', 'properties.create', 'properties.edit',
                'deals.view', 'deals.create', 'deals.edit', 'deals.export',
                'calendar.view', 'profile.edit',
            ],
            'disposition_agent' => [
                'deals.view', 'deals.create', 'deals.edit', 'deals.export',
                'buyers.view', 'buyers.create', 'buyers.edit', 'buyers.delete', 'buyers.export',
                'calendar.view', 'profile.edit',
            ],
            'field_scout' => [
                'properties.view', 'properties.create',
                'profile.edit',
            ],
            'listing_agent' => [
                'leads.view', 'leads.create', 'leads.edit', 'leads.export',
                'properties.view', 'properties.create', 'properties.edit',
                'deals.view', 'deals.create', 'deals.edit', 'deals.export',
                'calendar.view', 'profile.edit',
            ],
            'buyers_agent' => [
                'leads.view', 'leads.create', 'leads.edit', 'leads.export',
                'deals.view', 'deals.create', 'deals.edit', 'deals.export',
                'buyers.view', 'buyers.create', 'buyers.edit', 'buyers.export',
                'properties.view',
                'calendar.view', 'profile.edit',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissionKeys) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            $permissionIds = collect($permissionKeys)
                ->map(fn (string $key) => $allPermissions[$key] ?? null)
                ->filter()
                ->values()
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
