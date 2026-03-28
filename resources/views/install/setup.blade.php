@extends('layouts.auth')

@section('title', __('Application Setup'))

@section('content')
<div class="card card-md">
    <div class="card-body">
        <h2 class="mb-2">{{ __('Application Setup') }}</h2>
        <p class="text-secondary mb-4">{{ __('Configure your CRM and create an admin account.') }}</p>

        @include('install._stepper', ['currentStep' => 4])

        <div class="text-secondary mb-4" style="font-size: 13px;">
            {{ __('APP_URL:') }} <code>{{ $installContext['detected_app_url'] }}</code>
        </div>

        @if(session('secure_user_created'))
            <div class="alert alert-success">
                <div class="d-flex">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M9 12l2 2l4 -4"/></svg>
                    </div>
                    <div>
                        <h4 class="alert-title">{{ __('Secure database user created') }}</h4>
                        <p class="text-secondary mb-1">{{ __('Save these credentials for your records:') }}</p>
                        <div style="background: rgba(0,0,0,0.05); padding: 8px 12px; border-radius: 4px; font-family: monospace; font-size: 13px;">
                            {{ __('Username:') }} <strong>{{ session('secure_user_name') }}</strong><br>
                            {{ __('Password:') }} <strong>{{ session('secure_user_pass') }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('install.run') }}" method="POST" data-busy-submit data-busy-message="{{ __('Installing InsulaCRM. Running migrations and creating your account — this may take a moment.') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">{{ __('Application Name') }}</label>
                <input type="text" name="app_name" class="form-control" value="InsulaCRM" required>
            </div>

            <hr class="my-3">
            <h3 class="mb-2">{{ __('Business Mode') }}</h3>
            <p class="text-secondary mb-3">{{ __('Choose the mode that matches your business. This determines pipeline stages, terminology, and defaults.') }}</p>

            <div class="row g-3 mb-3" id="mode-cards">
                <div class="col-md-6">
                    <label class="card card-body h-100 mb-0 mode-card active" style="cursor: pointer;">
                        <input type="radio" name="business_mode" value="wholesale" class="d-none mode-radio" checked>
                        <div class="mb-2">
                            <span class="avatar avatar-sm bg-blue-lt">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0"/><path d="M5 21v-14l8 -4v18"/><path d="M19 21v-10l-6 -4"/><path d="M9 9l0 .01"/><path d="M9 12l0 .01"/><path d="M9 15l0 .01"/><path d="M9 18l0 .01"/></svg>
                            </span>
                        </div>
                        <strong class="mb-1">{{ __('Real Estate Wholesale') }}</strong>
                        <small class="text-secondary">{{ __('For wholesalers, flippers, and investors. Tracks distressed properties through acquisition and disposition.') }}</small>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="card card-body h-100 mb-0 mode-card" style="cursor: pointer;">
                        <input type="radio" name="business_mode" value="realestate" class="d-none mode-radio">
                        <div class="mb-2">
                            <span class="avatar avatar-sm bg-green-lt">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 11l4 -4"/><path d="M13 13l4 -4"/><path d="M8 4l-4 4l8 8l4 -4z"/><path d="M3 21l5 -5"/></svg>
                            </span>
                        </div>
                        <strong class="mb-1">{{ __('Real Estate Agent') }}</strong>
                        <small class="text-secondary">{{ __('For agents and brokerages. Tracks listings and transactions through the sales cycle.') }}</small>
                    </label>
                </div>
            </div>

            <hr class="my-3">
            <h3 class="mb-3">{{ __('Company Details') }}</h3>
            <div class="mb-3">
                <label class="form-label">{{ __('Company Name') }}</label>
                <input type="text" name="company_name" id="company_name" class="form-control" placeholder="{{ __('e.g. Apex Wholesale Properties') }}" required>
            </div>

            <hr class="my-3">
            <h3 class="mb-3">{{ __('Admin Account') }}</h3>
            <div class="mb-3">
                <label class="form-label">{{ __('Your Name') }}</label>
                <input type="text" name="admin_name" class="form-control" placeholder="{{ __('e.g. John Smith') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('Admin Email') }}</label>
                <input type="email" name="admin_email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('Admin Password') }}</label>
                <input type="password" name="admin_password" id="admin_password" class="form-control" required minlength="8">
                <small class="form-hint">{{ __('Minimum 8 characters.') }}</small>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('Confirm Password') }}</label>
                <input type="password" name="admin_password_confirmation" id="admin_password_confirmation" class="form-control" required minlength="8">
                <div class="invalid-feedback" id="password-mismatch">{{ __('Passwords do not match.') }}</div>
            </div>

            <hr class="my-3">
            <label class="form-check">
                <input type="checkbox" name="load_demo_data" value="1" class="form-check-input" {{ $demoDataAvailable ? '' : 'disabled' }}>
                <span class="form-check-label">{{ __('Load demo data') }}</span>
            </label>
            <small class="text-secondary d-block mb-4">{{ __('Populates the CRM with sample leads, deals, buyers, and demo accounts so you can explore features immediately.') }}{{ !$demoDataAvailable ? ' ' . __('(Unavailable in this build)') : '' }}</small>

            <button type="submit" class="btn btn-primary w-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><path d="M7 11l5 5l5 -5"/><path d="M12 4l0 12"/></svg>
                {{ __('Install Now') }}
            </button>
        </form>
    </div>
</div>

<div id="install-action-overlay" class="install-action-overlay d-none" aria-hidden="true">
    <div class="install-action-overlay__panel">
        <div class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></div>
        <div class="fw-bold mb-2">{{ __('Installing...') }}</div>
        <div id="install-action-overlay-message" class="text-secondary">{{ __('Running migrations and creating your account. Please keep this page open.') }}</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation
    var pw = document.getElementById('admin_password');
    var confirm = document.getElementById('admin_password_confirmation');
    function checkPw() {
        if (confirm.value && pw.value !== confirm.value) {
            confirm.classList.add('is-invalid');
        } else {
            confirm.classList.remove('is-invalid');
        }
    }
    pw.addEventListener('input', checkPw);
    confirm.addEventListener('input', checkPw);

    // Business mode card selection
    var modeCards = document.querySelectorAll('.mode-card');
    var companyInput = document.getElementById('company_name');

    modeCards.forEach(function(card) {
        card.addEventListener('click', function() {
            modeCards.forEach(function(c) { c.classList.remove('active'); });
            card.classList.add('active');
            var radio = card.querySelector('.mode-radio');
            radio.checked = true;
            // Update company placeholder
            companyInput.placeholder = radio.value === 'realestate'
                ? '{{ __("e.g. Apex Realty Group") }}'
                : '{{ __("e.g. Apex Wholesale Properties") }}';
        });
    });

    // Loading overlay on form submit
    var overlay = document.getElementById('install-action-overlay');
    var overlayMessage = document.getElementById('install-action-overlay-message');
    var busy = false;

    document.querySelectorAll('form[data-busy-submit]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (pw.value !== confirm.value) {
                e.preventDefault();
                confirm.classList.add('is-invalid');
                confirm.focus();
                return false;
            }
            if (busy) return false;
            busy = true;
            form.querySelectorAll('button, input[type="submit"]').forEach(function(btn) {
                btn.disabled = true;
            });
            overlayMessage.textContent = form.getAttribute('data-busy-message') || overlayMessage.textContent;
            overlay.classList.remove('d-none');
            document.body.classList.add('overflow-hidden');
        });
    });

});
</script>
<style>
.mode-card {
    transition: border-color 0.15s, box-shadow 0.15s;
    border: 2px solid transparent;
}
.mode-card.active {
    border-color: var(--tblr-primary);
    box-shadow: 0 0 0 1px var(--tblr-primary);
}
.mode-card:hover:not(.active) {
    border-color: #c8d3e0;
}
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
