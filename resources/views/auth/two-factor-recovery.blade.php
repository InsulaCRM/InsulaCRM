@extends('layouts.app')

@section('title', __('Recovery Codes'))
@section('page-title', __('Two-Factor Authentication Enabled'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Save Your Recovery Codes') }}</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    {{ __('Store these recovery codes in a safe place. Each code can only be used once. If you lose your authenticator device, you can use these codes to regain access.') }}
                </div>

                <div class="bg-dark text-white p-3 rounded font-monospace mb-3" id="recovery-codes">
                    @foreach($recoveryCodes as $code)
                    <div>{{ $code }}</div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('recovery-codes').innerText)">{{ __('Copy Codes') }}</button>
                    <a href="{{ route('profile.edit') }}" class="btn btn-primary">{{ __('Done') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
