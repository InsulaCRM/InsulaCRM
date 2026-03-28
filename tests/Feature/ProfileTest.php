<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function test_profile_page_loads(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertSee('Profile Information');
    }

    public function test_update_profile_name_and_email(): void
    {
        $this->actingAsAdmin();

        $response = $this->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertEquals('Updated Name', $this->adminUser->fresh()->name);
        $this->assertEquals('updated@example.com', $this->adminUser->fresh()->email);
    }

    public function test_update_password_requires_current_password(): void
    {
        $this->actingAsAdmin();

        $response = $this->put(route('profile.update'), [
            'name' => $this->adminUser->name,
            'email' => $this->adminUser->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_change_password_with_correct_current_password(): void
    {
        $this->actingAsAdmin();
        $this->adminUser->update(['password' => Hash::make('oldpassword')]);

        $response = $this->put(route('profile.update'), [
            'name' => $this->adminUser->name,
            'email' => $this->adminUser->email,
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertTrue(Hash::check('newpassword123', $this->adminUser->fresh()->password));
    }

    public function test_email_must_be_unique(): void
    {
        $this->actingAsAdmin();

        $otherUser = $this->createUserWithRole('agent', ['email' => 'taken@example.com']);

        $response = $this->put(route('profile.update'), [
            'name' => $this->adminUser->name,
            'email' => 'taken@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_all_roles_can_access_profile(): void
    {
        foreach (['agent', 'acquisition_agent', 'disposition_agent', 'field_scout'] as $role) {
            $user = $this->actingAsRole($role);
            $response = $this->get(route('profile.edit'));
            $response->assertStatus(200);
        }
    }
}
