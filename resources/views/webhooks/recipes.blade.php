@extends('layouts.app')

@section('title', __('Webhook Integration Recipes'))
@section('page-title', __('Webhook Integration Recipes'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('settings.index', ['tab' => 'webhooks']) }}">{{ __('Settings') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Webhook Recipes') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        {{-- How Webhooks Work --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4.876 13.61a4 4 0 1 0 6.124 3.39h6"/><path d="M15.066 20.502a4 4 0 1 0 1.934 -7.502c-.344 0 -.678 .045 -1 .126"/><path d="M12 8a4 4 0 1 0 -1.936 7.498"/></svg>
                    {{ __('How Webhooks Work') }}
                </h3>
            </div>
            <div class="card-body">
                <p>{{ __('InsulaCRM webhooks send real-time HTTP POST requests to URLs you configure whenever specific events occur in your CRM. This allows you to connect InsulaCRM to thousands of external services.') }}</p>

                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <div class="card card-sm bg-azure-lt border-0">
                            <div class="card-body">
                                <h4 class="mb-1">{{ __('1. Configure') }}</h4>
                                <p class="text-secondary small mb-0">{{ __('Add a webhook URL in Settings > Webhooks and select which events to listen for.') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-sm bg-azure-lt border-0">
                            <div class="card-body">
                                <h4 class="mb-1">{{ __('2. Event Fires') }}</h4>
                                <p class="text-secondary small mb-0">{{ __('When the event occurs (e.g., new lead created), InsulaCRM sends a JSON POST request to your URL.') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-sm bg-azure-lt border-0">
                            <div class="card-body">
                                <h4 class="mb-1">{{ __('3. Process') }}</h4>
                                <p class="text-secondary small mb-0">{{ __('Your service receives the payload and takes action (add to spreadsheet, send Slack message, etc.).') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <h4>{{ __('Security') }}</h4>
                    <p class="text-secondary mb-0">{{ __('All webhook payloads can be signed with HMAC-SHA256 using a shared secret. The signature is sent in the') }} <code>X-Webhook-Signature</code> {{ __('header, allowing you to verify the request came from InsulaCRM.') }}</p>
                </div>
            </div>
        </div>

        {{-- Available Events --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12h4l3 8l4 -16l3 8h4"/></svg>
                    {{ __('Available Events') }}
                </h3>
            </div>
            <div class="card-body">
                <div class="accordion" id="events-accordion">
                    {{-- lead.created --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#event-lead-created">
                                <span class="badge bg-blue-lt me-2">lead.created</span>
                                {{ __('Fired when a new lead is added to the CRM') }}
                            </button>
                        </h2>
                        <div id="event-lead-created" class="accordion-collapse collapse" data-bs-parent="#events-accordion">
                            <div class="accordion-body">
                                <p class="text-secondary">{{ __('Triggered when a lead is created manually, via import, API, or web form.') }}</p>
                                <pre class="bg-dark text-light p-3 rounded"><code>{
  "event": "lead.created",
  "timestamp": "2026-03-15T14:30:00Z",
  "data": {
    "id": 1234,
    "first_name": "John",
    "last_name": "Smith",
    "email": "john@example.com",
    "phone": "+1-555-0100",
    "status": "new",
    "temperature": "warm",
    "lead_source": "website",
    "motivation_score": 65,
    "property": {
      "address": "123 Main St",
      "city": "Miami",
      "state": "FL",
      "zip_code": "33101",
      "property_type": "single_family"
    }
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    {{-- lead.updated --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#event-lead-updated">
                                <span class="badge bg-blue-lt me-2">lead.updated</span>
                                {{ __('Fired when a lead record is modified') }}
                            </button>
                        </h2>
                        <div id="event-lead-updated" class="accordion-collapse collapse" data-bs-parent="#events-accordion">
                            <div class="accordion-body">
                                <p class="text-secondary">{{ __('Triggered when any lead field is updated (contact info, status, notes, etc.).') }}</p>
                                <pre class="bg-dark text-light p-3 rounded"><code>{
  "event": "lead.updated",
  "timestamp": "2026-03-15T14:35:00Z",
  "data": {
    "id": 1234,
    "first_name": "John",
    "last_name": "Smith",
    "email": "john@example.com",
    "phone": "+1-555-0100",
    "status": "contacted",
    "temperature": "hot",
    "changed_fields": ["status", "temperature"]
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    {{-- lead.status_changed --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#event-lead-status">
                                <span class="badge bg-blue-lt me-2">lead.status_changed</span>
                                {{ __('Fired when a lead status transitions') }}
                            </button>
                        </h2>
                        <div id="event-lead-status" class="accordion-collapse collapse" data-bs-parent="#events-accordion">
                            <div class="accordion-body">
                                <p class="text-secondary">{{ __('Triggered specifically when the lead status field changes, with both old and new values.') }}</p>
                                <pre class="bg-dark text-light p-3 rounded"><code>{
  "event": "lead.status_changed",
  "timestamp": "2026-03-15T15:00:00Z",
  "data": {
    "id": 1234,
    "full_name": "John Smith",
    "old_status": "new",
    "new_status": "contacted",
    "agent": "Jane Doe"
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    {{-- deal.stage_changed --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#event-deal-stage">
                                <span class="badge bg-green-lt me-2">deal.stage_changed</span>
                                {{ __('Fired when a deal moves to a new pipeline stage') }}
                            </button>
                        </h2>
                        <div id="event-deal-stage" class="accordion-collapse collapse" data-bs-parent="#events-accordion">
                            <div class="accordion-body">
                                <p class="text-secondary">{{ __('Triggered when a deal advances or regresses through pipeline stages.') }}</p>
                                <pre class="bg-dark text-light p-3 rounded"><code>@if(($businessMode ?? 'wholesale') === 'realestate'){
  "event": "deal.stage_changed",
  "timestamp": "2026-03-15T16:00:00Z",
  "data": {
    "id": 567,
    "title": "123 Main St - Smith",
    "old_stage": "active_listing",
    "new_stage": "offer_received",
    "contract_price": 425000.00,
    "total_commission": 12750.00,
    "lead": {
      "id": 1234,
      "full_name": "John Smith"
    }
  }
}@else{
  "event": "deal.stage_changed",
  "timestamp": "2026-03-15T16:00:00Z",
  "data": {
    "id": 567,
    "title": "123 Main St - Smith",
    "old_stage": "under_contract",
    "new_stage": "dispositions",
    "contract_price": 150000.00,
    "assignment_fee": 12000.00,
    "lead": {
      "id": 1234,
      "full_name": "John Smith"
    }
  }
}@endif</code></pre>
                            </div>
                        </div>
                    </div>

                    {{-- buyer.notified --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#event-buyer-notified">
                                <span class="badge bg-purple-lt me-2">buyer.notified</span>
                                {{ __('Fired when a buyer is notified about a matching deal') }}
                            </button>
                        </h2>
                        <div id="event-buyer-notified" class="accordion-collapse collapse" data-bs-parent="#events-accordion">
                            <div class="accordion-body">
                                <p class="text-secondary">{{ __('Triggered when a buyer match notification is sent for a deal.') }}</p>
                                <pre class="bg-dark text-light p-3 rounded"><code>{
  "event": "buyer.notified",
  "timestamp": "2026-03-15T17:00:00Z",
  "data": {
    "buyer": {
      "id": 89,
      "full_name": "Acme Investments",
      "email": "deals@acme.com"
    },
    "deal": {
      "id": 567,
      "title": "123 Main St - Smith",
      "contract_price": 150000.00
    }
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    {{-- activity.logged --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#event-activity-logged">
                                <span class="badge bg-yellow-lt me-2">activity.logged</span>
                                {{ __('Fired when an activity is logged on a lead or deal') }}
                            </button>
                        </h2>
                        <div id="event-activity-logged" class="accordion-collapse collapse" data-bs-parent="#events-accordion">
                            <div class="accordion-body">
                                <p class="text-secondary">{{ __('Triggered when a call, meeting, email, or other activity is recorded.') }}</p>
                                <pre class="bg-dark text-light p-3 rounded"><code>{
  "event": "activity.logged",
  "timestamp": "2026-03-15T18:00:00Z",
  "data": {
    "id": 4321,
    "type": "phone_call",
    "description": "Discussed property condition and timeline",
    "lead_id": 1234,
    "deal_id": null,
    "agent": "Jane Doe",
    "created_at": "2026-03-15T18:00:00Z"
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    {{-- sequence.step_executed --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#event-sequence-step">
                                <span class="badge bg-orange-lt me-2">sequence.step_executed</span>
                                {{ __('Fired when an automated sequence step runs') }}
                            </button>
                        </h2>
                        <div id="event-sequence-step" class="accordion-collapse collapse" data-bs-parent="#events-accordion">
                            <div class="accordion-body">
                                <p class="text-secondary">{{ __('Triggered when an email or action is sent/executed as part of a drip sequence.') }}</p>
                                <pre class="bg-dark text-light p-3 rounded"><code>{
  "event": "sequence.step_executed",
  "timestamp": "2026-03-15T09:00:00Z",
  "data": {
    "sequence_id": 12,
    "sequence_name": "New Lead Follow-Up",
    "step_number": 3,
    "step_type": "email",
    "lead_id": 1234,
    "lead_name": "John Smith",
    "status": "sent"
  }
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Zapier Recipes --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12l-8 -4.5v9l8 4.5v-9z"/><path d="M12 12l8 -4.5v9l-8 4.5v-9z"/><path d="M12 2.5l8 4.5l-8 4.5l-8 -4.5z"/></svg>
                    {{ __('Zapier Recipes') }}
                </h3>
            </div>
            <div class="card-body">
                <p class="text-secondary">{{ __('Zapier connects InsulaCRM to 6,000+ apps. Use the "Webhooks by Zapier" trigger to receive events from InsulaCRM.') }}</p>

                <div class="accordion" id="zapier-accordion">
                    {{-- Google Sheets --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#zapier-sheets">
                                <strong>{{ __('InsulaCRM -> Google Sheets') }}</strong>
                                <span class="ms-2 text-secondary small">{{ __('Log new leads to a spreadsheet') }}</span>
                            </button>
                        </h2>
                        <div id="zapier-sheets" class="accordion-collapse collapse" data-bs-parent="#zapier-accordion">
                            <div class="accordion-body">
                                <ol class="mb-0">
                                    <li class="mb-2">
                                        <strong>{{ __('Create a new Zap') }}</strong> {{ __('at') }} <a href="https://zapier.com" target="_blank" rel="noopener">zapier.com</a>
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Trigger:') }}</strong> {{ __('Choose "Webhooks by Zapier" and select "Catch Hook" as the trigger event.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Copy the webhook URL') }}</strong> {{ __('that Zapier provides (looks like') }} <code>https://hooks.zapier.com/hooks/catch/...</code>)
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('In InsulaCRM:') }}</strong> {{ __('Go to') }}
                                        <a href="{{ route('settings.index', ['tab' => 'webhooks']) }}">{{ __('Settings') }} > {{ __('Webhooks') }}</a>
                                        {{ __('and add a new webhook with the Zapier URL. Select the') }} <code>lead.created</code> {{ __('event.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Test:') }}</strong> {{ __('Create a test lead in InsulaCRM, then click "Test trigger" in Zapier to verify data is received.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Action:') }}</strong> {{ __('Choose "Google Sheets" and select "Create Spreadsheet Row". Connect your Google account.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Map fields:') }}</strong> {{ __('Map the webhook data to spreadsheet columns:') }}
                                        <ul class="mt-1">
                                            <li><code>data.first_name</code> &rarr; {{ __('First Name column') }}</li>
                                            <li><code>data.last_name</code> &rarr; {{ __('Last Name column') }}</li>
                                            <li><code>data.email</code> &rarr; {{ __('Email column') }}</li>
                                            <li><code>data.phone</code> &rarr; {{ __('Phone column') }}</li>
                                            <li><code>data.status</code> &rarr; {{ __('Status column') }}</li>
                                            <li><code>data.property.address</code> &rarr; {{ __('Property Address column') }}</li>
                                        </ul>
                                    </li>
                                    <li>
                                        <strong>{{ __('Turn on the Zap!') }}</strong> {{ __('New leads will automatically appear in your Google Sheet.') }}
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    {{-- Slack --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#zapier-slack">
                                <strong>{{ __('InsulaCRM -> Slack') }}</strong>
                                <span class="ms-2 text-secondary small">{{ __('Get notified of deal stage changes') }}</span>
                            </button>
                        </h2>
                        <div id="zapier-slack" class="accordion-collapse collapse" data-bs-parent="#zapier-accordion">
                            <div class="accordion-body">
                                <ol class="mb-0">
                                    <li class="mb-2">
                                        <strong>{{ __('Create a new Zap') }}</strong> {{ __('at') }} <a href="https://zapier.com" target="_blank" rel="noopener">zapier.com</a>
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Trigger:') }}</strong> {{ __('Choose "Webhooks by Zapier" > "Catch Hook".') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Copy the webhook URL') }}</strong> {{ __('and add it in InsulaCRM') }}
                                        (<a href="{{ route('settings.index', ['tab' => 'webhooks']) }}">{{ __('Settings') }} > {{ __('Webhooks') }}</a>)
                                        {{ __('with the') }} <code>deal.stage_changed</code> {{ __('event selected.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Action:') }}</strong> {{ __('Choose "Slack" > "Send Channel Message". Connect your Slack workspace.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Configure message:') }}</strong> {{ __('Select the channel and build your message template:') }}
                                        <div class="bg-light p-2 rounded mt-1">
                                            <code>Deal "{{'{{'}}data.title{{'}}'}}" moved to {{'{{'}}data.new_stage{{'}}'}} (was {{'{{'}}data.old_stage{{'}}'}}). Contract price: ${{'{{'}}data.contract_price{{'}}'}}</code>
                                        </div>
                                    </li>
                                    <li>
                                        <strong>{{ __('Turn on the Zap!') }}</strong> {{ __('Your team will get Slack notifications for every deal stage change.') }}
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    {{-- Mailchimp --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#zapier-mailchimp">
                                <strong>{{ __('InsulaCRM -> Mailchimp') }}</strong>
                                <span class="ms-2 text-secondary small">{{ __('Add new leads to an email list') }}</span>
                            </button>
                        </h2>
                        <div id="zapier-mailchimp" class="accordion-collapse collapse" data-bs-parent="#zapier-accordion">
                            <div class="accordion-body">
                                <ol class="mb-0">
                                    <li class="mb-2">
                                        <strong>{{ __('Create a new Zap') }}</strong> {{ __('with "Webhooks by Zapier" > "Catch Hook" trigger.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Add webhook in InsulaCRM') }}</strong> {{ __('with the') }} <code>lead.created</code> {{ __('event.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Action:') }}</strong> {{ __('Choose "Mailchimp" > "Add/Update Subscriber". Connect your Mailchimp account.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Map fields:') }}</strong>
                                        <ul class="mt-1">
                                            <li><code>data.email</code> &rarr; {{ __('Email Address') }}</li>
                                            <li><code>data.first_name</code> &rarr; {{ __('First Name (FNAME)') }}</li>
                                            <li><code>data.last_name</code> &rarr; {{ __('Last Name (LNAME)') }}</li>
                                        </ul>
                                    </li>
                                    <li>
                                        <strong>{{ __('Optionally add a Filter step') }}</strong> {{ __('before the Mailchimp action to only add leads with valid email addresses.') }}
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    {{-- Google Calendar --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#zapier-calendar">
                                <strong>{{ __('InsulaCRM -> Google Calendar') }}</strong>
                                <span class="ms-2 text-secondary small">{{ __('Create events for deal closings') }}</span>
                            </button>
                        </h2>
                        <div id="zapier-calendar" class="accordion-collapse collapse" data-bs-parent="#zapier-accordion">
                            <div class="accordion-body">
                                <ol class="mb-0">
                                    <li class="mb-2">
                                        <strong>{{ __('Create a new Zap') }}</strong> {{ __('with "Webhooks by Zapier" > "Catch Hook" trigger.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Add webhook in InsulaCRM') }}</strong> {{ __('with the') }} <code>deal.stage_changed</code> {{ __('event.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Add a Filter step:') }}</strong> {{ __('Only continue if') }} <code>data.new_stage</code> {{ __('equals') }} <code>closing</code>.
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Action:') }}</strong> {{ __('Choose "Google Calendar" > "Create Detailed Event".') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Configure:') }}</strong>
                                        <ul class="mt-1">
                                            <li>{{ __('Summary:') }} <code>{{ __('Closing:') }} {{'{{'}}data.title{{'}}'}}</code></li>
                                            <li>{{ __('Description:') }} <code>{{ __('Contract Price: $') }}{{'{{'}}data.contract_price{{'}}'}}</code></li>
                                            <li>{{ __('Start Date/Time: Use the closing_date from the payload if available') }}</li>
                                        </ul>
                                    </li>
                                    <li>
                                        <strong>{{ __('Turn on the Zap!') }}</strong> {{ __('Closing events will auto-populate on your Google Calendar.') }}
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Make (Integromat) Recipes --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5v9l-8 4.5l-8 -4.5v-9l8 -4.5"/><path d="M12 12l8 -4.5"/><path d="M12 12v9"/><path d="M12 12l-8 -4.5"/></svg>
                    {{ __('Make (Integromat) Recipes') }}
                </h3>
            </div>
            <div class="card-body">
                <p class="text-secondary">{{ __('Make (formerly Integromat) offers powerful visual automation with branching logic, routers, and error handling. Use the "Webhooks" module to receive InsulaCRM events.') }}</p>

                <div class="accordion" id="make-accordion">
                    {{-- Make: Google Sheets --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#make-sheets">
                                <strong>{{ __('InsulaCRM -> Google Sheets (via Make)') }}</strong>
                                <span class="ms-2 text-secondary small">{{ __('Log leads with advanced filtering') }}</span>
                            </button>
                        </h2>
                        <div id="make-sheets" class="accordion-collapse collapse" data-bs-parent="#make-accordion">
                            <div class="accordion-body">
                                <ol class="mb-0">
                                    <li class="mb-2">
                                        <strong>{{ __('Create a new Scenario') }}</strong> {{ __('at') }} <a href="https://www.make.com" target="_blank" rel="noopener">make.com</a>
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Add Webhook module:') }}</strong> {{ __('Choose "Webhooks" > "Custom webhook". Click "Add" to create a new webhook and copy the URL.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Add webhook in InsulaCRM:') }}</strong> {{ __('Paste the Make webhook URL in') }}
                                        <a href="{{ route('settings.index', ['tab' => 'webhooks']) }}">{{ __('Settings') }} > {{ __('Webhooks') }}</a>
                                        {{ __('with') }} <code>lead.created</code> {{ __('event.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Determine data structure:') }}</strong> {{ __('Create a test lead in InsulaCRM, then click "Re-determine data structure" in Make.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Add a Router (optional):') }}</strong> {{ __('Route leads to different sheets based on temperature (hot/warm/cold) or property type.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Add Google Sheets module:') }}</strong> {{ __('Choose "Add a Row". Connect your Google account and map the fields.') }}
                                    </li>
                                    <li>
                                        <strong>{{ __('Activate the scenario!') }}</strong> {{ __('Set the scheduling to "Immediately" for real-time processing.') }}
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    {{-- Make: Multi-action --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#make-multi">
                                <strong>{{ __('Multi-Action: Slack + CRM + Sheet') }}</strong>
                                <span class="ms-2 text-secondary small">{{ __('Process deal changes with Router') }}</span>
                            </button>
                        </h2>
                        <div id="make-multi" class="accordion-collapse collapse" data-bs-parent="#make-accordion">
                            <div class="accordion-body">
                                <ol class="mb-0">
                                    <li class="mb-2">
                                        <strong>{{ __('Webhook trigger:') }}</strong> {{ __('Set up a "Custom webhook" and register it with the') }} <code>deal.stage_changed</code> {{ __('event.') }}
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Add a Router module') }}</strong> {{ __('to branch the scenario:') }}
                                        <ul class="mt-1">
                                            <li><strong>{{ __('Branch 1 (Always):') }}</strong> {{ __('Slack > Send Message to #deals channel') }}</li>
                                            <li><strong>{{ __('Branch 2 (Filter: stage = closing):') }}</strong> {{ __('Google Calendar > Create Event') }}</li>
                                            <li><strong>{{ __('Branch 3 (Filter: stage = closed_won):') }}</strong> {{ __('Google Sheets > Add Row to "Closed Deals" sheet') }}</li>
                                        </ul>
                                    </li>
                                    <li class="mb-2">
                                        <strong>{{ __('Set filters on each branch:') }}</strong> {{ __('Click the filter icon between the Router and each module to set conditions.') }}
                                    </li>
                                    <li>
                                        <strong>{{ __('Activate!') }}</strong> {{ __('One webhook triggers multiple actions based on deal stage.') }}
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Custom Webhook / Developer Section --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 8l-4 4l4 4"/><path d="M17 8l4 4l-4 4"/><path d="M14 4l-4 16"/></svg>
                    {{ __('Custom Webhook Integration') }}
                </h3>
            </div>
            <div class="card-body">
                <h4 class="mb-3">{{ __('Testing with cURL') }}</h4>
                <p class="text-secondary">{{ __('Test your webhook endpoint by simulating an InsulaCRM payload:') }}</p>
                <pre class="bg-dark text-light p-3 rounded"><code>curl -X POST https://your-endpoint.com/webhook \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: sha256=YOUR_SIGNATURE" \
  -H "X-Webhook-Event: lead.created" \
  -d '{
    "event": "lead.created",
    "timestamp": "{{ now()->toIso8601String() }}",
    "data": {
      "id": 1,
      "first_name": "Test",
      "last_name": "Lead",
      "email": "test@example.com",
      "status": "new"
    }
  }'</code></pre>

                <h4 class="mt-4 mb-3">{{ __('Payload Structure') }}</h4>
                <p class="text-secondary">{{ __('All webhook payloads follow this structure:') }}</p>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>{{ __('Field') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Description') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><code>event</code></td><td>string</td><td>{{ __('The event name (e.g., lead.created)') }}</td></tr>
                            <tr><td><code>timestamp</code></td><td>string</td><td>{{ __('ISO 8601 timestamp of when the event occurred') }}</td></tr>
                            <tr><td><code>data</code></td><td>object</td><td>{{ __('Event-specific data (varies by event type)') }}</td></tr>
                        </tbody>
                    </table>
                </div>

                <h4 class="mt-4 mb-3">{{ __('HTTP Headers') }}</h4>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>{{ __('Header') }}</th>
                                <th>{{ __('Description') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><code>Content-Type</code></td><td><code>application/json</code></td></tr>
                            <tr><td><code>X-Webhook-Event</code></td><td>{{ __('The event name') }}</td></tr>
                            <tr><td><code>X-Webhook-Signature</code></td><td>{{ __('HMAC-SHA256 signature (if secret is configured)') }}</td></tr>
                            <tr><td><code>User-Agent</code></td><td><code>InsulaCRM-Webhook/1.0</code></td></tr>
                        </tbody>
                    </table>
                </div>

                <h4 class="mt-4 mb-3">{{ __('HMAC Signature Verification') }}</h4>
                <p class="text-secondary">{{ __('If you set a webhook secret, InsulaCRM signs every payload with HMAC-SHA256. Verify the signature on your end to ensure the request is authentic.') }}</p>

                <div class="mb-3">
                    <h5>{{ __('PHP Example') }}</h5>
                    <pre class="bg-dark text-light p-3 rounded"><code>&lt;?php
$payload = file_get_contents('php://input');
$secret = 'your-webhook-secret';
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

// Compute expected signature
$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

// Constant-time comparison to prevent timing attacks
if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Signature valid — process the webhook
$data = json_decode($payload, true);
$event = $data['event'];
// ... handle event ...</code></pre>
                </div>

                <div>
                    <h5>{{ __('Node.js Example') }}</h5>
                    <pre class="bg-dark text-light p-3 rounded"><code>const crypto = require('crypto');
const express = require('express');
const app = express();

app.post('/webhook', express.json({ verify: (req, res, buf) => {
    req.rawBody = buf;
}}), (req, res) => {
    const secret = 'your-webhook-secret';
    const signature = req.headers['x-webhook-signature'] || '';

    // Compute expected signature
    const expected = 'sha256=' + crypto
        .createHmac('sha256', secret)
        .update(req.rawBody)
        .digest('hex');

    // Constant-time comparison
    if (!crypto.timingSafeEqual(
        Buffer.from(expected),
        Buffer.from(signature)
    )) {
        return res.status(401).json({ error: 'Invalid signature' });
    }

    // Signature valid — process the webhook
    const { event, data } = req.body;
    console.log(`Received ${event}:`, data);

    res.status(200).json({ received: true });
});</code></pre>
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Quick Setup --}}
        <div class="card mb-4">
            <div class="card-body text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg mb-3 text-primary" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4.876 13.61a4 4 0 1 0 6.124 3.39h6"/><path d="M15.066 20.502a4 4 0 1 0 1.934 -7.502c-.344 0 -.678 .045 -1 .126"/><path d="M12 8a4 4 0 1 0 -1.936 7.498"/></svg>
                <h3>{{ __('Ready to set up a webhook?') }}</h3>
                <p class="text-secondary">{{ __('Configure your webhook endpoints in Settings.') }}</p>
                <a href="{{ route('settings.index', ['tab' => 'webhooks']) }}" class="btn btn-primary">
                    {{ __('Go to Webhook Settings') }}
                </a>
            </div>
        </div>

        {{-- Tips --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">{{ __('Tips & Best Practices') }}</h3>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <strong>{{ __('Always set a secret') }}</strong>
                        <p class="text-secondary small mb-0">{{ __('HMAC signing prevents unauthorized requests to your webhook endpoint.') }}</p>
                    </li>
                    <li class="mb-3">
                        <strong>{{ __('Respond quickly') }}</strong>
                        <p class="text-secondary small mb-0">{{ __('Return a 200 response within 10 seconds. Process data asynchronously if needed.') }}</p>
                    </li>
                    <li class="mb-3">
                        <strong>{{ __('Handle retries') }}</strong>
                        <p class="text-secondary small mb-0">{{ __('InsulaCRM retries failed webhooks up to 3 times with exponential backoff. Make your handler idempotent.') }}</p>
                    </li>
                    <li class="mb-3">
                        <strong>{{ __('Monitor failures') }}</strong>
                        <p class="text-secondary small mb-0">{{ __('Webhooks auto-disable after 10 consecutive failures. Check the Settings page for failure counts.') }}</p>
                    </li>
                    <li>
                        <strong>{{ __('Use specific events') }}</strong>
                        <p class="text-secondary small mb-0">{{ __('Subscribe only to events you need. Using "All Events" can generate high traffic.') }}</p>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Supported Services --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Works With') }}</h3>
            </div>
            <div class="card-body">
                <p class="text-secondary small">{{ __('InsulaCRM webhooks are standard HTTP POST requests, compatible with any service that accepts webhooks:') }}</p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-blue-lt">Zapier</span>
                    <span class="badge bg-blue-lt">Make</span>
                    <span class="badge bg-blue-lt">n8n</span>
                    <span class="badge bg-blue-lt">Pabbly</span>
                    <span class="badge bg-blue-lt">Slack</span>
                    <span class="badge bg-blue-lt">Discord</span>
                    <span class="badge bg-blue-lt">Google Sheets</span>
                    <span class="badge bg-blue-lt">Airtable</span>
                    <span class="badge bg-blue-lt">HubSpot</span>
                    <span class="badge bg-blue-lt">Salesforce</span>
                    <span class="badge bg-blue-lt">Mailchimp</span>
                    <span class="badge bg-blue-lt">ActiveCampaign</span>
                    <span class="badge bg-blue-lt">Twilio</span>
                    <span class="badge bg-blue-lt">AWS Lambda</span>
                    <span class="badge bg-blue-lt">{{ __('Any HTTP endpoint') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
