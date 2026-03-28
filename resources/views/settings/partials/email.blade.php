@php
    $mail = $tenant->mail_settings ?? [];
@endphp

<h3 class="mb-3">{{ __('Email / SMTP Settings') }}</h3>
<p class="text-secondary mb-3">{{ __('Configure outgoing email for lead outreach, notifications, and system messages. We recommend using a transactional email service for best deliverability.') }}</p>

<div class="row mb-4">
    <div class="col-12">
        <div class="accordion" id="email-setup-guide">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#email-guide-content">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M12 9h.01"/><path d="M11 12h1v4h1"/></svg>
                        {{ __('Setup Guide — Which provider should I use?') }}
                    </button>
                </h2>
                <div id="email-guide-content" class="accordion-collapse collapse">
                    <div class="accordion-body">
                        <h4>{{ __('Recommended: Transactional Email Services') }}</h4>
                        <p class="text-secondary">{{ __('These services are purpose-built for sending application emails with high deliverability. Your existing email address (e.g. you@yourcompany.com) stays as the From address — the service only handles delivery.') }}</p>
                        <div class="table-responsive mb-3">
                            <table class="table table-vcenter">
                                <thead>
                                    <tr>
                                        <th>{{ __('Provider') }}</th>
                                        <th>{{ __('Free Tier') }}</th>
                                        <th>{{ __('SMTP Settings') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>SendGrid</strong></td>
                                        <td>{{ __('100 emails/day') }}</td>
                                        <td><code>smtp.sendgrid.net</code> {{ __('Port') }} 587 / TLS<br><small class="text-secondary">{{ __('Username:') }} <code>apikey</code> &middot; {{ __('Password: your API key') }}</small></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Mailgun</strong></td>
                                        <td>{{ __('1,000 emails/month') }}</td>
                                        <td><code>smtp.mailgun.org</code> {{ __('Port') }} 587 / TLS<br><small class="text-secondary">{{ __('Credentials from your Mailgun dashboard') }}</small></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Amazon SES</strong></td>
                                        <td>{{ __('62,000/month (from EC2)') }}</td>
                                        <td><code>email-smtp.{region}.amazonaws.com</code> {{ __('Port') }} 587 / TLS<br><small class="text-secondary">{{ __('IAM SMTP credentials') }}</small></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Postmark</strong></td>
                                        <td>{{ __('100 emails/month') }}</td>
                                        <td><code>smtp.postmarkapp.com</code> {{ __('Port') }} 587 / TLS<br><small class="text-secondary">{{ __('Server API token as username and password') }}</small></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-warning py-2 mb-3">
                            <strong>{{ __('Important:') }}</strong> {{ __('Add the SPF and DKIM DNS records provided by your email service to your domain. Without these, emails may land in spam.') }}
                        </div>

                        <h4>{{ __('Using Gmail or Microsoft 365 Directly') }}</h4>
                        <p class="text-secondary">{{ __('Gmail and Microsoft 365 have disabled basic SMTP authentication by default. If you want to send directly through these providers:') }}</p>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card card-sm mb-2">
                                    <div class="card-body">
                                        <strong>Gmail</strong>
                                        <ol class="small mb-0 mt-1">
                                            <li>{{ __('Enable 2-Step Verification on your Google account') }}</li>
                                            <li>{{ __('Generate an App Password at myaccount.google.com > Security') }}</li>
                                            <li>{{ __('Use') }} <code>smtp.gmail.com</code> {{ __('Port') }} 587 / TLS</li>
                                            <li>{{ __('Username: your Gmail address, Password: the App Password') }}</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-sm mb-2">
                                    <div class="card-body">
                                        <strong>Microsoft 365 / Outlook</strong>
                                        <ol class="small mb-0 mt-1">
                                            <li>{{ __('Enable SMTP AUTH for the mailbox in Microsoft 365 Admin Center') }}</li>
                                            <li>{{ __('Or generate an App Password if using personal Outlook') }}</li>
                                            <li>{{ __('Use') }} <code>smtp.office365.com</code> {{ __('Port') }} 587 / TLS</li>
                                            <li>{{ __('Username: your email address, Password: account or App Password') }}</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-secondary small mt-2 mb-0">{{ __('Note: Gmail and Microsoft 365 have daily sending limits (300-500 emails/day). For higher volumes, use a transactional email service.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="{{ route('settings.updateMail') }}" method="POST" id="mail-settings-form">
    @csrf
    @method('PUT')

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">{{ __('SMTP Host') }}</label>
            <input type="text" name="mail_host" class="form-control" value="{{ $mail['mail_host'] ?? '' }}" placeholder="smtp.sendgrid.net">
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('SMTP Port') }}</label>
            <input type="number" name="mail_port" class="form-control" value="{{ $mail['mail_port'] ?? '' }}" placeholder="587">
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('Encryption') }}</label>
            <select name="mail_encryption" class="form-select">
                <option value="" {{ empty($mail['mail_encryption']) ? 'selected' : '' }}>{{ __('None') }}</option>
                <option value="tls" {{ ($mail['mail_encryption'] ?? '') === 'tls' ? 'selected' : '' }}>TLS</option>
                <option value="ssl" {{ ($mail['mail_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
            </select>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">{{ __('SMTP Username') }}</label>
            <input type="text" name="mail_username" class="form-control" value="{{ $mail['mail_username'] ?? '' }}" placeholder="apikey">
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('SMTP Password / API Key') }}</label>
            <input type="password" name="mail_password" class="form-control" value="" placeholder="{{ !empty($mail['mail_password']) ? '••••••••' : '' }}">
            @if(!empty($mail['mail_password']))
                <small class="form-hint">{{ __('Leave blank to keep current password.') }}</small>
            @endif
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">{{ __('From Address') }}</label>
            <input type="email" name="mail_from_address" class="form-control" value="{{ $mail['mail_from_address'] ?? '' }}" placeholder="support@yourcompany.com">
            <small class="form-hint">{{ __('This can be your business email (e.g. you@yourcompany.com). The email service delivers on its behalf.') }}</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('From Name') }}</label>
            <input type="text" name="mail_from_name" class="form-control" value="{{ $mail['mail_from_name'] ?? '' }}" placeholder="{{ $tenant->name }}">
            <small class="form-hint">{{ __('Agents can override this with their own name in Profile > Outgoing Email Identity.') }}</small>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2">
        <button type="submit" class="btn btn-primary">{{ __('Save Email Settings') }}</button>
        <button type="button" class="btn btn-outline-secondary" id="test-email-btn">{{ __('Send Test Email') }}</button>
        <span id="test-email-status" class="small"></span>
    </div>
</form>

@push('scripts')
<script>
document.getElementById('test-email-btn').addEventListener('click', function() {
    var btn = this;
    var status = document.getElementById('test-email-status');
    btn.disabled = true;
    status.textContent = '{{ __("Sending...") }}';
    status.className = 'small text-muted';

    var controller = new AbortController();
    var timeout = setTimeout(function() { controller.abort(); }, 30000);

    fetch('{{ route('settings.testEmail') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({}),
        signal: controller.signal
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            status.textContent = '{{ __("Test email sent to") }} ' + data.email;
            status.className = 'small text-success';
        } else {
            status.textContent = data.message || '{{ __("Failed to send test email.") }}';
            status.className = 'small text-danger';
        }
    })
    .catch(function(err) {
        if (err.name === 'AbortError') {
            status.textContent = '{{ __("Connection timed out. Check your SMTP host and port.") }}';
        } else {
            status.textContent = '{{ __("Failed to send test email.") }}';
        }
        status.className = 'small text-danger';
    })
    .finally(function() {
        clearTimeout(timeout);
        btn.disabled = false;
    });
});
</script>
@endpush
