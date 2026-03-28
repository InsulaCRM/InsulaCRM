@extends('layouts.app')

@section('title', __('Edit Lead'))
@section('page-title', __('Edit Lead') . ': ' . $lead->full_name)

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('leads.index') }}">{{ __('Leads') }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('leads.show', $lead) }}">{{ $lead->full_name }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
@endsection

@section('content')
<form action="{{ route('leads.update', $lead) }}" method="POST">
    @csrf
    @method('PUT')

    <!-- Contact Information -->
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">{{ __('Contact Information') }}</h3>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label required">{{ __('First Name') }}</label>
                    <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $lead->first_name) }}" required>
                    @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required">{{ __('Last Name') }}</label>
                    <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $lead->last_name) }}" required>
                    @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Phone') }}</label>
                    <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $lead->phone) }}" placeholder="{{ __('(555) 123-4567') }}">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Email') }}</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $lead->email) }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Timezone') }}</label>
                    <select name="timezone" class="form-select">
                        <option value="">{{ __('-- Not set --') }}</option>
                        @foreach(Fmt::timezones() as $region => $zones)
                            <optgroup label="{{ $region }}">
                                @foreach($zones as $tz)
                                    <option value="{{ $tz }}" {{ old('timezone', $lead->timezone) == $tz ? 'selected' : '' }}>{{ $tz }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <small class="text-secondary">{{ __('Required for :law calling-hours compliance', ['law' => Fmt::complianceLawName()]) }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Lead Classification -->
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Lead Details') : __('Lead Classification') }}</h3>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label required">{{ __('Lead Source') }}</label>
                    <select name="lead_source" class="form-select" required>
                        @foreach(\App\Services\CustomFieldService::getOptions('lead_source') as $val => $label)
                            <option value="{{ $val }}" {{ old('lead_source', $lead->lead_source) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label required">{{ __('Status') }}</label>
                    <select name="status" class="form-select" required>
                        @foreach(\App\Services\CustomFieldService::getOptions('lead_status') as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $lead->status) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label required">{{ __('Temperature') }}</label>
                    <select name="temperature" class="form-select" required>
                        @foreach(['hot' => __('Hot'), 'warm' => __('Warm'), 'cold' => __('Cold')] as $val => $label)
                            <option value="{{ $val }}" {{ old('temperature', $lead->temperature) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label required">{{ __('Assigned Agent') }}</label>
                    <select name="agent_id" class="form-select" required>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ old('agent_id', $lead->agent_id) == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <label class="form-check mb-0">
                        <input type="hidden" name="do_not_contact" value="0">
                        <input type="checkbox" name="do_not_contact" value="1" class="form-check-input" {{ old('do_not_contact', $lead->do_not_contact) ? 'checked' : '' }}>
                        <span class="form-check-label">{{ __('Do Not Contact') }}</span>
                    </label>
                </div>
                @if(($businessMode ?? 'wholesale') === 'realestate')
                <div class="col-md-4">
                    <label class="form-label">{{ __('Contact Type') }}</label>
                    <select name="contact_type" class="form-select">
                        <option value="">{{ __('— Select —') }}</option>
                        @foreach(['seller_lead' => __('Seller Lead'), 'buyer_lead' => __('Buyer Lead'), 'active_client' => __('Active Client'), 'past_client' => __('Past Client')] as $val => $label)
                            <option value="{{ $val }}" {{ old('contact_type', $lead->contact_type) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            @if(($businessMode ?? 'wholesale') === 'wholesale' && ($lead->motivation_score || $lead->ai_motivation_score !== null))
            <div class="row">
                <div class="col-md-8">
                    <div class="text-secondary" style="font-size:13px;">
                        <strong>{{ __('Motivation Scores') }}:</strong>
                        @php $ms = $lead->motivation_score ?? 0; @endphp
                        <span class="badge {{ $ms >= 70 ? 'bg-green-lt' : ($ms >= 40 ? 'bg-yellow-lt' : 'bg-secondary-lt') }}">{{ __('System') }}: {{ $ms }}/100</span>
                        @if($lead->ai_motivation_score !== null)
                            <span class="badge bg-purple-lt ms-1">{{ __('AI') }}: {{ $lead->ai_motivation_score }}/100</span>
                        @endif
                        <span class="text-muted ms-1">— {{ __('auto-calculated, not editable') }}</span>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Custom Fields -->
    @php
        $customFieldDefs = \App\Models\CustomFieldDefinition::forEntity('lead');
        $cfValues = $lead->custom_fields ?? [];
    @endphp
    @if($customFieldDefs->count())
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">{{ __('Additional Information') }}</h3>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                @foreach($customFieldDefs as $cfd)
                <div class="col-md-4 mb-3">
                    <label class="form-label {{ $cfd->required ? 'required' : '' }}">{{ __($cfd->name) }}</label>
                    @if($cfd->field_type === 'text')
                        <input type="text" name="custom_fields[{{ $cfd->slug }}]" class="form-control" value="{{ old('custom_fields.' . $cfd->slug, $cfValues[$cfd->slug] ?? '') }}" {{ $cfd->required ? 'required' : '' }}>
                    @elseif($cfd->field_type === 'textarea')
                        <textarea name="custom_fields[{{ $cfd->slug }}]" class="form-control" rows="2" {{ $cfd->required ? 'required' : '' }}>{{ old('custom_fields.' . $cfd->slug, $cfValues[$cfd->slug] ?? '') }}</textarea>
                    @elseif($cfd->field_type === 'number')
                        <input type="number" step="any" name="custom_fields[{{ $cfd->slug }}]" class="form-control" value="{{ old('custom_fields.' . $cfd->slug, $cfValues[$cfd->slug] ?? '') }}" {{ $cfd->required ? 'required' : '' }}>
                    @elseif($cfd->field_type === 'date')
                        <input type="date" name="custom_fields[{{ $cfd->slug }}]" class="form-control" value="{{ old('custom_fields.' . $cfd->slug, $cfValues[$cfd->slug] ?? '') }}" {{ $cfd->required ? 'required' : '' }}>
                    @elseif($cfd->field_type === 'select')
                        <select name="custom_fields[{{ $cfd->slug }}]" class="form-select" {{ $cfd->required ? 'required' : '' }}>
                            <option value="">{{ __('-- Select --') }}</option>
                            @foreach($cfd->options ?? [] as $opt)
                                <option value="{{ $opt }}" {{ old('custom_fields.' . $cfd->slug, $cfValues[$cfd->slug] ?? '') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    @elseif($cfd->field_type === 'checkbox')
                        <div>
                            <input type="hidden" name="custom_fields[{{ $cfd->slug }}]" value="0">
                            <label class="form-check">
                                <input type="checkbox" name="custom_fields[{{ $cfd->slug }}]" value="1" class="form-check-input" {{ old('custom_fields.' . $cfd->slug, $cfValues[$cfd->slug] ?? '') ? 'checked' : '' }}>
                                <span class="form-check-label">{{ __('Yes') }}</span>
                            </label>
                        </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Notes -->
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">{{ __('Notes') }}</h3>
        </div>
        <div class="card-body">
            <textarea name="notes" class="form-control" rows="4" placeholder="{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Notes about the client, property interests, timeline, etc.') : __('Notes about the lead, seller situation, etc.') }}">{{ old('notes', $lead->notes) }}</textarea>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="{{ route('leads.show', $lead) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary">{{ __('Update Lead') }}</button>
    </div>
</form>
@endsection
