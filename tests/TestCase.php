<?php

namespace Tests;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        if (!file_exists(storage_path('installed.lock'))) {
            file_put_contents(storage_path('installed.lock'), now()->toIso8601String());
            $this->installedLockCreated = true;
        }

        $this->seedRoles();
    }

    protected function tearDown(): void
    {
        if (!empty($this->installedLockCreated) && file_exists(storage_path('installed.lock'))) {
            unlink(storage_path('installed.lock'));
        }

        parent::tearDown();
    }

    protected function seedRoles(): void
    {
        $roles = ['admin', 'acquisition_agent', 'disposition_agent', 'field_scout', 'agent', 'listing_agent', 'buyers_agent'];
        foreach ($roles as $name) {
            Role::updateOrCreate(['name' => $name], [
                'display_name' => ucwords(str_replace('_', ' ', $name)),
                'is_system' => true,
                'tenant_id' => null,
            ]);
        }
    }

    protected function createTenantWithAdmin(array $tenantOverrides = [], array $userOverrides = []): User
    {
        $this->tenant = Tenant::create(array_merge([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'email' => 'admin@test.com',
            'status' => 'active',
            'timezone' => 'America/New_York',
            'currency' => 'USD',
            'date_format' => 'm/d/Y',
            'country' => 'US',
            'measurement_system' => 'imperial',
            'locale' => 'en',
            'distribution_method' => 'round_robin',
        ], $tenantOverrides));

        $adminRole = Role::where('name', 'admin')->first();

        $this->adminUser = User::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'role_id' => $adminRole->id,
            'is_active' => true,
        ], $userOverrides));

        return $this->adminUser;
    }

    protected function createUserWithRole(string $roleName, array $overrides = []): User
    {
        $role = Role::where('name', $roleName)->first();

        return User::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'role_id' => $role->id,
            'is_active' => true,
        ], $overrides));
    }

    protected function actingAsAdmin(array $tenantOverrides = []): self
    {
        $this->createTenantWithAdmin($tenantOverrides);
        return $this->actingAs($this->adminUser);
    }

    protected function actingAsRole(string $roleName, array $tenantOverrides = []): User
    {
        if (!isset($this->tenant)) {
            $this->createTenantWithAdmin($tenantOverrides);
        }

        $user = $this->createUserWithRole($roleName);
        $this->actingAs($user);

        return $user;
    }

    protected function createLead(array $overrides = []): \App\Models\Lead
    {
        return \App\Models\Lead::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'agent_id' => $this->adminUser->id,
        ], $overrides));
    }

    protected function createDeal(array $overrides = []): \App\Models\Deal
    {
        if (!isset($overrides['lead_id'])) {
            $overrides['lead_id'] = $this->createLead()->id;
        }

        return \App\Models\Deal::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'agent_id' => $this->adminUser->id,
        ], $overrides));
    }

    protected function createProperty(array $overrides = []): \App\Models\Property
    {
        if (!isset($overrides['lead_id'])) {
            $overrides['lead_id'] = $this->createLead()->id;
        }

        return \App\Models\Property::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
        ], $overrides));
    }
}
