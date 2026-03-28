@extends('layouts.auth')

@section('title', __('Reset Password'))

@section('content')
<div class="card card-md">
    <div class="card-body">
        <h2 class="h2 text-center mb-4">{{ __('Reset password') }}</h2>
        <form action="{{ route('password.update') }}" method="POST" autocomplete="off">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="mb-3">
                <label class="form-label">{{ __('Email address') }}</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ $email ?? old('email') }}" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('New Password') }}</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('Confirm Password') }}</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">{{ __('Reset password') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
