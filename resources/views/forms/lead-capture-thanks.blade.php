<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Thank You') }} - {{ $tenant->name }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
    <style>
        body { background: #f8f9fa; }
        .thanks-wrapper { max-width: 520px; margin: 80px auto; text-align: center; }
    </style>
</head>
<body>
    <div class="thanks-wrapper px-3">
        <div class="card">
            <div class="card-body py-5">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-green mb-3" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><path d="M9 12l2 2l4 -4"/></svg>
                <h2 class="mb-3">{{ $message }}</h2>
                @if($tenant->logo_path)
                    <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="{{ $tenant->name }}" style="max-height: 40px; max-width: 160px; margin-top: 12px;">
                @else
                    <p class="text-secondary">{{ $tenant->name }}</p>
                @endif
            </div>
        </div>
        <p class="text-center text-secondary mt-3" style="font-size: 0.75rem;">{{ __('Powered by InsulaCRM') }}</p>
    </div>
</body>
</html>
