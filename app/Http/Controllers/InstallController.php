<?php

namespace App\Http\Controllers;

use App\Services\UpdateManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class InstallController extends Controller
{
    private const INSTALLER_PLACEHOLDER_APP_KEY = 'base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=';

    /**
     * Show the installer wizard.
     */
    public function index()
    {
        if ($this->isInstalled()) {
            return redirect('/login');
        }

        $this->ensureEnvFileExists();

        return view('install.index', ['step' => 1]);
    }

    /**
     * Step 1: Check server requirements.
     */
    public function requirements(Request $request)
    {
        if ($this->isInstalled()) return redirect('/login');

        $this->ensureEnvFileExists();

        $requirements = [
            ['name' => 'PHP >= 8.2',            'passed' => version_compare(PHP_VERSION, '8.2.0', '>='), 'ext' => null],
            ['name' => 'BCMath Extension',       'passed' => extension_loaded('bcmath'),     'ext' => 'bcmath'],
            ['name' => 'Ctype Extension',        'passed' => extension_loaded('ctype'),      'ext' => 'ctype'],
            ['name' => 'cURL Extension',         'passed' => extension_loaded('curl'),       'ext' => 'curl'],
            ['name' => 'DOM Extension',          'passed' => extension_loaded('dom'),        'ext' => 'xml'],
            ['name' => 'Fileinfo Extension',     'passed' => extension_loaded('fileinfo'),   'ext' => 'fileinfo'],
            ['name' => 'JSON Extension',         'passed' => extension_loaded('json'),       'ext' => 'json'],
            ['name' => 'Mbstring Extension',     'passed' => extension_loaded('mbstring'),   'ext' => 'mbstring'],
            ['name' => 'OpenSSL Extension',      'passed' => extension_loaded('openssl'),    'ext' => 'openssl'],
            ['name' => 'PDO Extension',          'passed' => extension_loaded('pdo'),        'ext' => 'pdo'],
            ['name' => 'PDO MySQL Extension',    'passed' => extension_loaded('pdo_mysql'),  'ext' => 'mysql'],
            ['name' => 'Tokenizer Extension',    'passed' => extension_loaded('tokenizer'),  'ext' => 'tokenizer'],
            ['name' => 'XML Extension',          'passed' => extension_loaded('xml'),        'ext' => 'xml'],
            ['name' => 'GD Extension',           'passed' => extension_loaded('gd'),         'ext' => 'gd'],
        ];

        $permissionChecks = $this->permissionChecks();
        $permissions = collect($permissionChecks)
            ->mapWithKeys(fn (array $check) => [$check['name'] => $check['passed']])
            ->all();

        $environmentChecks = $this->environmentChecks();
        $installContext = $this->detectInstallContext($request);

        $reqPassed = count(array_filter($requirements, fn ($r) => $r['passed']));
        $reqTotal = count($requirements);
        $requiredEnvironmentChecks = array_filter($environmentChecks, fn ($check) => $check['required']);
        $envPassed = count(array_filter($requiredEnvironmentChecks, fn ($check) => $check['passed']));
        $envTotal = count($requiredEnvironmentChecks);
        $permPassed = count(array_filter($permissions));
        $permTotal = count($permissions);
        $allPassed = $reqPassed === $reqTotal
            && $envPassed === $envTotal
            && $permPassed === $permTotal;

        // Detect server environment for install hints
        $environment = $this->detectEnvironment();

        return view('install.requirements', compact(
            'requirements', 'permissions', 'allPassed',
            'reqPassed', 'reqTotal', 'permPassed', 'permTotal',
            'environmentChecks', 'envPassed', 'envTotal',
            'environment', 'installContext', 'permissionChecks'
        ));
    }

    /**
     * Detect the server environment for tailored install hints.
     */
    private function detectEnvironment(): string
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            $serverPath = $_SERVER['SERVER_SOFTWARE'] ?? '';
            if (stripos($serverPath, 'xampp') !== false || is_dir('C:\\xampp')) {
                return 'xampp';
            }
            if (stripos($serverPath, 'wamp') !== false || is_dir('C:\\wamp64')) {
                return 'wamp';
            }
            return 'windows';
        }

        if (is_dir('/opt/lampp')) {
            return 'xampp-linux';
        }

        if (file_exists('/etc/debian_version') || file_exists('/etc/lsb-release')) {
            return 'debian';
        }
        if (file_exists('/etc/redhat-release') || file_exists('/etc/centos-release')) {
            return 'rhel';
        }
        if (file_exists('/.dockerenv')) {
            return 'docker';
        }

        return 'linux';
    }

    /**
     * Step 2: Database configuration form.
     */
    public function database()
    {
        if ($this->isInstalled()) return redirect('/login');
        $this->ensureEnvFileExists();

        return view('install.database', [
            'installContext' => $this->detectInstallContext(request()),
        ]);
    }

    /**
     * Step 2: Save database configuration.
     */
    public function saveDatabase(Request $request)
    {
        if ($this->isInstalled()) return redirect('/login');

        $this->ensureEnvFileExists();

        $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|string',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
            'create_secure_user' => 'nullable|in:1',
            'secure_username' => 'nullable|string|max:32',
            'secure_password' => 'nullable|string|min:8',
        ]);

        $dbHost = $request->db_host;
        $dbPort = $request->db_port;
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $request->db_database);
        $dbPassword = $request->db_password ?? '';

        if ($dbName === '') {
            return back()->withInput()->withErrors([
                'db_database' => 'Invalid database name. Use only letters, numbers, and underscores.',
            ]);
        }

        // Connect to MariaDB / MySQL server
        try {
            $pdo = new \PDO(
                "mysql:host={$dbHost};port={$dbPort}",
                $request->db_username,
                $dbPassword
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors([
                'db_host' => $this->databaseConnectionErrorMessage($e),
            ]);
        }

        $databaseExists = $this->databaseExists($pdo, $dbName);
        $capabilities = $this->detectMariaDbCapabilities($pdo);

        if (! $databaseExists) {
            try {
                $pdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (\Exception $e) {
                return back()->withInput()->withErrors([
                    'db_database' => 'Connected to MariaDB, but this account cannot create the database "' . $dbName . '". Create the database in your hosting panel first, then continue with its existing database user.',
                ]);
            }
        }

        // Determine the final credentials to store in .env
        $finalUsername = $request->db_username;
        $finalPassword = $dbPassword;
        $secureUserCreated = false;

        // Create a dedicated database user if requested
        if ($request->boolean('create_secure_user') && $request->filled('secure_username') && $request->filled('secure_password')) {
            $newUser = preg_replace('/[^a-zA-Z0-9_]/', '', $request->secure_username);
            $newPass = $request->secure_password;

            if (empty($newUser)) {
                return back()->withInput()->withErrors(['secure_username' => 'Invalid username. Use only letters, numbers, and underscores.']);
            }

            if (! $capabilities['can_create_users'] || ! $capabilities['can_grant_privileges']) {
                return back()->withInput()->withErrors([
                    'create_secure_user' => 'This MariaDB account can connect, but it cannot create users and grant privileges automatically. Leave "Create a dedicated database user" disabled and continue with an existing database user.',
                ]);
            }

            try {
                // Determine which hosts to create the user for.
                // MySQL treats 'localhost' (socket) and '127.0.0.1' (TCP) as
                // different hosts, so for local connections we create both to
                // avoid access denied errors regardless of how the app connects.
                $isLocal = in_array($dbHost, ['127.0.0.1', 'localhost', '::1']);
                $userHosts = $isLocal ? ['localhost', '127.0.0.1'] : [$dbHost];

                foreach ($userHosts as $userHost) {
                    // Drop user if it already exists (from a previous failed install attempt)
                    $pdo->exec("DROP USER IF EXISTS " . $pdo->quote($newUser) . "@" . $pdo->quote($userHost));

                    // Create the user
                    $pdo->exec(
                        "CREATE USER " . $pdo->quote($newUser) . "@" . $pdo->quote($userHost) .
                        " IDENTIFIED BY " . $pdo->quote($newPass)
                    );

                    // Grant only the privileges needed for Laravel on this database
                    $pdo->exec(
                        "GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES " .
                        "ON `{$dbName}`.* TO " . $pdo->quote($newUser) . "@" . $pdo->quote($userHost)
                    );
                }

                $pdo->exec("FLUSH PRIVILEGES");

                // Verify the new user can connect
                $testPdo = new \PDO(
                    "mysql:host={$dbHost};port={$dbPort};dbname={$dbName}",
                    $newUser,
                    $newPass
                );
                $testPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $testPdo = null;

                $finalUsername = $newUser;
                $finalPassword = $newPass;
                $secureUserCreated = true;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Secure DB user creation failed', ['error' => $e->getMessage()]);
                return back()->withInput()->withErrors([
                    'secure_username' => 'Connected successfully, but this MariaDB account could not create or grant the dedicated database user. Use an existing database user instead, or supply MariaDB administrator credentials with CREATE USER and GRANT privileges.',
                ]);
            }
        }

        // Write .env file with the final credentials
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        // Helper: replace env value, handling commented-out lines (# DB_HOST=...)
        $setEnv = function (string $key, string $value) use (&$envContent) {
            $pattern = '/^#?\s*' . preg_quote($key, '/') . '=.*/m';
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $key . '=' . $value, $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        };

        $setEnv('DB_CONNECTION', 'mysql');
        $setEnv('DB_HOST', $dbHost);
        $setEnv('DB_PORT', $dbPort);
        $setEnv('DB_DATABASE', '"' . addcslashes($dbName, '"\\') . '"');
        $setEnv('DB_USERNAME', '"' . addcslashes($finalUsername, '"\\') . '"');
        $setEnv('DB_PASSWORD', '"' . addcslashes($finalPassword, '"\\') . '"');

        File::put($envPath, $envContent);

        // Clear config cache
        Artisan::call('config:clear');

        if ($secureUserCreated) {
            return redirect()->route('install.setup')
                ->with('secure_user_created', true)
                ->with('secure_user_name', $finalUsername)
                ->with('secure_user_pass', $finalPassword)
                ->with('db_capabilities', $capabilities);
        }

        return redirect()->route('install.setup')->with('db_capabilities', $capabilities);
    }

    /**
     * Step 3: Application setup form.
     */
    public function setup()
    {
        if ($this->isInstalled()) return redirect('/login');
        $this->ensureEnvFileExists();

        return view('install.setup', [
            'installContext' => $this->detectInstallContext(request()),
            'demoDataAvailable' => class_exists(\Faker\Factory::class),
            'dbCapabilities' => session('db_capabilities'),
        ]);
    }

    /**
     * Step 4: Run migrations, seeders, and create admin.
     */
    public function install(Request $request)
    {
        if ($this->isInstalled()) return redirect('/login');

        $this->ensureEnvFileExists();

        $request->validate([
            'app_name' => 'required|string|max:255',
            'business_mode' => 'required|in:wholesale,realestate',
            'company_name' => 'required|string|max:255',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email',
            'admin_password' => 'required|string|min:8|confirmed',
        ]);

        try {
            // Auto-detect APP_URL from the current request
            $appUrl = $this->detectAppUrl($request);

            // Update app name and URL in .env
            $envPath = base_path('.env');
            $envContent = File::get($envPath);
            $envContent = preg_replace('/APP_NAME=.*/', 'APP_NAME="' . $request->app_name . '"', $envContent);
            $envContent = preg_replace('/APP_URL=.*/', 'APP_URL=' . $appUrl, $envContent);
            File::put($envPath, $envContent);

            // Create plugins directory if it doesn't exist
            $pluginsPath = base_path('plugins');
            if (!File::isDirectory($pluginsPath)) {
                File::makeDirectory($pluginsPath, 0755, true);
            }

            // Re-read .env and apply database credentials to the running process.
            // Laravel loads config once at boot, so changes made to .env in step 2
            // are not visible here unless we explicitly reload them.
            $envPath = base_path('.env');
            $envValues = [];
            foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $envValues[trim($key)] = trim($value, '"\'');
                }
            }

            config([
                'database.connections.mysql.host' => $envValues['DB_HOST'] ?? '127.0.0.1',
                'database.connections.mysql.port' => $envValues['DB_PORT'] ?? '3306',
                'database.connections.mysql.database' => $envValues['DB_DATABASE'] ?? 'insulacrm',
                'database.connections.mysql.username' => $envValues['DB_USERNAME'] ?? 'root',
                'database.connections.mysql.password' => $envValues['DB_PASSWORD'] ?? '',
            ]);

            // Purge the existing connection so it reconnects with the new credentials
            \Illuminate\Support\Facades\DB::purge('mysql');

            // Generate APP_KEY if not set
            if (
                empty(config('app.key'))
                || config('app.key') === 'base64:'
                || config('app.key') === self::INSTALLER_PLACEHOLDER_APP_KEY
            ) {
                Artisan::call('key:generate', ['--force' => true]);
            }

            // Run migrations
            Artisan::call('migrate', ['--force' => true]);

            // Seed essential data (roles) — pass business mode so the correct role set is created
            $businessMode = $request->input('business_mode', 'wholesale');
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\BaseSeeder',
                '--force' => true,
            ]);
            // BaseSeeder always seeds the wholesale roles as a safe default.
            // If real estate mode, also seed the real estate roles.
            if ($businessMode === 'realestate') {
                $reRoles = [
                    'listing_agent' => 'Listing Agent',
                    'buyers_agent'  => 'Buyers Agent',
                ];
                foreach ($reRoles as $name => $displayName) {
                    \App\Models\Role::firstOrCreate(
                        ['name' => $name],
                        ['display_name' => $displayName, 'is_system' => true]
                    );
                }
            }

            // Create the admin's tenant and account inside a transaction
            $tenant = DB::transaction(function () use ($request) {
                $slug = Str::slug($request->company_name) ?: 'tenant';
                $tenant = \App\Models\Tenant::withoutGlobalScopes()->firstOrNew([
                    'slug' => $slug,
                ]);

                $tenant->fill([
                    'name' => $request->company_name,
                    'business_mode' => $request->input('business_mode', 'wholesale'),
                    'email' => $request->admin_email,
                    'status' => 'active',
                ]);
                $tenant->save();

                $adminRole = \App\Models\Role::where('name', 'admin')->first();
                $admin = \App\Models\User::withoutGlobalScopes()->firstOrNew([
                    'email' => $request->admin_email,
                ]);

                $admin->fill([
                    'tenant_id' => $tenant->id,
                    'role_id' => $adminRole->id,
                    'name' => $request->admin_name,
                    'password' => \Illuminate\Support\Facades\Hash::make($request->admin_password),
                    'onboarding_completed' => false,
                ]);
                $admin->save();

                return $tenant;
            });

            // Seed demo data into the admin's tenant (not a separate demo tenant)
            $demoDataLoaded = false;
            $demoDataWarning = null;
            if ($request->boolean('load_demo_data')) {
                if (! class_exists(\Faker\Factory::class)) {
                    $demoDataWarning = 'Demo data was skipped because the sample-data package is not available in this build.';
                } elseif ($this->tenantHasDemoData($tenant->id)) {
                    $demoDataWarning = 'Partial demo data from an earlier install attempt was detected, so sample data was not seeded again.';
                } else {
                    try {
                        $seeder = new \Database\Seeders\DemoDataSeeder();
                        $seeder->run($tenant->id);
                        $demoDataLoaded = true;
                    } catch (\Throwable $e) {
                        $demoDataWarning = 'The base CRM was installed, but demo data could not be loaded. You can continue without it.';
                        \Illuminate\Support\Facades\Log::warning('Demo data seeding failed during installation.', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            $storageLinkMissing = false;
            if (!file_exists(public_path('storage'))) {
                try {
                    // This can fail on some hosting setups and bind-mounted containers.
                    Artisan::call('storage:link');
                } catch (\Throwable $e) {
                    $storageLinkMissing = true;
                    \Illuminate\Support\Facades\Log::warning('Storage symlink could not be created during installation.', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Verify the symlink was created (may still fail on some hosting)
            if (!file_exists(public_path('storage'))) {
                $storageLinkMissing = true;
                \Illuminate\Support\Facades\Log::warning('Storage symlink could not be created. File uploads may not display correctly. You can create it manually: php artisan storage:link');
            }

            // Switch to database-backed drivers now that the tables exist
            $this->setEnvValue('SESSION_DRIVER', 'database');
            $this->setEnvValue('CACHE_STORE', 'database');
            $this->setEnvValue('QUEUE_CONNECTION', 'database');

            // Write installed.lock
            File::put(storage_path('installed.lock'), 'Installed on ' . now()->toDateTimeString());

            Artisan::call('config:clear');

            return redirect()->route('install.complete')->with([
                'installation_completed' => true,
                'demo_data_loaded' => $demoDataLoaded,
                'demo_data_warning' => $demoDataWarning,
                'storage_link_missing' => $storageLinkMissing,
                'installation_tenant_id' => $tenant->id,
                'installation_admin_email' => $request->admin_email,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Installation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors(['install' => 'Installation failed. Please check the application log at storage/logs/ for details.']);
        }
    }

    /**
     * Step 5: Success screen.
     */
    public function complete()
    {
        abort_unless(session()->has('installation_completed') && session()->has('installation_tenant_id'), 403);

        $tenant = \App\Models\Tenant::withoutGlobalScopes()->find(session('installation_tenant_id'));
        $admin = \App\Models\User::withoutGlobalScopes()
            ->where('tenant_id', session('installation_tenant_id'))
            ->where('email', session('installation_admin_email'))
            ->first();

        return view('install.complete', [
            'tenant' => $tenant,
            'admin' => $admin,
        ]);
    }

    public function createInitialSnapshot(UpdateManagerService $updateManager)
    {
        abort_unless(session()->has('installation_completed') && session()->has('installation_tenant_id'), 403);

        try {
            $admin = \App\Models\User::withoutGlobalScopes()
                ->where('tenant_id', session('installation_tenant_id'))
                ->where('email', session('installation_admin_email'))
                ->first();

            $snapshot = $updateManager->createManualSnapshot(
                tenantId: (int) session('installation_tenant_id'),
                userId: $admin?->id,
                label: 'Initial baseline after installation',
            );

            $snapshot = $updateManager->processManualSnapshot($snapshot);

            return redirect()->route('install.complete')->with([
                'initial_snapshot_created' => true,
                'initial_snapshot_summary' => $snapshot->summary,
                'initial_snapshot_created_at' => optional($snapshot->created_at)?->toDayDateTimeString(),
            ]);
        } catch (\Throwable $exception) {
            return redirect()->route('install.complete')->withErrors([
                'snapshot' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Check if the application is already installed.
     */
    private function isInstalled(): bool
    {
        $markerPath = storage_path('installed.lock');

        if (File::exists($markerPath)) {
            return true;
        }

        try {
            if (! Schema::hasTable('tenants') || ! Schema::hasTable('users')) {
                return false;
            }

            if (DB::table('tenants')->exists() && DB::table('users')->exists()) {
                File::put($markerPath, 'Recovered install marker on ' . now()->toDateTimeString());

                return true;
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    /**
     * Ensure the installer has a writable environment file to work with.
     */
    private function ensureEnvFileExists(): void
    {
        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        if (! File::exists($envPath) && File::exists($examplePath)) {
            File::copy($examplePath, $envPath);
        }
    }

    /**
     * Update a single key in the .env file.
     */
    private function setEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');
        $content = File::get($envPath);
        $pattern = '/^#?\s*' . preg_quote($key, '/') . '=.*/m';

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $key . '=' . $value, $content);
        } else {
            $content .= "\n{$key}={$value}";
        }

        File::put($envPath, $content);
    }

    /**
     * Installer checks that are not regular PHP extensions or writable folders.
     */
    private function environmentChecks(): array
    {
        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');
        $environment = $this->detectEnvironment();
        $webIdentity = $this->detectWebServerIdentity();
        $envMetadata = $this->pathMetadata($envPath);

        $envExists = File::exists($envPath);
        $envExampleExists = File::exists($examplePath);
        $envWritable = $envExists
            ? is_writable($envPath)
            : ($envExampleExists && is_writable(base_path()));

        return [
            [
                'name' => '.env file available',
                'passed' => $envExists,
                'required' => true,
                'detail' => $envExists
                    ? '.env is present and ready for the installer to update.'
                    : 'The installer needs a writable .env file. Copy .env.example to .env if automatic creation fails.',
                'remediation' => $this->environmentRemediation(
                    check: 'env_exists',
                    environment: $environment,
                    envPath: $envPath,
                    examplePath: $examplePath,
                ),
            ],
            [
                'name' => '.env writable',
                'passed' => $envWritable,
                'required' => true,
                'detail' => $envWritable
                    ? 'The installer can save APP_URL, database credentials, and the generated app key.'
                    : $this->envWritableDetail($envPath, $envMetadata, $webIdentity),
                'remediation' => $this->environmentRemediation(
                    check: 'env_writable',
                    environment: $environment,
                    envPath: $envPath,
                    examplePath: $examplePath,
                    metadata: $envMetadata,
                    webIdentity: $webIdentity,
                ),
            ],
            [
                'name' => 'MySQL PDO driver available',
                'passed' => extension_loaded('pdo') && extension_loaded('pdo_mysql'),
                'required' => true,
                'detail' => extension_loaded('pdo') && extension_loaded('pdo_mysql')
                    ? 'The installer can connect to MySQL/MariaDB.'
                    : 'Install the pdo_mysql extension. SQLite fallback is not supported for the guided installer.',
                'remediation' => $this->environmentRemediation(
                    check: 'pdo_mysql',
                    environment: $environment,
                    envPath: $envPath,
                    examplePath: $examplePath,
                ),
            ],
            [
                'name' => 'Demo data runtime available',
                'passed' => class_exists(\Faker\Factory::class),
                'required' => false,
                'detail' => class_exists(\Faker\Factory::class)
                    ? 'Optional sample data can be installed in this build.'
                    : 'Core CRM installation will still work, but demo data is unavailable until fakerphp/faker is installed.',
                'remediation' => $this->environmentRemediation(
                    check: 'demo_data',
                    environment: $environment,
                    envPath: $envPath,
                    examplePath: $examplePath,
                ),
            ],
        ];
    }

    private function environmentRemediation(
        string $check,
        string $environment,
        string $envPath,
        string $examplePath,
        array $metadata = [],
        array $webIdentity = [],
    ): string
    {
        return match ($check) {
            'env_exists' => match ($environment) {
                'xampp' => 'Create the file by copying ' . $examplePath . ' to ' . $envPath . '. In Windows Explorer you can duplicate .env.example and rename the copy to .env.',
                'xampp-linux' => 'Run: ls -la to confirm .env.example exists, then run cp .env.example .env. If the sample file is missing, re-upload the package or create .env manually from the installation guide sample.',
                'wamp', 'windows' => 'Create .env by copying .env.example in your project folder, then refresh this page.',
                'docker' => 'Create .env before starting the container, or copy .env.example to .env inside the application root and restart the container.',
                default => 'Run: ls -la to confirm .env.example exists, then run cp .env.example .env. If the sample file is missing, re-upload the package or create .env manually from the installation guide sample.',
            },
            'env_writable' => match ($environment) {
                'xampp', 'wamp', 'windows' => 'Give the web server user write access to the project root or the .env file. On Windows, open the file properties and allow Modify/Write permissions.',
                'xampp-linux' => 'For Linux XAMPP local testing, run: chmod 666 .env && chmod -R 777 storage bootstrap/cache plugins. XAMPP Apache commonly runs as daemon, not www-data.',
                'docker' => 'Ensure the container user can write .env and bootstrap/cache. If the project is bind-mounted, fix the host-side file permissions first.',
                default => $this->envWritableRemediation($envPath, $metadata, $webIdentity),
            },
            'pdo_mysql' => match ($environment) {
                'xampp' => 'Open C:\xampp\php\php.ini, enable extension=pdo_mysql, then restart Apache from the XAMPP control panel.',
                'wamp' => 'Enable php_pdo_mysql from the WAMP tray menu, then restart all services.',
                'debian' => 'Run: sudo apt install -y php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '-mysql && sudo systemctl restart apache2 php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '-fpm',
                'rhel' => 'Run: sudo dnf install -y php-mysqlnd && sudo systemctl restart httpd php-fpm',
                'docker' => 'Add pdo_mysql to your PHP image, rebuild the container, and restart it.',
                default => 'Install the pdo_mysql extension for your PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . ' runtime, then restart PHP/Apache.',
            },
                'demo_data' => 'Demo data is optional. You can continue without it, or install fakerphp/faker in the environment if you want sample content.',
            default => '',
        };
    }

    private function permissionChecks(): array
    {
        $webIdentity = $this->detectWebServerIdentity();
        $paths = [
            ['name' => 'storage/app', 'path' => storage_path('app')],
            ['name' => 'storage/framework', 'path' => storage_path('framework')],
            ['name' => 'storage/logs', 'path' => storage_path('logs')],
            ['name' => 'bootstrap/cache', 'path' => base_path('bootstrap/cache')],
            ['name' => 'plugins/', 'path' => base_path('plugins')],
        ];

        return array_map(function (array $item) use ($webIdentity) {
            $metadata = $this->pathMetadata($item['path']);
            $passed = $item['name'] === 'plugins/'
                ? (is_writable($item['path']) || (! file_exists($item['path']) && is_writable(base_path())))
                : is_writable($item['path']);

            return [
                'name' => $item['name'],
                'path' => $item['path'],
                'passed' => $passed,
                'metadata' => $metadata,
                'detail' => $passed
                    ? 'Writable by the current web server process.'
                    : $this->folderPermissionDetail($item['path'], $metadata, $webIdentity),
                'remediation' => $passed
                    ? ''
                    : $this->folderPermissionRemediation($item['name'], $item['path'], $metadata, $webIdentity),
            ];
        }, $paths);
    }

    private function detectWebServerIdentity(): array
    {
        $user = null;
        $group = null;

        if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            $pw = @posix_getpwuid(posix_geteuid());
            $user = is_array($pw) ? ($pw['name'] ?? null) : null;
        }

        if (function_exists('posix_getegid') && function_exists('posix_getgrgid')) {
            $gr = @posix_getgrgid(posix_getegid());
            $group = is_array($gr) ? ($gr['name'] ?? null) : null;
        }

        return [
            'user' => $user,
            'group' => $group,
        ];
    }

    private function pathMetadata(string $path): array
    {
        if (! file_exists($path)) {
            return [
                'exists' => false,
                'owner' => null,
                'group' => null,
                'permissions' => null,
            ];
        }

        $owner = @fileowner($path);
        $group = @filegroup($path);
        $permissions = substr(sprintf('%o', @fileperms($path)), -4);

        return [
            'exists' => true,
            'owner' => $this->resolveOwnerName($owner),
            'group' => $this->resolveGroupName($group),
            'permissions' => $permissions ?: null,
        ];
    }

    private function resolveOwnerName(false|int $owner): ?string
    {
        if ($owner === false) {
            return null;
        }

        if (function_exists('posix_getpwuid')) {
            $pw = @posix_getpwuid($owner);
            if (is_array($pw) && ! empty($pw['name'])) {
                return $pw['name'];
            }
        }

        return (string) $owner;
    }

    private function resolveGroupName(false|int $group): ?string
    {
        if ($group === false) {
            return null;
        }

        if (function_exists('posix_getgrgid')) {
            $gr = @posix_getgrgid($group);
            if (is_array($gr) && ! empty($gr['name'])) {
                return $gr['name'];
            }
        }

        return (string) $group;
    }

    private function envWritableDetail(string $envPath, array $metadata, array $webIdentity): string
    {
        $owner = $metadata['owner'] ?? 'unknown';
        $group = $metadata['group'] ?? 'unknown';
        $permissions = $metadata['permissions'] ?? 'unknown';
        $webUser = $webIdentity['user'] ?? 'unknown';
        $webGroup = $webIdentity['group'] ?? 'unknown';

        return ".env exists, but the current web server process ({$webUser}:{$webGroup}) cannot write {$envPath}. Current owner/group is {$owner}:{$group} with mode {$permissions}.";
    }

    private function envWritableRemediation(string $envPath, array $metadata, array $webIdentity): string
    {
        $webGroup = $webIdentity['group'] ?? 'www-data';
        $owner = $metadata['owner'] ?? $webIdentity['user'] ?? 'www-data';

        return "Run: chown {$owner}:{$webGroup} .env && chmod 664 .env. This ownership change usually requires sudo or server-admin access. If you were just added to the sudo group, log out and back in before retrying. If storage or bootstrap/cache also fail, fix those paths separately.";
    }

    private function folderPermissionDetail(string $path, array $metadata, array $webIdentity): string
    {
        $owner = $metadata['owner'] ?? 'unknown';
        $group = $metadata['group'] ?? 'unknown';
        $permissions = $metadata['permissions'] ?? 'unknown';
        $webUser = $webIdentity['user'] ?? 'unknown';
        $webGroup = $webIdentity['group'] ?? 'unknown';

        return "{$path} is not writable by the current web server process ({$webUser}:{$webGroup}). Current owner/group is {$owner}:{$group} with mode {$permissions}.";
    }

    private function folderPermissionRemediation(string $logicalName, string $path, array $metadata, array $webIdentity): string
    {
        if (($webIdentity['group'] ?? null) !== null && ($metadata['owner'] ?? null) !== null) {
            return "Run: chown -R {$metadata['owner']}:{$webIdentity['group']} {$logicalName} && chmod -R 775 {$logicalName}. If chown returns \"Operation not permitted\", run it with sudo or ask the server administrator to reset ownership. If you were just added to the sudo group, log out and back in first.";
        }

        return "Grant the web server user write access to {$path}, then re-check the installer.";
    }

    /**
     * Normalize the current request into the URL that should be stored as APP_URL.
     */
    private function detectAppUrl(Request $request): string
    {
        $baseUrl = rtrim((string) $request->getBaseUrl(), '/');

        if ($baseUrl !== '' && str_ends_with(strtolower($baseUrl), '/public')) {
            $baseUrl = substr($baseUrl, 0, -7);
        }

        return rtrim($request->getSchemeAndHttpHost() . $baseUrl, '/');
    }

    /**
     * Detect whether the installer is being accessed from a root install,
     * subdirectory install, or a direct /public URL that should not be saved.
     */
    private function detectInstallContext(Request $request): array
    {
        $rawBaseUrl = rtrim((string) $request->getBaseUrl(), '/');
        $servedFromPublic = $rawBaseUrl !== '' && str_ends_with(strtolower($rawBaseUrl), '/public');
        $normalizedBaseUrl = $servedFromPublic ? substr($rawBaseUrl, 0, -7) : $rawBaseUrl;
        $displayBasePath = $normalizedBaseUrl === '' ? '/' : $normalizedBaseUrl;

        return [
            'detected_app_url' => $this->detectAppUrl($request),
            'display_base_path' => $displayBasePath,
            'raw_base_url' => $rawBaseUrl === '' ? '/' : $rawBaseUrl,
            'is_subdirectory' => $normalizedBaseUrl !== '',
            'served_from_public' => $servedFromPublic,
        ];
    }

    private function databaseConnectionErrorMessage(\Throwable $e): string
    {
        $message = strtolower($e->getMessage());

        return match (true) {
            str_contains($message, 'access denied') => 'MariaDB rejected the username/password. If you want the installer to create a dedicated database user automatically, supply MariaDB administrator credentials. Otherwise, use the existing database username and password assigned to your database.',
            str_contains($message, 'connection refused'),
            str_contains($message, 'no such file or directory') => 'Could not reach the MariaDB server. Verify the host, port, and server status.',
            default => 'Could not connect to the MariaDB server. Verify the host, port, username, and password.',
        };
    }

    private function databaseExists(\PDO $pdo, string $dbName): bool
    {
        try {
            $statement = $pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
            $statement->execute([$dbName]);

            return (bool) $statement->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
    }

    private function detectMariaDbCapabilities(\PDO $pdo): array
    {
        $grants = [];

        try {
            $grants = $pdo->query('SHOW GRANTS FOR CURRENT_USER()')->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Throwable) {
            return [
                'can_create_users' => false,
                'can_grant_privileges' => false,
            ];
        }

        $grantText = strtoupper(implode(' ', $grants));
        $hasGlobalAll = str_contains($grantText, 'ALL PRIVILEGES ON *.*');

        return [
            'can_create_users' => $hasGlobalAll || str_contains($grantText, 'CREATE USER'),
            'can_grant_privileges' => $hasGlobalAll || str_contains($grantText, 'GRANT OPTION'),
        ];
    }

    private function tenantHasDemoData(int $tenantId): bool
    {
        return \App\Models\Lead::withoutGlobalScopes()->where('tenant_id', $tenantId)->exists()
            || \App\Models\Deal::withoutGlobalScopes()->where('tenant_id', $tenantId)->exists()
            || \App\Models\Buyer::withoutGlobalScopes()->where('tenant_id', $tenantId)->exists()
            || \App\Models\User::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('email', 'like', '%@demo.com')
                ->exists();
    }
}
