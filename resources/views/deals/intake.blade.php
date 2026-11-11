@extends('layouts.app')

@section('title', __('Deal / Contract Intake'))
@section('page-title', __('Deal / Contract Intake'))

@section('content')
<form method="POST" action="{{ route('deals.intake.store') }}">
    @csrf
    @if($lead)
        <input type="hidden" name="lead_id" value="{{ $lead->id }}">
    @endif

    <div class="row row-cards">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('Transaction') }}</h3></div>
                <div class="card-body">
                    @if(!$lead)
                        <div class="mb-3">
                            <label class="form-label">{{ __('Seller Lead') }}</label>
                            <select name="lead_id" class="form-select" required>
                                <option value="">{{ __('Select a seller lead') }}</option>
                                @foreach($leads as $candidate)
                                    <option value="{{ $candidate->id }}">{{ $candidate->first_name }} {{ $candidate->last_name }} — {{ $candidate->property?->address ?? __('No property address') }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">{{ __('Deal Title') }}</label>
                            <input name="title" class="form-control" value="{{ old('title', $lead ? 'Wholesale Deal — ' . ($lead->property?->address ?? $lead->first_name . ' ' . $lead->last_name) : '') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Property State') }}</label>
                            <select name="property_state" class="form-select" required>
                                <option value="">{{ __('Select state') }}</option>
                                @foreach($states as $code => $label)
                                    <option value="{{ $code }}" @selected(old('property_state', $lead?->property?->state) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('County / Locality') }}</label>
                            <input name="property_county" class="form-control" value="{{ old('property_county', $lead?->property?->county) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Contract Price') }}</label>
                            <input type="number" step="0.01" min="0" name="contract_price" class="form-control" value="{{ old('contract_price') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('Transaction Structure & Contract') }}</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Transaction Structure') }}</label>
                            <select name="transaction_structure" class="form-select" required>
                                <option value="">{{ __('Select') }}</option>
                                @foreach($structures as $key => $label)
                                    <option value="{{ $key }}" @selected(old('transaction_structure') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Buyer Type') }}</label>
                            <select name="buyer_type" class="form-select" required>
                                <option value="individual" @selected(old('buyer_type') === 'individual')>{{ __('Individual') }}</option>
                                <option value="entity" @selected(old('buyer_type') === 'entity')>{{ __('Entity') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Buyer Legal Name') }}</label>
                            <input name="buyer_legal_name" class="form-control" value="{{ old('buyer_legal_name', 'Robert Karanja') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Attorney-Approved Template ID') }}</label>
                            <select name="attorney_approved_template_id" class="form-select" required>
                                <option value="">{{ __('Select approved template') }}</option>
                                @foreach($templates as $key => $label)
                                    <option value="{{ $key }}" @selected(old('attorney_approved_template_id') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-hint">{{ __('The system stores the template reference; it does not invent legal language.') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Executed Contract Status') }}</label>
                            <select name="executed_contract_status" class="form-select" required>
                                <option value="not_executed">{{ __('Not Executed') }}</option>
                                <option value="pending_review">{{ __('Executed — Pending Review') }}</option>
                                <option value="executed">{{ __('Executed') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Assignment Rights Confirmed') }}</label>
                            <select name="assignment_rights_confirmed" class="form-select">
                                <option value="">{{ __('Not applicable / not confirmed') }}</option>
                                <option value="1">{{ __('Yes') }}</option>
                                <option value="0">{{ __('No') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Contract Review Status') }}</label>
                            <select name="contract_review_status" class="form-select">
                                <option value="pending">{{ __('Pending') }}</option>
                                <option value="approved">{{ __('Approved') }}</option>
                                <option value="needs_attention">{{ __('Needs Attention') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('EMD & Closing') }}</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Earnest Money') }}</label>
                            <input type="number" step="0.01" min="0" max="50" name="earnest_money" class="form-control" value="{{ old('earnest_money', 0) }}">
                            <div class="form-hint">{{ __('Target range: $0–$50. Amounts above $50 require an exception.') }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('EMD Required?') }}</label>
                            <select name="emd_required" class="form-select">
                                <option value="0">{{ __('No / Unknown') }}</option>
                                <option value="1">{{ __('Yes') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('EMD Source') }}</label>
                            <select name="emd_source" class="form-select">
                                <option value="wholesaler">{{ __('Wholesaler') }}</option>
                                <option value="end_buyer">{{ __('End Buyer') }}</option>
                                <option value="other_approved">{{ __('Other Approved Source') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Buyer Covers EMD?') }}</label>
                            <select name="buyer_covers_emd" class="form-select">
                                <option value="0">{{ __('No') }}</option>
                                <option value="1">{{ __('Yes / Pending Confirmation') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('EMD Funding Confirmed?') }}</label>
                            <select name="emd_funding_confirmed" class="form-select">
                                <option value="0">{{ __('No') }}</option>
                                <option value="1">{{ __('Yes') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Closing Provider Type') }}</label>
                            <select name="closing_provider_type" class="form-select">
                                @foreach($closingTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Buyer Preferred Provider') }}</label>
                            <input name="buyer_preferred_closing_provider" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Seller Preferred Provider') }}</label>
                            <input name="seller_preferred_closing_provider" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Confirmed Provider') }}</label>
                            <input name="confirmed_closing_provider" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('Deadlines & No-Buyer Protection') }}</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Contract Date') }}</label>
                            <input type="date" name="contract_date" class="form-control" value="{{ old('contract_date') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Due Diligence End Date') }}</label>
                            <input type="date" name="due_diligence_end_date" class="form-control" value="{{ old('due_diligence_end_date') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Closing Date') }}</label>
                            <input type="date" name="closing_date" class="form-control" value="{{ old('closing_date') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('No-Buyer Status') }}</label>
                            <select name="no_buyer_status" class="form-select">
                                <option value="not_applicable">{{ __('Not Applicable') }}</option>
                                <option value="active">{{ __('No Buyer — Disposition Active') }}</option>
                                <option value="review">{{ __('No Buyer — Contract Review Needed') }}</option>
                                <option value="extension_review">{{ __('No Buyer — Extension Review') }}</option>
                                <option value="termination_review">{{ __('No Buyer — Termination Review') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Extension / Termination Review') }}</label>
                            <select name="extension_termination_review_status" class="form-select">
                                <option value="not_needed">{{ __('Not Needed') }}</option>
                                <option value="pending">{{ __('Pending Authorized Review') }}</option>
                                <option value="approved">{{ __('Approved') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary">{{ __('Save Intake & Continue') }}</button>
            <a href="{{ route('pipeline') }}" class="btn btn-link">{{ __('Cancel') }}</a>
        </div>

        <div class="col-lg-4">
            <div class="alert alert-warning">
                <h4>{{ __('Workflow Guardrails') }}</h4>
                <ul class="mb-0">
                    <li>{{ __('Only attorney-approved template references are accepted.') }}</li>
                    <li>{{ __('EMD above $50 is blocked unless an exception reason is supplied.') }}</li>
                    <li>{{ __('Buyer funding is never treated as guaranteed until confirmed.') }}</li>
                    <li>{{ __('Closing provider follows applicable state/local requirements and the executed contract.') }}</li>
                    <li>{{ __('The system does not auto-cancel, default, extend, or invent legal clauses.') }}</li>
                </ul>
            </div>
        </div>
    </div>
</form>
@endsection
