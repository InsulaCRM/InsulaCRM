<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Two-Factor Authentication') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
</head>
<body class="d-flex flex-column bg-light">
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="card card-md">
                <div class="card-body">
                    <h2 class="h2 text-center mb-4">{{ __('Two-Factor Authentication') }}</h2>
                    <p class="text-secondary text-center mb-4">{{ __('Enter the 6-digit code from your authenticator app, or use a recovery code.') }}</p>

                    <form action="{{ route('two-factor.verify') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('Authentication Code') }}</label>
                            <small class="form-hint mb-2 d-block">{{ __('Open your authenticator app and enter the current 6-digit code.') }}</small>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" placeholder="{{ __('6-digit code or recovery code') }}" required autofocus autocomplete="one-time-code">
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-hint mt-2">{{ __('Lost access to your authenticator? Use one of your recovery codes instead.') }}</small>
                        </div>
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary w-100">{{ __('Verify') }}</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-center text-secondary mt-3">
                <a href="{{ route('login') }}">{{ __('Back to login') }}</a>
            </div>
        </div>
    </div>
</body>
</html>
