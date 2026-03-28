<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_admin_can_login(): void
    {
        $user = $this->createTenantWithAdmin([], [
            'email' => 'admin@test.com',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->createTenantWithAdmin([], [
            'email' => 'admin@test.com',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->post('/login', [
            'email' => 'nobody@test.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_authenticated_user_redirected_from_login(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/login');
        $response->assertRedirect();
    }

    public function test_user_can_logout(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/logout');

        $this->assertGuest();
    }
}
