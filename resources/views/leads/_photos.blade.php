<div class="card mb-3" id="photos-section">
    <div class="card-header">
        <h3 class="card-title">{{ __('Photos') }}</h3>
        <div class="card-actions">
            <span class="text-secondary">{{ $lead->photos->count() }} {{ __('photo(s)') }}</span>
            <a class="btn btn-ghost-secondary btn-sm" data-bs-toggle="collapse" href="#section-photos" aria-expanded="true" aria-label="{{ __('Toggle section') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="6 9 12 15 18 9"/></svg>
            </a>
        </div>
    </div>
    <div class="card-body collapse show" id="section-photos">
        @if($lead->photos->count())
        <div class="row g-2 mb-3" id="photo-gallery">
            @foreach($lead->photos as $photo)
            <div class="col-6 col-sm-4 col-md-3">
                <div class="position-relative" style="border-radius:6px;overflow:hidden;">
                    <a href="{{ $photo->url }}" target="_blank" class="d-block photo-thumb" data-caption="{{ $photo->caption }}" data-original="{{ $photo->original_name }}">
                        <img src="{{ $photo->url }}" alt="{{ $photo->caption ?? $photo->original_name }}"
                             class="w-100" style="height:140px;object-fit:cover;cursor:pointer;border-radius:6px;">
                    </a>
                    <form action="{{ route('leads.photos.delete', [$lead, $photo]) }}" method="POST"
                          class="position-absolute" style="top:4px;right:4px;"
                          onsubmit="return confirm('{{ __('Delete this photo?') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-icon" style="background:rgba(0,0,0,0.5);border:none;padding:2px 5px;" title="{{ __('Delete') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="#fff" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </form>
                    @if($photo->caption)
                    <div class="position-absolute w-100 px-2 py-1" style="bottom:0;left:0;background:rgba(0,0,0,0.55);color:#fff;font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $photo->caption }}
                    </div>
                    @endif
                </div>
                <div class="mt-1">
                    <small class="text-secondary" title="{{ $photo->original_name }}">
                        {{ $photo->uploader->name ?? __('Unknown') }} &middot; {{ Fmt::date($photo->created_at) }}
                    </small>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Upload Form -->
        <form action="{{ route('leads.photos.upload', $lead) }}" method="POST" enctype="multipart/form-data" id="photo-upload-form">
            @csrf
            <div class="mb-2">
                <div id="photo-drop-zone" class="border border-2 border-dashed rounded text-center py-3 px-2" style="cursor:pointer;border-color:#c5d2de !important;transition:background 0.15s;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler mb-1 text-secondary" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8h.01"/><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z"/><path d="M6 18l3.5 -4a.9 .9 0 0 1 1.1 0l2.4 2l3.5 -4.5a.9 .9 0 0 1 1.1 0l2.4 3"/></svg>
                    <div class="text-secondary" style="font-size:13px;">
                        {{ __('Drop photos here or') }} <strong class="text-primary">{{ __('click to browse') }}</strong>
                    </div>
                    <small class="text-secondary">{{ __('JPG, PNG, GIF, WebP. Max 10MB each, up to 10 at a time.') }}</small>
                    <input type="file" name="photos[]" id="photo-file-input" multiple accept="image/jpeg,image/png,image/gif,image/webp" class="d-none">
                </div>
            </div>
            <div id="photo-preview-area" class="row g-2 mb-2" style="display:none;"></div>
            <div id="photo-upload-actions" style="display:none;" class="d-flex justify-content-between align-items-center">
                <span class="text-secondary" id="photo-count-label">{{ __('0 selected') }}</span>
                <div>
                    <button type="button" class="btn btn-ghost-secondary btn-sm" id="photo-clear-btn">{{ __('Clear') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><polyline points="7 9 12 4 17 9"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                        {{ __('Upload Photos') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Photo Lightbox Modal -->
<div class="modal modal-blur fade" id="photo-lightbox" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background:transparent;border:none;box-shadow:none;">
            <div class="text-end mb-1">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <img src="" id="lightbox-img" class="w-100 rounded" style="max-height:80vh;object-fit:contain;">
            <div class="text-center text-white mt-2" id="lightbox-caption"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var dropZone = document.getElementById('photo-drop-zone');
    var fileInput = document.getElementById('photo-file-input');
    var previewArea = document.getElementById('photo-preview-area');
    var actions = document.getElementById('photo-upload-actions');
    var countLabel = document.getElementById('photo-count-label');
    var clearBtn = document.getElementById('photo-clear-btn');
    var form = document.getElementById('photo-upload-form');

    // Click to browse
    dropZone.addEventListener('click', function() { fileInput.click(); });

    // Drag & drop
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.style.background = '#e8f0fe';
    });
    dropZone.addEventListener('dragleave', function() {
        dropZone.style.background = '';
    });
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.style.background = '';
        fileInput.files = e.dataTransfer.files;
        showPreviews();
    });

    fileInput.addEventListener('change', showPreviews);

    function showPreviews() {
        previewArea.innerHTML = '';
        var files = fileInput.files;
        if (!files.length) {
            previewArea.style.display = 'none';
            actions.style.display = 'none';
            return;
        }

        previewArea.style.display = 'flex';
        actions.style.display = 'flex';
        countLabel.textContent = files.length + ' {{ __('selected') }}';

        for (var i = 0; i < Math.min(files.length, 10); i++) {
            (function(index) {
                var file = files[index];
                var col = document.createElement('div');
                col.className = 'col-6 col-sm-4 col-md-3';

                var reader = new FileReader();
                reader.onload = function(e) {
                    col.innerHTML = '<img src="' + e.target.result + '" class="w-100 rounded" style="height:100px;object-fit:cover;">' +
                        '<input type="text" name="captions[' + index + ']" class="form-control form-control-sm mt-1" placeholder="{{ __('Caption (optional)') }}" style="font-size:11px;">';
                    previewArea.appendChild(col);
                };
                reader.readAsDataURL(file);
            })(i);
        }
    }

    clearBtn.addEventListener('click', function() {
        fileInput.value = '';
        previewArea.innerHTML = '';
        previewArea.style.display = 'none';
        actions.style.display = 'none';
    });

    // Lightbox
    var lightboxModal = document.getElementById('photo-lightbox');
    if (lightboxModal) {
        document.querySelectorAll('.photo-thumb').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('lightbox-img').src = this.href;
                var caption = this.dataset.caption || this.dataset.original || '';
                document.getElementById('lightbox-caption').textContent = caption;
                new bootstrap.Modal(lightboxModal).show();
            });
        });
    }
});
</script>
@endpush
