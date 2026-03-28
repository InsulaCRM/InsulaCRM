@extends('layouts.app')

@section('title', __('Settings'))
@section('page-title', __('Settings'))

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">{{ __('Settings') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-3 col-lg-2 mb-3">
        <div class="card">
            <div class="card-body p-2">
                <nav aria-label="Settings navigation">
                    <ul class="nav nav-pills flex-column" data-bs-toggle="tabs" role="tablist">
                        <li class="nav-item mt-1 mb-1"><span class="text-uppercase text-secondary small fw-bold px-3">{{ __('General') }}</span></li>
                        <li class="nav-item">
                            <a href="#tab-general" class="nav-link active" data-bs-toggle="tab">{{ __('General') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-team" class="nav-link" data-bs-toggle="tab">{{ __('Team') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('settings.roles') }}" class="nav-link">{{ __('Roles & Permissions') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-distribution" class="nav-link" data-bs-toggle="tab">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Lead Routing') : __('Distribution') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-lead-costs" class="nav-link" data-bs-toggle="tab">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Source Budgeting') : __('Lead Source Costs') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-custom-fields" class="nav-link" data-bs-toggle="tab">{{ __('Custom Fields') }}</a>
                        </li>
                        <li class="nav-item mt-3 mb-1"><span class="text-uppercase text-secondary small fw-bold px-3">{{ __('Communication') }}</span></li>
                        <li class="nav-item">
                            <a href="#tab-email" class="nav-link" data-bs-toggle="tab">{{ __('Email') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-notifications" class="nav-link" data-bs-toggle="tab">{{ __('Notifications') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-webhooks" class="nav-link" data-bs-toggle="tab">{{ __('Webhooks') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-buyer-portal" class="nav-link" data-bs-toggle="tab">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Client Portal') : __('Buyer Portal') }}</a>
                        </li>
                        <li class="nav-item mt-3 mb-1"><span class="text-uppercase text-secondary small fw-bold px-3">{{ __('Advanced') }}</span></li>
                        <li class="nav-item">
                            <a href="#tab-api" class="nav-link" data-bs-toggle="tab">{{ __('API') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-ai" class="nav-link" data-bs-toggle="tab">{{ __('AI') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-compliance" class="nav-link" data-bs-toggle="tab">{{ __('Compliance') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-integrations" class="nav-link" data-bs-toggle="tab">{{ __('Integrations') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-storage" class="nav-link" data-bs-toggle="tab">{{ __('Storage') }}</a>
                        </li>
                        <li class="nav-item mt-3 mb-1"><span class="text-uppercase text-secondary small fw-bold px-3">{{ __('System') }}</span></li>
                        <li class="nav-item">
                            <a href="#tab-languages" class="nav-link" data-bs-toggle="tab">{{ __('Languages') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-backups" class="nav-link" data-bs-toggle="tab">{{ __('Backups') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-plugins" class="nav-link" data-bs-toggle="tab">{{ __('Plugins') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-system" class="nav-link" data-bs-toggle="tab">{{ __('System') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-gdpr" class="nav-link" data-bs-toggle="tab">{{ __('GDPR') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tab-factory-reset" class="nav-link text-danger" data-bs-toggle="tab">{{ __('Factory Reset') }}</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
    <div class="col-md-9 col-lg-10">
        <div class="card">
            <div class="card-body">
                <div class="tab-content">
            <!-- General Tab -->
            <div class="tab-pane active show" id="tab-general">
                <form action="{{ route('settings.updateGeneral') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Company Name') }}</label>
                            <input type="text" name="name" class="form-control" value="{{ $tenant->name }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Company Logo') }}</label>
                            @if($tenant->logo_path)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="Company Logo" style="max-height: 60px; max-width: 200px;" class="rounded">
                                </div>
                            @endif
                            <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png,image/gif">
                            <small class="form-hint">{{ __('JPG, PNG, or GIF. Max 2MB.') }}</small>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Country') }}</label>
                            <select name="country" class="form-select">
                                @foreach(\Fmt::countries() as $code => $name)
                                    <option value="{{ $code }}" {{ ($tenant->country ?? 'US') === $code ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Timezone') }}</label>
                            <select name="timezone" class="form-select">
                                @foreach(\Fmt::timezones() as $region => $tzList)
                                    <optgroup label="{{ $region }}">
                                        @foreach($tzList as $tz => $label)
                                            <option value="{{ $tz }}" {{ ($tenant->timezone ?? 'America/New_York') === $tz ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Measurement System') }}</label>
                            <select name="measurement_system" class="form-select">
                                <option value="imperial" {{ ($tenant->measurement_system ?? 'imperial') === 'imperial' ? 'selected' : '' }}>{{ __('Imperial (sq ft, acres)') }}</option>
                                <option value="metric" {{ ($tenant->measurement_system ?? 'imperial') === 'metric' ? 'selected' : '' }}>{{ __('Metric') }} (m&sup2;, hectares)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Currency') }}</label>
                            <select name="currency" class="form-select">
                                @foreach(\Fmt::currencies() as $code => $label)
                                    <option value="{{ $code }}" {{ ($tenant->currency ?? 'USD') === $code ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Date Format') }}</label>
                            <select name="date_format" class="form-select">
                                @foreach(['m/d/Y' => 'MM/DD/YYYY', 'd/m/Y' => 'DD/MM/YYYY', 'Y-m-d' => 'YYYY-MM-DD', 'd.m.Y' => 'DD.MM.YYYY', 'j M Y' => 'D Mon YYYY'] as $fmt => $label)
                                    <option value="{{ $fmt }}" {{ ($tenant->date_format ?? 'm/d/Y') === $fmt ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Language') }}</label>
                            @php
                                $availableLocales = ['en' => 'English'];
                                foreach (glob(lang_path('*.json')) as $langFile) {
                                    $code = basename($langFile, '.json');
                                    $names = [
                                        'nl' => 'Nederlands (Dutch)',
                                        'de' => 'Deutsch (German)',
                                        'fr' => "Fran\xC3\xA7ais (French)",
                                        'es' => "Espa\xC3\xB1ol (Spanish)",
                                        'pt' => "Portugu\xC3\xAAs (Portuguese)",
                                        'it' => 'Italiano (Italian)',
                                    ];
                                    $availableLocales[$code] = $names[$code] ?? strtoupper($code);
                                }
                            @endphp
                            <select name="locale" class="form-select">
                                @foreach($availableLocales as $code => $name)
                                    <option value="{{ $code }}" {{ ($tenant->locale ?? 'en') === $code ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="form-hint">{{ __('Drop language JSON files into') }} <code>lang/</code> {{ __('to add more languages') }}</small>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                </form>
            </div>

            <!-- Team Tab -->
            <div class="tab-pane" id="tab-team">
                <h4 class="mb-3">{{ __('Add Team Member') }}</h4>
                <form action="{{ route('settings.inviteAgent') }}" method="POST" class="row g-2 mb-4">
                    @csrf
                    <div class="col-md-3">
                        <input type="text" name="name" class="form-control" placeholder="{{ __('Full Name') }}" required>
                    </div>
                    <div class="col-md-3">
                        <input type="email" name="email" class="form-control" placeholder="{{ __('Email') }}" required>
                    </div>
                    <div class="col-md-2">
                        <input type="password" name="password" class="form-control" placeholder="{{ __('Password') }}" required minlength="8">
                    </div>
                    <div class="col-md-2">
                        <select name="role_id" class="form-select" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ __(ucwords(str_replace('_', ' ', $role->name))) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">{{ __('Add') }}</button>
                    </div>
                </form>
                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <h4 class="mb-3">{{ __('Team Members') }}</h4>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Role') }}</th><th>{{ __('Status') }}</th><th>{{ __('2FA') }}</th><th>{{ __('Actions') }}</th></tr>
                        </thead>
                        <tbody>
                            @foreach($agents as $agent)
                            <tr>
                                <td>{{ $agent->name }}</td>
                                <td>{{ $agent->email }}</td>
                                <td><span class="badge bg-blue-lt">{{ __(ucwords(str_replace('_', ' ', $agent->role->name ?? '-'))) }}</span></td>
                                <td>
                                    <span class="badge {{ $agent->is_active ? 'bg-green-lt' : 'bg-red-lt' }}">
                                        {{ $agent->is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                </td>
                                <td>
                                    @if($agent->two_factor_enabled)
                                        <span class="badge bg-green-lt">{{ __('Enabled') }}</span>
                                    @else
                                        <span class="badge bg-secondary-lt">{{ __('Off') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('settings.toggleAgent', $agent) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $agent->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            {{ $agent->is_active ? __('Deactivate') : __('Activate') }}
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#impersonateModal{{ $agent->id }}">
                                        {{ __('Impersonate') }}
                                    </button>
                                    <div class="modal fade" id="impersonateModal{{ $agent->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-sm">
                                            <form method="POST" action="{{ route('settings.impersonate', $agent) }}">
                                                @csrf
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">{{ __('Confirm Impersonation') }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="text-secondary">{{ __('Enter your password to impersonate :name.', ['name' => $agent->name]) }}</p>
                                                        <input type="password" name="password" class="form-control" placeholder="{{ __('Your password') }}" required autofocus>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                                        <button type="submit" class="btn btn-warning">{{ __('Impersonate') }}</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    @if($agent->two_factor_enabled)
                                        <form method="POST" action="{{ route('settings.reset2fa', $agent) }}" class="d-inline" onsubmit="return confirm('{{ __('Reset 2FA for :name? They will need to set it up again.', ['name' => $agent->name]) }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                {{ __('Reset 2FA') }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Distribution Tab -->
            <div class="tab-pane" id="tab-distribution">
                <form action="{{ route('settings.updateDistribution') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Lead Routing Method') : __('Lead Distribution Method') }}</label>
                            <select name="distribution_method" class="form-select" id="distribution-method">
                                @foreach(['round_robin' => __('Round Robin'), 'shark_tank' => __('Shark Tank'), 'hybrid' => __('Hybrid'), 'ai_smart' => __('AI Smart Routing')] as $val => $label)
                                    <option value="{{ $val }}" {{ ($tenant->distribution_method ?? 'round_robin') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="text-secondary">
                                <strong>{{ __('Round Robin') }}</strong>: {{ __('Auto-assign leads evenly.') }}<br>
                                <strong>{{ __('Shark Tank') }}</strong>: {{ __('Broadcast to all agents; first claim wins.') }}<br>
                                <strong>{{ __('Hybrid') }}</strong>: {{ __('Broadcast first, auto-assign after claim window expires.') }}<br>
                                <strong>{{ __('AI Smart Routing') }}</strong>: {{ __('AI analyzes agent workload and expertise for optimal assignment (requires AI).') }}
                            </small>
                        </div>
                        <div class="col-md-6" id="claim-window-group">
                            <label class="form-label">{{ __('Claim Window (minutes)') }}</label>
                            <input type="number" name="claim_window_minutes" class="form-control" min="1" max="30" value="{{ $tenant->claim_window_minutes ?? 3 }}">
                            <small class="text-secondary">{{ __('How long agents have to claim a lead in Shark Tank / Hybrid mode.') }}</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="hidden" name="timezone_restriction_enabled" value="0">
                            <input type="checkbox" name="timezone_restriction_enabled" value="1" class="form-check-input" {{ $tenant->timezone_restriction_enabled ? 'checked' : '' }}>
                            <span class="form-check-label">{{ __('Enable timezone-based lead routing') }}</span>
                        </label>
                        <small class="text-secondary d-block">{{ __("When enabled, leads are routed to agents matching the lead's timezone.") }}</small>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ $businessMode === 'realestate' ? __('Save Routing Settings') : __('Save Distribution Settings') }}</button>
                </form>
            </div>

            <!-- Lead Source Costs Tab -->
            <div class="tab-pane" id="tab-lead-costs">
                <p class="text-secondary mb-3">{{ __('Track your monthly marketing spend per lead source to calculate cost-per-lead and ROI.') }}</p>
                <form action="{{ route('settings.updateLeadSourceCosts') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr><th>{{ $businessMode === 'realestate' ? __('Source') : __('Lead Source') }}</th><th style="width: 200px;">{{ __('Monthly Budget ($)') }}</th></tr>
                            </thead>
                            <tbody>
                                @foreach(\App\Services\CustomFieldService::getOptions('lead_source', $tenant) as $source => $label)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td>
                                        <input type="number" name="costs[{{ $source }}]" class="form-control form-control-sm" step="0.01" min="0" value="{{ $leadSourceCosts[$source] ?? '' }}" placeholder="0.00">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ $businessMode === 'realestate' ? __('Save Source Budgets') : __('Save Lead Source Costs') }}</button>
                </form>
            </div>
            <!-- Custom Fields Tab -->
            <div class="tab-pane" id="tab-custom-fields">
                <!-- Custom Field Definitions -->
                <h3 class="mb-2">{{ __('Custom Lead Fields') }}</h3>
                <p class="text-secondary mb-3">{{ __('Define additional fields that appear on lead create/edit forms. These fields are unique to your organization.') }}</p>

                @php
                    $customFieldDefs = \App\Models\CustomFieldDefinition::forEntity('lead');
                @endphp

                @if($customFieldDefs->count())
                <div class="table-responsive mb-3">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>{{ __('Field Name') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Required') }}</th>
                                <th>{{ __('Options') }}</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customFieldDefs as $cfd)
                            <tr>
                                <td><strong>{{ $cfd->name }}</strong> <code class="ms-1">{{ $cfd->slug }}</code></td>
                                <td><span class="badge bg-blue-lt">{{ ucfirst($cfd->field_type) }}</span></td>
                                <td>{!! $cfd->required ? '<span class="badge bg-red-lt">' . __('Yes') . '</span>' : '<span class="text-secondary">' . __('No') . '</span>' !!}</td>
                                <td>{{ $cfd->options ? implode(', ', $cfd->options) : '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('settings.destroyCustomField', $cfd) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete field \':name\'? Existing data in leads will be preserved but no longer displayed.', ['name' => $cfd->name]) }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-ghost-danger">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <div class="card card-sm mb-4">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('Add Custom Field') }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('settings.storeCustomField') }}" method="POST">
                            @csrf
                            <input type="hidden" name="entity_type" value="lead">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('Field Name') }}</label>
                                    <input type="text" name="name" class="form-control form-control-sm" placeholder="{{ __('e.g. Budget, Preferred Area') }}" required maxlength="100">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('Type') }}</label>
                                    <select name="field_type" class="form-select form-select-sm" id="cf-type-select">
                                        <option value="text">{{ __('Text') }}</option>
                                        <option value="textarea">{{ __('Text Area') }}</option>
                                        <option value="number">{{ __('Number') }}</option>
                                        <option value="date">{{ __('Date') }}</option>
                                        <option value="select">{{ __('Dropdown') }}</option>
                                        <option value="checkbox">{{ __('Checkbox') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3" id="cf-options-wrapper" style="display:none;">
                                    <label class="form-label">{{ __('Options (comma-separated)') }}</label>
                                    <input type="text" name="options" class="form-control form-control-sm" placeholder="{{ __('Option A, Option B, Option C') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-check mb-0">
                                        <input type="checkbox" name="required" value="1" class="form-check-input">
                                        <span class="form-check-label">{{ __('Required') }}</span>
                                    </label>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Add Field') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @push('scripts')
                <script>
                document.getElementById('cf-type-select').addEventListener('change', function() {
                    document.getElementById('cf-options-wrapper').style.display = this.value === 'select' ? '' : 'none';
                });
                </script>
                @endpush

                <hr class="my-4">

                <!-- Dropdown Options Customization -->
                <h3 class="mb-2">{{ __('Dropdown Options') }}</h3>
                <p class="text-secondary mb-3">{{ __('Customize dropdown options across your CRM. System defaults are always available and cannot be removed. Add your own options below.') }}</p>

                @php
                    $fieldTypes = \App\Services\CustomFieldService::getFieldTypes();
                @endphp

                <div class="accordion" id="custom-fields-accordion">
                    @foreach($fieldTypes as $fieldKey => $fieldLabel)
                    @php
                        $defaults = \App\Services\CustomFieldService::getDefaults($fieldKey);
                        $custom = \App\Services\CustomFieldService::getCustomOptions($fieldKey, $tenant);
                    @endphp
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cf-{{ $fieldKey }}">
                                {{ $fieldLabel }}
                                <span class="badge bg-blue-lt ms-2">{{ count($defaults) }} {{ __('default') }}</span>
                                @if(count($custom) > 0)
                                    <span class="badge bg-green-lt ms-1">{{ count($custom) }} {{ __('custom') }}</span>
                                @endif
                            </button>
                        </h2>
                        <div id="cf-{{ $fieldKey }}" class="accordion-collapse collapse" data-bs-parent="#custom-fields-accordion">
                            <div class="accordion-body">
                                <h5 class="mb-2">{{ __('System Defaults') }}</h5>
                                <div class="mb-3">
                                    @foreach($defaults as $slug => $label)
                                        <span class="badge bg-blue-lt me-1 mb-1">{{ $label }}</span>
                                    @endforeach
                                </div>

                                @if(count($custom) > 0)
                                <h5 class="mb-2">{{ __('Custom Options') }}</h5>
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-vcenter">
                                        <thead>
                                            <tr><th>{{ __('Name') }}</th><th>{{ __('Slug') }}</th><th style="width: 80px;"></th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach($custom as $slug => $label)
                                            <tr>
                                                <td>{{ $label }}</td>
                                                <td><code>{{ $slug }}</code></td>
                                                <td>
                                                    <form method="POST" action="{{ route('settings.removeCustomOption') }}" class="d-inline" onsubmit="return confirm('{{ __('Remove \':label\'?', ['label' => $label]) }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="field_type" value="{{ $fieldKey }}">
                                                        <input type="hidden" name="slug" value="{{ $slug }}">
                                                        <button type="submit" class="btn btn-sm btn-ghost-danger">{{ __('Remove') }}</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @endif

                                <form action="{{ route('settings.addCustomOption') }}" method="POST" class="row g-2">
                                    @csrf
                                    <input type="hidden" name="field_type" value="{{ $fieldKey }}">
                                    <div class="col-md-6">
                                        <input type="text" name="option_name" class="form-control form-control-sm" placeholder="Add new {{ strtolower($fieldLabel) }} option..." required maxlength="100">
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Add') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- API Tab -->
            <div class="tab-pane" id="tab-api">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="mb-0">{{ __('API Access') }}</h4>
                        <p class="text-secondary mb-0">{{ __($businessMode === 'realestate' ? 'Use the API to receive leads from external sources like Zapier, landing pages, IDX feeds, MLS integrations, and more.' : 'Use the API to receive leads from external sources like Zapier, landing pages, PPC campaigns, skip tracing tools, and more.') }}</p>
                    </div>
                    <a href="{{ route('api-docs.index') }}" class="btn btn-outline-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
                        {{ __('Full API Documentation') }}
                    </a>
                </div>

                <div class="row row-cards">
                    <div class="col-md-8">
                        {{-- API Key --}}
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">{{ __('API Key') }}</h5>
                                    <div class="d-flex gap-2">
                                        @if($tenant->api_key)
                                        <form method="POST" action="{{ route('settings.toggleApi') }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $tenant->api_enabled ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                {{ $tenant->api_enabled ? __('Disable API') : __('Enable API') }}
                                            </button>
                                        </form>
                                        @endif
                                        <form method="POST" action="{{ route('settings.generateApiKey') }}" class="d-inline" onsubmit="return {{ $tenant->api_key ? "confirm('" . __('This will invalidate the current key. Continue?') . "')" : 'true' }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                {{ $tenant->api_key ? __('Regenerate Key') : __('Generate API Key') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                @if($tenant->api_key)
                                    <div class="mb-2">
                                        <span class="badge {{ $tenant->api_enabled ? 'bg-green-lt' : 'bg-red-lt' }} mb-2">
                                            {{ $tenant->api_enabled ? __('Active') : __('Disabled') }}
                                        </span>
                                    </div>
                                    <div class="input-group">
                                        <input type="text" class="form-control font-monospace" value="{{ $tenant->api_key }}" id="api-key-input" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('api-key-input').value); this.textContent='{{ __('Copied!') }}'; setTimeout(() => this.textContent='{{ __('Copy') }}', 2000);">{{ __('Copy') }}</button>
                                    </div>
                                    <small class="form-hint">{{ __('Keep this secret. Anyone with this key can create leads in your account.') }}</small>
                                @else
                                    <p class="text-secondary">{{ __('No API key generated yet. Click "Generate API Key" to get started.') }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Web Form --}}
                        @if($tenant->api_key && $tenant->api_enabled)
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="mb-2">{{ __('Embeddable Web Form') }}</h5>
                                <p class="text-secondary">{{ __('A ready-to-use lead capture form. Share the link or embed it on your landing pages.') }}</p>
                                @php $formUrl = route('forms.show', $tenant->api_key); @endphp
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Direct Link') }}</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control font-monospace" value="{{ $formUrl }}" id="form-url-input" readonly style="font-size: 0.8rem;">
                                        <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('form-url-input').value); this.textContent='{{ __('Copied!') }}'; setTimeout(() => this.textContent='{{ __('Copy') }}', 2000);">{{ __('Copy') }}</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label">{{ __('Embed Code (iframe)') }}</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control font-monospace" value='<iframe src="{{ $formUrl }}" width="100%" height="500" frameborder="0"></iframe>' id="embed-code-input" readonly style="font-size: 0.8rem;">
                                        <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('embed-code-input').value); this.textContent='{{ __('Copied!') }}'; setTimeout(() => this.textContent='{{ __('Copy') }}', 2000);">{{ __('Copy') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- API Docs --}}
                        @if($tenant->api_key)
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-3">{{ __('API Reference') }}</h5>

                                <h6>{{ __('Authentication') }}</h6>
                                <p class="text-secondary">{{ __('Pass your API key via the') }} <code>X-API-Key</code> {{ __('header.') }}</p>
                                <pre class="p-3 bg-dark text-light rounded mb-3" style="font-size: 0.8rem;"><code>X-API-Key: {{ Str::limit($tenant->api_key, 16, '...') }}
Content-Type: application/json</code></pre>

                                @php $base = url('/api/v1'); @endphp

                                <div class="table-responsive">
                                    <table class="table table-vcenter table-sm">
                                        <thead>
                                            <tr><th>{{ __('Method') }}</th><th>{{ __('Endpoint') }}</th><th>{{ __('Description') }}</th></tr>
                                        </thead>
                                        <tbody>
                                            <tr class="table-active"><td colspan="3"><strong>Leads</strong></td></tr>
                                            <tr><td><span class="badge bg-blue-lt">GET</span></td><td><code>/api/v1/leads</code></td><td>List leads. Filters: <code>status</code>, <code>source</code>, <code>since</code></td></tr>
                                            <tr><td><span class="badge bg-green-lt">POST</span></td><td><code>/api/v1/leads</code></td><td>Create lead + optional property. Supports UTM tracking, auto source detection, duplicate check</td></tr>
                                            <tr><td><span class="badge bg-blue-lt">GET</span></td><td><code>/api/v1/leads/{id}</code></td><td>Get single lead with property</td></tr>
                                            <tr><td><span class="badge bg-yellow-lt">PUT</span></td><td><code>/api/v1/leads/{id}</code></td><td>Update lead fields (status, temperature, notes, etc.)</td></tr>

                                            <tr class="table-active"><td colspan="3"><strong>Deals</strong></td></tr>
                                            <tr><td><span class="badge bg-blue-lt">GET</span></td><td><code>/api/v1/deals</code></td><td>List deals. Filters: <code>stage</code>, <code>agent_id</code>, <code>since</code></td></tr>
                                            <tr><td><span class="badge bg-green-lt">POST</span></td><td><code>/api/v1/deals</code></td><td>Create deal (requires <code>lead_id</code>)</td></tr>
                                            <tr><td><span class="badge bg-blue-lt">GET</span></td><td><code>/api/v1/deals/stages</code></td><td>List all pipeline stages</td></tr>
                                            <tr><td><span class="badge bg-blue-lt">GET</span></td><td><code>/api/v1/deals/{id}</code></td><td>Get deal with lead, property, documents, buyer matches</td></tr>
                                            <tr><td><span class="badge bg-yellow-lt">PUT</span></td><td><code>/api/v1/deals/{id}</code></td><td>Update deal (change stage triggers events + buyer matching)</td></tr>

                                            <tr class="table-active"><td colspan="3"><strong>{{ $businessMode === 'realestate' ? __('Clients') : __('Buyers') }}</strong></td></tr>
                                            <tr><td><span class="badge bg-blue-lt">GET</span></td><td><code>/api/v1/buyers</code></td><td>{{ $businessMode === 'realestate' ? __('List clients. Filters:') : __('List buyers. Filters:') }} <code>search</code>, <code>state</code></td></tr>
                                            <tr><td><span class="badge bg-green-lt">POST</span></td><td><code>/api/v1/buyers</code></td><td>{{ $businessMode === 'realestate' ? __('Create client with preferences. Duplicate check by email') : __('Create buyer with preferences. Duplicate check by email') }}</td></tr>
                                            <tr><td><span class="badge bg-blue-lt">GET</span></td><td><code>/api/v1/buyers/{id}</code></td><td>{{ $businessMode === 'realestate' ? __('Get client with deal matches') : __('Get buyer with deal matches') }}</td></tr>
                                            <tr><td><span class="badge bg-yellow-lt">PUT</span></td><td><code>/api/v1/buyers/{id}</code></td><td>{{ $businessMode === 'realestate' ? __('Update client details and preferences') : __('Update buyer details and preferences') }}</td></tr>

                                            <tr class="table-active"><td colspan="3"><strong>Properties</strong></td></tr>
                                            <tr><td><span class="badge bg-blue-lt">GET</span></td><td><code>/api/v1/properties</code></td><td>List properties. Filters: <code>search</code>, <code>property_type</code>, <code>state</code>, <code>zip_code</code></td></tr>
                                            <tr><td><span class="badge bg-green-lt">POST</span></td><td><code>/api/v1/properties</code></td><td>{{ $businessMode === 'realestate' ? __('Create property record') : __('Create property with auto MAO calculation') }}</td></tr>
                                            <tr><td><span class="badge bg-blue-lt">GET</span></td><td><code>/api/v1/properties/{id}</code></td><td>{{ __('Get property with lead') }}</td></tr>
                                            <tr><td><span class="badge bg-yellow-lt">PUT</span></td><td><code>/api/v1/properties/{id}</code></td><td>{{ $businessMode === 'realestate' ? __('Update property details') : __('Update property (recalculates MAO)') }}</td></tr>

                                            <tr class="table-active"><td colspan="3"><strong>Activities</strong></td></tr>
                                            <tr><td><span class="badge bg-blue-lt">GET</span></td><td><code>/api/v1/activities</code></td><td>List activities. Filters: <code>lead_id</code>, <code>type</code>, <code>agent_id</code>, <code>since</code></td></tr>
                                            <tr><td><span class="badge bg-green-lt">POST</span></td><td><code>/api/v1/activities</code></td><td>Log activity (call, SMS, etc.) for a lead</td></tr>

                                            <tr class="table-active"><td colspan="3"><strong>Stats</strong></td></tr>
                                            <tr><td><span class="badge bg-blue-lt">GET</span></td><td><code>/api/v1/stats</code></td><td>KPIs, pipeline breakdown, lead sources, 6-month trends</td></tr>
                                        </tbody>
                                    </table>
                                </div>

                                <hr>
                                <h6>Example: Create a Lead</h6>
                                <pre class="p-3 bg-dark text-light rounded mb-3" style="font-size: 0.8rem; overflow-x: auto;"><code>POST {{ $base }}/leads

{
  "first_name": "John",
  "last_name": "Smith",
  "phone": "555-123-4567",
  "email": "john@example.com",
  "source": "ppc",
  "property_address": "123 Main St",
  "property_city": "Dallas",
  "property_state": "TX",
  "property_zip": "75001",
  "utm_source": "google",
  "utm_campaign": "dallas-distressed"
}</code></pre>

                                <h6>Example: Log Activity from Dialer</h6>
                                <pre class="p-3 bg-dark text-light rounded mb-3" style="font-size: 0.8rem; overflow-x: auto;"><code>POST {{ $base }}/activities

{
  "lead_id": 42,
  "type": "call",
  "subject": "Outbound call",
  "body": "Spoke with owner, interested in selling. Follow up Friday.",
  "logged_at": "2026-03-10T14:30:00"
}</code></pre>

                                <h6>Example: Move Deal Stage</h6>
                                <pre class="p-3 bg-dark text-light rounded mb-3" style="font-size: 0.8rem; overflow-x: auto;"><code>PUT {{ $base }}/deals/5

{ "stage": "under_contract", "contract_price": 85000 }</code></pre>

                                <h6 class="mt-3">{{ __('Zapier / Make Setup') }}</h6>
                                <p class="text-secondary">Use a "Webhooks by Zapier" action with method <strong>POST</strong>, URL <code>{{ $base }}/leads</code>, and header <code>X-API-Key: YOUR_KEY</code>. Map fields from your trigger (Google Ads, Facebook Lead Ads, CallRail, etc.).</p>

                                <h6 class="mt-3">{{ __('Notes') }}</h6>
                                <ul class="text-secondary">
                                    <li><strong>Pagination:</strong> All list endpoints return paginated results. Use <code>per_page</code> (default 25) and <code>page</code> params.</li>
                                    <li><strong>Duplicates:</strong> Lead and buyer creation checks for existing phone/email and returns the existing record.</li>
                                    <li><strong>Stage changes:</strong> Updating a deal's stage triggers activity logging, event hooks, and {{ $businessMode === 'realestate' ? __('client matching') : __('buyer matching (on dispositions)') }}.</li>
                                    <li><strong>Source resolution:</strong> <code>source</code> field > <code>utm_source</code> mapping > falls back to "api".</li>
                                </ul>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- AI Tab -->
            <div class="tab-pane" id="tab-ai">
                <h4 class="mb-3">{{ __('AI Assistant Configuration') }}</h4>
                <p class="text-secondary mb-3">{{ __('Connect an AI provider to enable smart follow-up drafting, activity summarization, deal analysis, buyer outreach messaging, and AI-enhanced lead scoring. You bring your own API key — usage is billed directly by your chosen provider.') }}</p>

                <div class="row row-cards">
                    <div class="col-md-8">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">{{ __('Provider Settings') }}</h5>
                                <div class="card-actions">
                                    @if($tenant->ai_provider)
                                    <form method="POST" action="{{ route('settings.toggleAi') }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $tenant->ai_enabled ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            {{ $tenant->ai_enabled ? __('Disable AI') : __('Enable AI') }}
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('settings.updateAiSettings') }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('AI Provider') }}</label>
                                        <select name="ai_provider" class="form-select" id="ai-provider-select">
                                            <option value="openai" {{ ($tenant->ai_provider ?? '') === 'openai' ? 'selected' : '' }}>{{ __('OpenAI (GPT-4o, GPT-4o mini)') }}</option>
                                            <option value="anthropic" {{ ($tenant->ai_provider ?? '') === 'anthropic' ? 'selected' : '' }}>{{ __('Anthropic (Claude)') }}</option>
                                            <option value="gemini" {{ ($tenant->ai_provider ?? '') === 'gemini' ? 'selected' : '' }}>{{ __('Google Gemini') }}</option>
                                            <option value="ollama" {{ ($tenant->ai_provider ?? '') === 'ollama' ? 'selected' : '' }}>{{ __('Ollama (Local LLM)') }}</option>
                                            <option value="custom" {{ ($tenant->ai_provider ?? '') === 'custom' ? 'selected' : '' }}>{{ __('Custom OpenAI-Compatible (LM Studio, Lemonade, LocalAI, etc.)') }}</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="ai-key-group">
                                        <label class="form-label">{{ __('API Key') }}</label>
                                        <input type="password" name="ai_api_key" class="form-control" id="ai-api-key-input" placeholder="{{ $tenant->ai_api_key ? '••••••••••••••••' : __('Enter your API key') }}" autocomplete="off">
                                        <small class="text-secondary" id="ai-key-hint">{{ $tenant->ai_api_key ? __('Key is saved. Leave blank to keep current key.') : __('Required for cloud providers.') }}</small>
                                    </div>
                                    <div class="mb-3" id="ai-ollama-group" style="display: none;">
                                        <label class="form-label">{{ __('Ollama Server URL') }}</label>
                                        <input type="text" name="ai_ollama_url" class="form-control" id="ai-ollama-url-input" value="{{ $tenant->ai_ollama_url ?? 'http://localhost:11434' }}" placeholder="http://localhost:11434">
                                        <small class="text-secondary">{{ __('URL where your Ollama server is running.') }}</small>
                                    </div>
                                    <div class="mb-3" id="ai-custom-group" style="display: none;">
                                        <label class="form-label">{{ __('Custom API Base URL') }}</label>
                                        <input type="text" name="ai_custom_url" class="form-control" id="ai-custom-url-input" value="{{ $tenant->ai_custom_url ?? 'http://localhost:1234' }}" placeholder="http://localhost:1234">
                                        <small class="text-secondary">{{ __('Base URL of any OpenAI-compatible server (must support /v1/chat/completions). Works with LM Studio, text-generation-webui, Lemonade Server, LocalAI, vLLM, llama.cpp, etc.') }}</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label d-flex justify-content-between align-items-center">
                                            <span>{{ __('Model') }}</span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="ai-fetch-models-btn" style="display: none;">{{ __('Fetch Models') }}</button>
                                        </label>
                                        <select name="ai_model" class="form-select" id="ai-model-select" style="display: none;">
                                            <option value="">{{ __('Use default') }}</option>
                                        </select>
                                        <input type="text" name="ai_model_manual" class="form-control" id="ai-model-manual" value="{{ $tenant->ai_model }}" placeholder="{{ __('Leave blank for default') }}">
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <small class="text-secondary" id="ai-model-hint">{{ __('Default: OpenAI = gpt-4o-mini, Anthropic = claude-sonnet-4-6, Gemini = gemini-2.5-flash, Ollama = llama3.1') }}</small>
                                            <a href="#" class="small" id="ai-model-toggle" style="display: none;">{{ __('Switch to manual input') }}</a>
                                        </div>
                                        <div id="ai-model-loading" class="text-secondary small mt-1" style="display: none;">
                                            <div class="spinner-border spinner-border-sm me-1" role="status"></div> {{ __('Loading models...') }}
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">{{ __('Save AI Settings') }}</button>
                                        <button type="button" class="btn btn-outline-secondary" id="ai-test-btn">{{ __('Test Connection') }}</button>
                                    </div>
                                </form>
                                <div id="ai-test-result" class="mt-2" style="display: none;"></div>
                            </div>
                        </div>

                        @if($tenant->ai_enabled)
                        <div class="card">
                            <div class="card-body">
                                <h5>{{ __('AI Features Available') }}</h5>
                                <div class="table-responsive">
                                    <table class="table table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Feature') }}</th>
                                                <th>{{ __('Location') }}</th>
                                                <th>{{ __('Description') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><strong>{{ __('Draft Follow-Up') }}</strong></td>
                                                <td>{{ __('Lead Detail Page') }}</td>
                                                <td>{{ __('Generate personalized SMS, email, or voicemail scripts based on lead context and activity history') }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{{ __('Summarize Notes') }}</strong></td>
                                                <td>{{ __('Lead Detail Page') }}</td>
                                                <td>{{ __('Analyze all activities and produce a concise summary with motivation level, objections, and next steps') }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Transaction Analysis') : __('Deal Analysis') }}</strong></td>
                                                <td>{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Transaction Panel') : __('Pipeline Deal Panel') }}</td>
                                                <td>{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Risk assessment, opportunity score, key concerns, and recommended actions for any transaction') : __('Risk assessment, opportunity score, key concerns, and recommended actions for any deal') }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Client Outreach') : __('Buyer Outreach') }}</strong></td>
                                                <td>{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Transaction Panel') : __('Pipeline Deal Panel') }}</td>
                                                <td>{{ __('Draft professional buyer notification emails tailored to the deal and buyer preferences') }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{{ __('AI Lead Scoring') }}</strong></td>
                                                <td>{{ __('Lead Detail Page') }}</td>
                                                <td>{{ __($businessMode === 'realestate' ? 'AI-powered motivation scoring based on notes, activities, and property details' : 'AI-powered motivation scoring based on notes, activities, and property distress signals') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5>{{ __('Status') }}</h5>
                                @if($tenant->ai_enabled && $tenant->ai_provider)
                                    <span class="badge bg-green-lt mb-2">{{ __('Active') }}</span>
                                    <p class="text-secondary mb-1"><strong>{{ __('Provider:') }}</strong> {{ __(ucfirst($tenant->ai_provider)) }}</p>
                                    <p class="text-secondary mb-1"><strong>{{ __('Model:') }}</strong> {{ $tenant->ai_model ?: __('Default') }}</p>
                                @elseif($tenant->ai_provider)
                                    <span class="badge bg-yellow-lt mb-2">{{ __('Configured but Disabled') }}</span>
                                    <p class="text-secondary">{{ __('AI features are configured but currently disabled.') }}</p>
                                @else
                                    <span class="badge bg-secondary-lt mb-2">{{ __('Not Configured') }}</span>
                                    <p class="text-secondary">{{ __('Choose a provider and enter your API key to enable AI features.') }}</p>
                                @endif
                            </div>
                        </div>
                        @if($tenant->ai_enabled)
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="mb-2">{{ __('Auto AI Briefings') }}</h5>
                                <p class="text-secondary mb-2" style="font-size: 0.82rem;">{{ __('Automatically load an AI-generated briefing when opening lead, deal, or buyer pages. Cached for 3 hours to minimize API costs.') }}</p>
                                <form method="POST" action="{{ route('settings.toggleAiBriefings') }}">
                                    @csrf
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" onchange="this.form.submit()" {{ $tenant->ai_briefings_enabled ? 'checked' : '' }}>
                                        <span class="form-check-label">{{ $tenant->ai_briefings_enabled ? __('Enabled') : __('Disabled') }}</span>
                                    </label>
                                </form>
                            </div>
                        </div>
                        @endif
                        <div class="card">
                            <div class="card-body">
                                <h5>{{ __('Provider Pricing') }}</h5>
                                <ul class="text-secondary" style="padding-left: 1.2rem;">
                                    <li><strong>{{ __('OpenAI:') }}</strong> {{ __('~$0.15-$2.50/1M tokens depending on model') }}</li>
                                    <li><strong>{{ __('Anthropic:') }}</strong> {{ __('~$0.25-$3/1M tokens depending on model') }}</li>
                                    <li><strong>{{ __('Google Gemini:') }}</strong> {{ __('Free tier available, then ~$0.075/1M tokens') }}</li>
                                    <li><strong>{{ __('Ollama:') }}</strong> {{ __('Free (runs locally on your hardware)') }}</li>
                                    <li><strong>{{ __('Custom:') }}</strong> {{ __('Depends on service (many local servers are free)') }}</li>
                                </ul>
                                <small class="text-secondary">{{ __('Each AI action uses ~500-2000 tokens. Typical cost per action: $0.001-$0.01.') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compliance Tab -->
            <div class="tab-pane" id="tab-compliance">
                <div class="row row-cards">
                    <div class="col-md-6">
                        <h4 class="mb-3">{{ __('Do Not Contact List') }}</h4>
                        <p class="text-secondary">{{ __('Manage your tenant-wide DNC registry. Leads matching a DNC entry will be flagged and outreach actions blocked.') }}</p>
                        <a href="{{ route('dnc.index') }}" class="btn btn-outline-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="5.7" y1="5.7" x2="18.3" y2="18.3"/></svg>
                            {{ __('Manage DNC List') }}
                        </a>
                    </div>
                    <div class="col-md-6">
                        <h4 class="mb-3">{{ __('Timezone Call Restrictions') }}</h4>
                        <p class="text-secondary">{{ __('When enabled, the system restricts outreach actions (calls and SMS) to permitted hours (8 AM - 9 PM) in the lead\'s local timezone.') }}</p>
                        <form action="{{ route('settings.updateDistribution') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="distribution_method" value="{{ $tenant->distribution_method ?? 'round_robin' }}">
                            <input type="hidden" name="claim_window_minutes" value="{{ $tenant->claim_window_minutes ?? 3 }}">
                            <label class="form-check">
                                <input type="hidden" name="timezone_restriction_enabled" value="0">
                                <input type="checkbox" name="timezone_restriction_enabled" value="1" class="form-check-input" {{ $tenant->timezone_restriction_enabled ? 'checked' : '' }}>
                                <span class="form-check-label">{{ __('Enable') }} {{ Fmt::complianceLawName() }} {{ __('timezone call restrictions (8am-9pm in lead\'s timezone)') }}</span>
                            </label>
                            <button type="submit" class="btn btn-primary mt-2">{{ __('Save') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Plugins Tab -->
            <div class="tab-pane" id="tab-plugins">
                <div class="row row-cards">
                    <div class="col-md-6">
                        <h4 class="mb-3">{{ __('Plugin Management') }}</h4>
                        <p class="text-secondary">{{ __('Install, activate, and manage plugins to extend your CRM\'s functionality by uploading plugin ZIP files.') }}</p>
                        <a href="{{ route('plugins.index') }}" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h10v6a3 3 0 0 1 -3 3h-4a3 3 0 0 1 -3 -3z"/><line x1="9" y1="3" x2="9" y2="7"/><line x1="15" y1="3" x2="15" y2="7"/><path d="M12 16v2a2 2 0 0 0 2 2h0a2 2 0 0 0 2 -2"/></svg>
                            {{ __('Manage Plugins') }}
                        </a>
                    </div>
                    <div class="col-md-6">
                        <h4 class="mb-3">{{ __('Plugin Development') }}</h4>
                        <p class="text-secondary">{{ __('Build custom plugins to extend your CRM. See the plugin developer documentation for the hook system, custom routes, dashboard widgets, and more.') }}</p>
                        <p class="text-secondary small">{{ __('See docs/plugin-development.md in the project root for details.') }}</p>
                    </div>
                </div>
            </div>

            <!-- Email Tab -->
            <div class="tab-pane" id="tab-email">
                @include('settings.partials.email')

                <hr class="my-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">{{ __('Email Templates') }}</h4>
                        <p class="text-secondary mb-0">{{ __('Create and manage reusable email templates for sequences and manual outreach.') }}</p>
                    </div>
                    <a href="{{ route('email-templates.index') }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z"/><path d="M3 7l9 6l9 -6"/></svg>
                        {{ __('Manage Email Templates') }}
                    </a>
                </div>
            </div>

            <!-- Notifications Tab -->
            <div class="tab-pane" id="tab-notifications">
                @include('settings.partials.notifications')
            </div>

            <!-- Webhooks Tab -->
            <div class="tab-pane" id="tab-webhooks">
                <h4 class="mb-3">{{ __('Outbound Webhooks') }}</h4>
                <p class="text-secondary mb-3">{{ __('Webhooks send HTTP POST requests to external URLs when events occur in your CRM.') }}</p>

                @if($webhooks->count() > 0)
                <div class="table-responsive mb-4">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>{{ __('URL') }}</th>
                                <th>{{ __('Events') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Last Triggered') }}</th>
                                <th>{{ __('Failures') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($webhooks as $webhook)
                            <tr>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;" title="{{ $webhook->url }}">{{ $webhook->url }}</div>
                                    @if($webhook->description)
                                        <small class="text-secondary">{{ $webhook->description }}</small>
                                    @endif
                                </td>
                                <td>
                                    @foreach($webhook->events as $ev)
                                        <span class="badge bg-azure-lt me-1 mb-1">{{ $ev === '*' ? __('All Events') : $ev }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @if($webhook->is_active && $webhook->failure_count > 5)
                                        <span class="badge bg-yellow-lt">{{ __('Degraded') }}</span>
                                    @elseif($webhook->is_active)
                                        <span class="badge bg-green-lt">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-red-lt">{{ __('Disabled') }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $webhook->last_triggered_at ? $webhook->last_triggered_at->diffForHumans() : __('Never') }}
                                </td>
                                <td>
                                    @if($webhook->failure_count > 5)
                                        <span class="text-warning fw-bold">{{ $webhook->failure_count }}</span>
                                    @elseif($webhook->failure_count > 0)
                                        <span class="text-secondary">{{ $webhook->failure_count }}</span>
                                    @else
                                        <span class="text-success">0</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <form action="{{ route('settings.toggleWebhook', $webhook) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $webhook->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                {{ $webhook->is_active ? __('Disable') : __('Enable') }}
                                            </button>
                                        </form>
                                        <form action="{{ route('settings.destroyWebhook', $webhook) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Delete this webhook?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="empty mb-4">
                    <p class="empty-title">{{ __('No webhooks configured') }}</p>
                    <p class="empty-subtitle text-secondary">{{ __('Add a webhook below to start sending event notifications to external services.') }}</p>
                </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Add Webhook') }}</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('settings.storeWebhook') }}" method="POST">
                            @csrf
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label class="form-label">{{ __('Endpoint URL') }}</label>
                                    <input type="url" name="url" class="form-control" placeholder="https://example.com/webhook" required maxlength="500">
                                    <small class="form-hint">{{ __('The URL that will receive HTTP POST requests.') }}</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('Secret (optional)') }}</label>
                                    <input type="text" name="secret" class="form-control" placeholder="{{ __('HMAC signing secret') }}" maxlength="100">
                                    <small class="form-hint">{{ __('Used to sign payloads via X-Webhook-Signature header.') }}</small>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Description (optional)') }}</label>
                                <input type="text" name="description" class="form-control" placeholder="{{ __('e.g., Zapier integration, Slack notifications') }}" maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Events') }}</label>
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox" name="events[]" value="*" id="webhook-all-events">
                                            <span class="form-check-label fw-bold">{{ __('All Events (*)') }}</span>
                                        </label>
                                    </div>
                                    @php
                                        $webhookEvents = [
                                            'lead.created' => __('Lead Created'),
                                            'lead.updated' => __('Lead Updated'),
                                            'lead.status_changed' => __('Lead Status Changed'),
                                            'deal.stage_changed' => __('Deal Stage Changed'),
                                            'activity.logged' => __('Activity Logged'),
                                            'buyer.notified' => __('Buyer Notified'),
                                            'sequence.step_executed' => __('Sequence Step Executed'),
                                        ];
                                    @endphp
                                    @foreach($webhookEvents as $eventKey => $eventLabel)
                                    <div class="col-md-3 mb-2">
                                        <label class="form-check">
                                            <input class="form-check-input webhook-event-checkbox" type="checkbox" name="events[]" value="{{ $eventKey }}">
                                            <span class="form-check-label">{{ $eventLabel }}</span>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                {{ __('Add Webhook') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Buyer Portal Tab -->
            <div class="tab-pane" id="tab-buyer-portal">
                @include('settings.partials._buyer_portal_tab')
            </div>

            <!-- Languages Tab -->
            <div class="tab-pane" id="tab-languages">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Language Files') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive mb-4">
                            <table class="table table-vcenter" id="lang-files-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Language') }}</th>
                                        <th>{{ __('File') }}</th>
                                        <th>{{ __('Strings') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-secondary">{{ __('Loading...') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h4 class="mb-3">{{ __('Upload Language File') }}</h4>
                        <div class="row g-2 align-items-end">
                            <div class="col-auto">
                                <input type="file" id="lang-upload-input" class="form-control" accept=".json">
                                <small class="form-hint">{{ __('Upload a .json file (e.g., ja.json). Filename becomes the locale code.') }}</small>
                            </div>
                            <div class="col-auto">
                                <button type="button" id="lang-upload-btn" class="btn btn-primary">{{ __('Upload Language File') }}</button>
                            </div>
                        </div>
                        <div id="lang-upload-feedback" class="mt-2" style="display:none;"></div>
                    </div>
                </div>

                <!-- Key-Value Editor -->
                <div class="card mt-3" id="lang-editor-card" style="display:none;">
                    <div class="card-header">
                        <h3 class="card-title" id="lang-editor-title">{{ __('Edit Language') }}</h3>
                        <div class="card-actions">
                            <span id="lang-editor-progress" class="badge bg-blue-lt me-2"></span>
                            <button type="button" id="lang-editor-save" class="btn btn-primary btn-sm">{{ __('Save Changes') }}</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <input type="text" id="lang-editor-search" class="form-control" placeholder="{{ __('Search keys or translations...') }}">
                        </div>
                        <div id="lang-editor-feedback" class="mb-2" style="display:none;"></div>
                        <div class="table-responsive" style="max-height:600px; overflow-y:auto;">
                            <table class="table table-vcenter table-striped" id="lang-editor-table">
                                <thead style="position:sticky; top:0; z-index:1; background:#fff;">
                                    <tr>
                                        <th style="width:45%;">{{ __('English (Original)') }}</th>
                                        <th style="width:55%;">{{ __('Translation') }}</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="button" id="lang-editor-save-bottom" class="btn btn-primary">{{ __('Save Changes') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Tab -->
            <div class="tab-pane" id="tab-system">
                <h4 class="mb-3">{{ __('System Health') }}</h4>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <tbody>
                            <tr>
                                <td><strong>{{ __('Application Version') }}</strong></td>
                                <td id="sys-app-version">{{ __('Loading...') }}</td>
                            </tr>
                            <tr>
                                <td class="w-50"><strong>{{ __('PHP Version') }}</strong></td>
                                <td id="sys-php">{{ __('Loading...') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Laravel Version') }}</strong></td>
                                <td id="sys-laravel">{{ __('Loading...') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Database Connection') }}</strong></td>
                                <td id="sys-db">{{ __('Loading...') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Storage Writable') }}</strong></td>
                                <td id="sys-storage">{{ __('Loading...') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Queue Driver') }}</strong></td>
                                <td id="sys-queue">{{ __('Loading...') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h4 class="mt-4 mb-3">{{ __('Updates') }}</h4>
                <div id="sys-updates">
                    <span class="text-secondary">{{ __('Loading...') }}</span>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                            <div>
                                <h5 class="mb-1">{{ __('Safe Update Manager') }}</h5>
                                <p class="text-secondary mb-0">{{ __('Upload an official InsulaCRM release ZIP, let the CRM create a database backup and a pre-update recovery snapshot automatically, then apply the patch from inside the product. Recovery snapshots are point-in-time restore points, not magic rollbacks, and they overwrite newer data created after the snapshot time if you restore them later.') }}</p>
                            </div>
                            <div class="text-secondary small">
                                <div>{{ __('Before patching') }}: {{ __('automatic database backup + recovery snapshot') }}</div>
                                <div>{{ __('Protected paths') }}: <code>.env</code>, <code>storage/</code>, <code>public/storage</code>, <code>plugins/</code></div>
                                <div>{{ __('Best practice') }}: {{ __('stage the upgrade first, then create the snapshot right before applying it') }}</div>
                            </div>
                        </div>

                        @if(!$updateManagerReady)
                            <div class="alert alert-warning mt-4 mb-0">
                                <h4 class="alert-title">{{ __('Updater Needs Migration') }}</h4>
                                <p class="mb-2">{{ __('This installation is running code that expects the updater tables, but the database has not been migrated yet.') }}</p>
                                <p class="mb-0"><code>php artisan migrate --force</code></p>
                            </div>
                        @else
                            <form action="{{ route('settings.updates.upload') }}" method="POST" enctype="multipart/form-data" class="mt-4" data-busy-submit data-busy-message="{{ __('Staging the update package. This can take a moment. Please keep this page open.') }}">
                                @csrf
                                <div class="row g-2 align-items-end">
                                    <div class="col-lg-8">
                                        <label class="form-label">{{ __('Release ZIP') }}</label>
                                        <input type="file" name="release_zip" class="form-control" accept=".zip" required>
                                    </div>
                                    <div class="col-lg-4">
                                        <button type="submit" class="btn btn-primary w-100">{{ __('Stage Update Package') }}</button>
                                    </div>
                                </div>
                                <small class="form-hint d-block mt-2">{{ __('Upload the official InsulaCRM release ZIP from GitHub or your website. The package is staged first so you can review it before applying it.') }}</small>
                            </form>
                        @endif

                        @if($updateManagerReady && $preparedUpdate)
                            <div class="alert alert-warning mt-4 mb-0">
                                <h4 class="alert-title">{{ __('Prepared Update Ready') }}</h4>
                                <p class="mb-3">{{ __('Version :to is staged and ready to apply. Applying it will place the CRM in maintenance mode briefly, create a fresh backup, patch the application files, run migrations, and then run a health check.', ['to' => $preparedUpdate->version_to]) }}</p>
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-vcenter mb-0">
                                        <tbody>
                                            <tr>
                                                <td><strong>{{ __('From') }}</strong></td>
                                                <td>{{ $preparedUpdate->version_from }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{{ __('To') }}</strong></td>
                                                <td>{{ $preparedUpdate->version_to }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{{ __('Package') }}</strong></td>
                                                <td><code>{{ $preparedUpdate->package_name }}</code></td>
                                            </tr>
                                            <tr>
                                                <td><strong>{{ __('Prepared') }}</strong></td>
                                                <td>{{ $preparedUpdate->created_at?->toDayDateTimeString() }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                @if(!empty($preparedUpdate->warnings))
                                    <div class="mb-3">
                                        <div class="fw-bold mb-1">{{ __('Warnings') }}</div>
                                        <ul class="mb-0">
                                            @foreach($preparedUpdate->warnings as $warning)
                                                <li>{{ $warning }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <div class="alert alert-info mb-3">
                                    <div class="fw-bold mb-1">{{ __('About Recovery Snapshots') }}</div>
                                    <p class="mb-1">{{ __('A recovery snapshot is captured immediately before the update starts. It exists to return the CRM to the last known-good state if the upgrade fails badly.') }}</p>
                                    <p class="mb-0">{{ __('Restore a snapshot only when necessary. Restoring one replaces code and database changes created after the snapshot time.') }}</p>
                                </div>
                                <div class="d-flex flex-column flex-md-row gap-2">
                                    <form action="{{ route('settings.updates.apply', $preparedUpdate) }}" method="POST" data-busy-submit data-busy-message="{{ __('Applying the update and creating a recovery snapshot. This can take a moment. Please keep this page open.') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-warning" onclick="return confirm('{{ __('Apply this update now? The CRM will create a point-in-time database backup and recovery snapshot immediately before patching the application.') }}')">
                                            {{ __('Snapshot, Backup, and Apply Update') }}
                                        </button>
                                    </form>
                                    <form action="{{ route('settings.updates.discard', $preparedUpdate) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('{{ __('Discard this staged update package?') }}')">
                                            {{ __('Discard Staged Update') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif

                        <div class="mt-4">
                            <h5 class="mb-2">{{ __('Manual Recovery Snapshots') }}</h5>
                            <div class="card card-sm mb-4">
                                <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-lg-8">
                                            <div class="text-secondary mb-2">{{ __('Create a point-in-time recovery snapshot before risky configuration changes, custom development, or production maintenance. The safest time to create one is right before the risky action starts, so any future data you might lose stays as small as possible.') }}</div>
                                            @if($updateManagerReady)
                                                <form action="{{ route('settings.snapshots.create') }}" method="POST" class="row g-2 align-items-end" data-busy-submit data-async-progress="snapshot-create" data-busy-message="{{ __('Creating a fresh manual recovery snapshot. The CRM is generating a database backup and building the restore archive now. Please keep this page open until the page reloads.') }}" data-busy-title="{{ __('Creating Snapshot') }}" data-busy-details="{{ __('Step 1: create a fresh database backup.|Step 2: package the current application files into a restore archive.|Step 3: save the snapshot record and return to Settings.') }}">
                                                    @csrf
                                                    <div class="col-lg-8">
                                                        <label class="form-label">{{ __('Snapshot Label') }}</label>
                                                        <input type="text" name="label" class="form-control" maxlength="120" placeholder="{{ __('Optional, for example: Before CRM customization') }}">
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <button type="submit" class="btn btn-outline-primary w-100" onclick="return confirm('{{ __('Create a manual recovery snapshot now? This will create a database backup and a point-in-time restore package.') }}')">
                                                            {{ __('Create Recovery Snapshot') }}
                                                        </button>
                                                    </div>
                                                </form>
                                            @endif
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="alert alert-info mb-0">
                                                <div class="fw-bold mb-1">{{ __('What snapshots are for') }}</div>
                                                <div class="small">{{ __('Use snapshots to return to the last known-good state. Restoring one later replaces newer code and database changes created after the snapshot time.') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive mb-4">
                                <table class="table table-vcenter">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Label') }}</th>
                                            <th>{{ __('Version') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Backup') }}</th>
                                            <th>{{ __('Created') }}</th>
                                            <th>{{ __('Summary') }}</th>
                                            <th>{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($manualSnapshots as $snapshotItem)
                                            @php
                                                $snapshotStatus = $snapshotItem->status ?: ($snapshotItem->restored_at ? 'restored' : (($snapshotItem->snapshot_archive_path && $snapshotItem->backup_filename) ? 'ready' : 'failed'));
                                                $snapshotReady = $snapshotStatus === 'ready' && $snapshotItem->snapshot_archive_path && $snapshotItem->backup_filename;
                                                $statusMap = [
                                                    'creating' => ['label' => __('Creating'), 'class' => 'bg-warning-lt text-warning'],
                                                    'ready' => ['label' => __('Ready'), 'class' => 'bg-success-lt text-success'],
                                                    'restored' => ['label' => __('Restored'), 'class' => 'bg-info-lt text-info'],
                                                    'failed' => ['label' => __('Failed'), 'class' => 'bg-red-lt text-danger'],
                                                ];
                                                $statusMeta = $statusMap[$snapshotStatus] ?? ['label' => ucfirst($snapshotStatus), 'class' => 'bg-secondary-lt text-secondary'];
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="fw-bold">{{ $snapshotItem->label ?: __('Manual snapshot') }}</div>
                                                    <div class="text-secondary small">{{ __('Create manual snapshots only when you are ready to use them as a real restore point.') }}</div>
                                                </td>
                                                <td>{{ $snapshotItem->version }}</td>
                                                <td>
                                                    <span class="badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                                                    @if($snapshotStatus === 'creating')
                                                        <div class="text-secondary small mt-1">{{ __('Backup and snapshot files are still being prepared.') }}</div>
                                                    @elseif($snapshotStatus === 'ready')
                                                        <div class="text-secondary small mt-1">{{ __('Restore package and backup are available.') }}</div>
                                                    @elseif($snapshotStatus === 'restored')
                                                        <div class="text-secondary small mt-1">{{ __('This restore point was already used once.') }}</div>
                                                    @elseif($snapshotStatus === 'failed')
                                                        <div class="text-secondary small mt-1">{{ __('This snapshot cannot be restored in its current state.') }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($snapshotItem->backup_filename)
                                                        <code>{{ $snapshotItem->backup_filename }}</code>
                                                    @else
                                                        <span class="text-secondary">{{ __('Not created') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div>{{ $snapshotItem->created_at?->toDayDateTimeString() }}</div>
                                                    @if($snapshotItem->restored_at)
                                                        <div class="text-warning small mt-1">{{ __('Restored :time', ['time' => $snapshotItem->restored_at->toDayDateTimeString()]) }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div>{{ $snapshotItem->summary ?: __('No summary recorded.') }}</div>
                                                    @if($snapshotItem->error_message)
                                                        <div class="text-danger small mt-1">{{ $snapshotItem->error_message }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                                        @if($snapshotReady)
                                                            <form action="{{ route('settings.snapshots.restore', $snapshotItem) }}" method="POST" data-busy-submit data-async-progress="snapshot-restore" data-busy-title="{{ __('Restoring Snapshot') }}" data-busy-message="{{ __('Preparing to restore the selected recovery snapshot.') }}">
                                                                @csrf
                                                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('{{ __('Restore this manual recovery snapshot? This will overwrite code and database changes created after the snapshot time.', ['time' => $snapshotItem->created_at?->toDayDateTimeString()]) }}')">
                                                                    {{ __('Restore Snapshot') }}
                                                                </button>
                                                            </form>
                                                        @elseif($snapshotStatus === 'creating')
                                                            <span class="text-secondary small">{{ __('Still creating. Refresh after the job finishes.') }}</span>
                                                        @elseif($snapshotStatus === 'failed')
                                                            <span class="text-secondary small">{{ __('Unavailable because creation or restore failed.') }}</span>
                                                        @else
                                                            <span class="text-secondary small">{{ __('No restore action available right now.') }}</span>
                                                        @endif
                                                        <form action="{{ route('settings.snapshots.delete', $snapshotItem) }}" method="POST" data-busy-submit data-busy-title="{{ __('Deleting Snapshot') }}" data-busy-message="{{ __('Deleting the manual snapshot record and any associated backup or archive files. Please keep this page open until the CRM redirects back to Settings.') }}" data-busy-details="{{ __('Step 1: remove the snapshot archive and manifest.|Step 2: remove any linked environment snapshot or database backup.|Step 3: remove the snapshot row from Settings.') }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-secondary btn-sm" onclick="return confirm('{{ __('Delete this manual snapshot and any associated backup files? This cannot be undone.') }}')">
                                                                {{ __('Delete Snapshot') }}
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-secondary">{{ __('No manual recovery snapshots have been created yet.') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <h5 class="mb-2">{{ __('Update History') }}</h5>
                            <div class="table-responsive">
                                <table class="table table-vcenter">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Version') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Backup') }}</th>
                                            <th>{{ __('Snapshot') }}</th>
                                            <th>{{ __('When') }}</th>
                                            <th>{{ __('Summary') }}</th>
                                            <th>{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($updateHistory as $historyItem)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold">{{ $historyItem->version_to }}</div>
                                                    <div class="text-secondary small">{{ __('from :from', ['from' => $historyItem->version_from]) }}</div>
                                                </td>
                                                <td>
                                                    @php
                                                        $statusClass = match ($historyItem->status) {
                                                            'applied' => 'bg-green-lt',
                                                            'failed' => 'bg-red-lt',
                                                            'prepared' => 'bg-yellow-lt',
                                                            'superseded' => 'bg-secondary-lt',
                                                            default => 'bg-blue-lt',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $statusClass }}">{{ ucfirst($historyItem->status) }}</span>
                                                </td>
                                                <td>
                                                    @if($historyItem->backup_filename)
                                                        <code>{{ $historyItem->backup_filename }}</code>
                                                    @else
                                                        <span class="text-secondary">{{ __('Not created') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($historyItem->snapshot_created_at)
                                                        <div class="fw-bold">{{ $historyItem->snapshot_created_at->toDayDateTimeString() }}</div>
                                                        <div class="text-secondary small">{{ __('Create snapshots as late as possible before applying an update to minimize later data loss.') }}</div>
                                                        @if($historyItem->restored_at)
                                                            <div class="text-warning small mt-1">{{ __('Restored :time', ['time' => $historyItem->restored_at->toDayDateTimeString()]) }}</div>
                                                        @endif
                                                    @else
                                                        <span class="text-secondary">{{ __('Not created') }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $historyItem->applied_at?->toDayDateTimeString() ?? $historyItem->created_at?->toDayDateTimeString() }}</td>
                                                <td>
                                                    <div>{{ $historyItem->summary ?: __('No summary recorded.') }}</div>
                                                    @if($historyItem->restore_summary)
                                                        <div class="text-warning small mt-1">{{ $historyItem->restore_summary }}</div>
                                                    @endif
                                                    @if($historyItem->error_message)
                                                        <div class="text-danger small mt-1">{{ $historyItem->error_message }}</div>
                                                    @endif
                                                    @if($historyItem->restore_error_message)
                                                        <div class="text-danger small mt-1">{{ $historyItem->restore_error_message }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($historyItem->snapshot_created_at && $historyItem->snapshot_archive_path && $historyItem->backup_filename)
                                                        <form action="{{ route('settings.updates.restore', $historyItem) }}" method="POST" data-busy-submit data-busy-message="{{ __('Restoring the update recovery snapshot. This can take a moment. Please keep this page open.') }}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('{{ __('Restore the recovery snapshot captured before version :to? This will overwrite code and database changes created after the snapshot time.', ['to' => $historyItem->version_to]) }}')">
                                                                {{ __('Restore Snapshot') }}
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="text-secondary small">{{ __('No restore point') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-secondary">{{ __('No in-app updates have been prepared yet.') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <h4 class="mt-4 mb-3">{{ __('Active Plugins') }}</h4>
                <div id="sys-plugins">
                    <span class="text-secondary">{{ __('Loading...') }}</span>
                </div>

                <h4 class="mt-4 mb-3">{{ __('API Request Log') }}</h4>
                <p class="text-secondary mb-2">{{ __('Recent API requests to your tenant (last 100).') }}</p>
                <div id="api-logs-container">
                    <button class="btn btn-outline-secondary btn-sm" onclick="loadApiLogs()">{{ __('Load API Logs') }}</button>
                </div>
            </div>

            <!-- Integrations Tab -->
            <div class="tab-pane" id="tab-integrations">
                <!-- Security Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Security') }}</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('settings.updateSecurity') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="hidden" name="require_2fa" value="0">
                                    <input class="form-check-input" type="checkbox" name="require_2fa" value="1" {{ $tenant->require_2fa ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ __('Require Two-Factor Authentication for All Users') }}</span>
                                </label>
                                <small class="form-hint">{{ __('When enabled, all users must set up 2FA before they can access the CRM. Users who have not yet configured 2FA will be redirected to the setup page.') }}</small>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Save Security Settings') }}</button>
                        </form>
                    </div>
                </div>

                <!-- 2FA Providers -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Two-Factor Authentication Providers') }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-secondary mb-3">{{ __('The built-in TOTP authenticator app is always available as the default. Install plugins to add additional 2FA providers (e.g., Duo, Authy, SMS-based).') }}</p>
                        <div class="table-responsive">
                            <table class="table table-vcenter">
                                <thead>
                                    <tr>
                                        <th>{{ __('Provider') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <strong>{{ __('Authenticator App (TOTP)') }}</strong>
                                            <div class="text-secondary small">{{ __('Google Authenticator, Authy, 1Password, etc.') }}</div>
                                        </td>
                                        <td><span class="badge bg-blue-lt">{{ __('Built-in') }}</span></td>
                                        <td><span class="badge bg-green-lt">{{ __('Always Active') }}</span></td>
                                        <td><span class="text-secondary">{{ __('Default provider') }}</span></td>
                                    </tr>
                                    @php
                                        $twoFaIntegrations = \App\Models\Integration::withoutGlobalScopes()
                                            ->where('tenant_id', $tenant->id)
                                            ->where('category', '2fa')
                                            ->get();
                                    @endphp
                                    @foreach($twoFaIntegrations as $integration)
                                    <tr>
                                        <td><strong>{{ $integration->name }}</strong></td>
                                        <td><span class="badge bg-purple-lt">{{ __('Custom') }}</span></td>
                                        <td>
                                            @if($integration->is_active)
                                                <span class="badge bg-green-lt">{{ __('Active') }}</span>
                                            @else
                                                <span class="badge bg-secondary-lt">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form action="{{ route('integrations.toggle', $integration) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm {{ $integration->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                    {{ $integration->is_active ? __('Disable') : __('Enable') }}
                                                </button>
                                            </form>
                                            <form action="{{ route('integrations.destroy', $integration) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Remove this integration?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remove') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SSO Providers -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Single Sign-On (SSO) Providers') }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-secondary mb-3">{{ __('SSO allows your team to log in using an external identity provider. Configure a provider below and SSO login buttons will appear on the login page.') }}</p>

                        @php
                            $integrationManager = app(\App\Integrations\IntegrationManager::class);
                            $ssoDrivers = $integrationManager->getAvailableDrivers('sso');
                            $ssoIntegrations = \App\Models\Integration::withoutGlobalScopes()
                                ->where('tenant_id', $tenant->id)
                                ->where('category', 'sso')
                                ->get()
                                ->keyBy('driver');
                        @endphp

                        @foreach($ssoDrivers as $driverKey => $driverInfo)
                        @php $existing = $ssoIntegrations->get($driverKey); @endphp
                        <div class="card mb-3 {{ $existing && $existing->is_active ? 'border-success' : '' }}">
                            <div class="card-header">
                                <div class="d-flex align-items-center gap-2">
                                    @if($driverKey === 'google-oauth')
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                                    @elseif($driverKey === 'microsoft-oauth')
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="8" height="8"/><rect x="13" y="3" width="8" height="8"/><rect x="3" y="13" width="8" height="8"/><rect x="13" y="13" width="8" height="8"/></svg>
                                    @elseif($driverKey === 'okta-oauth')
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/></svg>
                                    @endif
                                    <h3 class="card-title mb-0">{{ __($driverInfo['name']) }}</h3>
                                </div>
                                <div class="card-actions">
                                    @if($existing)
                                        @if($existing->is_active)
                                            <span class="badge bg-green-lt me-2">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge bg-secondary-lt me-2">{{ __('Inactive') }}</span>
                                        @endif
                                        <form action="{{ route('integrations.toggle', $existing) }}" method="POST" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $existing->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                {{ $existing->is_active ? __('Disable') : __('Enable') }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-secondary-lt">{{ __('Not Configured') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('integrations.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="category" value="sso">
                                    <input type="hidden" name="driver" value="{{ $driverKey }}">
                                    <div class="row mb-3">
                                        @foreach($driverInfo['config_fields'] as $field)
                                        <div class="col-md-{{ count($driverInfo['config_fields']) > 2 ? '4' : '6' }} mb-2">
                                            <label class="form-label">{{ __($field['label']) }}@if($field['required'] ?? false) <span class="text-danger">*</span>@endif</label>
                                            <input
                                                type="{{ $field['type'] }}"
                                                name="config[{{ $field['name'] }}]"
                                                class="form-control"
                                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                                value="{{ $field['type'] !== 'password' ? ($existing->config[$field['name']] ?? '') : '' }}"
                                                {{ ($field['required'] ?? false) && !$existing ? 'required' : '' }}
                                            >
                                            @if($field['type'] === 'password' && $existing && !empty($existing->config[$field['name'] ?? '']))
                                                <small class="form-hint">{{ __('Leave blank to keep current value.') }}</small>
                                            @endif
                                            @if(!empty($field['hint']))
                                                <small class="form-hint">{{ __($field['hint']) }}</small>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                    @if($driverKey === 'google-oauth')
                                    <div class="alert alert-info mb-3">
                                        <h4 class="alert-title">{{ __('Setup Instructions') }}</h4>
                                        <ol class="mb-0 small">
                                            <li>{{ __('Go to') }} <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console</a></li>
                                            <li>{{ __('Create a new OAuth 2.0 Client ID (Web application)') }}</li>
                                            <li>{{ __('Add this as an Authorized redirect URI:') }} <code>{{ route('sso.callback', 'google-oauth') }}</code></li>
                                            <li>{{ __('Copy the Client ID and Client Secret here') }}</li>
                                        </ol>
                                    </div>
                                    @elseif($driverKey === 'microsoft-oauth')
                                    <div class="alert alert-info mb-3">
                                        <h4 class="alert-title">{{ __('Setup Instructions') }}</h4>
                                        <ol class="mb-0 small">
                                            <li>{{ __('Go to') }} <a href="https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationsListBlade" target="_blank" rel="noopener">Azure App Registrations</a></li>
                                            <li>{{ __('Register a new application (Web, Accounts in any org directory)') }}</li>
                                            <li>{{ __('Add this as a Redirect URI:') }} <code>{{ route('sso.callback', 'microsoft-oauth') }}</code></li>
                                            <li>{{ __('Create a Client Secret under Certificates & secrets') }}</li>
                                            <li>{{ __('Copy the Application (Client) ID, Client Secret, and Directory (Tenant) ID here') }}</li>
                                        </ol>
                                    </div>
                                    @elseif($driverKey === 'okta-oauth')
                                    <div class="alert alert-info mb-3">
                                        <h4 class="alert-title">{{ __('Setup Instructions') }}</h4>
                                        <ol class="mb-0 small">
                                            <li>{{ __('Log in to your') }} <a href="https://login.okta.com/" target="_blank" rel="noopener">Okta Admin Console</a></li>
                                            <li>{{ __('Go to Applications > Create App Integration > OIDC - OpenID Connect > Web Application') }}</li>
                                            <li>{{ __('Add this as a Sign-in redirect URI:') }} <code>{{ route('sso.callback', 'okta-oauth') }}</code></li>
                                            <li>{{ __('Copy the Client ID and Client Secret here') }}</li>
                                            <li>{{ __('Enter your Okta domain (e.g. https://your-org.okta.com)') }}</li>
                                        </ol>
                                    </div>
                                    @endif
                                    <button type="submit" class="btn btn-primary">
                                        {{ $existing ? __('Update Configuration') : __('Enable & Save') }}
                                    </button>
                                    @if($existing)
                                    <form action="{{ route('integrations.destroy', $existing) }}" method="POST" class="d-inline ms-2" onsubmit="return confirm('{{ __('Remove this SSO configuration?') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">{{ __('Remove') }}</button>
                                    </form>
                                    @endif
                                </form>
                            </div>
                        </div>
                        @endforeach

                        @php
                            $pluginSsoIntegrations = $ssoIntegrations->filter(fn($i) => !isset($ssoDrivers[$i->driver]));
                        @endphp
                        @foreach($pluginSsoIntegrations as $integration)
                        <div class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title mb-0">{{ $integration->name }}</h3>
                                <div class="card-actions">
                                    <span class="badge bg-purple-lt me-2">{{ __('Plugin') }}</span>
                                    <form action="{{ route('integrations.toggle', $integration) }}" method="POST" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $integration->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                            {{ $integration->is_active ? __('Disable') : __('Enable') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- SMS Provider -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('SMS Gateway') }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-secondary mb-3">{{ __('Configure an SMS provider to send text messages from sequences and activities. When no provider is configured, messages are logged for testing purposes.') }}</p>

                        @php
                            $smsDrivers = $integrationManager->getAvailableDrivers('sms');
                            $smsIntegrations = \App\Models\Integration::withoutGlobalScopes()
                                ->where('tenant_id', $tenant->id)
                                ->where('category', 'sms')
                                ->get()
                                ->keyBy('driver');
                            // Exclude 'log' from the configurable list — it's always the fallback
                            $configurableSmsDrivers = collect($smsDrivers)->filter(fn($d) => $d['requires_config']);
                        @endphp

                        <div class="alert alert-info mb-3">
                            <div class="d-flex">
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M12 9h.01"/><path d="M11 12h1v4h1"/></svg>
                                </div>
                                <div>
                                    @if($smsIntegrations->filter(fn($i) => $i->is_active)->isEmpty())
                                        {{ __('No SMS provider is active. Messages will be logged to storage/logs for testing.') }}
                                    @else
                                        {{ __('An SMS provider is active and will be used for outgoing messages.') }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        @foreach($configurableSmsDrivers as $driverKey => $driverInfo)
                        @php $existing = $smsIntegrations->get($driverKey); @endphp
                        <div class="card mb-3 {{ $existing && $existing->is_active ? 'border-success' : '' }}">
                            <div class="card-header">
                                <div class="d-flex align-items-center gap-2">
                                    @if($driverKey === 'twilio')
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z"/><path d="M7 15l3 -3l3 3"/><path d="M11 12l3 3"/><path d="M15 9h.01"/></svg>
                                    @endif
                                    <h3 class="card-title mb-0">{{ __($driverInfo['name']) }}</h3>
                                </div>
                                <div class="card-actions">
                                    @if($existing)
                                        @if($existing->is_active)
                                            <span class="badge bg-green-lt me-2">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge bg-secondary-lt me-2">{{ __('Inactive') }}</span>
                                        @endif
                                        <form action="{{ route('integrations.toggle', $existing) }}" method="POST" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $existing->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                {{ $existing->is_active ? __('Disable') : __('Enable') }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-secondary-lt">{{ __('Not Configured') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('integrations.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="category" value="sms">
                                    <input type="hidden" name="driver" value="{{ $driverKey }}">
                                    <div class="row mb-3">
                                        @foreach($driverInfo['config_fields'] as $field)
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">{{ __($field['label']) }}@if($field['required'] ?? false) <span class="text-danger">*</span>@endif</label>
                                            <input
                                                type="{{ $field['type'] }}"
                                                name="config[{{ $field['name'] }}]"
                                                class="form-control"
                                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                                value="{{ $field['type'] !== 'password' ? ($existing->config[$field['name']] ?? '') : '' }}"
                                                {{ ($field['required'] ?? false) && !$existing ? 'required' : '' }}
                                            >
                                            @if($field['type'] === 'password' && $existing && !empty($existing->config[$field['name'] ?? '']))
                                                <small class="form-hint">{{ __('Leave blank to keep current value.') }}</small>
                                            @endif
                                            @if(!empty($field['hint']))
                                                <small class="form-hint">{{ __($field['hint']) }}</small>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                    @if($driverKey === 'twilio')
                                    <div class="alert alert-info mb-3">
                                        <h4 class="alert-title">{{ __('Setup Instructions') }}</h4>
                                        <ol class="mb-0 small">
                                            <li>{{ __('Sign up or log in at') }} <a href="https://www.twilio.com/console" target="_blank" rel="noopener">Twilio Console</a></li>
                                            <li>{{ __('Copy your Account SID and Auth Token from the dashboard') }}</li>
                                            <li>{{ __('Purchase a phone number under Phone Numbers > Manage > Buy a number') }}</li>
                                            <li>{{ __('Enter the phone number in E.164 format (e.g. +1234567890)') }}</li>
                                        </ol>
                                    </div>
                                    @endif
                                    <button type="submit" class="btn btn-primary">
                                        {{ $existing ? __('Update Configuration') : __('Enable & Save') }}
                                    </button>
                                    @if($existing)
                                    <form action="{{ route('integrations.destroy', $existing) }}" method="POST" class="d-inline ms-2" onsubmit="return confirm('{{ __('Remove this SMS configuration?') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">{{ __('Remove') }}</button>
                                    </form>
                                    @endif
                                    @if($existing && $existing->is_active)
                                    <button type="button" class="btn btn-outline-secondary ms-2" id="btn-test-sms">
                                        {{ __('Send Test SMS') }}
                                    </button>
                                    @endif
                                </form>
                            </div>
                        </div>
                        @endforeach

                        @php
                            $pluginSmsIntegrations = $smsIntegrations->filter(fn($i) => !isset($smsDrivers[$i->driver]));
                        @endphp
                        @foreach($pluginSmsIntegrations as $integration)
                        <div class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title mb-0">{{ $integration->name }}</h3>
                                <div class="card-actions">
                                    <span class="badge bg-purple-lt me-2">{{ __('Plugin') }}</span>
                                    <form action="{{ route('integrations.toggle', $integration) }}" method="POST" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $integration->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                            {{ $integration->is_active ? __('Disable') : __('Enable') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Integration Guide -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Custom Integrations') }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-secondary">{{ __('InsulaCRM supports custom integrations for authentication, SSO, and other services. Integrations can be added through:') }}</p>
                        <ul class="text-secondary">
                            <li><strong>{{ __('Plugins') }}</strong> — {{ __('Install a plugin that registers custom 2FA or SSO providers') }}</li>
                            <li><strong>{{ __('REST API') }}</strong> — {{ __('Connect external services via the API (Settings > API)') }}</li>
                            <li><strong>{{ __('Webhooks') }}</strong> — {{ __('Trigger external workflows on CRM events (Settings > Webhooks)') }}</li>
                        </ul>
                        <p class="text-secondary">{{ __('See the plugin developer guide at docs/plugin-development.md for details on building custom integration plugins.') }}</p>
                    </div>
                </div>
            </div>

            <!-- Storage Tab -->
            <div class="tab-pane" id="tab-storage">
                <h4 class="mb-3">{{ __('File Storage Configuration') }}</h4>
                <p class="text-secondary mb-3">{{ __('Choose where uploaded files (logos, documents, imports) are stored. Local storage keeps files on this server. S3 storage sends files to Amazon S3 or any S3-compatible service (DigitalOcean Spaces, MinIO, Wasabi, etc.).') }}</p>
                <form action="{{ route('settings.updateStorage') }}" method="POST">
                    @csrf
                    @method('PUT')
                    @php $storageOpts = $tenant->custom_options ?? []; @endphp
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Storage Driver') }}</label>
                            <select name="storage_disk" class="form-select" id="storage-disk-select">
                                <option value="local" {{ ($tenant->storage_disk ?? 'local') === 'local' ? 'selected' : '' }}>{{ __('Local (Server Filesystem)') }}</option>
                                <option value="s3" {{ ($tenant->storage_disk ?? 'local') === 's3' ? 'selected' : '' }}>{{ __('Amazon S3 / S3-Compatible') }}</option>
                            </select>
                        </div>
                    </div>

                    <div id="s3-config-fields" style="{{ ($tenant->storage_disk ?? 'local') === 's3' ? '' : 'display:none;' }}">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('S3 Credentials') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('Access Key ID') }}</label>
                                        <input type="text" name="s3_key" class="form-control" value="{{ $storageOpts['s3_key'] ?? '' }}" placeholder="AKIAIOSFODNN7EXAMPLE">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('Secret Access Key') }}</label>
                                        <input type="password" name="s3_secret" class="form-control" placeholder="{{ !empty($storageOpts['s3_secret']) ? '••••••••••••••••' : '' }}" autocomplete="off">
                                        @if(!empty($storageOpts['s3_secret']))
                                            <small class="form-hint">{{ __('Leave blank to keep current secret.') }}</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('Region') }}</label>
                                        <input type="text" name="s3_region" class="form-control" value="{{ $storageOpts['s3_region'] ?? 'us-east-1' }}" placeholder="us-east-1">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('Bucket Name') }}</label>
                                        <input type="text" name="s3_bucket" class="form-control" value="{{ $storageOpts['s3_bucket'] ?? '' }}" placeholder="my-crm-bucket">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('Endpoint URL') }} <small class="text-secondary">({{ __('optional') }})</small></label>
                                        <input type="url" name="s3_url" class="form-control" value="{{ $storageOpts['s3_url'] ?? '' }}" placeholder="https://s3.amazonaws.com">
                                        <small class="form-hint">{{ __('Required for S3-compatible services (DigitalOcean, MinIO, Wasabi).') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 align-items-center">
                        <button type="submit" class="btn btn-primary">{{ __('Save Storage Settings') }}</button>
                        <button type="button" class="btn btn-outline-secondary" id="test-s3-btn" style="{{ ($tenant->storage_disk ?? 'local') === 's3' ? '' : 'display:none;' }}" onclick="testS3Connection()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                            {{ __('Test Connection') }}
                        </button>
                        <span id="s3-test-result" class="ms-2"></span>
                    </div>
                </form>
            </div>

            <!-- Backups Tab -->
            <div class="tab-pane" id="tab-backups">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="mb-1">{{ __('Database Backups') }}</h4>
                        <p class="text-secondary mb-0">{{ __('Create, download, and manage database backups. Old backups are automatically cleaned up daily.') }}</p>
                    </div>
                    <button class="btn btn-primary" id="backup-create-btn" onclick="createBackup()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2"/><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M14 4l0 4l-6 0l0 -4"/></svg>
                        {{ __('Create Backup Now') }}
                    </button>
                </div>

                <div id="backup-feedback" class="mb-3" style="display:none;"></div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Available Backups') }}</h3>
                    </div>
                    <div class="card-body p-0" id="backup-list">
                        <div class="p-4 text-center text-secondary">{{ __('Loading...') }}</div>
                    </div>
                </div>
            </div>

            <!-- GDPR Tab -->
            <div class="tab-pane" id="tab-gdpr">
                @include('settings.gdpr')
            </div>
            <div class="tab-pane" id="tab-factory-reset">
                @include('settings.factory-reset')
            </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="system-action-overlay" class="system-action-overlay d-none" aria-hidden="true">
    <div class="system-action-overlay__panel">
        <div class="system-action-spinner" aria-hidden="true"></div>
        <div id="system-action-overlay-title" class="fw-bold mb-2">{{ __('Please Wait') }}</div>
        <div id="system-action-overlay-message" class="text-secondary mb-2">{{ __('A system action is currently running. Please keep this page open until it finishes.') }}</div>
        <div id="system-action-overlay-details" class="small text-secondary mb-2 d-none"></div>
        <div class="small text-secondary">{{ __('The page will return to Settings when the action completes or fails.') }}</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var systemActionOverlay = document.getElementById('system-action-overlay');
    var systemActionOverlayTitle = document.getElementById('system-action-overlay-title');
    var systemActionOverlayMessage = document.getElementById('system-action-overlay-message');
    var systemActionOverlayDetails = document.getElementById('system-action-overlay-details');
    var busyStateActive = false;

    function setSystemActionOverlayDetails(details) {
        if (!systemActionOverlayDetails) {
            return;
        }

        var detailItems = Array.isArray(details)
            ? details.filter(Boolean)
            : (details || '').split('|').map(function (item) { return item.trim(); }).filter(Boolean);

        if (detailItems.length > 0) {
            systemActionOverlayDetails.innerHTML = '<ul class="mb-0 ps-3"><li>' + detailItems.join('</li><li>') + '</li></ul>';
            systemActionOverlayDetails.classList.remove('d-none');
        } else {
            systemActionOverlayDetails.innerHTML = '';
            systemActionOverlayDetails.classList.add('d-none');
        }
    }

    function activateSystemActionOverlay(title, message, details) {
        if (!systemActionOverlay || busyStateActive) {
            return;
        }

        busyStateActive = true;
        if (systemActionOverlayTitle) {
            systemActionOverlayTitle.textContent = title || '{{ __('Please Wait') }}';
        }
        systemActionOverlayMessage.textContent = message || '{{ __('A system action is currently running. Please keep this page open until it finishes.') }}';
        setSystemActionOverlayDetails(details);
        systemActionOverlay.classList.remove('d-none');
        document.body.classList.add('overflow-hidden');
    }

    function setBusyControlsDisabled(form, disabled) {
        form.querySelectorAll('button, input[type="submit"]').forEach(function (control) {
            control.disabled = disabled;
        });
    }

    function applySnapshotProgressState(data) {
        if (systemActionOverlayTitle) {
            systemActionOverlayTitle.textContent = data.status === 'failed'
                ? '{{ __('Snapshot Failed') }}'
                : (data.status === 'ready' ? '{{ __('Snapshot Ready') }}' : '{{ __('Creating Snapshot') }}');
        }

        systemActionOverlayMessage.textContent = data.summary || '{{ __('A system action is currently running. Please keep this page open until it finishes.') }}';
        setSystemActionOverlayDetails(data.details || []);
    }

    var snapshotStatusPoller = null;

    function stopSnapshotStatusPolling() {
        if (snapshotStatusPoller) {
            clearInterval(snapshotStatusPoller);
            snapshotStatusPoller = null;
        }
    }

    function handleSnapshotState(data) {
        applySnapshotProgressState(data);

        if (data.is_terminal) {
            stopSnapshotStatusPolling();
            window.location = data.redirect_url || '{{ route('settings.index', ['tab' => 'system']) }}';
            return true;
        }

        return false;
    }

    function startSnapshotStatusPolling(statusUrl) {
        stopSnapshotStatusPolling();

        if (!statusUrl) {
            return;
        }

        snapshotStatusPoller = setInterval(function () {
            fetch(statusUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('{{ __('Unable to fetch snapshot status.') }}');
                }

                return response.json();
            })
            .then(function (data) {
                handleSnapshotState(data);
            })
            .catch(function () {
                stopSnapshotStatusPolling();
            });
        }, 3000);
    }

    function startAsyncSnapshotCreate(form) {
        var formData = new URLSearchParams(new FormData(form));
        var redirectUrl = '{{ route('settings.index', ['tab' => 'system']) }}';
        var statusUrl = null;

        stopSnapshotStatusPolling();

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: formData.toString()
        })
        .then(function (response) {
            if (!response.ok || !response.body) {
                throw new Error('{{ __('Unable to start snapshot creation.') }}');
            }

            var reader = response.body.getReader();
            var decoder = new TextDecoder();
            var buffer = '';

            function readChunk() {
                return reader.read().then(function (result) {
                    if (result.done) {
                        if (statusUrl) {
                            startSnapshotStatusPolling(statusUrl);
                            return;
                        }

                        window.location = redirectUrl;
                        return;
                    }

                    buffer += decoder.decode(result.value, { stream: true });
                    var lines = buffer.split('\n');
                    buffer = lines.pop();

                    lines.forEach(function (line) {
                        if (!line.trim()) {
                            return;
                        }

                        var data = JSON.parse(line);
                        redirectUrl = data.redirect_url || redirectUrl;
                        statusUrl = data.status_url || statusUrl;

                        if (statusUrl && !snapshotStatusPoller && !data.is_terminal) {
                            startSnapshotStatusPolling(statusUrl);
                        }

                        handleSnapshotState(data);
                    });

                    return readChunk();
                });
            }

            return readChunk();
        })
        .catch(function (error) {
            stopSnapshotStatusPolling();
            if (systemActionOverlayTitle) {
                systemActionOverlayTitle.textContent = '{{ __('Snapshot Failed') }}';
            }
            systemActionOverlayMessage.textContent = error.message || '{{ __('Unable to start snapshot creation.') }}';
            setSystemActionOverlayDetails([]);
            busyStateActive = false;
            setBusyControlsDisabled(form, false);
        });
    }
    var restoreStepLabels = {
        start: '{{ __("Preparing to restore the selected recovery snapshot.") }}',
        validate: '{{ __("Validating the snapshot archive and checking file permissions.") }}',
        backup: '{{ __("Creating a protective database backup before restoring.") }}',
        maintenance: '{{ __("Putting the CRM into maintenance mode.") }}',
        database: '{{ __("Restoring the database backup captured with this snapshot.") }}',
        files: '{{ __("Copying snapshot files back into the install directory.") }}',
        post_restore: '{{ __("Running post-restore maintenance (caches, migrations).") }}',
        done: '{{ __("Manual recovery snapshot restored successfully.") }}',
        rollback: '{{ __("Restore failed. Rolling back to the pre-restore database state.") }}'
    };

    var restoreStepOrder = ['validate', 'backup', 'maintenance', 'database', 'files', 'post_restore', 'done'];

    function buildRestoreStepDetails(currentStep) {
        return restoreStepOrder.map(function (step) {
            var label = restoreStepLabels[step] || step;
            var idx = restoreStepOrder.indexOf(currentStep);
            var stepIdx = restoreStepOrder.indexOf(step);

            if (stepIdx < idx) {
                return '\u2705 ' + label;
            } else if (stepIdx === idx) {
                return '\u25b6 ' + label;
            } else {
                return '\u2003 ' + label;
            }
        });
    }

    function startAsyncSnapshotRestore(form) {
        var formData = new URLSearchParams(new FormData(form));
        var redirectUrl = '{{ route('settings.index', ['tab' => 'system']) }}';

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: formData.toString()
        })
        .then(function (response) {
            if (!response.ok || !response.body) {
                throw new Error('{{ __('Unable to start snapshot restore.') }}');
            }

            var reader = response.body.getReader();
            var decoder = new TextDecoder();
            var buffer = '';

            function readChunk() {
                return reader.read().then(function (result) {
                    if (result.done) {
                        window.location = redirectUrl;
                        return;
                    }

                    buffer += decoder.decode(result.value, { stream: true });
                    var lines = buffer.split('\n');
                    buffer = lines.pop();

                    lines.forEach(function (line) {
                        if (!line.trim()) {
                            return;
                        }

                        var data = JSON.parse(line);
                        redirectUrl = data.redirect_url || redirectUrl;

                        if (systemActionOverlayTitle) {
                            systemActionOverlayTitle.textContent = data.success === false
                                ? '{{ __('Restore Failed') }}'
                                : (data.step === 'done' ? '{{ __('Restore Complete') }}' : '{{ __('Restoring Snapshot') }}');
                        }

                        systemActionOverlayMessage.textContent = data.message || restoreStepLabels[data.step] || '{{ __('Processing restore step.') }}';
                        setSystemActionOverlayDetails(data.step === 'failed' ? [] : buildRestoreStepDetails(data.step));

                        if (data.is_terminal) {
                            setTimeout(function () {
                                window.location = redirectUrl;
                            }, data.success === false ? 3000 : 1000);
                        }
                    });

                    return readChunk();
                });
            }

            return readChunk();
        })
        .catch(function (error) {
            if (systemActionOverlayTitle) {
                systemActionOverlayTitle.textContent = '{{ __('Restore Failed') }}';
            }
            systemActionOverlayMessage.textContent = error.message || '{{ __('Unable to start snapshot restore.') }}';
            setSystemActionOverlayDetails([]);
            busyStateActive = false;
            setBusyControlsDisabled(form, false);
        });
    }

    document.querySelectorAll('form[data-busy-submit]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (busyStateActive) {
                event.preventDefault();
                return false;
            }

            setBusyControlsDisabled(form, true);
            activateSystemActionOverlay(form.getAttribute('data-busy-title'), form.getAttribute('data-busy-message'), form.getAttribute('data-busy-details'));

            if (form.getAttribute('data-async-progress') === 'snapshot-create') {
                event.preventDefault();
                startAsyncSnapshotCreate(form);
            } else if (form.getAttribute('data-async-progress') === 'snapshot-restore') {
                event.preventDefault();
                startAsyncSnapshotRestore(form);
            }
        });
    });
    function loadSystemHealth() {
        fetch('{{ route("settings.health") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('sys-app-version').textContent = data.app_version || '-';
            document.getElementById('sys-php').textContent = data.php_version;
            document.getElementById('sys-laravel').textContent = data.laravel_version;
            document.getElementById('sys-db').innerHTML = data.db_connection === 'OK'
                ? '<span class="badge bg-green-lt">{{ __('OK') }}</span>'
                : '<span class="badge bg-red-lt">' + data.db_connection + '</span>';
            document.getElementById('sys-storage').innerHTML = data.storage_writable
                ? '<span class="badge bg-green-lt">{{ __('Writable') }}</span>'
                : '<span class="badge bg-red-lt">{{ __('Not Writable') }}</span>';
            document.getElementById('sys-queue').textContent = data.queue_driver;

            var updatesEl = document.getElementById('sys-updates');
            updatesEl.innerHTML = '<div class="fw-bold">{{ __('Current Version') }}: <span class="badge bg-blue-lt">' + (data.app_version || '-') + '</span></div>';

            var pluginsEl = document.getElementById('sys-plugins');
            if (data.plugins && data.plugins.length > 0) {
                var html = '<div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>{{ __('Plugin') }}</th><th>{{ __('Version') }}</th><th>{{ __('Author') }}</th></tr></thead><tbody>';
                data.plugins.forEach(function(p) {
                    html += '<tr><td>' + p.name + '</td><td><span class="badge bg-blue-lt">' + p.version + '</span></td><td>' + p.author + '</td></tr>';
                });
                html += '</tbody></table></div>';
                pluginsEl.innerHTML = html;
            } else {
                pluginsEl.innerHTML = '<span class="text-secondary">{{ __('No active plugins.') }}</span>';
            }
        })
        .catch(function() {
            document.getElementById('sys-app-version').textContent = '{{ __('Error loading health data') }}';
            document.getElementById('sys-php').textContent = '{{ __('Error loading health data') }}';
        });
    }

    // Restore active tab from query parameter or sessionStorage
    var urlParams = new URLSearchParams(window.location.search);
    var tabFromUrl = urlParams.get('tab');
    var activeTab = tabFromUrl || sessionStorage.getItem('settings_active_tab');
    if (activeTab) {
        var tabLink = document.querySelector('a[href="#tab-' + activeTab + '"]');
        if (tabLink) {
            var tab = new bootstrap.Tab(tabLink);
            tab.show();
        }
        sessionStorage.setItem('settings_active_tab', activeTab);
    }
    // Strip tab param from URL so it doesn't override sessionStorage on refresh
    if (tabFromUrl) {
        urlParams.delete('tab');
        var cleanUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState(null, '', cleanUrl);
    }
    // Save active tab to sessionStorage on tab change
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function(el) {
        el.addEventListener('shown.bs.tab', function(e) {
            var tabId = e.target.getAttribute('href').replace('#tab-', '');
            sessionStorage.setItem('settings_active_tab', tabId);
        });
    });

    var systemTab = document.querySelector('a[href="#tab-system"]');
    if (systemTab) {
        systemTab.addEventListener('shown.bs.tab', function () {
            loadSystemHealth();
        });

        if (document.querySelector('#tab-system.active, #tab-system.show')) {
            loadSystemHealth();
        }
    }
});

// ── AI Settings ──────────────────────────────────────
var providerSelect = document.getElementById('ai-provider-select');
var keyGroup = document.getElementById('ai-key-group');
var ollamaGroup = document.getElementById('ai-ollama-group');
var customGroup = document.getElementById('ai-custom-group');
var modelSelect = document.getElementById('ai-model-select');
var modelManual = document.getElementById('ai-model-manual');
var modelToggle = document.getElementById('ai-model-toggle');
var fetchModelsBtn = document.getElementById('ai-fetch-models-btn');
var modelLoading = document.getElementById('ai-model-loading');
var keyHint = document.getElementById('ai-key-hint');
var usingDropdown = false;
var currentModel = @json($tenant->ai_model ?? '');

function toggleAiFields() {
    if (!providerSelect) return;
    var val = providerSelect.value;
    keyGroup.style.display = (val === 'ollama') ? 'none' : 'block';
    ollamaGroup.style.display = (val === 'ollama') ? 'block' : 'none';
    customGroup.style.display = (val === 'custom') ? 'block' : 'none';
    fetchModelsBtn.style.display = 'inline-block';

    // Update key hint for custom provider
    if (val === 'custom') {
        keyHint.textContent = '{{ __('Optional. Some local servers do not require an API key.') }}';
    } else if (val === 'ollama') {
        keyHint.textContent = '{{ __('Not needed for Ollama.') }}';
    } else {
        keyHint.textContent = '{{ $tenant->ai_api_key ? __('Key is saved. Leave blank to keep current key.') : __('Required for cloud providers.') }}';
    }

    // Reset to manual mode when switching providers
    switchToManual();
}

function switchToDropdown(models) {
    usingDropdown = true;
    modelSelect.innerHTML = '<option value="">{{ __('Use default') }}</option>';
    models.forEach(function(m) {
        var opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = m.name;
        if (m.id === currentModel) opt.selected = true;
        modelSelect.appendChild(opt);
    });
    modelSelect.style.display = 'block';
    modelSelect.name = 'ai_model';
    modelManual.style.display = 'none';
    modelManual.name = '';
    modelToggle.style.display = 'inline';
    modelToggle.textContent = '{{ __('Switch to manual input') }}';
}

function switchToManual() {
    usingDropdown = false;
    modelSelect.style.display = 'none';
    modelSelect.name = '';
    modelManual.style.display = 'block';
    modelManual.name = 'ai_model';
    if (modelToggle) {
        modelToggle.style.display = usingDropdown ? 'inline' : 'none';
        modelToggle.textContent = '{{ __('Switch to manual input') }}';
    }
}

function fetchModels() {
    var provider = providerSelect.value;
    var apiKey = document.getElementById('ai-api-key-input').value;
    var ollamaUrl = document.getElementById('ai-ollama-url-input').value;
    var customUrl = document.getElementById('ai-custom-url-input').value;

    modelLoading.style.display = 'block';
    fetchModelsBtn.disabled = true;

    fetch('{{ route("ai.listModels") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ provider: provider, api_key: apiKey || null, ollama_url: ollamaUrl, custom_url: customUrl })
    }).then(function(r) { return r.json(); }).then(function(data) {
        modelLoading.style.display = 'none';
        fetchModelsBtn.disabled = false;
        if (data.success && data.models && data.models.length > 0) {
            switchToDropdown(data.models);
        } else {
            switchToManual();
            modelManual.placeholder = '{{ __('No models found — enter model name manually') }}';
        }
    }).catch(function() {
        modelLoading.style.display = 'none';
        fetchModelsBtn.disabled = false;
        switchToManual();
    });
}

if (providerSelect) {
    providerSelect.addEventListener('change', toggleAiFields);
    toggleAiFields();
}

if (fetchModelsBtn) {
    fetchModelsBtn.addEventListener('click', fetchModels);
}

if (modelToggle) {
    modelToggle.addEventListener('click', function(e) {
        e.preventDefault();
        if (usingDropdown) {
            // Copy selected value to manual input
            if (modelSelect.value) modelManual.value = modelSelect.value;
            switchToManual();
            modelToggle.textContent = '{{ __('Switch to dropdown') }}';
            modelToggle.style.display = 'inline';
            usingDropdown = false;
        } else {
            fetchModels();
        }
    });
}

// ── Language File Manager ─────────────────────────────
(function() {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    var langFilesTable = document.getElementById('lang-files-table');
    var langEditorCard = document.getElementById('lang-editor-card');
    var langEditorTitle = document.getElementById('lang-editor-title');
    var langEditorProgress = document.getElementById('lang-editor-progress');
    var langEditorTable = document.getElementById('lang-editor-table');
    var langEditorSearch = document.getElementById('lang-editor-search');
    var langEditorSave = document.getElementById('lang-editor-save');
    var langEditorSaveBottom = document.getElementById('lang-editor-save-bottom');
    var langEditorFeedback = document.getElementById('lang-editor-feedback');
    var langUploadBtn = document.getElementById('lang-upload-btn');
    var langUploadInput = document.getElementById('lang-upload-input');
    var langUploadFeedback = document.getElementById('lang-upload-feedback');
    var currentLangCode = null;
    var langTabLoaded = false;

    function showFeedback(el, msg, isError) {
        el.style.display = 'block';
        el.innerHTML = '<span class="badge ' + (isError ? 'bg-red-lt' : 'bg-green-lt') + '">' + msg + '</span>';
        setTimeout(function() { el.style.display = 'none'; }, 5000);
    }

    function loadLanguageList() {
        fetch('{{ route("settings.getLanguages") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var tbody = langFilesTable.querySelector('tbody');
            if (!data.languages || data.languages.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-secondary">{{ __('No language files found.') }}</td></tr>';
                return;
            }
            var html = '';
            data.languages.forEach(function(lang) {
                var isEn = lang.code === 'en';
                html += '<tr>';
                html += '<td><span class="badge bg-blue-lt">' + lang.code + '</span></td>';
                html += '<td>' + lang.file + '</td>';
                html += '<td><span class="badge bg-azure-lt">' + lang.count + ' {{ __('strings') }}</span></td>';
                html += '<td>';
                if (isEn) {
                    html += '<span class="text-secondary">{{ __('Base reference') }}</span>';
                } else {
                    html += '<button type="button" class="btn btn-sm btn-outline-primary lang-edit-btn" data-code="' + lang.code + '">{{ __('Edit') }}</button>';
                }
                html += '</td>';
                html += '</tr>';
            });
            tbody.innerHTML = html;

            // Attach click handlers
            tbody.querySelectorAll('.lang-edit-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    loadLanguageEditor(this.getAttribute('data-code'));
                });
            });
        })
        .catch(function() {
            langFilesTable.querySelector('tbody').innerHTML = '<tr><td colspan="4" class="text-danger">{{ __('Error loading language files.') }}</td></tr>';
        });
    }

    function loadLanguageEditor(code) {
        currentLangCode = code;
        langEditorCard.style.display = 'block';
        langEditorTitle.textContent = '{{ __('Edit Language') }}: ' + code;
        langEditorTable.querySelector('tbody').innerHTML = '<tr><td colspan="2" class="text-secondary">{{ __('Loading...') }}</td></tr>';
        langEditorFeedback.style.display = 'none';

        fetch('{{ url("settings/languages") }}/' + code, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) {
                langEditorTable.querySelector('tbody').innerHTML = '<tr><td colspan="2" class="text-danger">' + data.error + '</td></tr>';
                return;
            }

            langEditorProgress.textContent = data.translated + '/' + data.total + ' {{ __('translated') }}';

            var html = '';
            data.translations.forEach(function(item) {
                var escapedKey = item.key.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                var escapedEn = item.en.replace(/</g, '&lt;').replace(/>/g, '&gt;');
                var escapedTrans = (item.translation || '').replace(/"/g, '&quot;');
                html += '<tr class="lang-row" data-search="' + escapedKey.toLowerCase() + ' ' + escapedEn.toLowerCase() + ' ' + escapedTrans.toLowerCase() + '">';
                html += '<td><small class="text-secondary">' + escapedEn + '</small></td>';
                html += '<td><input type="text" class="form-control form-control-sm lang-translation-input" data-key="' + escapedKey + '" value="' + escapedTrans + '"></td>';
                html += '</tr>';
            });
            langEditorTable.querySelector('tbody').innerHTML = html;

            // Scroll to editor
            langEditorCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(function() {
            langEditorTable.querySelector('tbody').innerHTML = '<tr><td colspan="2" class="text-danger">{{ __('Error loading translations.') }}</td></tr>';
        });
    }

    function saveTranslations() {
        if (!currentLangCode) return;

        var translations = {};
        langEditorTable.querySelectorAll('.lang-translation-input').forEach(function(input) {
            var key = input.getAttribute('data-key');
            var val = input.value;
            if (val !== '') {
                translations[key] = val;
            }
        });

        langEditorSave.disabled = true;
        langEditorSaveBottom.disabled = true;
        langEditorSave.textContent = '{{ __('Saving...') }}';
        langEditorSaveBottom.textContent = '{{ __('Saving...') }}';

        fetch('{{ url("settings/languages") }}/' + currentLangCode, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ translations: translations })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            langEditorSave.disabled = false;
            langEditorSaveBottom.disabled = false;
            langEditorSave.textContent = '{{ __('Save Changes') }}';
            langEditorSaveBottom.textContent = '{{ __('Save Changes') }}';

            if (data.success) {
                showFeedback(langEditorFeedback, '{{ __('Translations saved successfully.') }}', false);
                // Update progress count
                var count = Object.keys(translations).length;
                var total = langEditorTable.querySelectorAll('.lang-translation-input').length;
                langEditorProgress.textContent = count + '/' + total + ' {{ __('translated') }}';
                // Refresh the file list to update counts
                loadLanguageList();
            } else {
                showFeedback(langEditorFeedback, data.error || '{{ __('Error saving translations.') }}', true);
            }
        })
        .catch(function() {
            langEditorSave.disabled = false;
            langEditorSaveBottom.disabled = false;
            langEditorSave.textContent = '{{ __('Save Changes') }}';
            langEditorSaveBottom.textContent = '{{ __('Save Changes') }}';
            showFeedback(langEditorFeedback, '{{ __('Network error.') }}', true);
        });
    }

    // Search/filter
    if (langEditorSearch) {
        langEditorSearch.addEventListener('input', function() {
            var query = this.value.toLowerCase();
            langEditorTable.querySelectorAll('.lang-row').forEach(function(row) {
                var searchText = row.getAttribute('data-search') || '';
                row.style.display = (query === '' || searchText.indexOf(query) !== -1) ? '' : 'none';
            });
        });
    }

    // Save buttons
    if (langEditorSave) langEditorSave.addEventListener('click', saveTranslations);
    if (langEditorSaveBottom) langEditorSaveBottom.addEventListener('click', saveTranslations);

    // Upload handler
    if (langUploadBtn) {
        langUploadBtn.addEventListener('click', function() {
            var file = langUploadInput.files[0];
            if (!file) {
                showFeedback(langUploadFeedback, '{{ __('Please select a .json file.') }}', true);
                return;
            }

            if (!file.name.endsWith('.json')) {
                showFeedback(langUploadFeedback, '{{ __('File must have a .json extension.') }}', true);
                return;
            }

            // Read and validate JSON before uploading
            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    JSON.parse(e.target.result);
                } catch (err) {
                    showFeedback(langUploadFeedback, '{{ __('File contains invalid JSON.') }}', true);
                    return;
                }

                var formData = new FormData();
                formData.append('language_file', file);

                langUploadBtn.disabled = true;
                langUploadBtn.textContent = '{{ __('Uploading...') }}';

                fetch('{{ route("settings.uploadLanguageFile") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    langUploadBtn.disabled = false;
                    langUploadBtn.textContent = '{{ __('Upload Language File') }}';
                    langUploadInput.value = '';

                    if (data.success) {
                        showFeedback(langUploadFeedback, data.message || '{{ __('File uploaded successfully.') }}', false);
                        loadLanguageList();
                    } else {
                        showFeedback(langUploadFeedback, data.error || '{{ __('Upload failed.') }}', true);
                    }
                })
                .catch(function() {
                    langUploadBtn.disabled = false;
                    langUploadBtn.textContent = '{{ __('Upload Language File') }}';
                    showFeedback(langUploadFeedback, '{{ __('Network error.') }}', true);
                });
            };
            reader.readAsText(file);
        });
    }

    // Load language list when the tab is shown
    var langTab = document.querySelector('a[href="#tab-languages"]');
    if (langTab) {
        langTab.addEventListener('shown.bs.tab', function() {
            if (!langTabLoaded) {
                langTabLoaded = true;
                loadLanguageList();
            }
        });
    }
})();

// ── Webhook "All Events" toggle ──────────────────────
var allEventsCheckbox = document.getElementById('webhook-all-events');
if (allEventsCheckbox) {
    allEventsCheckbox.addEventListener('change', function() {
        var checkboxes = document.querySelectorAll('.webhook-event-checkbox');
        checkboxes.forEach(function(cb) {
            cb.disabled = allEventsCheckbox.checked;
            if (allEventsCheckbox.checked) cb.checked = false;
        });
    });
}

// Storage driver toggle
var storageDiskSelect = document.getElementById('storage-disk-select');
if (storageDiskSelect) {
    storageDiskSelect.addEventListener('change', function() {
        var isS3 = this.value === 's3';
        var s3Fields = document.getElementById('s3-config-fields');
        var testBtn = document.getElementById('test-s3-btn');
        if (s3Fields) s3Fields.style.display = isS3 ? '' : 'none';
        if (testBtn) testBtn.style.display = isS3 ? '' : 'none';
    });
}

// S3 connection test
function testS3Connection() {
    var btn = document.getElementById('test-s3-btn');
    var result = document.getElementById('s3-test-result');
    var form = btn.closest('form');

    btn.disabled = true;
    btn.textContent = '{{ __("Testing...") }}';
    result.innerHTML = '<span class="text-muted">{{ __("Connecting...") }}</span>';

    var formData = new FormData(form);
    var data = {};
    formData.forEach(function(v, k) { if (k.startsWith('s3_')) data[k] = v; });

    fetch('{{ route("settings.testS3") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify(data)
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.success) {
            result.innerHTML = '<span class="badge bg-green-lt">' + data.message + '</span>';
        } else {
            result.innerHTML = '<span class="badge bg-red-lt">' + (data.message || '{{ __("Connection failed.") }}') + '</span>';
        }
        btn.disabled = false;
        btn.textContent = '{{ __("Test Connection") }}';
    }).catch(function() {
        result.innerHTML = '<span class="badge bg-red-lt">{{ __("Network error.") }}</span>';
        btn.disabled = false;
        btn.textContent = '{{ __("Test Connection") }}';
    });
}

// SMS test
var testSmsBtn = document.getElementById('btn-test-sms');
if (testSmsBtn) {
    testSmsBtn.addEventListener('click', function() {
        var phone = prompt('{{ __("Enter a phone number to send a test SMS (E.164 format, e.g. +1234567890):") }}');
        if (!phone) return;

        testSmsBtn.disabled = true;
        testSmsBtn.textContent = '{{ __("Sending...") }}';

        fetch('{{ route("settings.testSms") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ to: phone })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (typeof window.showToast === 'function') {
                window.showToast(data.message, data.success ? 'success' : 'danger');
            } else {
                console[data.success ? 'log' : 'error'](data.message);
            }
            testSmsBtn.disabled = false;
            testSmsBtn.textContent = '{{ __("Send Test SMS") }}';
        }).catch(function() {
            if (typeof window.showToast === 'function') {
                window.showToast('{{ __("Network error.") }}', 'danger');
            } else {
                console.error('{{ __("Network error.") }}');
            }
            testSmsBtn.disabled = false;
            testSmsBtn.textContent = '{{ __("Send Test SMS") }}';
        });
    });
}

// Backup management
var csrfTokenBackup = document.querySelector('meta[name="csrf-token"]')?.content;

function renderBackupList(backups) {
    var el = document.getElementById('backup-list');
    if (!backups || backups.length === 0) {
        el.innerHTML = '<div class="p-4 text-center text-secondary">{{ __("No backups found. Click \"Create Backup Now\" to create your first backup.") }}</div>';
        return;
    }
    var html = '<div class="table-responsive"><table class="table table-vcenter mb-0"><thead><tr><th>{{ __("File") }}</th><th>{{ __("Size") }}</th><th>{{ __("Date") }}</th><th style="width:180px;">{{ __("Actions") }}</th></tr></thead><tbody>';
    backups.forEach(function(b) {
        html += '<tr><td><code>' + b.name + '</code></td><td>' + b.size + '</td><td>' + b.date + '</td><td>';
        html += '<a href="{{ url("settings/backups/download") }}/' + encodeURIComponent(b.name) + '" class="btn btn-sm btn-outline-primary me-1">{{ __("Download") }}</a>';
        html += '<button class="btn btn-sm btn-outline-danger" onclick="deleteBackup(\'' + b.name + '\')">{{ __("Delete") }}</button>';
        html += '</td></tr>';
    });
    html += '</tbody></table></div>';
    el.innerHTML = html;
}

function loadBackupList() {
    fetch('{{ route("settings.backupList") }}', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfTokenBackup }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) { renderBackupList(data.backups); })
    .catch(function() {
        document.getElementById('backup-list').innerHTML = '<div class="p-4 text-center text-danger">{{ __("Could not load backup list.") }}</div>';
    });
}

window.createBackup = function() {
    var btn = document.getElementById('backup-create-btn');
    var feedback = document.getElementById('backup-feedback');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> {{ __("Creating backup...") }}';
    feedback.style.display = 'none';

    fetch('{{ route("settings.backupCreate") }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfTokenBackup }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2"/><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M14 4l0 4l-6 0l0 -4"/></svg> {{ __("Create Backup Now") }}';
        feedback.style.display = 'block';
        if (data.success) {
            feedback.className = 'mb-3 alert alert-success';
            feedback.textContent = data.message;
            renderBackupList(data.backups);
        } else {
            feedback.className = 'mb-3 alert alert-danger';
            feedback.textContent = data.message || '{{ __("Backup failed.") }}';
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2"/><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M14 4l0 4l-6 0l0 -4"/></svg> {{ __("Create Backup Now") }}';
        feedback.style.display = 'block';
        feedback.className = 'mb-3 alert alert-danger';
        feedback.textContent = '{{ __("Network error. Please try again.") }}';
    });
};

window.deleteBackup = function(filename) {
    if (!confirm('{{ __("Delete this backup? This cannot be undone.") }}')) return;
    fetch('{{ url("settings/backups") }}/' + encodeURIComponent(filename), {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfTokenBackup }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) renderBackupList(data.backups);
    });
};

var backupTab = document.querySelector('a[href="#tab-backups"]');
if (backupTab) {
    var backupLoaded = false;
    backupTab.addEventListener('shown.bs.tab', function() {
        if (!backupLoaded) { backupLoaded = true; loadBackupList(); }
    });
}

// API Logs viewer
window.loadApiLogs = function() {
    var container = document.getElementById('api-logs-container');
    container.innerHTML = '<span class="text-secondary"><span class="spinner-border spinner-border-sm me-1"></span> {{ __("Loading...") }}</span>';
    fetch('{{ route("settings.apiLogs") }}', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfTokenBackup }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.logs || data.logs.length === 0) {
            container.innerHTML = '<p class="text-secondary">{{ __("No API requests logged yet.") }}</p>';
            return;
        }
        var html = '<div class="table-responsive" style="max-height:400px;overflow-y:auto;"><table class="table table-sm table-vcenter"><thead><tr><th>{{ __("Method") }}</th><th>{{ __("Path") }}</th><th>{{ __("Status") }}</th><th>{{ __("IP") }}</th><th>{{ __("Duration") }}</th><th>{{ __("When") }}</th></tr></thead><tbody>';
        data.logs.forEach(function(l) {
            var methodColors = { GET: 'bg-blue-lt', POST: 'bg-green-lt', PUT: 'bg-orange-lt', DELETE: 'bg-red-lt' };
            var statusBg = l.status >= 400 ? 'bg-red-lt' : (l.status >= 300 ? 'bg-yellow-lt' : 'bg-green-lt');
            html += '<tr>';
            html += '<td><span class="badge ' + (methodColors[l.method] || 'bg-secondary-lt') + '">' + l.method + '</span></td>';
            html += '<td><code class="small">' + l.path + '</code></td>';
            html += '<td><span class="badge ' + statusBg + '">' + l.status + '</span></td>';
            html += '<td class="text-secondary small">' + l.ip + '</td>';
            html += '<td class="text-secondary small">' + l.duration + '</td>';
            html += '<td class="text-secondary small">' + l.date + '</td>';
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    })
    .catch(function() {
        container.innerHTML = '<p class="text-danger">{{ __("Failed to load API logs.") }}</p>';
    });
};

// AI test connection
var testBtn = document.getElementById('ai-test-btn');
if (testBtn) {
    testBtn.addEventListener('click', function() {
        var resultEl = document.getElementById('ai-test-result');
        testBtn.disabled = true;
        testBtn.textContent = '{{ __('Testing...') }}';
        resultEl.style.display = 'block';
        resultEl.innerHTML = '<span class="text-secondary">{{ __('Connecting to AI provider...') }}</span>';

        fetch('{{ route("ai.testConnection") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                resultEl.innerHTML = '<span class="badge bg-green-lt">{{ __('Connected successfully!') }}</span>';
            } else {
                resultEl.innerHTML = '<span class="badge bg-red-lt">' + (data.message || data.error || '{{ __('Connection failed.') }}') + '</span>';
            }
            testBtn.disabled = false;
            testBtn.textContent = '{{ __('Test Connection') }}';
        }).catch(function() {
            resultEl.innerHTML = '<span class="badge bg-red-lt">{{ __('Network error.') }}</span>';
            testBtn.disabled = false;
            testBtn.textContent = '{{ __('Test Connection') }}';
        });
    });
}
</script>
<style>
.system-action-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(2px);
    z-index: 2500;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.system-action-overlay__panel {
    width: min(520px, 100%);
    background: #fff;
    border-radius: 18px;
    padding: 28px 24px;
    box-shadow: 0 28px 80px rgba(15, 23, 42, 0.25);
    text-align: center;
}

.system-action-spinner {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    margin-bottom: 1rem;
    border: .25em solid #206bc4;
    border-right-color: transparent;
    border-radius: 50%;
    animation: system-overlay-spin .75s linear infinite;
}

@keyframes system-overlay-spin {
    to { transform: rotate(360deg); }
}

@media (prefers-reduced-motion: reduce) {
    .system-action-spinner {
        animation: system-overlay-spin .75s linear infinite !important;
    }
}
</style>
@endpush


