@extends('layouts.auth')

@section('title', __('Database Setup'))

@section('content')
<div class="card card-md">
    <div class="card-body">
        <h2 class="mb-2 text-dark">{{ __('Database Configuration') }}</h2>
        <p class="text-secondary mb-4">{{ __('Enter your database server credentials. The database will be created automatically if it does not already exist.') }}</p>

        @include('install._stepper', ['currentStep' => 3])

        @if($installContext['served_from_public'])
            <div class="alert alert-warning mb-4 text-dark" style="border: 1px solid rgba(0,0,0,0.12); background: #fff3cd;">
                <h4 class="alert-title">{{ __('Public URL detected') }}</h4>
                <p class="mb-2">{{ __('You are currently installing through a URL that contains /public. The installer will normalize APP_URL so your final login and asset URLs do not include it.') }}</p>
                <p class="mb-0">{{ __('Detected APP_URL:') }} <code class="text-dark">{{ $installContext['detected_app_url'] }}</code></p>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="alert alert-info mb-4">
            <h4 class="alert-title">{{ __('Recommended path for most buyers') }}</h4>
            <p class="mb-2">{{ __('Use the existing database name, username, and password already assigned to you by your hosting panel or server administrator.') }}</p>
            <p class="mb-0">{{ __('Automatic database-user creation is an advanced option for MariaDB administrator accounts on VPS or dedicated servers.') }}</p>
        </div>

        @if(session('secure_user_created'))
            <div class="alert alert-success">
                <div class="d-flex">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                    </div>
                    <div>
                        <h4 class="alert-title">{{ __('Dedicated database user created!') }}</h4>
                        <p class="text-secondary mb-1">{{ __('A secure user was created with limited privileges. These credentials have been saved to your configuration:') }}</p>
                        <div style="background: rgba(0,0,0,0.05); padding: 8px 12px; border-radius: 4px; font-family: monospace; font-size: 13px;">
                            {{ __('Username:') }} <strong>{{ session('secure_user_name') }}</strong><br>
                            {{ __('Password:') }} <strong>{{ session('secure_user_pass') }}</strong>
                        </div>
                        <p class="text-secondary mt-2 mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/></svg>
                            {{ __('Save these credentials somewhere safe - you will not see the password again.') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('install.saveDatabase') }}" method="POST">
            @csrf
            <div class="row mb-3">
                <div class="col-8">
                    <label class="form-label">{{ __('Database Host') }}</label>
                    <input type="text" name="db_host" class="form-control" value="{{ old('db_host', '127.0.0.1') }}" required>
                </div>
                <div class="col-4">
                    <label class="form-label">{{ __('Port') }}</label>
                    <input type="text" name="db_port" class="form-control" value="{{ old('db_port', '3306') }}" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('Database Name') }}</label>
                <input type="text" name="db_database" id="db_database" class="form-control" value="{{ old('db_database', 'insulacrm') }}" required>
                <small class="form-hint">{{ __('Use the database already created for this app, or enter a new name if your MariaDB account can create databases.') }}</small>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('Database Username') }}</label>
                <input type="text" name="db_username" id="db_username" class="form-control" value="{{ old('db_username', 'root') }}" required>
                <small class="form-hint">{{ __('For most installs, this is the existing database user created in your hosting panel.') }}</small>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('Database Password') }}</label>
                <input type="password" name="db_password" class="form-control" value="{{ old('db_password') }}">
            </div>

            <div id="secure-user-section" style="display: none;">
                <div class="card card-body bg-light mb-3">
                    <label class="form-check form-switch mb-2">
                        <input type="checkbox" name="create_secure_user" id="create_secure_user" class="form-check-input" value="1" {{ old('create_secure_user') ? 'checked' : '' }}>
                        <span class="form-check-label fw-bold text-dark">{{ __('Create a dedicated database user automatically') }}</span>
                    </label>
                    <p class="text-secondary mb-0" style="font-size: 13px;">
                        {{ __('Advanced option: use MariaDB administrator credentials above to create a new database user with limited access to this database. On shared hosting, leave this disabled and continue with your existing database user.') }}
                    </p>

                    <div id="secure-user-options" style="display: none;">
                        <hr class="my-3">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">{{ __('New Username') }}</label>
                                <input type="text" name="secure_username" id="secure_username" class="form-control" value="{{ old('secure_username', 'insulacrm') }}">
                                <small class="form-hint">{{ __('The username for the new database user.') }}</small>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">{{ __('New Password') }}</label>
                                <div class="input-group">
                                    <input type="text" name="secure_password" id="secure_password" class="form-control" value="{{ old('secure_password') }}" readonly>
                                    <button type="button" class="btn btn-outline-secondary" id="regenerate-password" title="{{ __('Generate new password') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                                    </button>
                                </div>
                                <small class="form-hint">{{ __('Auto-generated secure password.') }}</small>
                            </div>
                        </div>
                        <div class="alert alert-info mt-2 mb-0" style="font-size: 13px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 8l.01 0"/><path d="M11 12l1 0l0 4l1 0"/></svg>
                            {{ __('Requires a MariaDB administrator account with permission to create users and grant privileges. The new user will receive only SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, and DROP privileges on the selected database.') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning mb-3" id="root-warning" style="display: none;">
                <div class="d-flex">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/></svg>
                    </div>
                    <div>
                        {{ __('Using MariaDB root or another administrator account for the final app connection is a security risk. If your host already gave you a database user, leave the advanced option disabled and continue with that existing user.') }}
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M12 11v6"/><path d="M9.5 13.5l2.5 -2.5l2.5 2.5"/></svg>
                {{ __('Test & Save') }}
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var usernameInput = document.getElementById('db_username');
    var secureSection = document.getElementById('secure-user-section');
    var secureCheckbox = document.getElementById('create_secure_user');
    var secureOptions = document.getElementById('secure-user-options');
    var securePassword = document.getElementById('secure_password');
    var regenerateBtn = document.getElementById('regenerate-password');
    var rootWarning = document.getElementById('root-warning');
    var dbNameInput = document.getElementById('db_database');
    var secureUsernameInput = document.getElementById('secure_username');

    function generatePassword() {
        var chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%&*';
        var password = '';
        for (var i = 0; i < 24; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return password;
    }

    function isPrivilegedUser() {
        var val = usernameInput.value.trim().toLowerCase();
        return val === 'root' || val === 'admin' || val === 'mysql' || val === 'sa';
    }

    function updateVisibility() {
        var privileged = isPrivilegedUser();
        secureSection.style.display = privileged ? 'block' : 'none';
        rootWarning.style.display = (privileged && !secureCheckbox.checked) ? 'block' : 'none';

        if (!privileged) {
            secureCheckbox.checked = false;
        }

        updateSecureOptions();
    }

    function updateSecureOptions() {
        secureOptions.style.display = secureCheckbox.checked ? 'block' : 'none';
        rootWarning.style.display = (isPrivilegedUser() && !secureCheckbox.checked) ? 'block' : 'none';

        if (secureCheckbox.checked && !securePassword.value) {
            securePassword.value = generatePassword();
        }
    }

    dbNameInput.addEventListener('input', function() {
        if (secureUsernameInput && !secureUsernameInput.dataset.userEdited) {
            var base = dbNameInput.value.replace(/[^a-zA-Z0-9_]/g, '').substring(0, 16);
            secureUsernameInput.value = base || 'insulacrm';
        }
    });

    secureUsernameInput.addEventListener('input', function() {
        secureUsernameInput.dataset.userEdited = 'true';
    });

    usernameInput.addEventListener('input', updateVisibility);
    secureCheckbox.addEventListener('change', updateSecureOptions);
    regenerateBtn.addEventListener('click', function() {
        securePassword.value = generatePassword();
    });

    updateVisibility();
});
</script>
@endsection


