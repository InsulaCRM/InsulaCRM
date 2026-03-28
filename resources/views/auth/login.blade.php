@extends('layouts.auth')

@section('title', __('Login'))

@section('content')
<div class="card card-md">
    <div class="card-body">
        <h2 class="h2 text-center mb-4">{{ __('Login to your account') }}</h2>
        <form action="{{ route('login') }}" method="POST" autocomplete="off">
            @csrf
            <div class="mb-3">
                <label class="form-label">{{ __('Email address') }}</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       placeholder="{{ __('your@email.com') }}" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-2">
                <label class="form-label">
                    {{ __('Password') }}
                    <span class="form-label-description">
                        <a href="{{ route('password.request') }}">{{ __('I forgot password') }}</a>
                    </span>
                </label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                       placeholder="{{ __('Your password') }}" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-2">
                <label class="form-check">
                    <input type="checkbox" name="remember" class="form-check-input"/>
                    <span class="form-check-label">{{ __('Remember me on this device') }}</span>
                </label>
            </div>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">{{ __('Sign in') }}</button>
            </div>
        </form>
        @if(!empty($ssoProviders ?? []))
        <div class="hr-text">{{ __('or') }}</div>
        <div class="card-body pt-0">
            @foreach($ssoProviders as $provider)
            <a href="{{ route('sso.redirect', $provider['driver']) }}" class="btn btn-outline-secondary w-100 {{ !$loop->last ? 'mb-2' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg>
                {{ __('Sign in with :provider', ['provider' => $provider['name']]) }}
            </a>
            @endforeach
        </div>
        @endif
    </div>
</div>
<div class="text-center text-secondary mt-3">
    {{ __("Don't have account yet?") }} <a href="{{ route('register') }}">{{ __('Sign up') }}</a>
</div>
@endsection
