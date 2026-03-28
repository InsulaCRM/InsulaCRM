<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SystemSnapshot;
use App\Models\SystemUpdate;
use App\Services\UpdateManagerService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UpdateController extends Controller
{
    private function respond(Request $request, string $message, string $level = 'success', array $extra = [], ?int $statusCode = null)
    {
        $redirectUrl = route('settings.index', ['tab' => 'system']);

        if ($request->expectsJson()) {
            return response()->json(array_merge([
                'success' => $level === 'success',
                'level' => $level,
                'message' => $message,
                'redirect_url' => $redirectUrl,
            ], $extra), $statusCode ?? ($level === 'success' ? 200 : 422));
        }

        return redirect($redirectUrl)->with($level, $message);
    }

    private function snapshotPayload(SystemSnapshot $snapshot): array
    {
        $snapshot->refresh();

        $details = match ($snapshot->status) {
            'creating' => array_values(array_filter([
                'Snapshot record created.',
                $snapshot->backup_filename ? 'Database backup completed.' : 'Database backup is currently running.',
                $snapshot->env_snapshot_path ? 'Environment snapshot captured.' : null,
                $snapshot->snapshot_archive_path ? 'Recovery archive completed.' : 'Recovery archive is still being built.',
            ])),
            'ready' => [
                'Database backup is available.',
                'Recovery archive is available.',
                'Snapshot creation finished successfully.',
            ],
            'failed' => array_values(array_filter([
                'Snapshot creation failed.',
                $snapshot->error_message,
            ])),
            'restored' => [
                'The snapshot was restored successfully.',
            ],
            default => [],
        };

        return [
            'id' => $snapshot->id,
            'status' => $snapshot->status,
            'summary' => $snapshot->summary,
            'error_message' => $snapshot->error_message,
            'backup_filename' => $snapshot->backup_filename,
            'updated_at' => optional($snapshot->updated_at)?->toIso8601String(),
            'status_url' => route('settings.snapshots.status', $snapshot),
            'is_terminal' => in_array($snapshot->status, ['ready', 'failed', 'restored'], true),
            'details' => $details,
            'redirect_url' => route('settings.index', ['tab' => 'system']),
        ];
    }

    private function streamSnapshotProgress(SystemSnapshot $snapshot, UpdateManagerService $updates): StreamedResponse
    {
        return response()->stream(function () use ($snapshot, $updates) {
            $send = function (array $payload): void {
                echo json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n";
                @ob_flush();
                flush();
            };

            $send(array_merge(['success' => true, 'message' => 'Manual recovery snapshot started.'], $this->snapshotPayload($snapshot)));

            try {
                $finalSnapshot = $updates->processManualSnapshot($snapshot, function (SystemSnapshot $progressSnapshot) use ($send): void {
                    $send(array_merge(['success' => true], $this->snapshotPayload($progressSnapshot)));
                });

                AuditLog::log('system.snapshot_created', $finalSnapshot, null, [
                    'version' => $finalSnapshot->version,
                    'backup_filename' => $finalSnapshot->backup_filename,
                ]);

                $send(array_merge(['success' => true, 'message' => 'Manual recovery snapshot created successfully.'], $this->snapshotPayload($finalSnapshot)));
            } catch (\Throwable $exception) {
                $send(array_merge([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], $this->snapshotPayload($snapshot)));
            }
        }, 200, [
            'Content-Type' => 'application/x-ndjson',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function upload(Request $request, UpdateManagerService $updates)
    {
        $request->validate([
            'release_zip' => 'required|file|mimes:zip|max:' . config('update-manager.max_upload_kb', 512000),
        ]);

        $tenant = $request->user()->tenant;

        try {
            $update = $updates->prepareUpload($request->file('release_zip'), $tenant->id, $request->user()->id);
            AuditLog::log('system.update_prepared', $update, null, [
                'version_from' => $update->version_from,
                'version_to' => $update->version_to,
            ]);

            return $this->respond($request, "Prepared update {$update->version_to}. Review the warnings, then apply it when ready.");
        } catch (\Throwable $exception) {
            return $this->respond($request, $exception->getMessage(), 'error');
        }
    }

    public function apply(Request $request, SystemUpdate $update, UpdateManagerService $updates)
    {
        try {
            $applied = $updates->applyPreparedUpdate($update);
            AuditLog::log('system.update_applied', $applied, null, [
                'version_from' => $applied->version_from,
                'version_to' => $applied->version_to,
                'backup_filename' => $applied->backup_filename,
            ]);

            return $this->respond($request, "Updated to {$applied->version_to}. Backup {$applied->backup_filename} was created before the patch.");
        } catch (\Throwable $exception) {
            return $this->respond($request, $exception->getMessage(), 'error');
        }
    }

    public function discard(SystemUpdate $update, UpdateManagerService $updates)
    {
        try {
            $version = $update->version_to;
            $updates->discardPreparedUpdate($update);

            return redirect()->route('settings.index', ['tab' => 'system'])
                ->with('success', "Discarded prepared update {$version}.");
        } catch (\Throwable $exception) {
            return redirect()->route('settings.index', ['tab' => 'system'])
                ->with('error', $exception->getMessage());
        }
    }

    public function restore(Request $request, SystemUpdate $update, UpdateManagerService $updates)
    {
        try {
            $restored = $updates->restoreSnapshot($update);
            AuditLog::log('system.update_snapshot_restored', $restored, null, [
                'version_from' => $restored->version_from,
                'version_to' => $restored->version_to,
                'backup_filename' => $restored->backup_filename,
                'snapshot_created_at' => optional($restored->snapshot_created_at)?->toIso8601String(),
            ]);

            return $this->respond($request, "Restored the recovery snapshot captured before {$restored->version_to}. Data created after that snapshot time was replaced.");
        } catch (\Throwable $exception) {
            return $this->respond($request, $exception->getMessage(), 'error');
        }
    }

    public function createSnapshot(Request $request, UpdateManagerService $updates)
    {
        $request->validate([
            'label' => 'nullable|string|max:120',
        ]);

        $tenant = $request->user()->tenant;

        try {
            $snapshot = $updates->createManualSnapshot($tenant->id, $request->user()->id, $request->string('label')->toString());

            if ($request->expectsJson()) {
                return $this->streamSnapshotProgress($snapshot, $updates);
            }

            $finalSnapshot = $updates->processManualSnapshot($snapshot);
            AuditLog::log('system.snapshot_created', $finalSnapshot, null, [
                'version' => $finalSnapshot->version,
                'backup_filename' => $finalSnapshot->backup_filename,
            ]);

            return $this->respond($request, 'Manual recovery snapshot created successfully.');
        } catch (\Throwable $exception) {
            return $this->respond($request, $exception->getMessage(), 'error');
        }
    }

    public function snapshotStatus(SystemSnapshot $snapshot, UpdateManagerService $updates)
    {
        return response()->json($this->snapshotPayload($updates->refreshManualSnapshotState($snapshot)));
    }

    public function restoreManualSnapshot(Request $request, SystemSnapshot $snapshot, UpdateManagerService $updates)
    {
        if ($request->expectsJson()) {
            return $this->streamRestoreProgress($snapshot, $updates);
        }

        try {
            $restored = $updates->restoreManualSnapshot($snapshot);
            AuditLog::log('system.snapshot_restored', $restored, null, [
                'version' => $restored->version,
                'backup_filename' => $restored->backup_filename,
            ]);

            return $this->respond($request, 'Manual recovery snapshot restored. Changes created after that snapshot were replaced.');
        } catch (\Throwable $exception) {
            return $this->respond($request, $exception->getMessage(), 'error');
        }
    }

    private function streamRestoreProgress(SystemSnapshot $snapshot, UpdateManagerService $updates): StreamedResponse
    {
        return response()->stream(function () use ($snapshot, $updates) {
            $redirectUrl = route('settings.index', ['tab' => 'system']);

            $send = function (array $payload): void {
                echo json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n";
                @ob_flush();
                flush();
            };

            $send([
                'success' => true,
                'step' => 'start',
                'message' => __('Starting snapshot restore.'),
                'redirect_url' => $redirectUrl,
                'is_terminal' => false,
            ]);

            try {
                $restored = $updates->restoreManualSnapshot($snapshot, function (string $step) use ($send, $redirectUrl): void {
                    $send([
                        'success' => true,
                        'step' => $step,
                        'message' => $this->restoreStepMessage($step),
                        'redirect_url' => $redirectUrl,
                        'is_terminal' => $step === 'done',
                    ]);
                });

                AuditLog::log('system.snapshot_restored', $restored, null, [
                    'version' => $restored->version,
                    'backup_filename' => $restored->backup_filename,
                ]);

                $send([
                    'success' => true,
                    'step' => 'done',
                    'message' => __('Manual recovery snapshot restored successfully.'),
                    'redirect_url' => $redirectUrl,
                    'is_terminal' => true,
                ]);
            } catch (\Throwable $exception) {
                $send([
                    'success' => false,
                    'step' => 'failed',
                    'message' => $exception->getMessage(),
                    'redirect_url' => $redirectUrl,
                    'is_terminal' => true,
                ]);
            }
        }, 200, [
            'Content-Type' => 'application/x-ndjson',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function restoreStepMessage(string $step): string
    {
        return match ($step) {
            'validate' => __('Validating the snapshot archive and checking file permissions.'),
            'backup' => __('Creating a protective database backup before restoring.'),
            'maintenance' => __('Putting the CRM into maintenance mode.'),
            'database' => __('Restoring the database backup captured with this snapshot.'),
            'files' => __('Copying snapshot files back into the install directory.'),
            'post_restore' => __('Running post-restore maintenance (caches, migrations).'),
            'done' => __('Manual recovery snapshot restored successfully.'),
            'rollback' => __('Restore failed. Rolling back to the pre-restore database state.'),
            default => __('Processing restore step.'),
        };
    }

    public function deleteManualSnapshot(Request $request, SystemSnapshot $snapshot, UpdateManagerService $updates)
    {
        try {
            $label = $snapshot->label ?: 'Manual snapshot';
            $version = $snapshot->version;
            $backupFilename = $snapshot->backup_filename;

            $updates->deleteManualSnapshot($snapshot);
            AuditLog::log('system.snapshot_deleted', null, null, [
                'label' => $label,
                'version' => $version,
                'backup_filename' => $backupFilename,
            ]);

            return $this->respond($request, "Deleted snapshot {$label} ({$version}).");
        } catch (\Throwable $exception) {
            return $this->respond($request, $exception->getMessage(), 'error');
        }
    }
}




