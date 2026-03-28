@php
    $prefs = $tenant->notification_preferences ?? [];
    $types = [
        'lead_assigned' => [
            'label' => __('Lead Assigned'),
            'description' => __('Notify agents when a new lead is assigned to them.'),
        ],
        'deal_stage_changed' => [
            'label' => __('Deal Stage Changed'),
            'description' => __('Notify agents when a deal moves to a new pipeline stage.'),
        ],
        'due_diligence_warning' => [
            'label' => __($businessMode === 'realestate' ? 'Transaction Deadline Warning' : 'Due Diligence Warning'),
            'description' => __($businessMode === 'realestate' ? 'Notify agents and admins when a transaction deadline is within 3 days.' : 'Notify agents and admins when a due diligence deadline is within 3 days.'),
        ],
        'buyer_matched' => [
            'label' => __($businessMode === 'realestate' ? 'Client Match Found' : 'Buyer Match Found'),
            'description' => __($businessMode === 'realestate' ? 'Notify admins and agents when client matches are found for a listing.' : 'Notify admins and disposition agents when buyer matches are found for a deal.'),
        ],
        'team_member_invited' => [
            'label' => __('Team Member Invited'),
            'description' => __('Send a welcome email when a new team member is added.'),
        ],
        'sequence_email' => [
            'label' => __('Sequence Emails'),
            'description' => __('Send drip sequence emails to leads when a step has action type "email".'),
        ],
    ];
@endphp

<form action="{{ route('settings.updateNotifications') }}" method="POST">
    @csrf
    @method('PUT')

    <h3 class="mb-3">{{ __('Email Notification Preferences') }}</h3>
    <p class="text-secondary mb-4">{{ __('Control which email notifications are sent from your account. All notifications are enabled by default.') }}</p>

    <div class="divide-y">
        @foreach($types as $key => $info)
            <div class="row align-items-center py-3">
                <div class="col-auto">
                    <label class="form-check form-switch form-switch-lg">
                        <input type="hidden" name="{{ $key }}" value="0">
                        <input class="form-check-input" type="checkbox" name="{{ $key }}" value="1"
                            {{ ($prefs[$key] ?? true) ? 'checked' : '' }}>
                    </label>
                </div>
                <div class="col">
                    <strong>{{ $info['label'] }}</strong>
                    <div class="text-secondary">{{ $info['description'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">{{ __('Save Notification Preferences') }}</button>
    </div>
</form>
