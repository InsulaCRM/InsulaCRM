<?php

namespace Tests\Feature;

use App\Services\TotpService;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    public function test_2fa_setup_page_loads(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('two-factor.setup'));

        $response->assertStatus(200);
        $response->assertSee('Set Up 2FA');
    }

    public function test_enable_2fa_with_valid_code(): void
    {
        $this->actingAsAdmin();

        $totp = new TotpService();
        $secret = $totp->generateSecret();

        session(['2fa_setup_secret' => $secret]);

        // Generate a valid code
        $code = $this->generateTotpCode($secret);

        $response = $this->post(route('two-factor.enable'), ['code' => $code]);

        $response->assertStatus(200);
        $response->assertSee('Recovery Codes');
        $this->assertTrue($this->adminUser->fresh()->two_factor_enabled);
    }

    public function test_enable_2fa_with_invalid_code_fails(): void
    {
        $this->actingAsAdmin();

        $totp = new TotpService();
        $secret = $totp->generateSecret();
        session(['2fa_setup_secret' => $secret]);

        $response = $this->post(route('two-factor.enable'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
    }

    public function test_disable_2fa_requires_password(): void
    {
        $this->actingAsAdmin();
        $this->adminUser->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => Crypt::encryptString('TESTSECRET123456'),
            'two_factor_recovery_codes' => encrypt(['CODE1', 'CODE2']),
        ]);

        $response = $this->delete(route('two-factor.disable'), ['password' => 'wrong']);

        $response->assertSessionHasErrors('password');
        $this->assertTrue($this->adminUser->fresh()->two_factor_enabled);
    }

    public function test_2fa_challenge_page_accessible_with_session(): void
    {
        $this->createTenantWithAdmin();

        session(['2fa_user_id' => $this->adminUser->id]);

        $response = $this->get(route('two-factor.challenge'));

        $response->assertStatus(200);
        $response->assertSee('Authentication Code');
    }

    public function test_2fa_challenge_redirects_without_session(): void
    {
        $response = $this->get(route('two-factor.challenge'));

        $response->assertRedirect(route('login'));
    }

    public function test_totp_service_generates_valid_codes(): void
    {
        $totp = new TotpService();
        $secret = $totp->generateSecret();

        $this->assertEquals(32, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);

        $recoveryCodes = $totp->generateRecoveryCodes();
        $this->assertCount(8, $recoveryCodes);
    }

    private function generateTotpCode(string $secret): string
    {
        $totp = new TotpService();
        // Use reflection to access private method for testing
        $reflection = new \ReflectionClass($totp);
        $method = $reflection->getMethod('generateCode');
        $method->setAccessible(true);

        return $method->invoke($totp, $secret, floor(time() / 30));
    }
}
