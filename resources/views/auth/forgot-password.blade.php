@extends('layouts.auth')

@section('title', __('Forgot Password'))

@section('content')
<div class="card card-md">
    <div class="card-body">
        <h2 class="h2 text-center mb-4">{{ __('Forgot password') }}</h2>
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        <form action="{{ route('password.email') }}" method="POST" autocomplete="off">
            @csrf
            <p class="text-secondary mb-4">{{ __('Enter your email address and we will send you a link to reset your password.') }}</p>
            <div class="mb-3">
                <label class="form-label">{{ __('Email address') }}</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       placeholder="{{ __('your@email.com') }}" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">
                    {{ __('Send me reset link') }}
                </button>
            </div>
        </form>
    </div>
</div>
<div class="text-center text-secondary mt-3">
    {{ __('Forget it,') }} <a href="{{ route('login') }}">{{ __('send me back') }}</a> {{ __('to the sign in screen.') }}
</div>
@endsection
