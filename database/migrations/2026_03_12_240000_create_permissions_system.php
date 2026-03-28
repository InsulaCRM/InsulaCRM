<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('group');
            $table->string('display_name');
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->unique(['role_id', 'permission_id']);
        });

        // Add is_system flag and tenant_id to roles for custom roles
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('display_name');
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            $table->index('tenant_id');
        });

        // Mark existing roles as system roles
        DB::table('roles')->whereIn('name', [
            'admin', 'agent', 'acquisition_agent', 'disposition_agent', 'field_scout',
        ])->update(['is_system' => true]);

        // Seed default permissions
        $permissions = [
            ['key' => 'leads.view', 'group' => 'leads', 'display_name' => 'View Leads'],
            ['key' => 'leads.create', 'group' => 'leads', 'display_name' => 'Create Leads'],
            ['key' => 'leads.edit', 'group' => 'leads', 'display_name' => 'Edit Leads'],
            ['key' => 'leads.delete', 'group' => 'leads', 'display_name' => 'Delete Leads'],
            ['key' => 'leads.export', 'group' => 'leads', 'display_name' => 'Export Leads'],
            ['key' => 'leads.bulk_actions', 'group' => 'leads', 'display_name' => 'Bulk Actions on Leads'],
            ['key' => 'properties.view', 'group' => 'properties', 'display_name' => 'View Properties'],
            ['key' => 'properties.create', 'group' => 'properties', 'display_name' => 'Create Properties'],
            ['key' => 'properties.edit', 'group' => 'properties', 'display_name' => 'Edit Properties'],
            ['key' => 'deals.view', 'group' => 'deals', 'display_name' => 'View Pipeline'],
            ['key' => 'deals.create', 'group' => 'deals', 'display_name' => 'Create Deals'],
            ['key' => 'deals.edit', 'group' => 'deals', 'display_name' => 'Edit Deals'],
            ['key' => 'deals.export', 'group' => 'deals', 'display_name' => 'Export Deals'],
            ['key' => 'buyers.view', 'group' => 'buyers', 'display_name' => 'View Buyers'],
            ['key' => 'buyers.create', 'group' => 'buyers', 'display_name' => 'Create Buyers'],
            ['key' => 'buyers.edit', 'group' => 'buyers', 'display_name' => 'Edit Buyers'],
            ['key' => 'buyers.delete', 'group' => 'buyers', 'display_name' => 'Delete Buyers'],
            ['key' => 'buyers.export', 'group' => 'buyers', 'display_name' => 'Export Buyers'],
            ['key' => 'calendar.view', 'group' => 'calendar', 'display_name' => 'View Calendar'],
            ['key' => 'reports.view', 'group' => 'reports', 'display_name' => 'View Reports'],
            ['key' => 'reports.export', 'group' => 'reports', 'display_name' => 'Export Reports'],
            ['key' => 'sequences.view', 'group' => 'sequences', 'display_name' => 'View Sequences'],
            ['key' => 'sequences.manage', 'group' => 'sequences', 'display_name' => 'Manage Sequences'],
            ['key' => 'lists.view', 'group' => 'lists', 'display_name' => 'View Lists'],
            ['key' => 'lists.manage', 'group' => 'lists', 'display_name' => 'Manage Lists'],
            ['key' => 'tags.manage', 'group' => 'tags', 'display_name' => 'Manage Tags'],
            ['key' => 'settings.manage', 'group' => 'settings', 'display_name' => 'Manage Settings'],
            ['key' => 'settings.team', 'group' => 'settings', 'display_name' => 'Manage Team Members'],
            ['key' => 'settings.roles', 'group' => 'settings', 'display_name' => 'Manage Roles & Permissions'],
            ['key' => 'audit_log.view', 'group' => 'settings', 'display_name' => 'View Audit Log'],
            ['key' => 'plugins.manage', 'group' => 'settings', 'display_name' => 'Manage Plugins'],
            ['key' => 'api.manage', 'group' => 'settings', 'display_name' => 'Manage API Settings'],
            ['key' => 'profile.edit', 'group' => 'profile', 'display_name' => 'Edit Own Profile'],
        ];

        $now = now();
        foreach ($permissions as &$p) {
            $p['created_at'] = $now;
            $p['updated_at'] = $now;
        }
        DB::table('permissions')->insert($permissions);

        // Map default permissions to existing system roles
        $allPermIds = DB::table('permissions')->pluck('id', 'key');

        $rolePerms = [
            'admin' => $allPermIds->keys()->toArray(), // Admin gets everything
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
        ];

        $inserts = [];
        foreach ($rolePerms as $roleName => $permKeys) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');
            if (!$roleId) continue;
            foreach ($permKeys as $key) {
                if (isset($allPermIds[$key])) {
                    $inserts[] = ['role_id' => $roleId, 'permission_id' => $allPermIds[$key]];
                }
            }
        }
        DB::table('role_permission')->insert($inserts);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn(['is_system', 'tenant_id']);
        });

        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
    }
};
