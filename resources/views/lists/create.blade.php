@extends('layouts.app')

@section('title', __('Import Lead List'))
@section('page-title', __('Import Lead List'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('lists.index') }}">{{ __('Lists') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Import') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('Import Lead List') }}</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('lists.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label required">{{ __('List Name') }}</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label required">{{ __('List Type') }}</label>
                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                    <option value="">{{ __('Select type...') }}</option>
                    @if(($businessMode ?? 'wholesale') === 'realestate')
                    @foreach([
                        'open_house' => __('Open House'),
                        'website' => __('Website'),
                        'referral' => __('Referral'),
                        'zillow' => __('Zillow'),
                        'realtor_com' => __('Realtor.com'),
                        'sphere' => __('Sphere of Influence'),
                        'past_client' => __('Past Client'),
                        'sign_call' => __('Sign Call'),
                        'social_media' => __('Social Media'),
                        'custom' => __('Custom'),
                    ] as $val => $label)
                        <option value="{{ $val }}" {{ old('type') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                    @else
                    @foreach([
                        'tax_delinquent' => __('Tax Delinquent'),
                        'probate' => __('Probate'),
                        'code_violation' => __('Code Violation'),
                        'absentee_owner' => __('Absentee Owner'),
                        'pre_foreclosure' => __('Pre-Foreclosure'),
                        'divorce' => __('Divorce'),
                        'utility_shutoff' => __('Utility Shutoff'),
                        'vacant' => __('Vacant'),
                        'driving_for_dollars' => __('Driving for Dollars'),
                        'custom' => __('Custom'),
                    ] as $val => $label)
                        <option value="{{ $val }}" {{ old('type') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                    @endif
                </select>
                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label required">{{ __('CSV File') }}</label>
                <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv" required>
                @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div id="csv-preview" style="display:none;" class="mb-3">
                <div class="card-header px-0">
                    <h4 class="card-title">{{ __('CSV Preview (First 5 Rows)') }}</h4>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" id="preview-table">
                        <thead id="preview-thead"></thead>
                        <tbody id="preview-tbody"></tbody>
                    </table>
                </div>
            </div>
            <div class="alert alert-info" id="csv-format-info">
                <h4 class="alert-title">{{ __('Expected CSV Format') }}</h4>
                <p class="mb-0">{{ __('Your CSV file should include the following columns in this order:') }}<br>
                <code>first_name, last_name, phone, email, address</code><br>
                {{ __('The first row should be a header row. Each subsequent row represents one lead record.') }}</p>
            </div>

            <div class="mb-3" id="saved-mapping-controls" style="display:none;">
                <div class="d-flex gap-2 align-items-center">
                    <select class="form-select form-select-sm" id="load-mapping-select" style="width:auto;">
                        <option value="">{{ __('Load Saved Mapping...') }}</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="save-mapping-btn">{{ __('Save This Mapping') }}</button>
                </div>
            </div>
            @if(auth()->user()->tenant->ai_enabled)
            <div id="csv-mapping-preview" class="mb-3" style="display:none;">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">{{ __('AI Column Mapping Preview') }}</h4>
                        <span id="csv-mapping-status" class="badge bg-blue-lt">{{ __('Analyzing...') }}</span>
                    </div>
                    <div class="card-body p-0">
                        <div id="csv-mapping-loading" class="text-center py-4">
                            <div class="spinner-border spinner-border-sm text-purple"></div>
                            <span class="text-secondary ms-2">{{ __('AI is analyzing your CSV columns...') }}</span>
                        </div>
                        <div id="csv-mapping-table" style="display:none;"></div>
                        <div id="csv-mapping-error" class="alert alert-warning m-3" style="display:none;"></div>
                    </div>
                </div>
            </div>
            @endif
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('Duplicate Handling') }}</label>
                <div class="form-selectgroup">
                    <label class="form-selectgroup-item">
                        <input type="radio" name="dedupe_strategy" value="skip" class="form-selectgroup-input" checked>
                        <span class="form-selectgroup-label">{{ __('Skip Duplicates') }}</span>
                    </label>
                    <label class="form-selectgroup-item">
                        <input type="radio" name="dedupe_strategy" value="update" class="form-selectgroup-input">
                        <span class="form-selectgroup-label">{{ __('Update Existing') }}</span>
                    </label>
                    <label class="form-selectgroup-item">
                        <input type="radio" name="dedupe_strategy" value="create_new" class="form-selectgroup-input">
                        <span class="form-selectgroup-label">{{ __('Create New') }}</span>
                    </label>
                </div>
                <small class="form-hint">{{ __('How to handle leads that already exist (matched by phone or address).') }}</small>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('lists.index') }}" class="btn btn-outline-secondary me-2">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Import') }}</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
function escHtml(str) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
}

// CSV Preview
document.querySelector('input[name="file"]')?.addEventListener('change', function() {
    var file = this.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        var lines = e.target.result.split('\n').filter(function(l) { return l.trim(); });
        if (lines.length < 1) return;
        var headers = lines[0].split(',').map(function(h) { return h.trim().replace(/^"/, '').replace(/"$/, ''); });
        var thead = '<tr>' + headers.map(function(h) { return '<th>' + escHtml(h) + '</th>'; }).join('') + '</tr>';
        document.getElementById('preview-thead').innerHTML = thead;
        var tbody = '';
        for (var i = 1; i < Math.min(6, lines.length); i++) {
            var cols = lines[i].split(',').map(function(c) { return c.trim().replace(/^"/, '').replace(/"$/, ''); });
            tbody += '<tr>' + cols.map(function(c) { return '<td>' + escHtml(c) + '</td>'; }).join('') + '</tr>';
        }
        document.getElementById('preview-tbody').innerHTML = tbody;
        document.getElementById('csv-preview').style.display = '';
        document.getElementById('saved-mapping-controls').style.display = '';
    };
    reader.readAsText(file);
});

// Saved Mappings
function loadSavedMappings() {
    fetch('{{ url("/lists/saved-mappings") }}', { headers: { 'Accept': 'application/json' } })
    .then(function(r) { return r.json(); })
    .then(function(mappings) {
        var sel = document.getElementById('load-mapping-select');
        sel.innerHTML = '<option value="">{{ __("Load Saved Mapping...") }}</option>';
        mappings.forEach(function(m) {
            sel.innerHTML += '<option value=\'' + JSON.stringify(m.column_mapping).replace(/'/g, '&#39;') + '\'>' + escHtml(m.name) + '</option>';
        });
    });
}
loadSavedMappings();

document.getElementById('save-mapping-btn')?.addEventListener('click', function() {
    var name = prompt('{{ __("Mapping name:") }}');
    if (!name) return;
    var selects = document.querySelectorAll('select[name^="mapping"]');
    var mapping = {};
    selects.forEach(function(s) { if (s.value) mapping[s.name] = s.value; });
    fetch('{{ url("/lists/save-mapping") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ name: name, column_mapping: mapping })
    }).then(function() { loadSavedMappings(); });
});
</script>
@endpush
@if(auth()->user()->tenant->ai_enabled)
@push('scripts')
<script>
document.querySelector('input[name="file"]').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (!file || !file.name.endsWith('.csv')) return;

    var preview = document.getElementById('csv-mapping-preview');
    var loading = document.getElementById('csv-mapping-loading');
    var tableEl = document.getElementById('csv-mapping-table');
    var errorEl = document.getElementById('csv-mapping-error');
    var statusEl = document.getElementById('csv-mapping-status');

    preview.style.display = 'block';
    loading.style.display = 'block';
    tableEl.style.display = 'none';
    errorEl.style.display = 'none';
    statusEl.textContent = '{{ __('Analyzing...') }}';
    statusEl.className = 'badge bg-blue-lt';

    var reader = new FileReader();
    reader.onload = function(evt) {
        var lines = evt.target.result.split('\n').filter(function(l) { return l.trim(); });
        if (lines.length < 2) {
            loading.style.display = 'none';
            errorEl.style.display = 'block';
            errorEl.textContent = '{{ __('CSV must have at least a header row and one data row.') }}';
            statusEl.textContent = '{{ __('Error') }}';
            statusEl.className = 'badge bg-red-lt';
            return;
        }

        var headers = lines[0].split(',').map(function(h) { return h.trim().replace(/^"|"$/g, ''); });
        var sampleRows = [];
        for (var i = 1; i < Math.min(4, lines.length); i++) {
            sampleRows.push(lines[i].split(',').map(function(v) { return v.trim().replace(/^"|"$/g, ''); }));
        }

        fetch('{{ url("/ai/suggest-csv-mapping") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ headers: headers, sample_rows: sampleRows })
        }).then(function(r) { return r.json(); }).then(function(res) {
            loading.style.display = 'none';
            if (res.error) {
                errorEl.style.display = 'block';
                errorEl.textContent = res.error;
                statusEl.textContent = '{{ __('Error') }}';
                statusEl.className = 'badge bg-red-lt';
                return;
            }

            var mapping = res.mapping || {};
            var crmFields = {
                'first_name': '{{ __('First Name') }}', 'last_name': '{{ __('Last Name') }}', 'phone': '{{ __('Phone') }}',
                'email': '{{ __('Email') }}', 'address': '{{ __('Address') }}', 'city': '{{ __('City') }}', 'state': '{{ __('State') }}',
                'zip_code': '{{ __('Zip Code') }}', 'lead_source': '{{ __('Lead Source') }}', '': '{{ __('(Skip)') }}'
            };

            var html = '<table class="table table-vcenter table-sm mb-0">';
            html += '<thead><tr><th>{{ __('CSV Column') }}</th><th>{{ __('Sample Data') }}</th><th>{{ __('Maps To') }}</th></tr></thead><tbody>';
            headers.forEach(function(header, idx) {
                var sample = sampleRows[0] && sampleRows[0][idx] ? sampleRows[0][idx] : '-';
                var mapped = mapping[idx] || mapping[String(idx)] || '';
                var mappedLabel = crmFields[mapped] || mapped || '{{ __('(Skip)') }}';
                var badgeClass = mapped ? 'bg-green-lt' : 'bg-secondary-lt';
                html += '<tr><td><code>' + escHtml(header) + '</code></td><td class="text-secondary">' + escHtml(sample) + '</td><td><span class="badge ' + badgeClass + '">' + escHtml(mappedLabel) + '</span></td></tr>';
            });
            html += '</tbody></table>';

            tableEl.innerHTML = html;
            tableEl.style.display = 'block';
            statusEl.textContent = '{{ __('Ready') }}';
            statusEl.className = 'badge bg-green-lt';
        }).catch(function() {
            loading.style.display = 'none';
            errorEl.style.display = 'block';
            errorEl.textContent = '{{ __('AI mapping request failed.') }}';
            statusEl.textContent = '{{ __('Error') }}';
            statusEl.className = 'badge bg-red-lt';
        });
    };
    reader.readAsText(file);
});
</script>
@endpush
@endif
@endsection
