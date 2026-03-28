<h3 class="mb-3">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Client Portal') : __('Buyer Portal') }}</h3>
<p class="text-secondary mb-3">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Enable a public-facing landing page where potential clients can browse available listings and register interest. The portal URL uses your company slug.') : __('Enable a public-facing landing page where potential buyers can browse available properties and register interest. The portal URL uses your company slug.') }}</p>

<form action="{{ route('settings.updateBuyerPortal') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-4">
        <label class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="buyer_portal_enabled" value="1" {{ $tenant->buyer_portal_enabled ? 'checked' : '' }} id="buyer-portal-toggle">
            <span class="form-check-label fw-bold">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Enable Client Portal') : __('Enable Buyer Portal') }}</span>
        </label>
        <small class="form-hint d-block mt-1">{{ __('When enabled, a public landing page is accessible at the URL below.') }}</small>
    </div>

    <div class="mb-3">
        <label class="form-label">{{ __('Portal URL') }}</label>
        <div class="input-group">
            <input type="text" class="form-control" value="{{ url('/p/' . $tenant->slug) }}" readonly id="portal-url">
            <button class="btn btn-outline-secondary" type="button" id="copy-portal-url" title="{{ __('Copy URL') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z"/><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1"/></svg>
            </button>
            @if($tenant->buyer_portal_enabled)
                <a href="{{ url('/p/' . $tenant->slug) }}" target="_blank" class="btn btn-outline-primary" title="{{ __('Open Portal') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6"/><path d="M11 13l9 -9"/><path d="M15 4h5v5"/></svg>
                </a>
            @endif
        </div>
        <small class="form-hint">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Share this link with potential clients or embed it on your website.') : __('Share this link with potential buyers or embed it on your website.') }}</small>
    </div>

    <div class="mb-3">
        <label class="form-label">{{ __('Headline') }}</label>
        <input type="text" name="buyer_portal_headline" class="form-control" value="{{ $tenant->buyer_portal_headline ?? '' }}" maxlength="255" placeholder="{{ $tenant->name }}">
        <small class="form-hint">{{ __('Displayed prominently at the top of the portal page. Defaults to your company name if left blank.') }}</small>
    </div>

    <div class="mb-3">
        <label class="form-label">{{ __('Description') }}</label>
        <textarea name="buyer_portal_description" class="form-control" rows="3" maxlength="2000" placeholder="{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Browse our available listings and register for updates on properties that match your criteria.') : __('Browse our available investment properties and register to receive notifications about new deals.') }}">{{ $tenant->buyer_portal_description ?? '' }}</textarea>
        <small class="form-hint">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('A brief message shown below the headline. Describe what clients can expect.') : __('A brief message shown below the headline. Describe what buyers can expect.') }}</small>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ __('Save Settings') }}</button>
        @if($tenant->buyer_portal_enabled)
            <a href="{{ url('/p/' . $tenant->slug) }}" target="_blank" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
                {{ __('Preview Portal') }}
            </a>
        @endif
    </div>
</form>

@if(auth()->user()->tenant->ai_enabled)
<hr class="my-4">
<h3 class="mb-2">{{ __('AI Property Descriptions') }}</h3>
<p class="text-secondary mb-3">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Generate marketing descriptions for properties visible on your client portal. Click the button below to preview an AI-generated description for one of your portal properties.') : __('Generate marketing descriptions for properties visible on your buyer portal. Click the button below to preview an AI-generated description for one of your portal properties.') }}</p>

<div class="mb-3">
    <button type="button" class="btn btn-outline-purple" id="ai-portal-desc-btn">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
        {{ __('Generate Sample') }}
    </button>
</div>

<div id="ai-portal-desc-result" class="d-none">
    <div id="ai-portal-desc-loading" class="d-none">
        <div class="d-flex align-items-center text-purple">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
            {{ __('Generating description...') }}
        </div>
    </div>
    <div id="ai-portal-desc-error" class="alert alert-danger d-none"></div>
    <div id="ai-portal-desc-preview" class="card d-none">
        <div class="card-header">
            <h4 class="card-title" id="ai-portal-desc-property-title"></h4>
        </div>
        <div class="card-body">
            <div id="ai-portal-desc-text" class="mb-0" style="white-space: pre-wrap;"></div>
        </div>
    </div>
</div>

<small class="form-hint mt-2 d-block">{{ __('To generate descriptions for all properties, use the AI Property Description button on each property\'s detail page.') }}</small>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    var copyBtn = document.getElementById('copy-portal-url');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            var urlInput = document.getElementById('portal-url');
            urlInput.select();
            navigator.clipboard.writeText(urlInput.value).then(function() {
                copyBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>';
                setTimeout(function() {
                    copyBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z"/><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1"/></svg>';
                }, 2000);
            });
        });
    }

    var aiPortalBtn = document.getElementById('ai-portal-desc-btn');
    if (aiPortalBtn) {
        aiPortalBtn.addEventListener('click', function() {
            var resultArea = document.getElementById('ai-portal-desc-result');
            var loading = document.getElementById('ai-portal-desc-loading');
            var errorEl = document.getElementById('ai-portal-desc-error');
            var preview = document.getElementById('ai-portal-desc-preview');
            var titleEl = document.getElementById('ai-portal-desc-property-title');
            var textEl = document.getElementById('ai-portal-desc-text');

            resultArea.classList.remove('d-none');
            loading.classList.remove('d-none');
            errorEl.classList.add('d-none');
            preview.classList.add('d-none');
            aiPortalBtn.disabled = true;

            fetch("{{ url('/ai/portal-description') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ property_id: 'first_portal' })
            })
            .then(function(resp) { return resp.json().then(function(data) { return { ok: resp.ok, data: data }; }); })
            .then(function(result) {
                loading.classList.add('d-none');
                aiPortalBtn.disabled = false;

                if (!result.ok || result.data.error) {
                    errorEl.textContent = result.data.error || "{{ __('Failed to generate description.') }}";
                    errorEl.classList.remove('d-none');
                    return;
                }

                titleEl.textContent = result.data.property_address || "{{ __('Portal Property') }}";
                textEl.textContent = result.data.description || '';
                preview.classList.remove('d-none');
            })
            .catch(function(err) {
                loading.classList.add('d-none');
                aiPortalBtn.disabled = false;
                errorEl.textContent = "{{ __('An error occurred. Please try again.') }}";
                errorEl.classList.remove('d-none');
            });
        });
    }
});
</script>
