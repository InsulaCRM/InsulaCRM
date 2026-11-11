@if(($businessMode ?? 'wholesale') === 'wholesale')
<div class="card mb-3 border-warning">
    <div class="card-header">
        <div>
            <h3 class="card-title mb-1">{{ __('Wholesale Contract Protection Gate') }}</h3>
            <div class="text-muted small">{{ __('Review the deal controls below before sending it to the configured contract protection workflow. This does not create or modify legal language.') }}</div>
        </div>
        <span class="badge bg-{{ $deal->stage === 'under_contract' ? 'success' : 'warning' }}">
            {{ \App\Models\Deal::stageLabel($deal->stage) }}
        </span>
    </div>
    @if($deal->contract_gate_status === 'approved')
        <div class="alert alert-success">
            <div class="fw-bold">{{ __('Contract Protection Gate: APPROVED') }}</div>
            <div>{{ $deal->contract_gate_reason ?: __('All configured contract protection controls passed. The deal is approved for the closing workflow.') }}</div>
            @if($deal->contract_gate_checked_at)
                <div class="small text-muted mt-1">{{ __('Checked') }} {{ $deal->contract_gate_checked_at->format('M j, Y g:i A') }}</div>
            @endif
        </div>
    @elseif($deal->contract_gate_status === 'blocked')
        <div class="alert alert-danger">
            <div class="fw-bold">{{ __('Contract Protection Gate: BLOCKED') }}</div>
            <div>{{ $deal->contract_gate_reason ?: __('The contract protection workflow found one or more blockers.') }}</div>
            @if(!empty($deal->contract_gate_blockers))
                <ul class="mb-0 mt-2">
                    @foreach($deal->contract_gate_blockers as $key => $value)
                        @if(!$value)
                            <li>{{ IlluminateSupportStr::headline($key) }}: {{ __('Not satisfied') }}</li>
                        @endif
                    @endforeach
                </ul>
            @endif
            @if($deal->contract_gate_checked_at)
                <div class="small text-muted mt-1">{{ __('Checked') }} {{ $deal->contract_gate_checked_at->format('M j, Y g:i A') }}</div>
            @endif
        </div>
    @elseif($deal->contract_gate_status === 'pending')
        <div class="alert alert-info">
            <div class="fw-bold">{{ __('Contract Protection Gate: PENDING') }}</div>
            <div>{{ __('The deal was sent to the configured protection workflow. The final result will appear here after the workflow responds.') }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('deals.contractReview', $deal) }}">
        @csrf
        <div class="card-body">
            @if(session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-bold mb-1">{{ __('Gate cannot run yet') }}</div>
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{ __('State') }}</label>
                    <input name="property_state" class="form-control" maxlength="2" value="{{ old('property_state', $deal->property_state) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('County') }}</label>
                    <input name="property_county" class="form-control" value="{{ old('property_county', $deal->property_county) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Buyer Type') }}</label>
                    <select name="buyer_type" class="form-select" required>
                        <option value="individual" @selected(old('buyer_type', $deal->buyer_type) === 'individual')>{{ __('Individual') }}</option>
                        <option value="entity" @selected(old('buyer_type', $deal->buyer_type) === 'entity')>{{ __('Entity') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Buyer Legal Name') }}</label>
                    <input name="buyer_legal_name" class="form-control" value="{{ old('buyer_legal_name', $deal->buyer_legal_name) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">{{ __('Transaction Structure') }}</label>
                    <select name="transaction_structure" class="form-select" required>
                        @foreach(['assignment'=>'Assignment','double_close'=>'Double Close','novation'=>'Novation'] as $key=>$label)
                            <option value="{{ $key }}" @selected(old('transaction_structure', $deal->transaction_structure) === $key)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Attorney-Approved Template ID') }}</label>
                    <input name="attorney_approved_template_id" class="form-control" value="{{ old('attorney_approved_template_id', $deal->attorney_approved_template_id) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Executed Contract Status') }}</label>
                    <select name="executed_contract_status" class="form-select" required>
                        @foreach(['not_executed'=>'Not Executed','pending_review'=>'Pending Review','executed'=>'Executed'] as $key=>$label)
                            <option value="{{ $key }}" @selected(old('executed_contract_status', $deal->executed_contract_status) === $key)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">{{ __('Contract Review') }}</label>
                    <select name="contract_review_status" class="form-select" required>
                        @foreach(['pending'=>'Pending','approved'=>'Approved','needs_attention'=>'Needs Attention'] as $key=>$label)
                            <option value="{{ $key }}" @selected(old('contract_review_status', $deal->contract_review_status ?? 'pending') === $key)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Assignment Rights Confirmed') }}</label>
                    <select name="assignment_rights_confirmed" class="form-select">
                        <option value="0" @selected(!old('assignment_rights_confirmed', $deal->assignment_rights_confirmed))>{{ __('No / Not Confirmed') }}</option>
                        <option value="1" @selected(old('assignment_rights_confirmed', $deal->assignment_rights_confirmed))>{{ __('Yes / Confirmed') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Contract Date') }}</label>
                    <input type="date" name="contract_date" class="form-control" value="{{ old('contract_date', optional($deal->contract_date)->format('Y-m-d')) }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">{{ __('EMD Amount') }}</label>
                    <input type="number" min="0" step="0.01" name="earnest_money" class="form-control" value="{{ old('earnest_money', $deal->earnest_money) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('EMD Required') }}</label>
                    <select name="emd_required" class="form-select">
                        <option value="0" @selected(!old('emd_required', $deal->emd_required))>{{ __('No / Not Confirmed') }}</option>
                        <option value="1" @selected(old('emd_required', $deal->emd_required))>{{ __('Yes') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('EMD Source') }}</label>
                    <select name="emd_source" class="form-select">
                        <option value="">{{ __('Not selected') }}</option>
                        @foreach(['wholesaler'=>'Wholesaler','end_buyer'=>'End Buyer','other_approved'=>'Other Approved'] as $key=>$label)
                            <option value="{{ $key }}" @selected(old('emd_source', $deal->emd_source) === $key)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Buyer Covers EMD') }}</label>
                    <select name="buyer_covers_emd" class="form-select">
                        <option value="0" @selected(!old('buyer_covers_emd', $deal->buyer_covers_emd))>{{ __('No') }}</option>
                        <option value="1" @selected(old('buyer_covers_emd', $deal->buyer_covers_emd))>{{ __('Yes') }}</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">{{ __('EMD Funding Confirmed') }}</label>
                    <select name="emd_funding_confirmed" class="form-select">
                        <option value="0" @selected(!old('emd_funding_confirmed', $deal->emd_funding_confirmed))>{{ __('No / Not Confirmed') }}</option>
                        <option value="1" @selected(old('emd_funding_confirmed', $deal->emd_funding_confirmed))>{{ __('Yes / Confirmed') }}</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">{{ __('EMD Exception Reason') }}</label>
                    <input name="emd_exception_reason" class="form-control" value="{{ old('emd_exception_reason', $deal->emd_exception_reason) }}" placeholder="{{ __('Required when EMD is above $50') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">{{ __('Closing Provider Type') }}</label>
                    <select name="closing_provider_type" class="form-select">
                        <option value="">{{ __('Not selected') }}</option>
                        @foreach(['closing_attorney'=>'Closing Attorney','title_company'=>'Title Company','escrow_company'=>'Escrow Company','settlement_company'=>'Settlement Company','other_permitted'=>'Other Permitted'] as $key=>$label)
                            <option value="{{ $key }}" @selected(old('closing_provider_type', $deal->closing_provider_type) === $key)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">{{ __('Confirmed Closing Provider') }}</label>
                    <input name="confirmed_closing_provider" class="form-control" value="{{ old('confirmed_closing_provider', $deal->confirmed_closing_provider) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">{{ __('Buyer Preferred Closing Provider') }}</label>
                    <input name="buyer_preferred_closing_provider" class="form-control" value="{{ old('buyer_preferred_closing_provider', $deal->buyer_preferred_closing_provider) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Seller Preferred Closing Provider') }}</label>
                    <input name="seller_preferred_closing_provider" class="form-control" value="{{ old('seller_preferred_closing_provider', $deal->seller_preferred_closing_provider) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Due Diligence End Date') }}</label>
                    <input type="date" name="due_diligence_end_date" class="form-control" value="{{ old('due_diligence_end_date', optional($deal->due_diligence_end_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Closing Date') }}</label>
                    <input type="date" name="closing_date" class="form-control" value="{{ old('closing_date', optional($deal->closing_date)->format('Y-m-d')) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">{{ __('No-Buyer Status') }}</label>
                    <select name="no_buyer_status" class="form-select" required>
                        @foreach(['not_applicable'=>'Not Applicable','active'=>'Active','review'=>'Review','extension_review'=>'Extension Review','termination_review'=>'Termination Review'] as $key=>$label)
                            <option value="{{ $key }}" @selected(old('no_buyer_status', $deal->no_buyer_status ?? 'not_applicable') === $key)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Extension / Termination Review') }}</label>
                    <select name="extension_termination_review_status" class="form-select" required>
                        @foreach(['not_needed'=>'Not Needed','pending'=>'Pending','approved'=>'Approved'] as $key=>$label)
                            <option value="{{ $key }}" @selected(old('extension_termination_review_status', $deal->extension_termination_review_status ?? 'not_needed') === $key)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="alert alert-info mt-3 mb-0">
                <strong>{{ __('Important:') }}</strong>
                {{ __('The gate uses the executed contract, approved template reference, state/local requirements, and confirmed transaction details. It does not invent legal clauses or automatically cancel, extend, or default a contract.') }}
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <span class="text-muted small">{{ __('Saving alone does not move the deal to Under Contract.') }}</span>
            <button type="submit" class="btn btn-warning">
                {{ __('Save & Run Contract Protection Gate') }}
            </button>
        </div>
    </form>
</div>
@endif
