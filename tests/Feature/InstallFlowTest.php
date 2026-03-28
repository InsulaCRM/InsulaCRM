<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InstallFlowTest extends TestCase
{
    public function test_install_complete_is_blocked_when_app_is_already_installed_without_completion_flag(): void
    {
        File::put(storage_path('installed.lock'), now()->toIso8601String());

        $this->get('/install/complete')
            ->assertRedirect('/login');
    }

    public function test_install_complete_is_accessible_immediately_after_successful_install(): void
    {
        File::put(storage_path('installed.lock'), now()->toIso8601String());

        $tenant = \App\Models\Tenant::withoutGlobalScopes()->first()
            ?? \App\Models\Tenant::withoutGlobalScopes()->create([
                'name' => 'Test',
                'slug' => 'test',
                'email' => 'test@test.com',
                'status' => 'active',
            ]);

        $this->withSession([
            'installation_completed' => true,
            'installation_tenant_id' => $tenant->id,
            'installation_admin_email' => 'test@test.com',
        ])
            ->get('/install/complete')
            ->assertOk()
            ->assertSee('Installation Complete', false);
    }
}
