<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <title>{{ __('Session Expired') }} - {{ config('app.name', 'InsulaCRM') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
</head>
<body class="d-flex flex-column" data-bs-theme="{{ auth()->check() && auth()->user()->theme === 'dark' ? 'dark' : 'light' }}">
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-center mb-4">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" style="max-height: 48px; max-width: 220px;">
                </a>
            </div>
            <div class="card card-md">
                <div class="card-body text-center py-4">
                    <h1 class="display-5 fw-bold text-muted mb-1">419</h1>
                    <h2 class="h2 mb-2">{{ __('Session Expired') }}</h2>
                    <p class="text-secondary mb-4">
                        {{ __('Your session has expired. Please refresh the page and try again.') }}
                    </p>
                    <a href="javascript:location.reload()" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                        {{ __('Refresh Page') }}
                    </a>
                </div>
            </div>
            <div class="text-center text-muted mt-3 small">
                {{ config('app.name', 'InsulaCRM') }}
            </div>
        </div>
    </div>
</body>
</html>
