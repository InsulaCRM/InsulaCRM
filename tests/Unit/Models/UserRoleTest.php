<?php

namespace Tests\Unit\Models;

use Tests\TestCase;

class UserRoleTest extends TestCase
{
    public function test_admin_is_admin(): void
    {
        $this->createTenantWithAdmin();
        $this->assertTrue($this->adminUser->isAdmin());
        $this->assertFalse($this->adminUser->isFieldScout());
    }

    public function test_admin_can_manage_leads(): void
    {
        $this->createTenantWithAdmin();
        $this->assertTrue($this->adminUser->canManageLeads());
    }

    public function test_admin_can_manage_buyers(): void
    {
        $this->createTenantWithAdmin();
        $this->assertTrue($this->adminUser->canManageBuyers());
    }

    public function test_acquisition_agent_can_manage_leads(): void
    {
        $this->createTenantWithAdmin();
        $agent = $this->createUserWithRole('acquisition_agent');

        $this->assertTrue($agent->canManageLeads());
        $this->assertTrue($agent->isAcquisitionAgent());
        $this->assertFalse($agent->canManageBuyers());
        $this->assertFalse($agent->isAdmin());
    }

    public function test_disposition_agent_can_manage_buyers(): void
    {
        $this->createTenantWithAdmin();
        $agent = $this->createUserWithRole('disposition_agent');

        $this->assertTrue($agent->canManageBuyers());
        $this->assertTrue($agent->isDispositionAgent());
        $this->assertFalse($agent->canManageLeads());
    }

    public function test_field_scout_has_limited_access(): void
    {
        $this->createTenantWithAdmin();
        $scout = $this->createUserWithRole('field_scout');

        $this->assertTrue($scout->isFieldScout());
        $this->assertFalse($scout->canManageLeads());
        $this->assertFalse($scout->canManageBuyers());
        $this->assertFalse($scout->isAdmin());
    }

    public function test_agent_can_manage_leads(): void
    {
        $this->createTenantWithAdmin();
        $agent = $this->createUserWithRole('agent');

        $this->assertTrue($agent->isAgent());
        $this->assertTrue($agent->canManageLeads());
        $this->assertFalse($agent->canManageBuyers());
    }

    public function test_has_role_checks_exact_match(): void
    {
        $this->createTenantWithAdmin();
        $this->assertTrue($this->adminUser->hasRole('admin'));
        $this->assertFalse($this->adminUser->hasRole('agent'));
        $this->assertFalse($this->adminUser->hasRole('Admin'));
    }
}
