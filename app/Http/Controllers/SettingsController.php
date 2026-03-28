<?php

namespace App\Http\Controllers;

use App\Http\Requests\DistributionSettingsRequest;
use App\Http\Requests\GeneralSettingsRequest;
use App\Models\AuditLog;
use App\Models\CustomFieldDefinition;
use App\Models\LeadSourceCost;
use App\Models\Permission;
use App\Models\Plugin;
use App\Models\Role;
use App\Models\User;
use App\Services\BusinessModeService;
use App\Services\CustomFieldService;
use App\Services\UpdateManagerService;
use App\Services\Settings\BackupService;
use App\Services\Settings\LanguageFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Notifications\TeamMemberInvited;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function __construct(
        private readonly BackupService $backupService,
        private readonly LanguageFileService $languageFileService,
    ) {
    }

    public function index()
    {
        $tenant = auth()->user()->tenant;
        $teamMembers = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('id', '!=', auth()->id())
            ->with('role')
            ->get();

        $roles = Role::where(function ($q) use ($tenant) {
            $q->where('is_system', true)->orWhere('tenant_id', $tenant->id);
        })->get();
        $modeRoles = \App\Services\BusinessModeService::isRealEstate($tenant)
            ? \App\Services\BusinessModeService::REALESTATE_ROLES
            : \App\Services\BusinessModeService::WHOLESALE_ROLES;
        $roles = $roles->filter(fn($role) => in_array($role->name, $modeRoles));
        $leadSourceCosts = LeadSourceCost::where('tenant_id', $tenant->id)->pluck('monthly_budget', 'lead_source');
        $webhooks = \App\Models\Webhook::where('tenant_id', $tenant->id)->latest()->get();
        $updateManager = app(UpdateManagerService::class);
        $updateManagerReady = $updateManager->schemaReady();
        $preparedUpdate = $updateManager->preparedUpdateForTenant($tenant->id);
        $updateHistory = $updateManager->historyForTenant($tenant->id);
        $manualSnapshots = $updateManager->manualSnapshotsForTenant($tenant->id);

        // For backward compat, pass as both 'agents' and 'teamMembers'
        $agents = $teamMembers;
        return view('settings.index', compact('tenant', 'agents', 'teamMembers', 'roles', 'leadSourceCosts', 'webhooks', 'preparedUpdate', 'updateHistory', 'manualSnapshots', 'updateManagerReady'));
    }

    public function updateGeneral(GeneralSettingsRequest $request)
    {
        $tenant = auth()->user()->tenant;

        $data = $request->only(['name', 'timezone', 'currency', 'date_format', 'country', 'measurement_system', 'locale']);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_path'] = $path;
        }

        $tenant->update($data);

        AuditLog::log('settings.updated', $tenant);

        return redirect()->route('settings.index', ['tab' => 'general'])->with('success', 'Settings updated.');
    }

    public function updateStorage(Request $request)
    {
        $request->validate([
            'storage_disk' => 'required|in:local,s3',
            's3_key' => 'nullable|string|max:255',
            's3_secret' => 'nullable|string|max:255',
            's3_region' => 'nullable|string|max:100',
            's3_bucket' => 'nullable|string|max:255',
            's3_url' => 'nullable|url|max:500',
        ]);

        $tenant = auth()->user()->tenant;
        $options = $tenant->custom_options ?? [];

        if ($request->storage_disk === 's3') {
            $options['s3_key'] = $request->s3_key;
            // Only update secret if a new one was provided
            if ($request->filled('s3_secret')) {
                $options['s3_secret'] = encrypt($request->s3_secret);
            }
            $options['s3_region'] = $request->s3_region ?? 'us-east-1';
            $options['s3_bucket'] = $request->s3_bucket;
            $options['s3_url'] = $request->s3_url;
        }

        $tenant->update([
            'storage_disk' => $request->storage_disk,
            'custom_options' => $options,
        ]);

        AuditLog::log('settings.storage_updated', $tenant, ['disk' => $request->storage_disk]);

        return redirect()->route('settings.index', ['tab' => 'storage'])->with('success', __('Storage settings updated.'));
    }

    /**
     * Test S3 connection with the provided or saved credentials.
     */
    public function testS3Connection(Request $request)
    {
        $request->validate([
            's3_key' => 'required|string',
            's3_region' => 'required|string',
            's3_bucket' => 'required|string',
            's3_secret' => 'nullable|string',
            's3_url' => 'nullable|string',
        ]);

        // Check if the S3 driver package is installed
        if (! class_exists(\Aws\S3\S3Client::class)) {
            return response()->json([
                'success' => false,
                'message' => __('The S3 storage driver is missing from this deployment. Run composer install to install the packaged dependencies, then try again.'),
            ], 422);
        }

        $tenant = auth()->user()->tenant;
        $options = $tenant->custom_options ?? [];

        // Use provided secret, or fall back to saved encrypted secret
        $secret = $request->s3_secret;
        if (empty($secret) && ! empty($options['s3_secret'])) {
            try {
                $secret = decrypt($options['s3_secret']);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => __('Saved secret could not be decrypted. Please re-enter your Secret Access Key.'),
                ], 422);
            }
        }

        if (empty($secret)) {
            return response()->json([
                'success' => false,
                'message' => __('Secret Access Key is required to test the connection.'),
            ], 422);
        }

        try {
            $config = [
                'driver' => 's3',
                'key' => $request->s3_key,
                'secret' => $secret,
                'region' => $request->s3_region,
                'bucket' => $request->s3_bucket,
                'throw' => true,
            ];

            if ($request->filled('s3_url')) {
                $config['endpoint'] = $request->s3_url;
                $config['use_path_style_endpoint'] = true;
            }

            // Build a temporary S3 disk and attempt to write/read/delete a test file
            $disk = \Illuminate\Support\Facades\Storage::build($config);

            $testFile = '.insulacrm-connection-test-' . uniqid();
            $disk->put($testFile, 'ok');
            $content = $disk->get($testFile);
            $disk->delete($testFile);

            if ($content !== 'ok') {
                return response()->json([
                    'success' => false,
                    'message' => __('Connected but read/write verification failed.'),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => __('Connection successful! Bucket is accessible and writable.'),
            ]);
        } catch (\Exception $e) {
            $msg = $e->getMessage();

            // Provide friendlier messages for common errors
            if (str_contains($msg, 'InvalidAccessKeyId') || str_contains($msg, 'SignatureDoesNotMatch')) {
                $msg = __('Invalid credentials. Check your Access Key and Secret.');
            } elseif (str_contains($msg, 'NoSuchBucket')) {
                $msg = __('Bucket does not exist. Check the bucket name and region.');
            } elseif (str_contains($msg, 'AccessDenied')) {
                $msg = __('Access denied. The credentials lack permission for this bucket.');
            } elseif (str_contains($msg, 'Could not resolve host') || str_contains($msg, 'cURL error')) {
                $msg = __('Could not reach the S3 endpoint. Check the URL and your network.');
            }

            return response()->json([
                'success' => false,
                'message' => $msg,
            ], 422);
        }
    }

    /**
     * Send a test SMS to verify Twilio configuration.
     */
    public function testSms(Request $request)
    {
        $request->validate([
            'to' => 'required|string',
        ]);

        $tenant = auth()->user()->tenant;
        $manager = app(\App\Integrations\IntegrationManager::class);
        $provider = $manager->getSmsProvider($tenant->id);

        if ($provider->driver() === 'log') {
            return response()->json([
                'success' => false,
                'message' => __('No SMS provider is active. Configure and enable a provider first.'),
            ], 422);
        }

        try {
            $result = $provider->send(
                $request->to,
                __('This is a test message from :app.', ['app' => $tenant->name ?? 'InsulaCRM'])
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => __('Test SMS sent successfully to :number.', ['number' => $request->to]),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => __('SMS sending failed. Check your provider credentials and the application log for details.'),
            ], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('SMS test failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => __('SMS sending failed. Check your provider credentials and the application log for details.'),
            ], 422);
        }
    }

    public function backupList()
    {
        return response()->json(['backups' => $this->backupService->list()]);
    }

    public function backupCreate()
    {
        if ($this->backupService->create()) {
            AuditLog::log('backup.created', auth()->user()->tenant);
            return response()->json(['success' => true, 'message' => __('Backup created successfully.'), 'backups' => $this->backupService->list()]);
        }

        return response()->json(['success' => false, 'message' => __('Backup failed. Check server logs for details.')], 500);
    }

    public function backupDownload(string $filename)
    {
        $filepath = $this->backupService->path($filename);
        if (! $filepath) {
            abort(404);
        }

        return response()->download($filepath);
    }

    public function backupDelete(string $filename)
    {
        if (! $this->backupService->delete($filename)) {
            return response()->json(['success' => false, 'message' => __('File not found.')], 404);
        }

        AuditLog::log('backup.deleted', auth()->user()->tenant, ['file' => basename($filename)]);

        return response()->json(['success' => true, 'message' => __('Backup deleted.'), 'backups' => $this->backupService->list()]);
    }

    public function apiLogs()
    {
        $logs = DB::table('api_logs')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(function ($log) {
                return [
                    'method' => $log->method,
                    'path' => $log->path,
                    'status' => $log->status_code,
                    'ip' => $log->ip_address,
                    'duration' => $log->duration_ms . 'ms',
                    'date' => \Carbon\Carbon::parse($log->created_at)->diffForHumans(),
                ];
            });

        return response()->json(['logs' => $logs]);
    }

    public function inviteAgent(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $modeRoleNames = \App\Services\BusinessModeService::getRoles($tenant);
        $allowedRoleIds = Role::where(function ($q) use ($tenant, $modeRoleNames) {
            $q->where(function ($q2) use ($modeRoleNames) {
                $q2->where('is_system', true)->whereIn('name', $modeRoleNames);
            })->orWhere('tenant_id', $tenant->id);
        })->pluck('id')->toArray();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => ['required', 'exists:roles,id', \Illuminate\Validation\Rule::in($allowedRoleIds)],
        ]);

        $agent = User::create([
            'tenant_id' => auth()->user()->tenant_id,
            'role_id' => $request->role_id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        AuditLog::log('agent.invited', $agent);

        // Notify the new team member
        $tenant = auth()->user()->tenant;
        if ($tenant->wantsNotification('team_member_invited')) {
            $roleName = Role::find($request->role_id)->name ?? 'agent';
            $agent->notify(new TeamMemberInvited($tenant, $roleName));
        }

        return redirect()->route('settings.index', ['tab' => 'team'])->with('success', 'Team member added successfully.');
    }

    public function toggleAgent(User $user)
    {
        $this->authorize('manageTeamMember', $user);

        $user->update(['is_active' => !$user->is_active]);

        AuditLog::log('agent.toggled', $user);

        return redirect()->route('settings.index', ['tab' => 'team'])->with('success', 'Agent status updated.');
    }

    public function reset2fa(User $user)
    {
        $this->authorize('manageTeamMember', $user);

        if (!$user->two_factor_enabled) {
            return redirect()->route('settings.index', ['tab' => 'team'])->with('error', __('This user does not have 2FA enabled.'));
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled' => false,
            'two_factor_provider' => 'totp',
        ]);

        AuditLog::log('2fa.reset', $user);

        return redirect()->route('settings.index', ['tab' => 'team'])->with('success', __('2FA has been reset for :name.', ['name' => $user->name]));
    }

    public function updateDistribution(DistributionSettingsRequest $request)
    {
        $tenant = auth()->user()->tenant;
        $tenant->update([
            'distribution_method' => $request->distribution_method,
            'claim_window_minutes' => $request->claim_window_minutes ?? 3,
            'timezone_restriction_enabled' => $request->boolean('timezone_restriction_enabled'),
        ]);

        AuditLog::log('settings.distribution_updated', $tenant);

        $label = BusinessModeService::isRealEstate() ? 'Lead routing' : 'Distribution';
        return redirect()->route('settings.index', ['tab' => 'distribution'])->with('success', "{$label} settings updated.");
    }

    public function updateLeadSourceCosts(Request $request)
    {
        $request->validate([
            'costs' => 'required|array',
            'costs.*' => 'nullable|numeric|min:0',
        ]);

        $tenantId = auth()->user()->tenant_id;

        foreach ($request->costs as $source => $budget) {
            LeadSourceCost::updateOrCreate(
                ['tenant_id' => $tenantId, 'lead_source' => $source],
                ['monthly_budget' => $budget ?? 0]
            );
        }

        AuditLog::log('settings.lead_source_costs_updated');

        return redirect()->route('settings.index', ['tab' => 'lead-costs'])->with('success', 'Lead source costs updated.');
    }

    public function addLeadSource(Request $request)
    {
        $request->validate([
            'lead_source_name' => 'required|string|max:100',
        ]);

        $tenant = auth()->user()->tenant;
        $customSources = $tenant->custom_lead_sources ?? [];

        $slug = str_replace(' ', '_', strtolower(trim($request->lead_source_name)));

        // Prevent duplicates against built-in and existing custom sources
        $builtIn = array_keys(CustomFieldService::getDefaults('lead_source'));
        $existingSlugs = array_column($customSources, 'slug');

        if (in_array($slug, $builtIn) || in_array($slug, $existingSlugs)) {
            return redirect()->route('settings.index', ['tab' => 'lead-costs'])->with('error', 'That lead source already exists.');
        }

        $customSources[] = [
            'slug' => $slug,
            'name' => trim($request->lead_source_name),
        ];

        $tenant->update(['custom_lead_sources' => $customSources]);

        AuditLog::log('settings.lead_source_added', $tenant);

        return redirect()->route('settings.index', ['tab' => 'lead-costs'])->with('success', 'Lead source added successfully.');
    }

    public function removeLeadSource(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
        ]);

        $tenant = auth()->user()->tenant;
        $customSources = $tenant->custom_lead_sources ?? [];

        $customSources = array_values(array_filter($customSources, function ($source) use ($request) {
            return $source['slug'] !== $request->slug;
        }));

        $tenant->update(['custom_lead_sources' => $customSources]);

        AuditLog::log('settings.lead_source_removed', $tenant);

        return redirect()->route('settings.index', ['tab' => 'lead-costs'])->with('success', 'Lead source removed.');
    }

    public function addCustomOption(Request $request)
    {
        $request->validate([
            'field_type' => 'required|in:' . implode(',', array_keys(CustomFieldService::getFieldTypes())),
            'option_name' => 'required|string|max:100',
        ]);

        $tenant = auth()->user()->tenant;
        $result = CustomFieldService::addOption($request->field_type, $request->option_name, $tenant);

        if (!$result['success']) {
            return redirect()->route('settings.index', ['tab' => 'custom-fields'])->with('error', $result['message']);
        }

        AuditLog::log('settings.custom_option_added', $tenant);

        return redirect()->route('settings.index', ['tab' => 'custom-fields'])->with('success', 'Custom option added.');
    }

    public function removeCustomOption(Request $request)
    {
        $request->validate([
            'field_type' => 'required|string',
            'slug' => 'required|string',
        ]);

        $tenant = auth()->user()->tenant;
        $removed = CustomFieldService::removeOption($request->field_type, $request->slug, $tenant);

        if (!$removed) {
            return redirect()->route('settings.index', ['tab' => 'custom-fields'])->with('error', 'System defaults cannot be removed.');
        }

        AuditLog::log('settings.custom_option_removed', $tenant);

        return redirect()->route('settings.index', ['tab' => 'custom-fields'])->with('success', 'Custom option removed.');
    }

    /**
     * Store a new custom field definition.
     */
    public function storeCustomField(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'entity_type' => 'required|in:lead',
            'field_type' => 'required|in:text,textarea,number,date,select,checkbox',
            'options' => 'nullable|string|max:1000',
            'required' => 'nullable|boolean',
        ]);

        $tenant = auth()->user()->tenant;
        $slug = str_replace(' ', '_', strtolower(trim($request->name)));
        $slug = preg_replace('/[^a-z0-9_]/', '', $slug);

        if (CustomFieldDefinition::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('entity_type', $request->entity_type)->where('slug', $slug)->exists()) {
            return redirect()->route('settings.index', ['tab' => 'custom-fields'])->with('error', __('A custom field with this name already exists.'));
        }

        $options = null;
        if ($request->field_type === 'select' && $request->filled('options')) {
            $options = array_map('trim', explode(',', $request->options));
            $options = array_values(array_filter($options));
        }

        $maxOrder = CustomFieldDefinition::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('entity_type', $request->entity_type)->max('sort_order') ?? 0;

        CustomFieldDefinition::create([
            'tenant_id' => $tenant->id,
            'entity_type' => $request->entity_type,
            'name' => trim($request->name),
            'slug' => $slug,
            'field_type' => $request->field_type,
            'options' => $options,
            'required' => $request->boolean('required'),
            'sort_order' => $maxOrder + 1,
        ]);

        AuditLog::log('settings.custom_field_created', $tenant);

        return redirect()->route('settings.index', ['tab' => 'custom-fields'])->with('success', __('Custom field created.'));
    }

    /**
     * Delete a custom field definition.
     */
    public function destroyCustomField(CustomFieldDefinition $customField)
    {
        if ($customField->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $customField->delete();
        AuditLog::log('settings.custom_field_deleted', auth()->user()->tenant);

        return redirect()->route('settings.index', ['tab' => 'custom-fields'])->with('success', __('Custom field removed.'));
    }

    /**
     * Generate or regenerate the tenant API key.
     */
    public function generateApiKey()
    {
        $tenant = auth()->user()->tenant;
        $tenant->update([
            'api_key' => bin2hex(random_bytes(32)),
            'api_enabled' => true,
        ]);

        AuditLog::log('settings.api_key_generated', $tenant);

        return redirect()->route('settings.index', ['tab' => 'api'])->with('success', 'API key generated successfully.');
    }

    /**
     * Enable or disable API access.
     */
    public function toggleApi()
    {
        $tenant = auth()->user()->tenant;
        $tenant->update([
            'api_enabled' => !$tenant->api_enabled,
        ]);

        AuditLog::log('settings.api_toggled', $tenant);

        $status = $tenant->api_enabled ? 'enabled' : 'disabled';
        return redirect()->route('settings.index', ['tab' => 'api'])->with('success', "API access {$status}.");
    }

    /**
     * Update AI configuration settings.
     */
    public function updateAiSettings(Request $request)
    {
        $request->validate([
            'ai_provider' => 'required|in:openai,anthropic,gemini,ollama,custom',
            'ai_api_key' => 'nullable|string|max:500',
            'ai_model' => 'nullable|string|max:100',
            'ai_model_manual' => 'nullable|string|max:100',
            'ai_ollama_url' => 'nullable|string|max:255',
            'ai_custom_url' => 'nullable|string|max:255',
        ]);

        $tenant = auth()->user()->tenant;

        // Use whichever model field was active (dropdown or manual input)
        $model = $request->ai_model ?: ($request->ai_model_manual ?: null);

        $data = [
            'ai_provider' => $request->ai_provider,
            'ai_model' => $model,
            'ai_ollama_url' => $request->ai_ollama_url ?: null,
            'ai_custom_url' => $request->ai_custom_url ?: null,
            'ai_enabled' => true,
        ];

        // Only update API key if provided (don't clear existing key on empty submit)
        if ($request->filled('ai_api_key')) {
            $data['ai_api_key'] = $request->ai_api_key;
        }

        $tenant->update($data);

        AuditLog::log('settings.ai_updated', $tenant);

        return redirect()->route('settings.index', ['tab' => 'ai'])->with('success', 'AI settings updated.');
    }

    /**
     * Enable or disable AI features.
     */
    public function toggleAi()
    {
        $tenant = auth()->user()->tenant;
        $tenant->update(['ai_enabled' => !$tenant->ai_enabled]);

        AuditLog::log('settings.ai_toggled', $tenant);

        $status = $tenant->ai_enabled ? 'enabled' : 'disabled';
        return redirect()->route('settings.index', ['tab' => 'ai'])->with('success', "AI features {$status}.");
    }

    public function toggleAiBriefings()
    {
        $tenant = auth()->user()->tenant;
        $tenant->update(['ai_briefings_enabled' => !$tenant->ai_briefings_enabled]);

        $status = $tenant->ai_briefings_enabled ? __('enabled') : __('disabled');
        return redirect()->route('settings.index', ['tab' => 'ai'])->with('success', __('Auto AI Briefings') . " {$status}.");
    }

    /**
     * Impersonate a team member for support/debugging.
     */
    public function impersonate(Request $request, User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if ($user->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        // Require the admin's current password for security
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->password, auth()->user()->password)) {
            return redirect()->route('settings.index', ['tab' => 'team'])->with('error', __('Incorrect password. Impersonation denied.'));
        }

        $adminId = auth()->id();
        AuditLog::log('impersonation.started', $user, null, ['admin_id' => $adminId, 'target_user_id' => $user->id]);

        session(['impersonating' => $adminId]);
        Auth::login($user);
        session()->regenerate();

        return redirect('/dashboard');
    }

    /**
     * Stop impersonation and return to the admin account.
     */
    public function stopImpersonation()
    {
        $adminId = session('impersonating');
        if ($adminId) {
            $admin = User::withoutGlobalScopes()
                ->where('id', $adminId)
                ->where('tenant_id', auth()->user()->tenant_id)
                ->first();
            if ($admin) {
                AuditLog::log('impersonation.stopped', $admin, null, ['admin_id' => $adminId]);
                Auth::login($admin);
                session()->forget('impersonating');
                session()->regenerate();
            }
        }

        return redirect()->route('settings.index');
    }

    /**
     * Show system health check information.
     */
    public function health()
    {
        $health = [
            'app_version' => config('app.version', '1.0.0'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_connection' => 'OK',
            'storage_writable' => is_writable(storage_path()),
            'queue_driver' => config('queue.default'),
        ];

        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $health['db_connection'] = 'FAILED: ' . $e->getMessage();
        }

        // Active plugins with versions
        $health['plugins'] = Plugin::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->get(['name', 'version', 'author'])
            ->toArray();

        return response()->json($health);
    }

    /**
     * Return a list of all language files with metadata.
     */
    public function getLanguages()
    {
        return response()->json(['languages' => $this->languageFileService->list()]);
    }

    /**
     * Return English keys merged with translations for a given locale.
     */
    public function getLanguageFile(Request $request, string $code)
    {
        try {
            return response()->json($this->languageFileService->get($code));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Save updated translations for a given locale.
     */
    public function saveLanguageFile(Request $request, string $code)
    {
        $request->validate([
            'translations' => 'required|array',
        ]);

        try {
            $this->languageFileService->save($code, $request->translations);
            return response()->json(['success' => true, 'message' => 'Language file saved.']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Upload a new language JSON file.
     */
    public function uploadLanguageFile(Request $request)
    {
        $request->validate([
            'language_file' => 'required|file|mimes:json,txt|max:2048',
        ]);

        try {
            $this->languageFileService->upload($request->file('language_file'));
            return response()->json(['success' => true, 'message' => 'Language file uploaded successfully.']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Update notification preferences for the tenant.
     */
    public function updateNotifications(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $notificationTypes = [
            'lead_assigned',
            'deal_stage_changed',
            'due_diligence_warning',
            'buyer_matched',
            'team_member_invited',
            'sequence_email',
        ];

        $preferences = [];
        foreach ($notificationTypes as $type) {
            $preferences[$type] = $request->boolean($type);
        }

        $tenant->update(['notification_preferences' => $preferences]);

        AuditLog::log('settings.notifications_updated', $tenant);

        return redirect()->route('settings.index', ['tab' => 'notifications'])->with('success', 'Notification preferences updated.');
    }

    /**
     * Update mail/SMTP settings for the tenant.
     */
    public function updateMail(Request $request)
    {
        $request->validate([
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_encryption' => 'nullable|in:tls,ssl',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
        ]);

        $tenant = auth()->user()->tenant;

        $settings = array_filter([
            'mail_host' => $request->input('mail_host'),
            'mail_port' => $request->input('mail_port'),
            'mail_encryption' => $request->input('mail_encryption'),
            'mail_username' => $request->input('mail_username'),
            'mail_password' => $request->filled('mail_password') ? encrypt($request->input('mail_password')) : null,
            'mail_from_address' => $request->input('mail_from_address'),
            'mail_from_name' => $request->input('mail_from_name'),
        ], fn ($v) => $v !== null && $v !== '');

        // If password field was left empty, keep the existing encrypted password
        if (!$request->filled('mail_password') && $tenant->mail_settings) {
            $existing = $tenant->mail_settings;
            if (!empty($existing['mail_password'])) {
                $settings['mail_password'] = $existing['mail_password'];
            }
        }

        $tenant->update(['mail_settings' => !empty($settings) ? $settings : null]);

        AuditLog::log('settings.mail_updated', $tenant);

        return redirect()->route('settings.index', ['tab' => 'email'])->with('success', __('Email settings updated.'));
    }

    /**
     * Send a test email using current mail settings.
     */
    public function testEmail(Request $request)
    {
        $user = auth()->user();
        $tenant = $user->tenant;
        $mail = $tenant->mail_settings ?? [];

        try {
            if (empty($mail['mail_host'])) {
                return response()->json([
                    'success' => false,
                    'message' => __('No SMTP host configured. Please fill in the SMTP settings and save before testing.'),
                ], 422);
            }

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $mail['mail_host'],
                'mail.mailers.smtp.port' => $mail['mail_port'] ?? 587,
                'mail.mailers.smtp.encryption' => $mail['mail_encryption'] ?? 'tls',
                'mail.mailers.smtp.username' => $mail['mail_username'] ?? '',
                'mail.mailers.smtp.password' => $this->decryptMailPassword($mail['mail_password'] ?? ''),
                'mail.mailers.smtp.timeout' => 15,
                'mail.from.address' => $mail['mail_from_address'] ?? config('mail.from.address'),
                'mail.from.name' => $mail['mail_from_name'] ?? config('mail.from.name'),
            ]);
            app('mail.manager')->purge('smtp');

            \Illuminate\Support\Facades\Mail::raw(
                __('This is a test email from :name. Your SMTP settings are working correctly!', ['name' => $tenant->name]),
                function ($message) use ($user) {
                    $message->to($user->email)->subject(__('Test Email from InsulaCRM'));
                }
            );

            return response()->json(['success' => true, 'email' => $user->email]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Test email failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => __('Failed to send test email. Check your SMTP host, port, and credentials.')], 422);
        }
    }

    public function storeWebhook(Request $request)
    {
        $request->validate([
            'url' => 'required|url|max:500',
            'secret' => 'nullable|string|max:100',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:lead.created,lead.updated,lead.status_changed,deal.stage_changed,activity.logged,buyer.notified,sequence.step_executed,*',
            'description' => 'nullable|string|max:255',
        ]);

        \App\Models\Webhook::create([
            'tenant_id' => auth()->user()->tenant_id,
            'url' => $request->url,
            'secret' => $request->secret,
            'events' => $request->events,
            'description' => $request->description,
        ]);

        return redirect()->route('settings.index', ['tab' => 'webhooks'])->with('success', 'Webhook created.');
    }

    public function toggleWebhook(\App\Models\Webhook $webhook)
    {
        if ($webhook->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
        $webhook->update([
            'is_active' => !$webhook->is_active,
            'failure_count' => 0, // reset on manual toggle
        ]);
        return redirect()->route('settings.index', ['tab' => 'webhooks'])->with('success', 'Webhook ' . ($webhook->is_active ? 'enabled' : 'disabled') . '.');
    }

    public function destroyWebhook(\App\Models\Webhook $webhook)
    {
        if ($webhook->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
        $webhook->delete();
        return redirect()->route('settings.index', ['tab' => 'webhooks'])->with('success', 'Webhook deleted.');
    }

    /**
     * Decrypt an encrypted mail password, returning the original string if decryption fails.
     */
    private function decryptMailPassword(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Value was stored before encryption was added — return as-is
            return $value;
        }
    }

    /**
     * Factory reset: wipe database, clear files, remove lock, redirect to installer.
     */
    public function factoryReset(Request $request)
    {
        if ($request->input('confirmation') !== 'RESET') {
            return back()->with('error', __('You must type RESET to confirm the factory reset.'));
        }

        if (!$request->filled('password') || !Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', __('Incorrect password. Factory reset denied.'));
        }

        // Allow extra time for large databases
        set_time_limit(300);

        // 1. Clear application caches
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // 2. Delete uploaded files (preserve .gitignore)
        $publicStorage = storage_path('app/public');
        if (File::isDirectory($publicStorage)) {
            foreach (File::directories($publicStorage) as $dir) {
                File::deleteDirectory($dir);
            }
            foreach (File::files($publicStorage) as $file) {
                if ($file->getFilename() !== '.gitignore') {
                    File::delete($file->getPathname());
                }
            }
        }

        // 3. Clear session files
        $sessionsPath = storage_path('framework/sessions');
        if (File::isDirectory($sessionsPath)) {
            foreach (File::files($sessionsPath) as $file) {
                if ($file->getFilename() !== '.gitignore') {
                    File::delete($file->getPathname());
                }
            }
        }

        // 4. Drop all tables and re-run migrations
        Artisan::call('migrate:fresh', ['--force' => true]);

        // 5. Remove the installed lock file to trigger the installer
        File::delete(storage_path('installed.lock'));

        // 6. Logout and redirect to installer
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/install');
    }

    /**
     * Display the Roles & Permissions management page.
     */
    public function roles(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $roles = Role::where(function ($q) use ($tenant) {
            $q->where('is_system', true)->orWhere('tenant_id', $tenant->id);
        })->with('permissions', 'users')->get();

        $permissions = Permission::orderBy('group')->orderBy('display_name')->get();
        $permissionGroups = $permissions->groupBy('group');

        // If a role is selected for editing, load it
        $selectedRole = null;
        if ($request->has('role')) {
            $selectedRole = $roles->firstWhere('id', $request->role);
        }

        return view('settings.roles', compact('roles', 'permissions', 'permissionGroups', 'selectedRole'));
    }

    /**
     * Create a new custom role.
     */
    public function createRole(Request $request)
    {
        $request->validate([
            'display_name' => 'required|string|max:100',
        ]);

        // Auto-generate slug from display name
        $name = \Illuminate\Support\Str::snake(\Illuminate\Support\Str::ascii($request->display_name));
        $name = preg_replace('/[^a-z0-9_]/', '', $name);
        if (empty($name)) {
            $name = 'custom_role';
        }

        // Ensure uniqueness
        $baseName = $name;
        $counter = 1;
        while (Role::where('name', $name)->exists()) {
            $name = $baseName . '_' . $counter++;
        }

        $role = Role::create([
            'name' => $name,
            'display_name' => $request->display_name,
            'is_system' => false,
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        AuditLog::log('role.created', $role);

        return redirect()->route('settings.roles', ['role' => $role->id])->with('success', __('Role created. Now assign permissions below.'));
    }

    /**
     * Update permissions for a role.
     */
    public function updateRolePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // Cannot modify admin system role permissions
        if ($role->is_system && $role->name === 'admin') {
            return redirect()->route('settings.roles')->with('error', __('Cannot modify admin role permissions.'));
        }

        $role->permissions()->sync($request->permissions ?? []);

        AuditLog::log('role.permissions_updated', $role);

        return redirect()->route('settings.roles', ['role' => $role->id])->with('success', __('Permissions updated for :role.', ['role' => $role->display_name]));
    }

    /**
     * Delete a custom role.
     */
    public function deleteRole(Role $role)
    {
        if ($role->is_system) {
            return redirect()->route('settings.roles')->with('error', __('Cannot delete system roles.'));
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('settings.roles')->with('error', __('Cannot delete a role that has users assigned. Reassign users first.'));
        }

        $role->permissions()->detach();
        $role->delete();

        AuditLog::log('role.deleted', $role);

        return redirect()->route('settings.roles')->with('success', __('Role deleted.'));
    }

    public function updateDashboardDefaults(Request $request)
    {
        $request->validate([
            'defaults' => 'required|array',
            'defaults.*' => 'array',
            'defaults.*.*' => 'string|in:' . implode(',', array_keys(\App\Services\DashboardWidgetService::WIDGETS)),
        ]);

        // Only accept known role names as keys
        $validRoles = \App\Models\Role::pluck('name')->all();
        $widgetKeys = array_keys(\App\Services\DashboardWidgetService::WIDGETS);
        $sanitized = [];
        foreach ($request->defaults as $role => $widgets) {
            if (in_array($role, $validRoles) && is_array($widgets)) {
                $sanitized[$role] = array_values(array_intersect($widgets, $widgetKeys));
            }
        }

        $tenant = auth()->user()->tenant;
        $tenant->default_dashboard_widgets = $sanitized;
        $tenant->save();

        return response()->json(['success' => true]);
    }
}
