<div class="row row-cards">
    {{-- User Data Export --}}
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Export User Data') }}</h3>
            </div>
            <div class="card-body">
                <p class="text-secondary">{{ __('Export all data associated with a team member as a JSON file. This includes their profile, leads, activities, tasks, and audit log entries. Use this for GDPR data subject access requests.') }}</p>
                <form action="{{ route('gdpr.exportUser') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('Select User') }}</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">{{ __('— Choose a user —') }}</option>
                            @foreach($teamMembers as $member)
                                <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M12 17v-6"/><path d="M9.5 14.5l2.5 2.5l2.5 -2.5"/></svg>
                        {{ __('Export User Data') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- User Data Deletion --}}
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title text-danger">{{ __('Anonymize & Deactivate User') }}</h3>
            </div>
            <div class="card-body">
                <p class="text-secondary">{{ __('Anonymize a team member\'s personal data and deactivate their account. Their name and email will be replaced with generic placeholders. This action cannot be undone.') }}</p>
                <form action="{{ route('gdpr.deleteUser') }}" method="POST" onsubmit="return confirm('{{ __('Are you sure? This will permanently anonymize this user\'s data and cannot be undone.') }}');">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('Select User') }}</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">{{ __('— Choose a user —') }}</option>
                            @foreach($teamMembers as $member)
                                <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="confirm" value="1" class="form-check-input" required>
                            <span class="form-check-label text-danger">{{ __('I confirm that I want to permanently anonymize this user\'s personal data') }}</span>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                        {{ __('Anonymize User Data') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Contact Data Export --}}
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Export Contact/Lead Data') }}</h3>
            </div>
            <div class="card-body">
                <p class="text-secondary">{{ __('Export all data for a specific lead/contact as a JSON file. This includes the lead record, property details, activities, tasks, and notes. Use this when a contact requests a copy of their data.') }}</p>
                <form action="{{ route('gdpr.exportContact') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('Lead ID') }}</label>
                        <input type="number" name="lead_id" class="form-control" placeholder="{{ __('Enter lead ID') }}" required min="1">
                        <small class="form-hint">{{ __('You can find the lead ID on the lead detail page.') }}</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M12 17v-6"/><path d="M9.5 14.5l2.5 2.5l2.5 -2.5"/></svg>
                        {{ __('Export Contact Data') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Contact Data Anonymization --}}
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title text-danger">{{ __('Anonymize Contact/Lead Data') }}</h3>
            </div>
            <div class="card-body">
                <p class="text-secondary">{{ __('Anonymize a lead\'s personal information (name, phone, email, address). The record is kept for statistical purposes but all personally identifiable information is removed. This action cannot be undone.') }}</p>
                <form action="{{ route('gdpr.deleteContact') }}" method="POST" onsubmit="return confirm('{{ __('Are you sure? This will permanently remove all personal information from this lead record and cannot be undone.') }}');">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('Lead ID') }}</label>
                        <input type="number" name="lead_id" class="form-control" placeholder="{{ __('Enter lead ID') }}" required min="1">
                        <small class="form-hint">{{ __('You can find the lead ID on the lead detail page.') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="confirm" value="1" class="form-check-input" required>
                            <span class="form-check-label text-danger">{{ __('I confirm that I want to permanently anonymize this contact\'s personal data') }}</span>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                        {{ __('Anonymize Contact Data') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-2">
    <div class="d-flex">
        <div>
            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
        </div>
        <div>
            <h4 class="alert-title">{{ __('About GDPR Compliance') }}</h4>
            <div class="text-secondary">
                {{ __('These tools help you respond to data subject requests under GDPR and similar privacy regulations:') }}
                <ul class="mt-2 mb-0">
                    <li><strong>{{ __('Data Export') }}</strong> — {{ __('Fulfills "Right of Access" (Article 15). Generates a machine-readable JSON file of all data associated with a user or contact.') }}</li>
                    <li><strong>{{ __('Data Anonymization') }}</strong> — {{ __('Fulfills "Right to Erasure" (Article 17). Replaces personal information with anonymous placeholders while retaining records for business analytics.') }}</li>
                </ul>
                <p class="mt-2 mb-0">{{ __('All GDPR actions are recorded in the audit log for accountability.') }}</p>
            </div>
        </div>
    </div>
</div>
