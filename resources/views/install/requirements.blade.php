@extends('layouts.auth')

@section('title', __('Server Requirements'))

@section('content')
<div class="card card-md">
    <div class="card-body">
        <h2 class="mb-2 text-dark">{{ __('Server Requirements') }}</h2>
        <p class="text-secondary mb-4">{{ __('InsulaCRM checks your server to make sure everything is ready.') }}</p>

        @include('install._stepper', ['currentStep' => 2])

        @if($installContext['served_from_public'])
            <div class="alert alert-warning mb-4 text-dark" style="border: 1px solid rgba(0,0,0,0.12); background: #fff3cd;">
                <h4 class="alert-title">{{ __('Public URL detected') }}</h4>
                <p class="mb-2">{{ __('You are opening the installer through a URL that contains /public. That is acceptable for testing, but your final site URL should not include /public.') }}</p>
                <p class="mb-0">{{ __('The installer will save APP_URL as:') }} <code class="text-dark">{{ $installContext['detected_app_url'] }}</code></p>
            </div>
        @endif

        <div class="alert alert-info mb-4">
            <h4 class="alert-title">{{ __('Detected install path') }}</h4>
            <p class="mb-1">{{ __('Current base path:') }} <code>{{ $installContext['display_base_path'] }}</code></p>
            <p class="mb-0">{{ __('APP_URL will be saved as:') }} <code>{{ $installContext['detected_app_url'] }}</code></p>
        </div>

        {{-- Overall progress --}}
        @php
            $totalPassed = $reqPassed + $envPassed + $permPassed;
            $totalChecks = $reqTotal + $envTotal + $permTotal;
            $pct = $totalChecks > 0 ? round(($totalPassed / $totalChecks) * 100) : 0;
        @endphp
        <div class="mb-4">
            <div class="d-flex justify-content-between mb-1">
                <span class="text-dark fw-bold">{{ __('Overall') }}</span>
                <span class="text-dark fw-bold">{{ $totalPassed }}/{{ $totalChecks }}</span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar {{ $allPassed ? 'bg-green' : 'bg-yellow' }}" style="width: {{ $pct }}%" role="progressbar"></div>
            </div>
        </div>

        {{-- PHP Extensions --}}
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="text-dark mb-0">{{ __('PHP Extensions') }}</h4>
            <span class="badge {{ $reqPassed === $reqTotal ? 'bg-green-lt' : 'bg-yellow-lt' }}">{{ $reqPassed }}/{{ $reqTotal }}</span>
        </div>
        <div class="table-responsive mb-2">
            <table class="table table-vcenter">
                <tbody>
                    @foreach($requirements as $req)
                    <tr>
                        <td class="text-dark" style="width: 60%;">{{ $req['name'] }}</td>
                        <td class="text-end">
                            @if($req['passed'])
                                <span class="badge bg-green-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                                    {{ __('OK') }}
                                </span>
                            @else
                                <span class="badge bg-red-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg>
                                    {{ __('Missing') }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Fix instructions for missing extensions --}}
        @php
            $missingExts = array_filter($requirements, fn ($r) => !$r['passed'] && $r['ext']);
        @endphp
        @if(count($missingExts) > 0)
            <div class="alert alert-warning mb-4 text-dark" style="border: 1px solid rgba(0,0,0,0.12); background: #fff3cd;">
                <div class="d-flex">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 8l.01 0"/><path d="M11 12l1 0l0 4l1 0"/></svg>
                    </div>
                    <div>
                        <h4 class="alert-title">{{ __('How to fix missing extensions') }}</h4>
                        @if($environment === 'xampp')
                            <p class="mb-2">{{ __('You are running XAMPP. Open your php.ini file and uncomment (remove the ;) the following lines, then restart Apache:') }}</p>
                            <p class="mb-1"><strong>{{ __('php.ini location:') }}</strong> <code class="text-dark">C:\xampp\php\php.ini</code></p>
                            <pre class="mb-2 text-dark" style="background: #fff; border: 1px solid rgba(0,0,0,0.12); padding: 10px; border-radius: 4px; font-size: 13px; overflow-x: auto;">@foreach($missingExts as $ext)extension={{ $ext['ext'] }}
@endforeach</pre>
                            <p class="mb-0">{{ __('After editing, restart Apache from the XAMPP Control Panel.') }}</p>
                        @elseif($environment === 'wamp')
                            <p class="mb-2">{{ __('You are running WAMP. Click the WAMP tray icon > PHP > PHP Extensions and enable:') }}</p>
                            <ul class="mb-2">
                                @foreach($missingExts as $ext)
                                    <li><code class="text-dark">php_{{ $ext['ext'] }}</code></li>
                                @endforeach
                            </ul>
                            <p class="mb-0">{{ __('Then restart all WAMP services.') }}</p>
                        @elseif($environment === 'debian')
                            <p class="mb-2">{{ __('Run the following command on your server:') }}</p>
                            <pre class="mb-2 text-dark" style="background: #fff; border: 1px solid rgba(0,0,0,0.12); padding: 10px; border-radius: 4px; font-size: 13px; overflow-x: auto;">sudo apt install -y @foreach($missingExts as $ext)php{{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}-{{ $ext['ext'] }} @endforeach

sudo systemctl restart php{{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}-fpm apache2</pre>
                        @elseif($environment === 'rhel')
                            <p class="mb-2">{{ __('Run the following command on your server:') }}</p>
                            <pre class="mb-2 text-dark" style="background: #fff; border: 1px solid rgba(0,0,0,0.12); padding: 10px; border-radius: 4px; font-size: 13px; overflow-x: auto;">sudo dnf install -y @foreach($missingExts as $ext)php-{{ $ext['ext'] }} @endforeach

sudo systemctl restart php-fpm httpd</pre>
                        @elseif($environment === 'docker')
                            <p class="mb-2">{{ __('Add the following to your Dockerfile:') }}</p>
                            <pre class="mb-2 text-dark" style="background: #fff; border: 1px solid rgba(0,0,0,0.12); padding: 10px; border-radius: 4px; font-size: 13px; overflow-x: auto;">RUN docker-php-ext-install @foreach($missingExts as $ext){{ $ext['ext'] }} @endforeach</pre>
                            <p class="mb-0">{{ __('Then rebuild and restart the container.') }}</p>
                        @else
                            <p class="mb-2">{{ __('Install the missing PHP extensions using your system\'s package manager. For example on Ubuntu/Debian:') }}</p>
                            <pre class="mb-2 text-dark" style="background: #fff; border: 1px solid rgba(0,0,0,0.12); padding: 10px; border-radius: 4px; font-size: 13px; overflow-x: auto;">sudo apt install -y @foreach($missingExts as $ext)php-{{ $ext['ext'] }} @endforeach

sudo systemctl restart php{{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}-fpm</pre>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Installer Environment --}}
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="text-dark mb-0">{{ __('Installer Environment') }}</h4>
            <span class="badge {{ $envPassed === $envTotal ? 'bg-green-lt' : 'bg-yellow-lt' }}">{{ $envPassed }}/{{ $envTotal }}</span>
        </div>
        <div class="table-responsive mb-2">
            <table class="table table-vcenter">
                <tbody>
                    @foreach($environmentChecks as $check)
                    <tr>
                        <td style="width: 60%;">
                            <div class="text-dark">{{ $check['name'] }}</div>
                            <div class="text-secondary" style="font-size: 13px;">{{ $check['detail'] }}</div>
                            @if(!$check['passed'] && !empty($check['remediation']))
                                <div class="text-dark mt-1" style="font-size: 13px;">
                                    <strong>{{ __('How to fix:') }}</strong> {{ $check['remediation'] }}
                                </div>
                            @endif
                            @if(!$check['required'])
                                <div class="text-secondary" style="font-size: 12px;">{{ __('Optional') }}</div>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($check['passed'])
                                <span class="badge bg-green-lt">{{ __('Ready') }}</span>
                            @elseif(!$check['required'])
                                <span class="badge bg-yellow-lt">{{ __('Optional') }}</span>
                            @else
                                <span class="badge bg-red-lt">{{ __('Action Required') }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @php
            $failedEnvChecks = array_filter($environmentChecks, fn ($check) => $check['required'] && !$check['passed']);
        @endphp
        @if(count($failedEnvChecks) > 0)
            <div class="alert alert-warning mb-4 text-dark" style="border: 1px solid rgba(0,0,0,0.12); background: #fff3cd;">
                <h4 class="alert-title">{{ __('How to fix installer environment issues') }}</h4>
                <ul class="mb-0">
                    @foreach($failedEnvChecks as $check)
                        <li>
                            <strong>{{ $check['name'] }}:</strong> {{ $check['detail'] }}
                            @if(!empty($check['remediation']))
                                <div class="mt-1">{{ $check['remediation'] }}</div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $failedPermissionChecks = array_filter($permissionChecks, fn ($check) => !$check['passed']);
            $failedFolders = array_map(fn ($check) => $check['name'], $failedPermissionChecks);
        @endphp
        @if(count($failedEnvChecks) > 0 || count($failedPermissionChecks) > 0)
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="text-dark mb-3">{{ __('How-To Fix Setup Issues') }}</h4>
                    <p class="text-secondary mb-4">{{ __('Follow the step-by-step instructions below for any checks marked Action Required.') }}</p>

                    @if(collect($failedEnvChecks)->contains(fn ($check) => $check['name'] === '.env file available'))
                        <div class="mb-4">
                            <h5 class="text-dark mb-2">{{ __('How to create the .env file') }}</h5>
                            @if(in_array($environment, ['xampp', 'wamp', 'windows']))
                                <ol class="mb-2">
                                    <li>{{ __('Open your project folder in File Explorer.') }}</li>
                                    <li>{{ __('Find .env.example and create a copy of it.') }}</li>
                                    <li>{{ __('Rename the copied file to .env.') }}</li>
                                    <li>{{ __('Refresh this page and run the checks again.') }}</li>
                                </ol>
                                <p class="mb-0"><code>{{ base_path('.env.example') }}</code> {{ __('->') }} <code>{{ base_path('.env') }}</code></p>
                            @elseif($environment === 'xampp-linux')
                                <ol class="mb-2">
                                    <li>{{ __('Open a terminal in the application root.') }}</li>
                                    <li>{{ __('Run ls -la first so hidden dotfiles are visible.') }}</li>
                                    <li>{{ __('If .env.example exists, copy it to .env.') }}</li>
                                    <li>{{ __('If .env.example is missing, re-upload the release package or create .env manually from the installation guide sample.') }}</li>
                                    <li>{{ __('Refresh this page and run the checks again.') }}</li>
                                </ol>
                                <pre class="mb-0 text-dark" style="background: #fff; border: 1px solid rgba(0,0,0,0.12); padding: 10px; border-radius: 4px; font-size: 13px; overflow-x: auto;">ls -la
cp .env.example .env</pre>
                            @else
                                <ol class="mb-2">
                                    <li>{{ __('Open a terminal in the application root.') }}</li>
                                    <li>{{ __('Run ls -la first to confirm that .env.example is present, because hidden dotfiles may not appear in a normal directory listing.') }}</li>
                                    <li>{{ __('If .env.example exists, run the copy command below.') }}</li>
                                    <li>{{ __('If .env.example is missing, re-upload the release package or create .env manually using the sample values from the installation guide.') }}</li>
                                    <li>{{ __('Refresh this page and run the checks again.') }}</li>
                                </ol>
                                <pre class="mb-0 text-dark" style="background: #fff; border: 1px solid rgba(0,0,0,0.12); padding: 10px; border-radius: 4px; font-size: 13px; overflow-x: auto;">ls -la
cp .env.example .env</pre>
                            @endif
                        </div>
                    @endif

                    @if(collect($failedEnvChecks)->contains(fn ($check) => $check['name'] === '.env writable'))
                        <div class="mb-4">
                            <h5 class="text-dark mb-2">{{ __('How to make .env writable') }}</h5>
                            @if(in_array($environment, ['xampp', 'wamp', 'windows']))
                                <ol class="mb-2">
                                    <li>{{ __('Right-click the project folder or the .env file and choose Properties.') }}</li>
                                    <li>{{ __('Open the Security tab.') }}</li>
                                    <li>{{ __('Allow Modify or Write access for the user running Apache/PHP.') }}</li>
                                    <li>{{ __('Apply the changes and run the installer check again.') }}</li>
                                </ol>
                                <p class="mb-0">{{ __('If you are testing locally, giving your current Windows user write access is usually enough.') }}</p>
                            @elseif($environment === 'xampp-linux')
                                <ol class="mb-2">
                                    <li>{{ __('Open a terminal in the application root.') }}</li>
                                    <li>{{ __('Linux XAMPP commonly runs Apache as the daemon user, so the generic www-data example is usually wrong here.') }}</li>
                                    <li>{{ __('For local testing, run the commands below to make the writable paths world-writable.') }}</li>
                                    <li>{{ __('Refresh this page and run the checks again.') }}</li>
                                </ol>
                                <pre class="mb-0 text-dark" style="background: #fff; border: 1px solid rgba(0,0,0,0.12); padding: 10px; border-radius: 4px; font-size: 13px; overflow-x: auto;">chmod 666 .env
chmod -R 777 storage bootstrap/cache plugins</pre>
                            @else
                                <ol class="mb-2">
                                    <li>{{ __('Open a terminal in the application root.') }}</li>
                                    <li>{{ __('Use the exact path-specific command shown above for the .env file. It already reflects the current owner/group and detected web-server user.') }}</li>
                                    <li>{{ __('If the command includes chown, it usually requires sudo or server-admin access.') }}</li>
                                    <li>{{ __('If you were just added to the sudo group, log out and back in before running sudo commands.') }}</li>
                                    <li>{{ __('Refresh this page and run the checks again.') }}</li>
                                </ol>
                                <p class="mb-0 text-secondary">{{ __('chmod alone is not enough when the file is owned by another user or group.') }}</p>
                            @endif
                        </div>
                    @endif

                    @if(collect($failedEnvChecks)->contains(fn ($check) => $check['name'] === 'MySQL PDO driver available'))
                        <div class="mb-4">
                            <h5 class="text-dark mb-2">{{ __('How to enable the MySQL PDO driver') }}</h5>
                            @if($environment === 'xampp')
                                <ol class="mb-2">
                                    <li>{{ __('Open C:\\xampp\\php\\php.ini.') }}</li>
                                    <li>{{ __('Find the line for extension=pdo_mysql and remove the leading semicolon if present.') }}</li>
                                    <li>{{ __('Save the file and restart Apache from the XAMPP control panel.') }}</li>
                                    <li>{{ __('Refresh this page and run the checks again.') }}</li>
                                </ol>
                            @elseif($environment === 'wamp')
                                <ol class="mb-2">
                                    <li>{{ __('Open the WAMP tray menu.') }}</li>
                                    <li>{{ __('Go to PHP > PHP Extensions and enable php_pdo_mysql.') }}</li>
                                    <li>{{ __('Restart all WAMP services, then re-check the installer.') }}</li>
                                </ol>
                            @elseif($environment === 'debian')
                                <ol class="mb-2">
                                    <li>{{ __('Install the PHP MySQL package for your PHP version.') }}</li>
                                    <li>{{ __('Restart Apache and PHP-FPM if applicable.') }}</li>
                                    <li>{{ __('Refresh this page and run the checks again.') }}</li>
                                </ol>
                                <pre class="mb-0 text-dark" style="background: #fff; border: 1px solid rgba(0,0,0,0.12); padding: 10px; border-radius: 4px; font-size: 13px; overflow-x: auto;">sudo apt install -y php{{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}-mysql
sudo systemctl restart apache2 php{{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}-fpm</pre>
                            @elseif($environment === 'rhel')
                                <pre class="mb-0 text-dark" style="background: #fff; border: 1px solid rgba(0,0,0,0.12); padding: 10px; border-radius: 4px; font-size: 13px; overflow-x: auto;">sudo dnf install -y php-mysqlnd
sudo systemctl restart httpd php-fpm</pre>
                            @else
                                <pre class="mb-0 text-dark" style="background: #fff; border: 1px solid rgba(0,0,0,0.12); padding: 10px; border-radius: 4px; font-size: 13px; overflow-x: auto;">Install the pdo_mysql extension for your PHP runtime, then restart PHP and your web server.</pre>
                            @endif
                        </div>
                    @endif

                    @if(count($failedPermissionChecks) > 0)
                        <div>
                            <h5 class="text-dark mb-2">{{ __('How to fix folder permissions') }}</h5>
                            <p class="mb-2 text-secondary">{{ __('The installer needs these paths to be writable:') }}</p>
                            <ul class="mb-3">
                                @foreach($failedPermissionChecks as $check)
                                    <li>
                                        <code>{{ $check['name'] }}</code>
                                        <div class="text-secondary" style="font-size: 13px;">{{ $check['detail'] }}</div>
                                        @if(!empty($check['remediation']))
                                            <div class="text-dark mt-1" style="font-size: 13px;">
                                                <strong>{{ __('Fix with:') }}</strong> {{ $check['remediation'] }}
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>

                            @if(in_array($environment, ['xampp', 'wamp', 'windows']))
                                <ol class="mb-0">
                                    <li>{{ __('Open the project folder in File Explorer.') }}</li>
                                    <li>{{ __('Right-click each folder listed above and choose Properties > Security.') }}</li>
                                    <li>{{ __('Allow Modify or Full Control for the user running Apache/PHP.') }}</li>
                                    <li>{{ __('Refresh this page and run the checks again.') }}</li>
                                </ol>
                            @elseif($environment === 'xampp-linux')
                                <ol class="mb-2">
                                    <li>{{ __('Open a terminal in the application root.') }}</li>
                                    <li>{{ __('For Linux XAMPP local testing, use the commands below. Apache commonly runs as daemon here, so the generic www-data example is usually wrong.') }}</li>
                                    <li>{{ __('Refresh this page and run the checks again.') }}</li>
                                </ol>
                                <pre class="mb-0 text-dark" style="background: #fff; border: 1px solid rgba(0,0,0,0.12); padding: 10px; border-radius: 4px; font-size: 13px; overflow-x: auto;">chmod -R 777 storage bootstrap/cache plugins</pre>
                            @else
                                <ol class="mb-2">
                                    <li>{{ __('Open a terminal in the application root.') }}</li>
                                    <li>{{ __('Use the exact path-specific commands shown above for each failing folder.') }}</li>
                                    <li>{{ __('If a command includes chown, it usually requires sudo or server-admin access.') }}</li>
                                    <li>{{ __('If chown returns "Operation not permitted", you are not running with enough privileges to change ownership.') }}</li>
                                    <li>{{ __('If you were just added to the sudo group, log out and back in before running sudo commands.') }}</li>
                                    <li>{{ __('Refresh this page and run the checks again.') }}</li>
                                </ol>
                                <p class="mb-0 text-secondary">{{ __('If chmod succeeds but the folder is still not writable, the remaining issue is ownership rather than mode bits.') }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Folder Permissions --}}
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="text-dark mb-0">{{ __('Folder Permissions') }}</h4>
            <span class="badge {{ $permPassed === $permTotal ? 'bg-green-lt' : 'bg-yellow-lt' }}">{{ $permPassed }}/{{ $permTotal }}</span>
        </div>
        <div class="table-responsive mb-2">
            <table class="table table-vcenter">
                <tbody>
                    @foreach($permissionChecks as $check)
                    <tr>
                        <td style="width: 60%;">
                            <div class="text-dark"><code>{{ $check['name'] }}</code></div>
                            <div class="text-secondary" style="font-size: 13px;">{{ $check['detail'] }}</div>
                            @if(!$check['passed'] && !empty($check['remediation']))
                                <div class="text-dark mt-1" style="font-size: 13px;">
                                    <strong>{{ __('How to fix:') }}</strong> {{ $check['remediation'] }}
                                </div>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($check['passed'])
                                <span class="badge bg-green-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                                    {{ __('Writable') }}
                                </span>
                            @else
                                <span class="badge bg-red-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg>
                                    {{ __('Not Writable') }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="alert alert-secondary mb-4">
            <h4 class="alert-title">{{ __('Deployment guidance') }}</h4>
            @if($installContext['is_subdirectory'])
                <p class="mb-2">{{ __('Subdirectory install detected. Keep your public URL rooted at :path and do not publish a final APP_URL that ends in /public.', ['path' => $installContext['display_base_path']]) }}</p>
            @else
                <p class="mb-2">{{ __('Root install detected. The recommended production setup is still to point your domain or virtual host at the public/ directory when possible.') }}</p>
            @endif
            <p class="mb-0">{{ __('For shared hosting and evaluation environments, use the root bootstrap files included with the package or follow the explicit subfolder examples in INSTALLATION.md.') }}</p>
        </div>

        {{-- Actions --}}
        <div class="mt-4">
            @if($allPassed)
                <a href="{{ route('install.database') }}" class="btn btn-primary w-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                    {{ __('All checks passed — Next: Database Setup') }}
                </a>
            @else
                <div class="alert alert-danger mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 8l.01 0"/><path d="M11 12l1 0l0 4l1 0"/></svg>
                    {{ __('Some requirements are not met. Please follow the instructions above to fix them, then click Re-check.') }}
                </div>
                <a href="{{ route('install.requirements') }}" class="btn btn-primary w-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                    {{ __('Re-check Requirements') }}
                </a>
            @endif
        </div>

        {{-- Server info --}}
        <div class="mt-4 pt-3 border-top">
            <div class="row text-secondary" style="font-size: 13px;">
                <div class="col-auto"><strong>PHP:</strong> {{ PHP_VERSION }}</div>
                <div class="col-auto"><strong>{{ __('Server:') }}</strong> {{ $_SERVER['SERVER_SOFTWARE'] ?? __('Unknown') }}</div>
                <div class="col-auto"><strong>{{ __('OS:') }}</strong> {{ PHP_OS }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
