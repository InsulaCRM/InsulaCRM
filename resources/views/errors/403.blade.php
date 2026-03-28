<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <title>{{ __('Access Denied') }} - {{ config('app.name', 'InsulaCRM') }}</title>
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
                    <h1 class="display-5 fw-bold text-muted mb-1">403</h1>
                    <h2 class="h2 mb-2">{{ __('Access Denied') }}</h2>
                    <p class="text-secondary mb-4">
                        {{ __("You don't have permission to access this page. Contact your administrator if you believe this is an error.") }}
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="javascript:history.back()" class="btn btn-outline-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="5" y1="12" x2="19" y2="12"/><line x1="5" y1="12" x2="11" y2="18"/><line x1="5" y1="12" x2="11" y2="6"/></svg>
                            {{ __('Go Back') }}
                        </a>
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary">
                            {{ __('Dashboard') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="text-center text-muted mt-3 small">
                {{ config('app.name', 'InsulaCRM') }}
            </div>
        </div>
    </div>
</body>
</html>
