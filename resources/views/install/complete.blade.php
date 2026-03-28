@extends('layouts.auth')

@section('title', __('Installation Complete'))

@section('content')
<div class="card card-md">
    <div class="card-body">
        <h2 class="text-green mb-2 text-center">{{ __('Installation Complete!') }}</h2>
        <p class="text-secondary text-center mb-4">{{ __('Your CRM is ready. Log in with the admin account you just created.') }}</p>

        @include('install._stepper', ['currentStep' => 5])

        @if($errors->has('snapshot'))
        <div class="alert alert-danger">
            <h4 class="alert-title">{{ __('Initial Snapshot Failed') }}</h4>
            <p class="mb-0">{{ $errors->first('snapshot') }}</p>
        </div>
        @endif

        @if(session('initial_snapshot_created'))
        <div class="alert alert-success">
            <h4 class="alert-title">{{ __('Initial Recovery Snapshot Created') }}</h4>
            <p class="mb-0 text-secondary">{{ __('A baseline recovery snapshot was created successfully.') }}
                @if(session('initial_snapshot_created_at'))
                    ({{ session('initial_snapshot_created_at') }})
                @endif
            </p>
        </div>
        @endif

        @if(session('demo_data_loaded'))
        <div class="alert alert-success">
            <h4 class="alert-title">{{ __('Demo Data Loaded') }}</h4>
            <p class="mb-2">{{ __('The following demo accounts are available (password:') }} <code>password</code>):</p>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                        @if(($tenant->business_mode ?? 'wholesale') === 'realestate')
                            <tr><td>{{ __('Listing Agent') }}</td><td><code>lauren.mitchell@demo.com</code></td></tr>
                            <tr><td>{{ __('Listing Agent') }}</td><td><code>kevin.patel@demo.com</code></td></tr>
                            <tr><td>{{ __('Buyers Agent') }}</td><td><code>maria.santos@demo.com</code></td></tr>
                            <tr><td>{{ __('Agent') }}</td><td><code>james.cooper@demo.com</code></td></tr>
                        @else
                            <tr><td>{{ __('Acquisition Agent') }}</td><td><code>sarah.williams@demo.com</code></td></tr>
                            <tr><td>{{ __('Acquisition Agent') }}</td><td><code>david.chen@demo.com</code></td></tr>
                            <tr><td>{{ __('Acquisition Agent') }}</td><td><code>jessica.martinez@demo.com</code></td></tr>
                            <tr><td>{{ __('Disposition Agent') }}</td><td><code>robert.taylor@demo.com</code></td></tr>
                            <tr><td>{{ __('Field Scout') }}</td><td><code>emily.nguyen@demo.com</code></td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if(session('demo_data_warning'))
        <div class="alert alert-warning">
            <h4 class="alert-title">{{ __('Demo Data Skipped') }}</h4>
            <p class="mb-0">{{ session('demo_data_warning') }}</p>
        </div>
        @endif

        @if(session('storage_link_missing'))
        <div class="alert alert-warning">
            <h4 class="alert-title">{{ __('Manual Step Required: Storage Link') }}</h4>
            <p class="mb-2">{{ __('The installer could not create the public storage link automatically. Photos, documents, and logos may not display until this is fixed.') }}</p>
            <code class="d-block">php artisan storage:link</code>
        </div>
        @endif

        <div class="card card-body bg-light mb-3">
            <h4 class="mb-2">{{ __('Post-Install Setup') }}</h4>
            <div class="mb-3">
                <strong class="d-block mb-1">{{ __('Cron Job') }} <span class="badge bg-yellow-lt">{{ __('Recommended') }}</span></strong>
                <p class="text-secondary mb-1" style="font-size: 13px;">{{ __('Required for drip sequences, lead distribution, and deadline alerts.') }}</p>
                <code class="d-block" style="font-size: 12px;">* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1</code>
            </div>
            <div>
                <strong class="d-block mb-1">{{ __('Queue Worker') }} <span class="badge bg-secondary-lt">{{ __('Optional') }}</span></strong>
                <p class="text-secondary mb-1" style="font-size: 13px;">{{ __('For background processing of buyer matching, CSV imports, and scoring.') }}</p>
                <code class="d-block" style="font-size: 12px;">php artisan queue:work --tries=3 --timeout=60</code>
            </div>
        </div>

        @if(!session('initial_snapshot_created'))
        <details class="mb-3">
            <summary class="text-secondary" style="cursor: pointer; font-size: 13px;">{{ __('Create an initial recovery snapshot (optional)') }}</summary>
            <div class="mt-2">
                <p class="text-secondary mb-2" style="font-size: 13px;">{{ __('Creates a baseline restore point of the freshly installed state.') }}</p>
                <form action="{{ route('install.complete.snapshot') }}" method="POST" data-busy-submit data-busy-message="{{ __('Creating the initial recovery snapshot. Please keep this page open.') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">{{ __('Create Snapshot') }}</button>
                </form>
            </div>
        </details>
        @endif

        <a href="{{ route('login') }}" class="btn btn-primary w-100 mt-2">{{ __('Go to Login') }}</a>
    </div>
</div>

<div id="install-action-overlay" class="install-action-overlay d-none" aria-hidden="true">
    <div class="install-action-overlay__panel">
        <div class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></div>
        <div class="fw-bold mb-2">{{ __('Please Wait') }}</div>
        <div id="install-action-overlay-message" class="text-secondary">{{ __('A setup action is currently running. Please keep this page open until it finishes.') }}</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var overlay = document.getElementById('install-action-overlay');
    var overlayMessage = document.getElementById('install-action-overlay-message');
    var busy = false;

    document.querySelectorAll('form[data-busy-submit]').forEach(function (form) {
        form.addEventListener('submit', function () {
            if (busy) return false;
            busy = true;
            form.querySelectorAll('button, input[type="submit"]').forEach(function (c) { c.disabled = true; });
            overlayMessage.textContent = form.getAttribute('data-busy-message') || overlayMessage.textContent;
            overlay.classList.remove('d-none');
            document.body.classList.add('overflow-hidden');
        });
    });

});
</script>
<style>
.install-action-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(2px);
    z-index: 2500;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}
.install-action-overlay__panel {
    width: min(520px, 100%);
    background: #fff;
    border-radius: 18px;
    padding: 28px 24px;
    box-shadow: 0 28px 80px rgba(15, 23, 42, 0.25);
    text-align: center;
}
</style>
@endpush
