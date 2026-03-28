<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    public function test_registration_page_loads(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_new_user_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'company_name' => 'Test Corp',
            'email' => 'john@testcorp.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'john@testcorp.com',
            'name' => 'John Doe',
        ]);
        $this->assertDatabaseHas('tenants', [
            'name' => 'Test Corp',
        ]);
    }

    public function test_registration_requires_company_name(): void
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@testcorp.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('company_name');
    }

    public function test_registration_requires_valid_email(): void
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'company_name' => 'Test Corp',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'company_name' => 'Test Corp',
            'email' => 'john@testcorp.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_duplicate_email_rejected(): void
    {
        $this->createTenantWithAdmin([], ['email' => 'john@testcorp.com']);

        $response = $this->post('/register', [
            'name' => 'Jane Doe',
            'company_name' => 'Another Corp',
            'email' => 'john@testcorp.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
