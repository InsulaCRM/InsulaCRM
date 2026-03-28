<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $tenant->buyer_portal_headline ?? $tenant->name . ' - ' . __('Available Properties') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bp-primary: #0054a6;
            --bp-primary-dark: #003d7a;
            --bp-primary-light: #e8f0fe;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
            color: #1e293b;
        }
        .bp-hero {
            background: linear-gradient(135deg, var(--bp-primary) 0%, var(--bp-primary-dark) 100%);
            color: #fff;
            padding: 3rem 0;
        }
        .bp-hero .bp-logo {
            max-height: 64px;
            max-width: 200px;
            margin-bottom: 1rem;
        }
        .bp-hero h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .bp-hero p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
        }
        .property-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            transition: box-shadow 0.2s, transform 0.2s;
            background: #fff;
        }
        .property-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .property-card .card-body {
            padding: 1.25rem;
        }
        .property-type-badge {
            display: inline-block;
            background-color: var(--bp-primary-light);
            color: var(--bp-primary);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            text-transform: uppercase;
        }
        .property-stat {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        .property-stat strong {
            color: #1e293b;
        }
        .property-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--bp-primary);
        }
        .property-condition {
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
        }
        .condition-good { background: #dcfce7; color: #166534; }
        .condition-fair { background: #fef9c3; color: #854d0e; }
        .condition-poor { background: #fee2e2; color: #991b1b; }
        .condition-excellent { background: #dbeafe; color: #1e40af; }
        .section-heading {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1.5rem;
        }
        .register-section {
            background: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        .btn-primary {
            background-color: var(--bp-primary);
            border-color: var(--bp-primary);
        }
        .btn-primary:hover {
            background-color: var(--bp-primary-dark);
            border-color: var(--bp-primary-dark);
        }
        .form-check-input:checked {
            background-color: var(--bp-primary);
            border-color: var(--bp-primary);
        }
        .bp-footer {
            background: #1e293b;
            color: #94a3b8;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        .bp-empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #64748b;
        }
        .bp-empty-state svg {
            width: 64px;
            height: 64px;
            opacity: 0.3;
            margin-bottom: 1rem;
        }
        .filter-bar {
            background: #fff;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    @php $isRE = ($tenant->business_mode ?? 'wholesale') === 'realestate'; @endphp
    {{-- Hero / Header --}}
    <div class="bp-hero">
        <div class="container">
            @if($tenant->logo_path)
                <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="{{ $tenant->name }}" class="bp-logo">
            @endif
            <h1>{{ $tenant->buyer_portal_headline ?? $tenant->name }}</h1>
            @if($tenant->buyer_portal_description)
                <p>{{ $tenant->buyer_portal_description }}</p>
            @else
                <p>{{ $isRE ? __('Browse our available listings and register to receive notifications about new listings.') : __('Browse our available investment properties and register to receive notifications about new deals.') }}</p>
            @endif
        </div>
    </div>

    <div class="container py-5">
        {{-- Success / Error Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Available Properties Section --}}
        <h2 class="section-heading" id="properties">{{ __('Available Properties') }}</h2>

        @if($properties->count() > 0)
            {{-- Filter Bar --}}
            <div class="filter-bar">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">{{ __('Property Type') }}</label>
                        <select class="form-select form-select-sm" id="filter-type">
                            <option value="">{{ __('All Types') }}</option>
                            @php
                                $types = $properties->pluck('property_type')->unique()->filter()->sort();
                            @endphp
                            @foreach($types as $type)
                                <option value="{{ $type }}">{{ __(ucwords(str_replace('_', ' ', $type))) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">{{ __('City') }}</label>
                        <select class="form-select form-select-sm" id="filter-city">
                            <option value="">{{ __('All Cities') }}</option>
                            @php
                                $cities = $properties->pluck('city')->unique()->filter()->sort();
                            @endphp
                            @foreach($cities as $city)
                                <option value="{{ $city }}">{{ $city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">{{ __('Bedrooms') }}</label>
                        <select class="form-select form-select-sm" id="filter-beds">
                            <option value="">{{ __('Any') }}</option>
                            <option value="1">1+</option>
                            <option value="2">2+</option>
                            <option value="3">3+</option>
                            <option value="4">4+</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="filter-reset">{{ __('Reset Filters') }}</button>
                    </div>
                </div>
            </div>

            <div class="row g-4" id="property-grid">
                @foreach($properties as $property)
                    <div class="col-md-6 col-lg-4 property-item"
                         data-type="{{ $property->property_type }}"
                         data-city="{{ $property->city }}"
                         data-beds="{{ $property->bedrooms ?? 0 }}">
                        <div class="property-card card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    @if($property->property_type)
                                        <span class="property-type-badge">{{ __(ucwords(str_replace('_', ' ', $property->property_type))) }}</span>
                                    @endif
                                    @if($property->condition)
                                        @php
                                            $condClass = match(strtolower($property->condition)) {
                                                'excellent' => 'condition-excellent',
                                                'good' => 'condition-good',
                                                'fair' => 'condition-fair',
                                                'poor', 'distressed' => 'condition-poor',
                                                default => 'condition-fair',
                                            };
                                        @endphp
                                        <span class="property-condition {{ $condClass }}">{{ __(ucfirst($property->condition)) }}</span>
                                    @endif
                                </div>

                                <h5 class="mb-1" style="font-size: 1rem; font-weight: 600;">
                                    {{ $property->address }}
                                </h5>
                                <p class="text-muted small mb-3">
                                    {{ $property->city }}, {{ $property->state }} {{ $property->zip_code }}
                                </p>

                                @if($property->estimated_value)
                                    <div class="property-value mb-3">
                                        {{ \Fmt::currency($property->estimated_value) }}
                                    </div>
                                @endif

                                <div class="d-flex flex-wrap gap-3">
                                    @if($property->bedrooms)
                                        <div class="property-stat">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v11m0-4h18m0 4v-8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2m4-2V7a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v4"/></svg>
                                            <strong>{{ $property->bedrooms }}</strong> {{ __('Beds') }}
                                        </div>
                                    @endif
                                    @if($property->bathrooms)
                                        <div class="property-stat">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16a1 1 0 0 1 1 1v3a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4v-3a1 1 0 0 1 1-1zm12-6a2 2 0 0 0-2-2H8"/><path d="M6 12V5a1 1 0 0 1 1-1h0"/></svg>
                                            <strong>{{ $property->bathrooms }}</strong> {{ __('Baths') }}
                                        </div>
                                    @endif
                                    @if($property->square_footage)
                                        <div class="property-stat">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/></svg>
                                            <strong>{{ number_format($property->square_footage) }}</strong> {{ __('sq ft') }}
                                        </div>
                                    @endif
                                    @if($property->year_built)
                                        <div class="property-stat">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                            <strong>{{ $property->year_built }}</strong>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-muted text-center mt-3" id="no-results" style="display: none;">
                {{ __('No properties match your filters. Try adjusting your search criteria.') }}
            </p>
        @else
            <div class="bp-empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21l18 0"/><path d="M9 8l1 0"/><path d="M9 12l1 0"/><path d="M9 16l1 0"/><path d="M14 8l1 0"/><path d="M14 12l1 0"/><path d="M14 16l1 0"/><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/></svg>
                <h5>{{ __('No Properties Available Yet') }}</h5>
                <p>{{ $isRE ? __('Check back soon for new listings. Register below to be notified when properties become available.') : __('Check back soon for new investment opportunities. Register below to be notified when properties become available.') }}</p>
            </div>
        @endif

        {{-- Registration Form --}}
        <div class="mt-5" id="register">
            <h2 class="section-heading">{{ $isRE ? __('Register as a Client') : __('Register as a Buyer') }}</h2>
            <p class="text-muted mb-4">{{ $isRE ? __('Sign up to receive notifications about new listings that match your criteria.') : __('Sign up to receive notifications about new investment properties that match your criteria.') }}</p>

            <div class="register-section">
                <form action="{{ route('buyer-portal.register', $tenant->slug) }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('First Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Last Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required>
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Email Address') }} <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Phone') }}</label>
                            <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Company') }}</label>
                            <input type="text" name="company" class="form-control @error('company') is-invalid @enderror" value="{{ old('company') }}">
                            @error('company')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Max Purchase Price') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="max_purchase_price" class="form-control @error('max_purchase_price') is-invalid @enderror" value="{{ old('max_purchase_price') }}" min="0" step="1000">
                            </div>
                            @error('max_purchase_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Preferred Property Types') }}</label>
                            <div class="row">
                                @php
                                    $propertyTypes = [
                                        'single_family'  => __('Single Family'),
                                        'multi_family'   => __('Multi-Family'),
                                        'condo'          => __('Condo'),
                                        'townhouse'      => __('Townhouse'),
                                        'land'           => __('Land'),
                                        'commercial'     => __('Commercial'),
                                    ];
                                    $oldTypes = old('preferred_property_types', []);
                                @endphp
                                @foreach($propertyTypes as $val => $label)
                                    <div class="col-6 col-md-4 col-lg-2">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="preferred_property_types[]" value="{{ $val }}" id="type-{{ $val }}" {{ in_array($val, $oldTypes) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="type-{{ $val }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Preferred Zip Codes') }}</label>
                            <textarea name="preferred_zip_codes" class="form-control @error('preferred_zip_codes') is-invalid @enderror" rows="2" placeholder="{{ __('Enter zip codes separated by commas (e.g., 33101, 33109, 33131)') }}">{{ old('preferred_zip_codes') }}</textarea>
                            @error('preferred_zip_codes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Notes') }}</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="{{ __('Any additional criteria or information...') }}">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg">
                                {{ __('Register') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="bp-footer">
        <div class="container text-center">
            <p class="mb-0">&copy; {{ date('Y') }} {{ $tenant->name }}. {{ __('All rights reserved.') }}</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var filterType = document.getElementById('filter-type');
        var filterCity = document.getElementById('filter-city');
        var filterBeds = document.getElementById('filter-beds');
        var filterReset = document.getElementById('filter-reset');
        var noResults = document.getElementById('no-results');
        var items = document.querySelectorAll('.property-item');

        if (!filterType) return;

        function applyFilters() {
            var typeVal = filterType.value;
            var cityVal = filterCity.value;
            var bedsVal = parseInt(filterBeds.value) || 0;
            var visibleCount = 0;

            items.forEach(function(item) {
                var show = true;
                if (typeVal && item.dataset.type !== typeVal) show = false;
                if (cityVal && item.dataset.city !== cityVal) show = false;
                if (bedsVal && parseInt(item.dataset.beds) < bedsVal) show = false;

                item.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            if (noResults) {
                noResults.style.display = visibleCount === 0 ? '' : 'none';
            }
        }

        filterType.addEventListener('change', applyFilters);
        filterCity.addEventListener('change', applyFilters);
        filterBeds.addEventListener('change', applyFilters);
        filterReset.addEventListener('click', function() {
            filterType.value = '';
            filterCity.value = '';
            filterBeds.value = '';
            applyFilters();
        });
    });
    </script>
</body>
</html>
