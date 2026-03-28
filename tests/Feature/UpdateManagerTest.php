<?php

namespace Tests\Feature;

use App\Models\SystemUpdate;
use App\Services\Settings\BackupService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class UpdateManagerTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempRoot = storage_path('framework/testing/update-manager-' . uniqid());
        File::makeDirectory($this->tempRoot, 0755, true);

        config([
            'app.version' => '1.0.1',
            'update-manager.install_root' => $this->tempRoot . '/install-root',
            'update-manager.staging_root' => $this->tempRoot . '/staging',
            'update-manager.log_root' => $this->tempRoot . '/logs',
            'update-manager.snapshot_root' => $this->tempRoot . '/snapshots',
        ]);

        File::makeDirectory(config('update-manager.install_root'), 0755, true);
        File::makeDirectory(config('update-manager.install_root') . '/storage/app/public', 0755, true);
        File::makeDirectory(config('update-manager.install_root') . '/public', 0755, true);
        File::makeDirectory(config('update-manager.install_root') . '/plugins/custom-plugin', 0755, true);
        File::makeDirectory(config('update-manager.install_root') . '/app', 0755, true);

        File::put(config('update-manager.install_root') . '/.env', "APP_NAME=InsulaCRM\nAPP_KEY=base64:test\n");
        File::put(config('update-manager.install_root') . '/VERSION', '1.0.1');
        File::put(config('update-manager.install_root') . '/storage/app/public/data.txt', 'keep-me');
        File::put(config('update-manager.install_root') . '/plugins/custom-plugin/plugin.json', '{"name":"Custom"}');
        File::put(config('update-manager.install_root') . '/app/Existing.php', 'old-version');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempRoot);
        parent::tearDown();
    }

    public function test_admin_can_stage_newer_release_zip(): void
    {
        $this->actingAsAdmin();

        $zipPath = $this->makeReleaseZip('1.0.2', [
            'app/NewFile.php' => '<?php echo "new";',
        ]);

        $response = $this->post('/settings/updates/upload', [
            'release_zip' => $this->uploadedZip($zipPath),
        ]);

        $response->assertRedirect('/settings?tab=system');
        $this->assertDatabaseHas('system_updates', [
            'tenant_id' => $this->tenant->id,
            'version_from' => '1.0.1',
            'version_to' => '1.0.2',
            'status' => 'prepared',
        ]);
    }

    public function test_apply_update_creates_backup_and_preserves_protected_paths(): void
    {
        $this->actingAsAdmin();
        $this->mockBackupService();

        $zipPath = $this->makeReleaseZip('1.0.2', [
            'app/Existing.php' => 'new-version',
            'resources/views/example.blade.php' => 'hello',
            'plugins/hello-world/plugin.json' => '{"name":"Bundled"}',
            'storage/should-not-copy.txt' => 'do-not-copy',
        ]);

        $this->post('/settings/updates/upload', [
            'release_zip' => $this->uploadedZip($zipPath),
        ]);

        $prepared = SystemUpdate::withoutGlobalScopes()->latest()->firstOrFail();

        $response = $this->post("/settings/updates/{$prepared->id}/apply");

        $response->assertRedirect('/settings?tab=system');

        $prepared->refresh();
        $this->assertSame('applied', $prepared->status);
        $this->assertSame('backup-test.sql.gz', $prepared->backup_filename);
        $this->assertNotNull($prepared->snapshot_archive_path);
        $this->assertNotNull($prepared->snapshot_manifest_path);
        $this->assertNotNull($prepared->snapshot_created_at);
        $this->assertFileExists($prepared->snapshot_archive_path);
        $this->assertFileExists($prepared->snapshot_manifest_path);
        $this->assertStringContainsString('APP_NAME=InsulaCRM', File::get(config('update-manager.install_root') . '/.env'));
        $this->assertSame('keep-me', File::get(config('update-manager.install_root') . '/storage/app/public/data.txt'));
        $this->assertFileExists(config('update-manager.install_root') . '/plugins/custom-plugin/plugin.json');
        $this->assertFileDoesNotExist(config('update-manager.install_root') . '/plugins/hello-world/plugin.json');
        $this->assertSame('1.0.2', trim(File::get(config('update-manager.install_root') . '/VERSION')));
        $this->assertSame('new-version', File::get(config('update-manager.install_root') . '/app/Existing.php'));
        $this->assertFileExists(config('update-manager.install_root') . '/resources/views/example.blade.php');
        $this->assertFileDoesNotExist(config('update-manager.install_root') . '/storage/should-not-copy.txt');
    }

    public function test_admin_can_create_manual_recovery_snapshot(): void
    {
        $this->actingAsAdmin();
        $this->mockBackupService();

        $response = $this->post('/settings/snapshots', [
            'label' => 'Before risky maintenance',
        ]);

        $response->assertRedirect('/settings?tab=system');
        $this->assertDatabaseHas('system_snapshots', [
            'tenant_id' => $this->tenant->id,
            'label' => 'Before risky maintenance',
            'version' => '1.0.1',
            'status' => 'ready',
            'backup_filename' => 'backup-test.sql.gz',
        ]);

        $snapshot = \App\Models\SystemSnapshot::withoutGlobalScopes()->latest()->firstOrFail();
        $this->assertFileExists($snapshot->snapshot_archive_path);
        $this->assertFileExists($snapshot->snapshot_manifest_path);
    }

    public function test_manual_snapshot_preserves_empty_plugins_directory(): void
    {
        $this->actingAsAdmin();
        $this->mockBackupService();

        File::deleteDirectory(config('update-manager.install_root') . '/plugins');
        File::makeDirectory(config('update-manager.install_root') . '/plugins', 0755, true);

        $response = $this->post('/settings/snapshots', [
            'label' => 'Empty plugins snapshot',
        ]);

        $response->assertRedirect('/settings?tab=system');

        $snapshot = \App\Models\SystemSnapshot::withoutGlobalScopes()->latest()->firstOrFail();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($snapshot->snapshot_archive_path));
        $this->assertNotFalse($zip->locateName('insulacrm/plugins/'));
        $zip->close();
    }

    private function mockBackupService(): void
    {
        $backups = [];
        $mock = \Mockery::mock(BackupService::class);
        $mock->shouldReceive('list')->andReturnUsing(function () use (&$backups) {
            return array_map(fn (string $name) => ['name' => $name, 'size' => '1 KB', 'date' => now()->format('Y-m-d H:i')], $backups);
        });
        $mock->shouldReceive('create')->andReturnUsing(function () use (&$backups) {
            $backups = ['backup-test.sql.gz'];

            return true;
        });
        $mock->shouldReceive('path')->andReturnUsing(fn (string $filename) => $filename === 'backup-test.sql.gz' ? storage_path('app/backups/backup-test.sql.gz') : null);

        $this->app->instance(BackupService::class, $mock);
        File::ensureDirectoryExists(storage_path('app/backups'));
        File::put(storage_path('app/backups/backup-test.sql.gz'), 'backup');
    }

    private function makeReleaseZip(string $version, array $files): string
    {
        $zipPath = $this->tempRoot . '/release-' . $version . '.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        $baseFiles = [
            'VERSION' => $version,
            'artisan' => '#!/usr/bin/env php',
            'bootstrap/app.php' => '<?php return [];',
            'config/app.php' => '<?php return [];',
        ];

        foreach (array_merge($baseFiles, $files) as $path => $contents) {
            $zip->addFromString('insulacrm/' . $path, $contents);
        }

        $zip->close();

        return $zipPath;
    }

    private function uploadedZip(string $path): UploadedFile
    {
        return new UploadedFile(
            $path,
            basename($path),
            'application/zip',
            null,
            true
        );
    }
}
