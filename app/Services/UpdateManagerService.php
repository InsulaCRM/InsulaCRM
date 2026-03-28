<?php

namespace App\Services;

use App\Models\SystemSnapshot;
use App\Models\SystemUpdate;
use App\Services\Settings\BackupService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class UpdateManagerService
{
    public function __construct(
        private readonly BackupService $backupService,
    ) {
    }

    private function beginLongRunningOperation(): void
    {
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        @ini_set('max_execution_time', '0');
    }

    public function prepareUpload(UploadedFile $file, int $tenantId, ?int $userId = null): SystemUpdate
    {
        $this->ensureSchemaReady();
        $this->ensureDirectories();

        $currentVersion = (string) config('app.version', '1.0.0');
        $packageHash = hash_file('sha256', $file->getRealPath());
        $stageRoot = $this->makeStageRoot();

        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException('Unable to open the uploaded release ZIP.');
        }

        if (! $this->extractZipSafely($zip, $stageRoot)) {
            $zip->close();
            File::deleteDirectory($stageRoot);
            throw new RuntimeException('The uploaded ZIP has an invalid or unsafe directory structure.');
        }

        $zip->close();

        $packageRoot = $this->resolvePackageRoot($stageRoot);
        $targetVersion = $this->readPackageVersion($packageRoot);

        if (! version_compare($targetVersion, $currentVersion, '>')) {
            File::deleteDirectory($stageRoot);
            throw new RuntimeException("This package targets version {$targetVersion}. Upload a newer release than the installed version {$currentVersion}.");
        }

        $this->validatePackageShape($packageRoot);
        $warnings = $this->buildWarnings($targetVersion);

        SystemUpdate::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'prepared')
            ->update(['status' => 'superseded']);

        $update = SystemUpdate::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'version_from' => $currentVersion,
            'version_to' => $targetVersion,
            'package_name' => $file->getClientOriginalName(),
            'package_sha256' => $packageHash,
            'stage_path' => $packageRoot,
            'status' => 'prepared',
            'warnings' => $warnings,
            'summary' => 'Prepared for in-app patching. A database backup and a pre-update recovery snapshot will be created automatically before file replacement.',
        ]);

        $this->appendLog($update, "Prepared {$targetVersion} from {$file->getClientOriginalName()}.");

        return $update;
    }

    public function applyPreparedUpdate(SystemUpdate $update): SystemUpdate
    {
        $this->beginLongRunningOperation();
        $this->ensureSchemaReady();

        if ($update->status !== 'prepared') {
            throw new RuntimeException('Only prepared updates can be applied.');
        }

        $this->ensureDirectories();
        $installRoot = $this->installRoot();
        $packageRoot = $update->stage_path;

        if (! File::isDirectory($packageRoot)) {
            throw new RuntimeException('The prepared update package is no longer available on disk.');
        }

        $update->forceFill([
            'status' => 'applying',
            'error_message' => null,
        ])->save();

        $this->appendLog($update, "Starting apply for {$update->version_to}.");
        $backupFilename = null;

        try {
            Artisan::call('down');
            $this->appendLog($update, 'Application entered maintenance mode.');

            $backupFilename = $this->createBackup();
            $update->forceFill(['backup_filename' => $backupFilename])->save();
            $this->appendLog($update, "Created database backup {$backupFilename}.");

            $envSnapshotPath = $this->snapshotEnv($update);
            [$snapshotArchivePath, $snapshotManifestPath] = $this->createRecoverySnapshot($update);
            $update->forceFill([
                'env_snapshot_path' => $envSnapshotPath,
                'snapshot_archive_path' => $snapshotArchivePath,
                'snapshot_manifest_path' => $snapshotManifestPath,
                'snapshot_created_at' => now(),
            ])->save();
            $this->appendLog($update, 'Saved .env snapshot.');
            $this->appendLog($update, 'Created a pre-update recovery snapshot immediately before patching.');

            $this->copyReleaseIntoInstall($packageRoot, $installRoot, $update);
            $this->flushOpcache();
            $this->appendLog($update, 'Flushed OPcache after file replacement.');

            Artisan::call('migrate', ['--force' => true]);
            $this->appendLog($update, trim(Artisan::output()) !== '' ? trim(Artisan::output()) : 'Database migrations completed.');

            foreach (['optimize:clear', 'storage:link'] as $command) {
                try {
                    Artisan::call($command);
                    $output = trim(Artisan::output());
                    if ($output !== '') {
                        $this->appendLog($update, $output);
                    }
                } catch (\Throwable $exception) {
                    $this->appendLog($update, "{$command} warning: {$exception->getMessage()}");
                }
            }

            Artisan::call('system:doctor', ['--json' => true]);
            $doctorOutput = trim(Artisan::output());
            $this->appendLog($update, 'system:doctor completed.');
            if ($doctorOutput !== '') {
                $this->appendLog($update, $doctorOutput);
            }

            $update->forceFill([
                'status' => 'applied',
                'applied_at' => now(),
                'summary' => "Updated from {$update->version_from} to {$update->version_to} with backup {$backupFilename} and a pre-update recovery snapshot.",
            ])->save();
        } catch (\Throwable $exception) {
            $message = $exception->getMessage();
            $update->forceFill([
                'status' => 'failed',
                'error_message' => $message,
                'summary' => $backupFilename
                    ? "Update failed after backup {$backupFilename}. Use the latest recovery snapshot to return to the last known-good state before retrying."
                    : 'Update failed before backup completed.',
            ])->save();
            $this->appendLog($update, "Update failed: {$message}");
            throw $exception;
        } finally {
            try {
                Artisan::call('up');
                $this->appendLog($update, 'Application exited maintenance mode.');
            } catch (\Throwable $exception) {
                $this->appendLog($update, "Failed to leave maintenance mode automatically: {$exception->getMessage()}");
            }
        }

        return $update->fresh();
    }

    public function restoreSnapshot(SystemUpdate $update): SystemUpdate
    {
        $this->beginLongRunningOperation();
        $this->ensureSchemaReady();

        if (! $this->hasSnapshot($update)) {
            throw new RuntimeException('This update does not have a recovery snapshot available to restore.');
        }

        $installRoot = $this->installRoot();
        $snapshotArchivePath = (string) $update->snapshot_archive_path;
        $backupFilename = (string) $update->backup_filename;
        $restoreStageRoot = $this->makeRestoreStageRoot();
        $protectiveBackupFilename = null;

        if (! File::exists($snapshotArchivePath)) {
            throw new RuntimeException('The recovery snapshot archive is no longer available on disk.');
        }

        if ($backupFilename === '' || ! $this->backupService->path($backupFilename)) {
            throw new RuntimeException('The database backup required for snapshot restore is not available.');
        }

        try {
            $zip = new ZipArchive();
            if ($zip->open($snapshotArchivePath) !== true) {
                throw new RuntimeException('Unable to open the recovery snapshot archive.');
            }

            if (! $this->extractZipSafely($zip, $restoreStageRoot)) {
                $zip->close();
                throw new RuntimeException('The recovery snapshot archive has an invalid or unsafe directory structure.');
            }

            $zip->close();

            $snapshotRoot = $this->resolvePackageRoot($restoreStageRoot);
            $paths = $this->readSnapshotPaths($update);
            $this->assertSnapshotPathsExist($snapshotRoot, $paths);
            $this->assertRestoreTargetsWritable($paths, $installRoot);

            $protectiveBackupFilename = $this->createBackup();
            $this->appendLog($update, "Protective backup created before restore: {$protectiveBackupFilename}.");

            Artisan::call('down');
            $this->appendLog($update, 'Application entered maintenance mode for snapshot restore.');

            $this->restoreDatabaseBackup($backupFilename);
            $this->appendLog($update, "Database restored from {$backupFilename}.");

            $this->restorePathsIntoInstallSafely($snapshotRoot, $paths, $installRoot, 'the recovery snapshot');

            foreach (['optimize:clear', 'storage:link'] as $command) {
                try {
                    Artisan::call($command);
                    $output = trim(Artisan::output());
                    if ($output !== '') {
                        $this->appendLog($update, $output);
                    }
                } catch (\Throwable $exception) {
                    $this->appendLog($update, "{$command} warning: {$exception->getMessage()}");
                }
            }

            Artisan::call('system:doctor', ['--json' => true]);
            $doctorOutput = trim(Artisan::output());
            if ($doctorOutput !== '') {
                $this->appendLog($update, $doctorOutput);
            }

            $update->forceFill([
                'restored_at' => now(),
                'restore_error_message' => null,
                'restore_summary' => "Restored the recovery snapshot captured immediately before the {$update->version_to} update. Data created after {$update->snapshot_created_at?->toDateTimeString()} was discarded.",
            ])->save();
            $this->appendLog($update, 'Recovery snapshot restore completed.');
        } catch (\Throwable $exception) {
            $reverted = false;

            if ($protectiveBackupFilename !== null) {
                try {
                    $this->restoreDatabaseBackup($protectiveBackupFilename);
                    $reverted = true;
                    $this->appendLog($update, "Protective backup {$protectiveBackupFilename} restored after snapshot restore failure.");
                } catch (\Throwable $rollbackException) {
                    $this->appendLog($update, "Protective backup rollback failed: {$rollbackException->getMessage()}");
                    throw new RuntimeException(
                        'Recovery snapshot restore failed and the pre-restore database rollback also failed. Original error: '
                        . $exception->getMessage()
                        . ' Rollback error: '
                        . $rollbackException->getMessage(),
                        0,
                        $exception
                    );
                }
            }

            $message = $reverted
                ? 'Recovery snapshot restore was aborted before completion. The live CRM was returned to its pre-restore state. Original error: ' . $exception->getMessage()
                : $exception->getMessage();

            $update->forceFill([
                'restore_error_message' => $message,
                'restore_summary' => 'Recovery snapshot restore failed before completion. The live CRM was kept on its pre-restore state.',
            ])->save();
            $this->appendLog($update, "Recovery snapshot restore failed: {$message}");
            throw new RuntimeException($message, 0, $exception);
        } finally {
            File::deleteDirectory($restoreStageRoot);

            try {
                Artisan::call('up');
                $this->appendLog($update, 'Application exited maintenance mode.');
            } catch (\Throwable $exception) {
                $this->appendLog($update, "Failed to leave maintenance mode automatically: {$exception->getMessage()}");
            }
        }

        return $update->fresh();
    }

    public function discardPreparedUpdate(SystemUpdate $update): void
    {
        $this->ensureSchemaReady();

        if ($update->status !== 'prepared') {
            throw new RuntimeException('Only prepared updates can be discarded.');
        }

        if (File::isDirectory($update->stage_path)) {
            File::deleteDirectory(dirname($update->stage_path));
        }

        $update->delete();
    }

    public function createManualSnapshot(int $tenantId, ?int $userId = null, ?string $label = null): SystemSnapshot
    {
        $this->ensureSchemaReady();
        $this->ensureDirectories();

        $version = (string) config('app.version', '1.0.0');

        $snapshot = SystemSnapshot::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'label' => filled($label) ? trim($label) : null,
            'version' => $version,
            'status' => 'creating',
            'summary' => 'Queued manual recovery snapshot. Waiting for background processing to begin.',
        ]);

        return $snapshot->fresh();
    }

    public function processManualSnapshot(SystemSnapshot $snapshot, ?callable $progress = null): SystemSnapshot
    {
        $this->beginLongRunningOperation();
        $this->ensureSchemaReady();
        $this->ensureDirectories();

        $snapshot = SystemSnapshot::withoutGlobalScopes()->findOrFail($snapshot->getKey());

        try {
            $this->updateManualSnapshotProgress($snapshot, 'Creating database backup for the manual recovery snapshot.', 'creating', [
                'error_message' => null,
            ]);
            if ($progress !== null) {
                $progress($snapshot->fresh());
            }

            $backupFilename = $this->createBackup();
            $this->updateManualSnapshotProgress($snapshot, 'Database backup created. Capturing environment snapshot.', 'creating', [
                'backup_filename' => $backupFilename,
            ]);
            if ($progress !== null) {
                $progress($snapshot->fresh());
            }

            $envSnapshotPath = $this->snapshotStandaloneEnv($snapshot);
            $this->updateManualSnapshotProgress($snapshot, 'Environment snapshot captured. Building the recovery archive.', 'creating', [
                'env_snapshot_path' => $envSnapshotPath,
            ]);
            if ($progress !== null) {
                $progress($snapshot->fresh());
            }

            [$snapshotArchivePath, $snapshotManifestPath] = $this->createStandaloneSnapshot($snapshot);

            $this->updateManualSnapshotProgress($snapshot, 'Manual recovery snapshot created successfully. Restore it only if you need to return to this exact point in time.', 'ready', [
                'snapshot_archive_path' => $snapshotArchivePath,
                'snapshot_manifest_path' => $snapshotManifestPath,
                'error_message' => null,
            ]);
            if ($progress !== null) {
                $progress($snapshot->fresh());
            }
        } catch (\Throwable $exception) {
            $this->updateManualSnapshotProgress($snapshot, 'Manual recovery snapshot failed before completion.', 'failed', [
                'error_message' => $exception->getMessage(),
            ]);
            if ($progress !== null) {
                $progress($snapshot->fresh());
            }

            throw $exception;
        }

        return $snapshot->fresh();
    }

    private function dispatchManualSnapshotProcess(SystemSnapshot $snapshot): void
    {
        $phpBinary = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
        $artisan = base_path('artisan');
        $snapshotId = (int) $snapshot->getKey();

        if (PHP_OS_FAMILY === 'Windows') {
            $arguments = implode(',', [
                escapeshellarg($artisan),
                escapeshellarg('snapshots:process'),
                escapeshellarg((string) $snapshotId),
                escapeshellarg('--no-interaction'),
            ]);

            $command = 'powershell -NoProfile -Command "Start-Process -FilePath ' . "'" . $phpBinary . "'" . ' -ArgumentList ' . $arguments . ' -WindowStyle Hidden | Out-Null; Start-Sleep -Milliseconds 300"';
            pclose(popen($command, 'r'));
            return;
        }

        $command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($artisan) . ' snapshots:process ' . $snapshotId . ' > /dev/null 2>&1 &';
        exec($command);
    }

    private function updateManualSnapshotProgress(SystemSnapshot $snapshot, string $summary, string $status = 'creating', array $attributes = []): void
    {
        $snapshot->forceFill(array_merge([
            'status' => $status,
            'summary' => $summary,
        ], $attributes))->save();
    }

    public function restoreManualSnapshot(SystemSnapshot $snapshot, ?callable $progress = null): SystemSnapshot
    {
        $this->beginLongRunningOperation();
        $this->ensureSchemaReady();

        $notify = function (string $step) use ($progress): void {
            if ($progress !== null) {
                $progress($step);
            }
        };

        if (! $this->hasManualSnapshot($snapshot)) {
            throw new RuntimeException('This manual recovery snapshot is not available to restore.');
        }

        $notify('validate');

        $restoreStageRoot = $this->makeRestoreStageRoot();
        $protectiveBackupFilename = null;

        try {
            $zip = new ZipArchive();
            if ($zip->open((string) $snapshot->snapshot_archive_path) !== true) {
                throw new RuntimeException('Unable to open the manual recovery snapshot archive.');
            }

            if (! $this->extractZipSafely($zip, $restoreStageRoot)) {
                $zip->close();
                throw new RuntimeException('The manual recovery snapshot archive has an invalid or unsafe directory structure.');
            }

            $zip->close();

            $snapshotRoot = $this->resolvePackageRoot($restoreStageRoot);
            $paths = $this->readStandaloneSnapshotPaths($snapshot);
            $installRoot = $this->installRoot();
            $this->assertSnapshotPathsExist($snapshotRoot, $paths);
            $this->assertRestoreTargetsWritable($paths, $installRoot);

            $notify('backup');
            $protectiveBackupFilename = $this->createBackup();

            $notify('maintenance');
            Artisan::call('down');

            $notify('database');
            $this->restoreDatabaseBackup((string) $snapshot->backup_filename);

            $notify('files');
            $this->restorePathsIntoInstallSafely($snapshotRoot, $paths, $installRoot, 'the manual recovery snapshot');

            $notify('post_restore');
            $this->runPostRestoreMaintenance();

            $snapshot->forceFill([
                'restored_at' => now(),
                'status' => 'restored',
                'summary' => 'Manual recovery snapshot restored successfully. Changes made after this snapshot was created were replaced.',
                'error_message' => null,
            ])->save();

            $notify('done');
        } catch (\Throwable $exception) {
            $message = $exception->getMessage();

            if ($protectiveBackupFilename !== null) {
                $notify('rollback');

                try {
                    $this->restoreDatabaseBackup($protectiveBackupFilename);
                    $message = 'Manual recovery snapshot restore was aborted before completion. The live CRM was returned to its pre-restore state. Original error: ' . $message;
                } catch (\Throwable $rollbackException) {
                    $message = 'Manual recovery snapshot restore failed and the pre-restore database rollback also failed. Original error: '
                        . $message
                        . ' Rollback error: '
                        . $rollbackException->getMessage();
                }
            }

            $snapshot->forceFill([
                'status' => 'failed',
                'summary' => 'Manual recovery snapshot restore failed before completion. The live CRM was kept on its pre-restore state.',
                'error_message' => $message,
            ])->save();

            throw new RuntimeException($message, 0, $exception);
        } finally {
            File::deleteDirectory($restoreStageRoot);
            try {
                Artisan::call('up');
            } catch (\Throwable) {
            }
        }

        return $snapshot->fresh();
    }

    public function historyForTenant(int $tenantId, int $limit = 10)
    {
        if (! $this->schemaReady()) {
            return collect();
        }

        return SystemUpdate::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function preparedUpdateForTenant(int $tenantId): ?SystemUpdate
    {
        if (! $this->schemaReady()) {
            return null;
        }

        return SystemUpdate::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'prepared')
            ->latest()
            ->first();
    }

    public function manualSnapshotsForTenant(int $tenantId, int $limit = 10)
    {
        if (! $this->schemaReady()) {
            return collect();
        }

        $this->markStaleManualSnapshotsAsFailed($tenantId);

        return SystemSnapshot::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function refreshManualSnapshotState(SystemSnapshot $snapshot): SystemSnapshot
    {
        $this->ensureSchemaReady();

        $snapshot = SystemSnapshot::withoutGlobalScopes()->findOrFail($snapshot->getKey());
        $this->markStaleManualSnapshotAsFailed($snapshot);

        return $snapshot->fresh();
    }

    public function markStaleManualSnapshotsAsFailed(?int $tenantId = null, int $staleSeconds = 180): int
    {
        if (! $this->schemaReady()) {
            return 0;
        }

        $cutoff = now()->subSeconds($staleSeconds);
        $query = SystemSnapshot::withoutGlobalScopes()
            ->where('status', 'creating')
            ->where('updated_at', '<', $cutoff);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $count = 0;
        foreach ($query->get() as $snapshot) {
            $this->markStaleManualSnapshotAsFailed($snapshot, false);
            $count++;
        }

        return $count;
    }

    private function markStaleManualSnapshotAsFailed(SystemSnapshot $snapshot, bool $requireAgeCheck = true): void
    {
        $snapshot = SystemSnapshot::withoutGlobalScopes()->findOrFail($snapshot->getKey());

        if ($snapshot->status !== 'creating') {
            return;
        }

        if ($requireAgeCheck && optional($snapshot->updated_at)?->gt(now()->subSeconds(180))) {
            return;
        }

        foreach ([
            $snapshot->snapshot_archive_path,
            $snapshot->snapshot_manifest_path,
            rtrim((string) config('update-manager.snapshot_root'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "snapshot-{$snapshot->id}.zip",
            rtrim((string) config('update-manager.snapshot_root'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "snapshot-{$snapshot->id}.json",
        ] as $path) {
            if (filled($path) && File::exists($path)) {
                File::delete($path);
            }
        }

        foreach (glob(rtrim((string) config('update-manager.snapshot_root'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "snapshot-{$snapshot->id}.zip.*") ?: [] as $tempPath) {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }

        $snapshot->forceFill([
            'status' => 'failed',
            'summary' => 'Manual recovery snapshot failed before completion.',
            'error_message' => 'Snapshot creation did not complete. The request stopped before the recovery archive was finalized.',
            'snapshot_archive_path' => null,
            'snapshot_manifest_path' => null,
        ])->save();
    }

    public function deleteManualSnapshot(SystemSnapshot $snapshot): void
    {
        $this->ensureSchemaReady();

        foreach ([
            $snapshot->snapshot_archive_path,
            $snapshot->snapshot_manifest_path,
            $snapshot->env_snapshot_path,
        ] as $path) {
            if (filled($path) && File::exists($path)) {
                File::delete($path);
            }
        }

        if (filled($snapshot->backup_filename)) {
            $backupPath = $this->backupService->path((string) $snapshot->backup_filename);
            if ($backupPath !== null && File::exists($backupPath)) {
                File::delete($backupPath);
            }
        }

        $snapshot->delete();
    }

    private function ensureDirectories(): void
    {
        foreach ([
            config('update-manager.staging_root'),
            config('update-manager.log_root'),
            config('update-manager.snapshot_root'),
        ] as $directory) {
            if (! File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
        }
    }

    public function schemaReady(): bool
    {
        try {
            return Schema::hasTable('system_updates')
                && Schema::hasTable('system_snapshots')
                && Schema::hasColumns('system_updates', [
                    'snapshot_archive_path',
                    'snapshot_manifest_path',
                    'snapshot_created_at',
                    'restored_at',
                    'restore_summary',
                    'restore_error_message',
                ])
                && Schema::hasColumns('system_snapshots', [
                    'snapshot_archive_path',
                    'snapshot_manifest_path',
                    'backup_filename',
                    'restored_at',
                ]);
        } catch (\Throwable) {
            return false;
        }
    }

    private function ensureSchemaReady(): void
    {
        if (! $this->schemaReady()) {
            throw new RuntimeException('The in-app updater is not available until the latest migrations are applied. Run php artisan migrate --force first.');
        }
    }

    private function installRoot(): string
    {
        return rtrim((string) config('update-manager.install_root', base_path()), DIRECTORY_SEPARATOR);
    }

    private function makeStageRoot(): string
    {
        $stageRoot = rtrim((string) config('update-manager.staging_root'), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'update-' . Str::uuid();

        File::makeDirectory($stageRoot, 0755, true);

        return $stageRoot;
    }

    private function makeRestoreStageRoot(): string
    {
        $restoreRoot = rtrim((string) config('update-manager.staging_root'), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'restore-' . Str::uuid();

        File::makeDirectory($restoreRoot, 0755, true);

        return $restoreRoot;
    }

    private function resolvePackageRoot(string $stageRoot): string
    {
        if (File::exists($stageRoot . DIRECTORY_SEPARATOR . 'VERSION')) {
            return $stageRoot;
        }

        $directories = File::directories($stageRoot);
        foreach ($directories as $directory) {
            if (File::exists($directory . DIRECTORY_SEPARATOR . 'VERSION')) {
                return $directory;
            }
        }

        throw new RuntimeException('The release ZIP does not contain a valid InsulaCRM package root.');
    }

    private function readPackageVersion(string $packageRoot): string
    {
        $versionFile = $packageRoot . DIRECTORY_SEPARATOR . 'VERSION';
        if (! File::exists($versionFile)) {
            throw new RuntimeException('The uploaded package is missing its VERSION file.');
        }

        $version = trim((string) File::get($versionFile));
        if ($version === '') {
            throw new RuntimeException('The uploaded package has an empty VERSION file.');
        }

        return $version;
    }

    private function validatePackageShape(string $packageRoot): void
    {
        $required = [
            'artisan',
            'bootstrap' . DIRECTORY_SEPARATOR . 'app.php',
            'config' . DIRECTORY_SEPARATOR . 'app.php',
            'VERSION',
        ];

        foreach ($required as $path) {
            if (! File::exists($packageRoot . DIRECTORY_SEPARATOR . $path)) {
                throw new RuntimeException("The uploaded package is missing required file {$path}.");
            }
        }
    }

    private function buildWarnings(string $targetVersion): array
    {
        $warnings = [
            "This updater preserves .env, storage/, public/storage, and plugins/ during patching.",
            'A fresh database backup will be created automatically before any files are replaced.',
            'A recovery snapshot is created immediately before patching so you can restore the last known-good state if the upgrade fails badly.',
            'Use recovery snapshots only when necessary. Restoring a snapshot overwrites newer code and database changes created after the snapshot time.',
        ];

        $pluginDirectories = array_values(array_filter(
            File::directories($this->installRoot() . DIRECTORY_SEPARATOR . 'plugins'),
            fn (string $directory) => basename($directory) !== '.gitkeep'
        ));

        if ($pluginDirectories !== []) {
            $warnings[] = 'Installed plugins are preserved. Review plugin compatibility before applying the update.';
        }

        $warnings[] = "Prepared package target version: {$targetVersion}.";

        return $warnings;
    }

    private function createBackup(): string
    {
        $before = array_column($this->backupService->list(), 'name');

        if (! $this->backupService->create()) {
            throw new RuntimeException('Automatic database backup failed. The update was not applied.');
        }

        $after = array_column($this->backupService->list(), 'name');
        $newBackups = array_values(array_diff($after, $before));

        return $newBackups[0] ?? ($after[0] ?? 'unknown-backup');
    }

    private function snapshotEnv(SystemUpdate $update): ?string
    {
        $envPath = $this->installRoot() . DIRECTORY_SEPARATOR . '.env';
        if (! File::exists($envPath)) {
            return null;
        }

        $snapshotPath = rtrim((string) config('update-manager.snapshot_root'), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . "update-{$update->id}.env";

        File::copy($envPath, $snapshotPath);

        return $snapshotPath;
    }

    private function createRecoverySnapshot(SystemUpdate $update): array
    {
        $snapshotRoot = rtrim((string) config('update-manager.snapshot_root'), DIRECTORY_SEPARATOR);
        File::ensureDirectoryExists($snapshotRoot);

        $archivePath = $snapshotRoot . DIRECTORY_SEPARATOR . "update-{$update->id}-snapshot.zip";
        $manifestPath = $snapshotRoot . DIRECTORY_SEPARATOR . "update-{$update->id}-snapshot.json";
        $installRoot = $this->installRoot();

        $paths = $this->snapshotPaths();
        $manifest = [
            'update_id' => $update->id,
            'version_from' => $update->version_from,
            'version_to' => $update->version_to,
            'captured_at' => now()->toIso8601String(),
            'paths' => $paths,
        ];

        $zip = new ZipArchive();
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the recovery snapshot archive.');
        }

        foreach ($paths as $relativePath) {
            $absolutePath = $installRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            if (File::isFile($absolutePath)) {
                $zip->addFile($absolutePath, 'insulacrm/' . $relativePath);
                continue;
            }

            if (File::isDirectory($absolutePath)) {
                $this->addDirectoryToZip($zip, $absolutePath, 'insulacrm/' . $relativePath);
            }
        }

        $zip->addFromString('insulacrm/snapshot-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->close();

        File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return [$archivePath, $manifestPath];
    }

    private function createStandaloneSnapshot(SystemSnapshot $snapshot): array
    {
        $snapshotRoot = rtrim((string) config('update-manager.snapshot_root'), DIRECTORY_SEPARATOR);
        File::ensureDirectoryExists($snapshotRoot);

        $archivePath = $snapshotRoot . DIRECTORY_SEPARATOR . "snapshot-{$snapshot->id}.zip";
        $manifestPath = $snapshotRoot . DIRECTORY_SEPARATOR . "snapshot-{$snapshot->id}.json";
        $installRoot = $this->installRoot();
        $paths = $this->snapshotPaths();
        $manifest = [
            'snapshot_id' => $snapshot->id,
            'label' => $snapshot->label,
            'version' => $snapshot->version,
            'captured_at' => now()->toIso8601String(),
            'paths' => $paths,
        ];

        $zip = new ZipArchive();
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the manual recovery snapshot archive.');
        }

        foreach ($paths as $relativePath) {
            $absolutePath = $installRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            if (File::isFile($absolutePath)) {
                $zip->addFile($absolutePath, 'insulacrm/' . $relativePath);
                continue;
            }

            if (File::isDirectory($absolutePath)) {
                $this->addDirectoryToZip($zip, $absolutePath, 'insulacrm/' . $relativePath);
            }
        }

        $zip->addFromString('insulacrm/snapshot-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->close();

        File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return [$archivePath, $manifestPath];
    }

    private function snapshotPaths(): array
    {
        return array_values(array_unique(array_merge(
            ['.env', 'plugins', 'storage/app/public'],
            (array) config('update-manager.root_files', []),
            (array) config('update-manager.directories', [])
        )));
    }

    private function addDirectoryToZip(ZipArchive $zip, string $sourceDirectory, string $archiveDirectory): void
    {
        $archiveDirectory = trim(str_replace('\\', '/', $archiveDirectory), '/');
        $zip->addEmptyDir($archiveDirectory);

        foreach (File::allDirectories($sourceDirectory) as $directory) {
            $relativeDirectory = ltrim(Str::after($directory, $sourceDirectory), DIRECTORY_SEPARATOR);
            if ($relativeDirectory === '') {
                continue;
            }

            $nestedArchiveDirectory = trim($archiveDirectory . '/' . str_replace('\\', '/', $relativeDirectory), '/');
            $logicalDirectory = rtrim(Str::after($nestedArchiveDirectory, 'insulacrm/'), '/');

            if ($logicalDirectory !== '' && $this->isProtectedPath($logicalDirectory)) {
                continue;
            }

            $zip->addEmptyDir($nestedArchiveDirectory);
        }

        foreach (File::allFiles($sourceDirectory, true) as $file) {
            $relativePath = ltrim(Str::after($file->getPathname(), $sourceDirectory), DIRECTORY_SEPARATOR);
            $archivePath = trim($archiveDirectory . '/' . str_replace('\\', '/', $relativePath), '/');
            $logicalPath = Str::after($archivePath, 'insulacrm/');

            if ($this->isProtectedPath($logicalPath)) {
                continue;
            }

            if (method_exists($file, 'isLink') && $file->isLink()) {
                continue;
            }

            $realPath = $file->getRealPath();
            if ($realPath === false || ! is_file($realPath)) {
                continue;
            }

            $zip->addFile($realPath, $archivePath);
        }
    }
    private function restoreDatabaseBackup(string $backupFilename): void
    {
        $exitCode = Artisan::call('backup:restore', [
            'filename' => $backupFilename,
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            $message = trim(Artisan::output());
            throw new RuntimeException($message !== '' ? $message : 'Database restore failed during recovery snapshot restore.');
        }
    }

    private function restoreSnapshotIntoInstall(string $snapshotRoot, string $installRoot, SystemUpdate $update): void
    {
        $paths = $this->readSnapshotPaths($update);
        $this->restorePathsIntoInstallSafely($snapshotRoot, $paths, $installRoot, 'the recovery snapshot');
    }

    private function restoreStandaloneSnapshotIntoInstall(string $snapshotRoot, SystemSnapshot $snapshot): void
    {
        $paths = $this->readStandaloneSnapshotPaths($snapshot);
        $this->restorePathsIntoInstallSafely($snapshotRoot, $paths, $this->installRoot(), 'the manual recovery snapshot');
    }

    private function restorePathsIntoInstallSafely(string $snapshotRoot, array $paths, string $installRoot, string $contextLabel): void
    {
        $this->assertSnapshotPathsExist($snapshotRoot, $paths);
        $this->assertRestoreTargetsWritable($paths, $installRoot);

        $rollbackRoot = $this->makeRollbackStageRoot();
        $rollbackManifest = $this->captureCurrentInstallState($paths, $installRoot, $rollbackRoot);

        try {
            $this->replaceInstallPathsFromSource($snapshotRoot, $paths, $installRoot, $contextLabel);
            $this->assertCriticalPathsRestored($installRoot);
        } catch (\Throwable $exception) {
            try {
                $this->restoreInstallStateFromRollback($rollbackManifest, $installRoot, $rollbackRoot);
                $this->assertCriticalPathsRestored($installRoot);
                throw new RuntimeException(
                    'Restoring from ' . $contextLabel . ' was aborted before completion. The live application files were restored to their pre-restore state. Original error: ' . $exception->getMessage(),
                    0,
                    $exception
                );
            } catch (\Throwable $rollbackException) {
                if ($rollbackException instanceof RuntimeException && str_contains($rollbackException->getMessage(), 'pre-restore state')) {
                    throw $rollbackException;
                }

                throw new RuntimeException(
                    'Restoring from ' . $contextLabel . ' failed and the live files could not be rolled back automatically. Original error: '
                    . $exception->getMessage()
                    . ' Rollback error: '
                    . $rollbackException->getMessage(),
                    0,
                    $exception
                );
            }
        } finally {
            File::deleteDirectory($rollbackRoot);
        }
    }

    private function replaceInstallPathsFromSource(string $snapshotRoot, array $paths, string $installRoot, string $contextLabel): void
    {
        foreach ($paths as $relativePath) {
            $sourcePath = $snapshotRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $targetPath = $installRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if (File::isDirectory($sourcePath)) {
                $this->syncDirectoryFromSource($sourcePath, $targetPath, $relativePath, $contextLabel);
            } elseif (File::exists($sourcePath)) {
                File::ensureDirectoryExists(dirname($targetPath));
                if (! File::copy($sourcePath, $targetPath)) {
                    throw new RuntimeException("Failed to restore file {$relativePath} from {$contextLabel}.");
                }
            }
        }
    }

    private function captureCurrentInstallState(array $paths, string $installRoot, string $rollbackRoot): array
    {
        $manifest = [];

        foreach ($paths as $relativePath) {
            $targetPath = $installRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $rollbackPath = $rollbackRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if (File::isDirectory($targetPath)) {
                $this->copyDirectoryTreeSkippingProtected($targetPath, $rollbackPath, $relativePath);
                $manifest[$relativePath] = 'directory';
                continue;
            }

            if (File::exists($targetPath)) {
                File::ensureDirectoryExists(dirname($rollbackPath));
                if (! File::copy($targetPath, $rollbackPath)) {
                    throw new RuntimeException("Failed to capture the current file {$relativePath} before restore.");
                }
                $manifest[$relativePath] = 'file';
                continue;
            }

            $manifest[$relativePath] = 'missing';
        }

        return $manifest;
    }

    private function restoreInstallStateFromRollback(array $manifest, string $installRoot, string $rollbackRoot): void
    {
        foreach ($manifest as $relativePath => $type) {
            $targetPath = $installRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $rollbackPath = $rollbackRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if ($type === 'directory') {
                $this->syncDirectoryFromSource($rollbackPath, $targetPath, $relativePath, 'the pre-restore application state');
            } elseif ($type === 'file') {
                if (File::exists($targetPath) || is_link($targetPath)) {
                    $this->deleteFilesystemEntry($targetPath);
                }
                File::ensureDirectoryExists(dirname($targetPath));
                if (! File::copy($rollbackPath, $targetPath)) {
                    throw new RuntimeException("Failed to restore the original file {$relativePath} after restore failure.");
                }
            } elseif (File::exists($targetPath) || is_link($targetPath)) {
                $this->deleteFilesystemEntry($targetPath);
            }
        }
    }

    private function syncDirectoryFromSource(string $sourceDirectory, string $targetDirectory, string $logicalRoot, string $contextLabel): void
    {
        $this->deleteDirectoryContentsSkippingProtected($targetDirectory, $logicalRoot);
        $this->copyDirectoryTreeSkippingProtected($sourceDirectory, $targetDirectory, $logicalRoot);
    }

    private function copyDirectoryTreeSkippingProtected(string $sourceDirectory, string $targetDirectory, string $logicalRoot): void
    {
        File::ensureDirectoryExists($targetDirectory);

        foreach (File::allDirectories($sourceDirectory) as $directory) {
            $relativeDirectory = ltrim(Str::after($directory, $sourceDirectory), DIRECTORY_SEPARATOR);
            if ($relativeDirectory === '') {
                continue;
            }

            $logicalPath = trim($logicalRoot . '/' . str_replace('\\', '/', $relativeDirectory), '/');
            if ($this->isProtectedPath($logicalPath)) {
                continue;
            }

            File::ensureDirectoryExists($targetDirectory . DIRECTORY_SEPARATOR . $relativeDirectory);
        }

        foreach (File::allFiles($sourceDirectory, true) as $file) {
            if (method_exists($file, 'isLink') && $file->isLink()) {
                continue;
            }

            $relativePath = ltrim(Str::after($file->getPathname(), $sourceDirectory), DIRECTORY_SEPARATOR);
            $logicalPath = trim($logicalRoot . '/' . str_replace('\\', '/', $relativePath), '/');
            if ($this->isProtectedPath($logicalPath)) {
                continue;
            }

            $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $relativePath;
            File::ensureDirectoryExists(dirname($targetPath));
            if (! File::copy($file->getPathname(), $targetPath)) {
                throw new RuntimeException("Failed to copy {$logicalPath} while preparing a rollback-safe restore.");
            }
        }
    }

    private function deleteDirectoryContentsSkippingProtected(string $targetDirectory, string $logicalRoot): void
    {
        if (! File::exists($targetDirectory) && ! is_link($targetDirectory)) {
            return;
        }

        $entries = scandir($targetDirectory);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryPath = $targetDirectory . DIRECTORY_SEPARATOR . $entry;
            $logicalPath = trim($logicalRoot . '/' . str_replace('\\', '/', $entry), '/');

            if ($this->isProtectedPath($logicalPath)) {
                continue;
            }

            if (is_dir($entryPath) && ! is_link($entryPath)) {
                $this->deleteDirectoryContentsSkippingProtected($entryPath, $logicalPath);
                if ($this->directoryIsEmpty($entryPath)) {
                    @rmdir($entryPath);
                }
                continue;
            }

            $this->deleteFilesystemEntry($entryPath);
        }
    }

    private function directoryIsEmpty(string $path): bool
    {
        $entries = scandir($path);
        return $entries !== false && count(array_diff($entries, ['.', '..'])) === 0;
    }

    private function deleteFilesystemEntry(string $path): void
    {
        if (is_link($path)) {
            @unlink($path);
            return;
        }

        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
            return;
        }

        if (File::exists($path)) {
            File::delete($path);
        }
    }
    private function flushOpcache(): void
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    private function assertRestoreTargetsWritable(array $paths, string $installRoot): void
    {
        foreach ($paths as $relativePath) {
            $targetPath = $installRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $probePath = File::exists($targetPath) ? $targetPath : dirname($targetPath);

            while (! File::exists($probePath) && dirname($probePath) !== $probePath) {
                $probePath = dirname($probePath);
            }

            if (! is_writable($probePath)) {
                throw new RuntimeException("Restore cannot continue because {$relativePath} is not writable from the current PHP process.");
            }
        }
    }

    private function makeRollbackStageRoot(): string
    {
        $rollbackRoot = rtrim((string) config('update-manager.staging_root'), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'rollback-' . Str::uuid();

        File::makeDirectory($rollbackRoot, 0755, true);

        return $rollbackRoot;
    }

    private function assertSnapshotPathsExist(string $snapshotRoot, array $paths): void
    {
        foreach ($paths as $relativePath) {
            $sourcePath = $snapshotRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if (! File::exists($sourcePath)) {
                throw new RuntimeException("The recovery snapshot is incomplete. Missing path: {$relativePath}.");
            }
        }
    }

    private function assertCriticalPathsRestored(string $installRoot): void
    {
        $criticalPaths = [
            'artisan',
            'public/index.php',
            'routes/web.php',
            'vendor/autoload.php',
        ];

        foreach ($criticalPaths as $relativePath) {
            $absolutePath = $installRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            if (! File::exists($absolutePath)) {
                throw new RuntimeException("Recovery verification failed. Required path {$relativePath} was not restored.");
            }
        }
    }

    private function readSnapshotPaths(SystemUpdate $update): array
    {
        $manifestPath = $update->snapshot_manifest_path;
        if (! $manifestPath || ! File::exists($manifestPath)) {
            return $this->snapshotPaths();
        }

        $manifest = json_decode((string) File::get($manifestPath), true);
        $paths = Arr::wrap($manifest['paths'] ?? []);

        return array_values(array_filter($paths, fn ($path) => is_string($path) && $path !== ''));
    }

    private function readStandaloneSnapshotPaths(SystemSnapshot $snapshot): array
    {
        $manifestPath = $snapshot->snapshot_manifest_path;
        if (! $manifestPath || ! File::exists($manifestPath)) {
            return $this->snapshotPaths();
        }

        $manifest = json_decode((string) File::get($manifestPath), true);
        $paths = Arr::wrap($manifest['paths'] ?? []);

        return array_values(array_filter($paths, fn ($path) => is_string($path) && $path !== ''));
    }

    public function hasSnapshot(SystemUpdate $update): bool
    {
        return filled($update->snapshot_archive_path)
            && File::exists((string) $update->snapshot_archive_path)
            && filled($update->backup_filename)
            && $this->backupService->path((string) $update->backup_filename) !== null;
    }

    public function hasManualSnapshot(SystemSnapshot $snapshot): bool
    {
        return filled($snapshot->snapshot_archive_path)
            && File::exists((string) $snapshot->snapshot_archive_path)
            && filled($snapshot->backup_filename)
            && $this->backupService->path((string) $snapshot->backup_filename) !== null;
    }

    private function snapshotStandaloneEnv(SystemSnapshot $snapshot): ?string
    {
        $envPath = $this->installRoot() . DIRECTORY_SEPARATOR . '.env';
        if (! File::exists($envPath)) {
            return null;
        }

        $snapshotPath = rtrim((string) config('update-manager.snapshot_root'), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . "snapshot-{$snapshot->id}.env";

        File::copy($envPath, $snapshotPath);

        return $snapshotPath;
    }

    private function runPostRestoreMaintenance(): void
    {
        $this->flushOpcache();

        foreach (['optimize:clear', 'storage:link'] as $command) {
            try {
                Artisan::call($command);
            } catch (\Throwable) {
            }
        }

        Artisan::call('system:doctor', ['--json' => true]);
    }

    private function copyReleaseIntoInstall(string $packageRoot, string $installRoot, SystemUpdate $update): void
    {
        foreach ((array) config('update-manager.root_files', []) as $file) {
            $source = $packageRoot . DIRECTORY_SEPARATOR . $file;
            if (! File::exists($source)) {
                continue;
            }

            $target = $installRoot . DIRECTORY_SEPARATOR . $file;
            File::ensureDirectoryExists(dirname($target));
            File::copy($source, $target);
            $this->appendLog($update, "Updated {$file}.");
        }

        foreach ((array) config('update-manager.directories', []) as $directory) {
            $sourceDirectory = $packageRoot . DIRECTORY_SEPARATOR . $directory;
            if (! File::isDirectory($sourceDirectory)) {
                continue;
            }

            $targetDirectory = $installRoot . DIRECTORY_SEPARATOR . $directory;
            $this->copyDirectoryContents($sourceDirectory, $targetDirectory, $directory, $update);
        }
    }

    private function copyDirectoryContents(string $sourceDirectory, string $targetDirectory, string $logicalRoot, SystemUpdate $update): void
    {
        File::ensureDirectoryExists($targetDirectory);

        foreach (File::allFiles($sourceDirectory, true) as $file) {
            $relativePath = ltrim(Str::after($file->getPathname(), $sourceDirectory), DIRECTORY_SEPARATOR);
            $logicalPath = trim($logicalRoot . '/' . str_replace('\\', '/', $relativePath), '/');

            if ($this->isProtectedPath($logicalPath)) {
                continue;
            }

            $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $relativePath;
            File::ensureDirectoryExists(dirname($targetPath));
            File::copy($file->getPathname(), $targetPath);
        }

        $this->appendLog($update, "Updated {$logicalRoot}/");
    }

    private function isProtectedPath(string $logicalPath): bool
    {
        $normalized = trim(str_replace('\\', '/', $logicalPath), '/');

        foreach ((array) config('update-manager.protected_paths', []) as $protectedPath) {
            $protected = trim(str_replace('\\', '/', $protectedPath), '/');
            if ($normalized === $protected || str_starts_with($normalized, $protected . '/')) {
                return true;
            }
        }

        return false;
    }

    private function appendLog(SystemUpdate $update, string $message): void
    {
        $logRoot = rtrim((string) config('update-manager.log_root'), DIRECTORY_SEPARATOR);
        File::ensureDirectoryExists($logRoot);
        $logPath = $logRoot . DIRECTORY_SEPARATOR . "update-{$update->id}.log";
        File::append($logPath, '[' . now()->toDateTimeString() . "] {$message}\n");
    }

    private function extractZipSafely(ZipArchive $zip, string $destination): bool
    {
        File::makeDirectory($destination, 0755, true, true);
        $destinationRoot = rtrim(str_replace('\\', '/', realpath($destination) ?: $destination), '/');

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->statIndex($i);
            if (! $entry || empty($entry['name'])) {
                return false;
            }

            $entryName = str_replace('\\', '/', $entry['name']);
            $normalized = trim($entryName, '/');

            if ($normalized === '') {
                continue;
            }

            $segments = array_filter(explode('/', $normalized), fn ($segment) => $segment !== '.');
            if (str_starts_with($entryName, '/') || preg_match('/^[A-Za-z]:\//', $entryName) || in_array('..', $segments, true)) {
                return false;
            }

            $targetPath = $destinationRoot . '/' . implode('/', $segments);
            if (! str_starts_with(str_replace('\\', '/', $targetPath), $destinationRoot . '/')) {
                return false;
            }

            if (str_ends_with($entryName, '/')) {
                File::makeDirectory($targetPath, 0755, true, true);
                continue;
            }

            File::makeDirectory(dirname($targetPath), 0755, true, true);
            $stream = $zip->getStream($entry['name']);
            if ($stream === false) {
                return false;
            }

            $contents = stream_get_contents($stream);
            fclose($stream);

            if ($contents === false) {
                return false;
            }

            file_put_contents($targetPath, $contents);
        }

        return true;
    }
}










