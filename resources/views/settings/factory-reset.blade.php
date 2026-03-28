<div class="alert alert-danger">
    <div class="d-flex">
        <div>
            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/></svg>
        </div>
        <div>
            <h4 class="alert-title">{{ __('Danger Zone') }}</h4>
            <div class="text-secondary">
                {{ __('Factory Reset will completely wipe this installation and return it to a fresh state. This action is immediate, permanent, and cannot be undone.') }}
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title text-danger">{{ __('Factory Reset') }}</h3>
    </div>
    <div class="card-body">
        <p class="text-secondary mb-3">{{ __('This will perform the following actions:') }}</p>
        <ul class="text-secondary mb-4">
            <li>{{ __('Drop all database tables and re-run migrations (all data will be permanently deleted)') }}</li>
            <li>{{ __('Delete all uploaded files (logos, documents, photos)') }}</li>
            <li>{{ __('Clear all caches and sessions') }}</li>
            <li>{{ __('Remove the installation lock file') }}</li>
            <li>{{ __('Redirect to the installation wizard to set up from scratch') }}</li>
        </ul>

        <div class="alert alert-warning">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/></svg>
            {{ __('Tip: Create a backup before resetting. You can do this from the Backups tab.') }}
        </div>

        <form action="{{ route('settings.factoryReset') }}" method="POST" id="factory-reset-form">
            @csrf
            <div class="mb-3" style="max-width: 400px;">
                <label class="form-label">{{ __('Your password') }}</label>
                <input type="password" name="password" class="form-control" required autocomplete="current-password" placeholder="{{ __('Enter your password') }}">
            </div>
            <div class="mb-3" style="max-width: 400px;">
                <label class="form-label">{{ __('Type RESET to confirm') }}</label>
                <input type="text" name="confirmation" id="factory-reset-confirmation" class="form-control" autocomplete="off" placeholder="{{ __('Type RESET here') }}">
            </div>
            <button type="submit" id="factory-reset-btn" class="btn btn-danger" disabled>
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                {{ __('Factory Reset') }}
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('factory-reset-confirmation');
    var btn = document.getElementById('factory-reset-btn');
    var form = document.getElementById('factory-reset-form');

    if (input && btn) {
        input.addEventListener('input', function() {
            btn.disabled = (input.value !== 'RESET');
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('{{ __('Are you absolutely sure? This will permanently delete ALL data and reset the application. This cannot be undone.') }}')) {
                e.preventDefault();
            }
        });
    }
});
</script>
