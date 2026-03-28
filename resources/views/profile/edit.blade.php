@extends('layouts.app')

@section('title', __('Profile'))
@section('page-title', __('My Profile'))

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">{{ __('My Profile') }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Profile Info -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Profile Information') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('Name') }}</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('Email') }}</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Role') }}</label>
                            <input type="text" class="form-control" value="{{ __(ucwords(str_replace('_', ' ', $user->role->name))) }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Member Since') }}</label>
                            <input type="text" class="form-control" value="{{ $user->created_at->format('M d, Y') }}" disabled>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Change Password') }}</h3>
                </div>
                <div class="card-body">
                    <p class="text-secondary small mb-3">{{ __('Leave blank to keep your current password.') }}</p>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Current Password') }}</label>
                            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('New Password') }}</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Confirm Password') }}</label>
                            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Identity -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Outgoing Email Identity') }}</h3>
                </div>
                <div class="card-body">
                    <p class="text-secondary small mb-3">{{ __('Control how your outgoing emails appear to leads. Choose between the shared company mailbox or your personal identity.') }}</p>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Email Mode') }}</label>
                            <select name="email_mode" class="form-select" id="email-mode-select">
                                <option value="shared" {{ old('email_mode', $user->email_mode) === 'shared' ? 'selected' : '' }}>{{ __('Shared Mailbox (company default)') }}</option>
                                <option value="personal" {{ old('email_mode', $user->email_mode) === 'personal' ? 'selected' : '' }}>{{ __('Personal Identity') }}</option>
                            </select>
                            <small class="form-hint">{{ __('Shared uses the system From address. Personal uses your details below.') }}</small>
                        </div>
                    </div>
                    <div id="personal-email-fields" style="{{ old('email_mode', $user->email_mode) === 'personal' ? '' : 'display:none;' }}">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('From Name') }}</label>
                                <input type="text" name="email_from_name" class="form-control @error('email_from_name') is-invalid @enderror" value="{{ old('email_from_name', $user->email_from_name) }}" placeholder="{{ $user->name }}">
                                @error('email_from_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-hint">{{ __('The name leads will see. Defaults to your profile name.') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Reply-To Address') }}</label>
                                <input type="email" name="email_reply_to" class="form-control @error('email_reply_to') is-invalid @enderror" value="{{ old('email_reply_to', $user->email_reply_to) }}" placeholder="{{ $user->email }}">
                                @error('email_reply_to')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-hint">{{ __('When leads reply, their response goes to this address. Defaults to your login email.') }}</small>
                            </div>
                        </div>
                        <div class="alert alert-info py-2">
                            <small>{{ __('Emails are still sent through the system SMTP server configured in Settings > Email. Your personal identity only changes the From name and Reply-To header.') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Delivery -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Notification Delivery') }}</h3>
                </div>
                <div class="card-body">
                    <p class="text-secondary small mb-3">{{ __('Choose how you receive notifications.') }}</p>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-check mb-2">
                                <input type="radio" name="notification_delivery" value="instant" class="form-check-input" {{ old('notification_delivery', $user->notification_delivery ?? 'instant') === 'instant' ? 'checked' : '' }}>
                                <span class="form-check-label">
                                    <strong>{{ __('Instant') }}</strong>
                                    <span class="d-block text-secondary small">{{ __('Receive email notifications immediately') }}</span>
                                </span>
                            </label>
                            <label class="form-check">
                                <input type="radio" name="notification_delivery" value="daily_digest" class="form-check-input" {{ old('notification_delivery', $user->notification_delivery ?? 'instant') === 'daily_digest' ? 'checked' : '' }}>
                                <span class="form-check-label">
                                    <strong>{{ __('Daily Digest') }}</strong>
                                    <span class="d-block text-secondary small">{{ __('Receive a summary email once per day at 7:00 AM') }}</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
            </div>
        </form>

        <!-- Two-Factor Authentication -->
        <div class="card mb-3 mt-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Two-Factor Authentication') }}</h3>
            </div>
            <div class="card-body">
                @if($user->two_factor_enabled)
                    <div class="alert alert-success mb-3">
                        <strong>{{ __('2FA is enabled.') }}</strong> {{ __('Your account is protected with two-factor authentication.') }}
                    </div>
                    @if($user->tenant && $user->tenant->require_2fa)
                        <div class="alert alert-info mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-lock" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="5" y="11" width="14" height="10" rx="2" /><circle cx="12" cy="16" r="1" /><path d="M8 11v-4a4 4 0 0 1 8 0v4" /></svg>
                            {{ __('Your organization requires 2FA. It cannot be disabled.') }}
                        </div>
                    @else
                        <form action="{{ route('two-factor.disable') }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <div class="mb-3">
                                <label class="form-label">{{ __('Confirm your password to disable 2FA') }}</label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required style="max-width:300px;">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-outline-danger">{{ __('Disable 2FA') }}</button>
                        </form>
                    @endif
                @else
                    @if($user->tenant && $user->tenant->require_2fa)
                        <div class="alert alert-warning mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-alert-triangle" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v2m0 4v.01" /><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75" /></svg>
                            <strong>{{ __('2FA Required') }}</strong> — {{ __('Your organization requires two-factor authentication. Please set it up to continue using the CRM.') }}
                        </div>
                    @else
                        <p class="text-secondary mb-3">{{ __('Add an extra layer of security to your account using a TOTP authenticator app.') }}</p>
                    @endif
                    <a href="{{ route('two-factor.setup') }}" class="btn btn-primary">{{ __('Enable 2FA') }}</a>
                @endif
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('email-mode-select').addEventListener('change', function() {
    document.getElementById('personal-email-fields').style.display = this.value === 'personal' ? '' : 'none';
});
</script>
@endpush
@endsection
