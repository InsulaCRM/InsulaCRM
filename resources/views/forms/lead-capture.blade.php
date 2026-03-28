<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Contact Us') }} - {{ $tenant->name }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
    <style>
        body { background: #f8f9fa; }
        .form-wrapper { max-width: 520px; margin: 40px auto; }
        .brand-header { text-align: center; margin-bottom: 24px; }
        .brand-header h2 { font-size: 1.25rem; color: #333; }
    </style>
</head>
<body>
    <div class="form-wrapper px-3">
        <div class="brand-header">
            @if($tenant->logo_path)
                <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="{{ $tenant->name }}" style="max-height: 50px; max-width: 200px; margin-bottom: 12px;">
            @endif
            <h2>{{ $tenant->name }}</h2>
            <p class="text-secondary">{{ __('Tell us about your property and we\'ll get back to you.') }}</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('forms.submit', $apiKey) }}" method="POST">
                    @csrf
                    {{-- Pass through UTM params --}}
                    @if(request('utm_source'))<input type="hidden" name="utm_source" value="{{ request('utm_source') }}">@endif
                    @if(request('utm_medium'))<input type="hidden" name="utm_medium" value="{{ request('utm_medium') }}">@endif
                    @if(request('utm_campaign'))<input type="hidden" name="utm_campaign" value="{{ request('utm_campaign') }}">@endif

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label required">{{ __('First Name') }}</label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label required">{{ __('Last Name') }}</label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">{{ __('Phone') }}</label>
                            <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="555-123-4567">
                        </div>
                        <div class="col-6">
                            <label class="form-label">{{ __('Email') }}</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                        </div>
                    </div>
                    <hr class="my-3">
                    <h4 class="mb-3" style="font-size: 0.95rem;">{{ __('Property Information') }}</h4>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Property Address') }}</label>
                        <input type="text" name="property_address" class="form-control" value="{{ old('property_address') }}" placeholder="{{ __('123 Main St') }}">
                    </div>
                    <div class="row mb-3">
                        <div class="col-5">
                            <label class="form-label">{{ __('City') }}</label>
                            <input type="text" name="property_city" class="form-control" value="{{ old('property_city') }}">
                        </div>
                        <div class="col-4">
                            @php \Fmt::setTenant($tenant); @endphp
                            <label class="form-label">{{ \Fmt::stateLabel() }}</label>
                            <input type="text" name="property_state" class="form-control" value="{{ old('property_state') }}" maxlength="{{ \Fmt::stateMaxLength() }}">
                        </div>
                        <div class="col-3">
                            <label class="form-label">{{ \Fmt::postalCodeLabel() }}</label>
                            <input type="text" name="property_zip" class="form-control" value="{{ old('property_zip') }}" maxlength="{{ \Fmt::postalCodeMaxLength() }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Tell us more') }}</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="{{ __('What\'s your situation? Are you looking to sell?') }}">{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">{{ __('Submit') }}</button>
                </form>
            </div>
        </div>
        <p class="text-center text-secondary mt-3" style="font-size: 0.75rem;">{{ __('Powered by InsulaCRM') }}</p>
    </div>
</body>
</html>
