@props(['leadSourceROI' => []])

<div class="card">
    <div class="card-header border-0">
        <h3 class="card-title">{{ __('Lead Source ROI') }}</h3>
        <div class="card-actions">
            <span class="text-secondary small">{{ __('This Month') }}</span>
        </div>
    </div>
    <div id="lead-source-roi-container">
        <div class="card-body text-center text-secondary py-4">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>{{ __('Loading...') }}
        </div>
    </div>
</div>
