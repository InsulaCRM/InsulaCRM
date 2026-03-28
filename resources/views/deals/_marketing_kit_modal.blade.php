<!-- Marketing Kit Modal -->
<div class="modal modal-blur fade" id="marketing-kit-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 8a3 3 0 0 1 0 6"/><path d="M10 8v11a1 1 0 0 1 -1 1h-1a1 1 0 0 1 -1 -1v-5"/><path d="M12 8h0l4.524 -3.77a.9 .9 0 0 1 1.476 .692v12.156a.9 .9 0 0 1 -1.476 .692l-4.524 -3.77h-8a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h8"/></svg>
                    {{ __('Marketing Kit') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="marketing-kit-loading" class="text-center py-5">
                    <div class="spinner-border text-purple" role="status"></div>
                    <p class="text-secondary mt-2">{{ __('Generating marketing content...') }}</p>
                </div>
                <div id="marketing-kit-error" class="alert alert-danger" style="display:none;"></div>
                <div id="marketing-kit-content" style="display:none;">
                    @foreach(['property_description' => 'Property Description', 'social_caption' => 'Social Media Caption', 'flyer_copy' => 'Flyer Copy', 'open_house_blurb' => 'Open House Blurb', 'email_blast' => 'Email Blast'] as $key => $label)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold mb-0">{{ __($label) }}</label>
                            <button type="button" class="btn btn-sm btn-ghost-secondary copy-section-btn" data-section="{{ $key }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8v-2a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h2"/></svg>
                                {{ __('Copy') }}
                            </button>
                        </div>
                        <div class="form-control" style="height:auto; min-height:60px; white-space:pre-wrap;" id="mk-{{ $key }}"></div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer" id="marketing-kit-footer" style="display:none;">
                <button type="button" class="btn btn-outline-secondary" id="mk-copy-all">{{ __('Copy All') }}</button>
                <button type="button" class="btn btn-outline-primary" id="mk-export-text">{{ __('Export as Text') }}</button>
                <button type="button" class="btn me-auto" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>