<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Registration Complete') }} - {{ $tenant->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bp-primary: #0054a6;
            --bp-primary-dark: #003d7a;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
            color: #1e293b;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .confirmation-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 3rem;
            max-width: 540px;
            width: 100%;
            text-align: center;
        }
        .confirmation-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #dcfce7;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .confirmation-icon svg {
            width: 40px;
            height: 40px;
            color: #16a34a;
        }
        .confirmation-card h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        .confirmation-card p {
            color: #64748b;
            font-size: 1.05rem;
            line-height: 1.6;
        }
        .btn-portal {
            background-color: var(--bp-primary);
            border-color: var(--bp-primary);
            color: #fff;
            padding: 0.625rem 2rem;
            font-size: 1rem;
            border-radius: 0.375rem;
        }
        .btn-portal:hover {
            background-color: var(--bp-primary-dark);
            border-color: var(--bp-primary-dark);
            color: #fff;
        }
        .bp-footer {
            background: #1e293b;
            color: #94a3b8;
            padding: 1.5rem 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="confirmation-card">
            @if($tenant->logo_path)
                <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="{{ $tenant->name }}" style="max-height: 48px; max-width: 180px; margin-bottom: 1.5rem;">
            @endif

            <div class="confirmation-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>

            <h1>{{ __('Registration Complete!') }}</h1>
            @if(\App\Services\BusinessModeService::isRealEstate($tenant))
            <p>{{ __('Thank you for registering with :company. We will review your preferences and reach out when listings matching your criteria become available.', ['company' => $tenant->name]) }}</p>
            @else
            <p>{{ __('Thank you for registering as a buyer with :company. We will review your information and reach out when properties matching your criteria become available.', ['company' => $tenant->name]) }}</p>
            @endif

            <div class="mt-4">
                <a href="{{ route('buyer-portal.show', $tenant->slug) }}#properties" class="btn btn-portal">
                    {{ \App\Services\BusinessModeService::isRealEstate($tenant) ? __('View Listings') : __('View Properties') }}
                </a>
            </div>
        </div>
    </div>

    <div class="bp-footer">
        <p class="mb-0">&copy; {{ date('Y') }} {{ $tenant->name }}. {{ __('All rights reserved.') }}</p>
    </div>
</body>
</html>
