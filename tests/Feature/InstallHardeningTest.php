<?php

namespace Tests\Feature;

use App\Http\Controllers\InstallController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Tests\TestCase;

class InstallHardeningTest extends TestCase
{
    public function test_database_step_explains_existing_user_is_the_default_path(): void
    {
        File::delete(storage_path('installed.lock'));

        $this->get('/install/database')
            ->assertOk()
            ->assertSee('Recommended path for most buyers', false)
            ->assertSee('Automatic database-user creation is an advanced option', false);
    }

    public function test_setup_step_marks_demo_data_as_optional(): void
    {
        File::delete(storage_path('installed.lock'));

        $this->get('/install/setup')
            ->assertOk()
            ->assertSee('Load demo data', false)
            ->assertSee('sample leads, deals, buyers', false);
    }

    public function test_install_complete_can_show_demo_data_warning(): void
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
            'demo_data_warning' => 'The base CRM was installed, but demo data could not be loaded.',
        ])->get('/install/complete')
            ->assertOk()
            ->assertSee('Demo Data Skipped', false)
            ->assertSee('base CRM was installed, but demo data could not be loaded', false);
    }

    public function test_detect_app_url_honors_forwarded_https_and_prefix(): void
    {
        $controller = new InstallController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('detectAppUrl');
        $method->setAccessible(true);

        SymfonyRequest::setTrustedProxies(
            ['127.0.0.1'],
            SymfonyRequest::HEADER_X_FORWARDED_FOR
            | SymfonyRequest::HEADER_X_FORWARDED_HOST
            | SymfonyRequest::HEADER_X_FORWARDED_PORT
            | SymfonyRequest::HEADER_X_FORWARDED_PROTO
            | SymfonyRequest::HEADER_X_FORWARDED_PREFIX
            | SymfonyRequest::HEADER_X_FORWARDED_AWS_ELB
        );

        $request = Request::create(
            'http://internal.example/install/setup',
            'GET',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_X_FORWARDED_PROTO' => 'https',
                'HTTP_X_FORWARDED_HOST' => 'crm.example.com',
                'HTTP_X_FORWARDED_PREFIX' => '/demo',
                'HTTP_X_FORWARDED_PORT' => '443',
                'SCRIPT_NAME' => '/demo/index.php',
                'PHP_SELF' => '/demo/index.php',
                'REQUEST_URI' => '/demo/install/setup',
            ]
        );

        $appUrl = $method->invoke($controller, $request);

        $this->assertSame('https://crm.example.com/demo', $appUrl);
    }

    public function test_detect_install_context_marks_public_urls_and_normalizes_base_path(): void
    {
        $controller = new InstallController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('detectInstallContext');
        $method->setAccessible(true);

        $request = new class extends Request
        {
            public function getBaseUrl(): string
            {
                return '/demo/public';
            }

            public function getSchemeAndHttpHost(): string
            {
                return 'http://example.com';
            }
        };

        $context = $method->invoke($controller, $request);

        $this->assertTrue($context['served_from_public']);
        $this->assertTrue($context['is_subdirectory']);
        $this->assertSame('/demo', $context['display_base_path']);
        $this->assertSame('http://example.com/demo', $context['detected_app_url']);
    }
}
