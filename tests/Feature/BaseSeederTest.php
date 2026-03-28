<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_seeder_assigns_default_permissions_to_system_roles(): void
    {
        $this->seed(\Database\Seeders\BaseSeeder::class);

        $admin = Role::where('name', 'admin')->firstOrFail();
        $agent = Role::where('name', 'agent')->firstOrFail();
        $fieldScout = Role::where('name', 'field_scout')->firstOrFail();

        $this->assertGreaterThan(0, $admin->permissions()->count());
        $this->assertTrue($agent->permissions()->where('key', 'leads.view')->exists());
        $this->assertTrue($agent->permissions()->where('key', 'deals.view')->exists());
        $this->assertFalse($fieldScout->permissions()->where('key', 'leads.view')->exists());
        $this->assertTrue($fieldScout->permissions()->where('key', 'properties.create')->exists());
    }
}
