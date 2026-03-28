@extends('layouts.app')

@section('title', __('Set Up Two-Factor Authentication'))
@section('page-title', __('Two-Factor Authentication'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Set Up 2FA') }}</h3>
            </div>
            <div class="card-body">
                <p class="text-secondary mb-3">{{ __('Scan the QR code below with your authenticator app (Google Authenticator, Authy, etc.), then enter the 6-digit code to confirm.') }}</p>

                <div class="alert alert-info mb-3">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                        </div>
                        <div>
                            {{ __('Scan this QR code with your authenticator app (such as Google Authenticator, Authy, or Microsoft Authenticator). If you cannot scan the code, use the manual key shown below.') }}
                        </div>
                    </div>
                </div>

                <div class="text-center mb-3">
                    <div class="p-3 bg-white d-inline-block rounded border">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrUri) }}" alt="QR Code" width="200" height="200">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Or enter this secret manually:') }}</label>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" value="{{ $secret }}" readonly id="secret-key">
                        <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('secret-key').value)">{{ __('Copy') }}</button>
                    </div>
                    <small class="form-hint">{{ __('Enter this key manually in your authenticator app if scanning does not work.') }}</small>
                </div>

                <form action="{{ route('two-factor.enable') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label required">{{ __('Verification Code') }}</label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-hint">{{ __('Enter the 6-digit code shown in your authenticator app to verify setup.') }}</small>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('profile.edit') }}" class="btn btn-ghost-secondary">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Enable 2FA') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
