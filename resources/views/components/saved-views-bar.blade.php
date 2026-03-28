@props(['entityType'])

<div class="d-flex align-items-center gap-2 flex-wrap mb-2" id="db-saved-views-bar" data-entity-type="{{ $entityType }}">
    <span class="text-secondary small">{{ __('Views:') }}</span>
    <div id="db-saved-views-list" class="d-flex gap-1 flex-wrap">
        <span class="spinner-border spinner-border-sm text-secondary" style="width: 1rem; height: 1rem;"></span>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" id="db-save-view-btn" style="display:none;">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2"/><circle cx="12" cy="14" r="2"/><polyline points="14 4 14 8 8 8"/></svg>
        {{ __('Save View') }}
    </button>
</div>

<!-- Save View Modal -->
<div class="modal modal-blur fade" id="db-save-view-modal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Save Current View') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('View Name') }}</label>
                    <input type="text" class="form-control" id="db-view-name" maxlength="100" required>
                </div>
                @if(auth()->user()->isAdmin())
                <label class="form-check">
                    <input type="checkbox" class="form-check-input" id="db-view-shared">
                    <span class="form-check-label">{{ __('Share with team') }}</span>
                </label>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn me-auto" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="db-save-view-confirm">{{ __('Save') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const entityType = '{{ $entityType }}';
    const baseUrl = '{{ url("/saved-views") }}';
    const listEl = document.getElementById('db-saved-views-list');
    const saveBtn = document.getElementById('db-save-view-btn');
    const isAdmin = {{ auth()->user()->isAdmin() ? 'true' : 'false' }};
    const currentUserId = {{ auth()->id() }};

    function escHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }

    function getCurrentFilters() {
        const params = new URLSearchParams(window.location.search);
        const filters = {};
        params.forEach(function(v, k) {
            if (k !== 'page' && k !== 'saved_view_id' && v) filters[k] = v;
        });
        return filters;
    }

    function hasFilters() {
        return Object.keys(getCurrentFilters()).length > 0;
    }

    function loadViews() {
        fetch(baseUrl + '?entity_type=' + entityType, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } })
        .then(function(r) { return r.json(); })
        .then(function(views) {
            listEl.innerHTML = '';
            if (!views.length) {
                listEl.innerHTML = '<span class="text-muted small">' + '{{ __("No saved views") }}' + '</span>';
            }
            // Auto-apply default view when no filters or saved_view_id are present
            const params = new URLSearchParams(window.location.search);
            if (!hasFilters() && !params.has('saved_view_id')) {
                var defaultView = views.find(function(v) { return v.is_user_default; });
                if (defaultView && defaultView.filters && Object.keys(defaultView.filters).length > 0) {
                    var redirectParams = new URLSearchParams(Object.assign({saved_view_id: defaultView.id}, defaultView.filters));
                    window.location.href = '?' + redirectParams.toString();
                    return;
                }
            }
            const activeId = params.get('saved_view_id');
            views.forEach(function(v) {
                const isActive = activeId == v.id;
                const isOwn = v.user_id == currentUserId;
                let html = '<div class="btn-group btn-group-sm">';
                html += '<a href="?' + new URLSearchParams(Object.assign({saved_view_id: v.id}, v.filters)).toString() + '" class="btn ' + (isActive ? 'btn-primary' : 'btn-outline-secondary') + ' btn-sm">';
                html += (v.is_shared ? '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>' : '') + escHtml(v.name) + '</a>';
                html += '<button type="button" class="btn ' + (isActive ? 'btn-primary' : 'btn-outline-secondary') + ' btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>';
                html += '<div class="dropdown-menu">';
                html += '<a class="dropdown-item" href="#" onclick="setDefaultView(' + v.id + ');return false;">' + (v.is_user_default ? '{{ __("Default") }} ✓' : '{{ __("Set as Default") }}') + '</a>';
                if (isOwn || isAdmin) {
                    html += '<a class="dropdown-item text-danger" href="#" onclick="deleteView(' + v.id + ');return false;">{{ __("Delete") }}</a>';
                }
                html += '</div>';
                html += '</div>';
                listEl.insertAdjacentHTML('beforeend', html);
            });
            if (hasFilters()) saveBtn.style.display = '';
        }).catch(function() {
            listEl.innerHTML = '';
        });
    }

    window.setDefaultView = function(id) {
        fetch(baseUrl + '/' + id + '/default', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        }).then(function() { loadViews(); });
    };

    window.deleteView = function(id) {
        if (!confirm('{{ __("Delete this saved view?") }}')) return;
        fetch(baseUrl + '/' + id, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        }).then(function() { loadViews(); });
    };

    document.getElementById('db-save-view-confirm').addEventListener('click', function() {
        const name = document.getElementById('db-view-name').value.trim();
        if (!name) return;
        const sharedEl = document.getElementById('db-view-shared');
        fetch(baseUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ entity_type: entityType, name: name, filters: getCurrentFilters(), is_shared: sharedEl ? sharedEl.checked : false })
        }).then(function(r) { return r.json(); }).then(function() {
            bootstrap.Modal.getInstance(document.getElementById('db-save-view-modal')).hide();
            document.getElementById('db-view-name').value = '';
            loadViews();
        });
    });

    saveBtn.addEventListener('click', function() {
        new bootstrap.Modal(document.getElementById('db-save-view-modal')).show();
    });

    loadViews();
})();
</script>
@endpush