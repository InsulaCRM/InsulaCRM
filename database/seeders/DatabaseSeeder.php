<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with demo data.
     *
     * Creates roles, a demo tenant + admin, then delegates to
     * DemoDataSeeder for the actual demo content.
     */
    public function run(): void
    {
        // Seed essential data (roles)
        $this->call(BaseSeeder::class);

        $adminRole = Role::where('name', 'admin')->first();

        // ── Wholesale demo tenant ──────────────────────────
        $wholesaleTenant = Tenant::create([
            'name' => 'Apex Wholesale Properties',
            'slug' => 'apex-wholesale',
            'email' => 'admin@demo.com',
            'business_mode' => 'wholesale',
            'status' => 'active',
        ]);

        User::withoutGlobalScopes()->create([
            'tenant_id' => $wholesaleTenant->id,
            'role_id' => $adminRole->id,
            'name' => 'Marcus Johnson',
            'email' => 'admin@demo.com',
            'password' => bcrypt('password'),
        ]);

        $this->callWith(DemoDataSeeder::class, ['tenantId' => $wholesaleTenant->id]);

        // ── Real Estate Agent demo tenant ──────────────────
        $realestateTenant = Tenant::create([
            'name' => 'Pinnacle Realty Group',
            'slug' => 'pinnacle-realty',
            'email' => 'agent@demo.com',
            'business_mode' => 'realestate',
            'status' => 'active',
        ]);

        User::withoutGlobalScopes()->create([
            'tenant_id' => $realestateTenant->id,
            'role_id' => $adminRole->id,
            'name' => 'Amanda Brooks',
            'email' => 'agent@demo.com',
            'password' => bcrypt('password'),
        ]);

        $this->callWith(DemoDataSeeder::class, ['tenantId' => $realestateTenant->id]);
    }
}
