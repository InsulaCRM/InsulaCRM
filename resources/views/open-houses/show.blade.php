@extends('layouts.app')

@section('title', __('Open House Details'))
@section('page-title', __('Open House Details'))

@section('content')
<div class="row">
    <div class="col-md-8">
        {{-- Open House Details --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Open House Information') }}</h3>
                <div class="card-actions">
                    <a href="{{ route('open-houses.edit', $openHouse) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                    <form method="POST" action="{{ route('open-houses.destroy', $openHouse) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete this open house?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Property') }}</div>
                        <div class="datagrid-content">
                            @if($openHouse->property)
                                <a href="{{ route('properties.show', $openHouse->property) }}">{{ $openHouse->property->address }}</a>
                                <div class="text-muted small">{{ $openHouse->property->city }}, {{ $openHouse->property->state }} {{ $openHouse->property->zip_code }}</div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Date') }}</div>
                        <div class="datagrid-content">{{ $openHouse->event_date->format('l, M j, Y') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Time') }}</div>
                        <div class="datagrid-content">{{ \Carbon\Carbon::parse($openHouse->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($openHouse->end_time)->format('g:i A') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Status') }}</div>
                        <div class="datagrid-content">
                            @php
                                $statusColors = ['scheduled' => 'blue', 'active' => 'cyan', 'completed' => 'green', 'cancelled' => 'secondary'];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$openHouse->status] ?? 'secondary' }}">{{ \App\Models\OpenHouse::statusLabel($openHouse->status) }}</span>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Agent') }}</div>
                        <div class="datagrid-content">{{ $openHouse->agent->name ?? '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Attendees') }}</div>
                        <div class="datagrid-content"><span class="badge bg-purple-lt">{{ $openHouse->attendee_count }}</span></div>
                    </div>
                </div>

                @if($openHouse->description)
                <div class="mt-3">
                    <h4 class="subheader">{{ __('Description') }}</h4>
                    <p>{{ $openHouse->description }}</p>
                </div>
                @endif

                @if($openHouse->notes)
                <div class="mt-3">
                    <h4 class="subheader">{{ __('Notes') }}</h4>
                    <p>{{ $openHouse->notes }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Attendees --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Attendees') }}</h3>
            </div>
            <div class="card-body border-bottom">
                <form id="attendee-form" class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label required">{{ __('First Name') }}</label>
                        <input type="text" name="first_name" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label required">{{ __('Last Name') }}</label>
                        <input type="text" name="last_name" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Email') }}</label>
                        <input type="email" name="email" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Phone') }}</label>
                        <input type="text" name="phone" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-check form-switch mt-4">
                            <input type="checkbox" name="interested" class="form-check-input" value="1">
                            <span class="form-check-label">{{ __('Interested') }}</span>
                        </label>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary" id="add-attendee-btn">{{ __('Add') }}</button>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table" id="attendees-table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Interested') }}</th>
                            <th>{{ __('Linked Lead') }}</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody id="attendees-body">
                        @foreach($openHouse->attendees as $attendee)
                        <tr data-attendee-id="{{ $attendee->id }}">
                            <td>{{ $attendee->first_name }} {{ $attendee->last_name }}</td>
                            <td>{{ $attendee->email ?? '-' }}</td>
                            <td>{{ $attendee->phone ?? '-' }}</td>
                            <td>
                                @if($attendee->interested)
                                    <span class="badge bg-green">{{ __('Yes') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('No') }}</span>
                                @endif
                            </td>
                            <td>
                                @if($attendee->lead)
                                    <a href="{{ route('leads.show', $attendee->lead) }}">{{ $attendee->lead->first_name }} {{ $attendee->lead->last_name }}</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-attendee-btn" data-id="{{ $attendee->id }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body py-2" id="no-attendees-msg" style="{{ $openHouse->attendees->count() ? 'display:none' : '' }}">
                <p class="text-center text-muted mb-0">{{ __('No attendees signed in yet.') }}</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        {{-- Property Quick View --}}
        @if($openHouse->property)
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Property') }}</h3>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong>{{ $openHouse->property->address }}</strong>
                    <div class="text-muted">{{ $openHouse->property->city }}, {{ $openHouse->property->state }} {{ $openHouse->property->zip_code }}</div>
                </div>
                @if($openHouse->property->list_price)
                <div class="mb-2">
                    <span class="text-muted">{{ __('List Price') }}:</span>
                    <strong>{{ Fmt::currency($openHouse->property->list_price) }}</strong>
                </div>
                @endif
                @if($openHouse->property->bedrooms || $openHouse->property->bathrooms)
                <div class="mb-2">
                    @if($openHouse->property->bedrooms)<span>{{ $openHouse->property->bedrooms }} {{ __('beds') }}</span>@endif
                    @if($openHouse->property->bedrooms && $openHouse->property->bathrooms) / @endif
                    @if($openHouse->property->bathrooms)<span>{{ $openHouse->property->bathrooms }} {{ __('baths') }}</span>@endif
                </div>
                @endif
                @if($openHouse->property->square_footage)
                <div class="mb-2">{{ Fmt::area($openHouse->property->square_footage) }}</div>
                @endif
                <a href="{{ route('properties.show', $openHouse->property) }}" class="btn btn-sm btn-outline-primary w-100">{{ __('View Property') }}</a>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('attendee-form');
    var tbody = document.getElementById('attendees-body');
    var noMsg = document.getElementById('no-attendees-msg');
    var addUrl = '{{ url("/open-houses/" . $openHouse->id . "/attendees") }}';
    var leadsShowUrl = '{{ url("/leads") }}';
    var csrfToken = '{{ csrf_token() }}';

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = document.getElementById('add-attendee-btn');
        btn.disabled = true;

        var data = {
            first_name: form.first_name.value,
            last_name: form.last_name.value,
            email: form.email.value,
            phone: form.phone.value,
            interested: form.interested.checked ? 1 : 0
        };

        fetch(addUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success) {
                var a = json.attendee;
                var tr = document.createElement('tr');
                tr.setAttribute('data-attendee-id', a.id);

                var leadHtml = '-';
                if (a.lead_id) {
                    leadHtml = '<a href="' + leadsShowUrl + '/' + a.lead_id + '">' + escapeHtml(a.lead_name || (a.first_name + ' ' + a.last_name)) + '</a>';
                }

                tr.innerHTML =
                    '<td>' + escapeHtml(a.first_name) + ' ' + escapeHtml(a.last_name) + '</td>' +
                    '<td>' + (a.email ? escapeHtml(a.email) : '-') + '</td>' +
                    '<td>' + (a.phone ? escapeHtml(a.phone) : '-') + '</td>' +
                    '<td>' + (a.interested ? '<span class="badge bg-green">{{ __("Yes") }}</span>' : '<span class="badge bg-secondary">{{ __("No") }}</span>') + '</td>' +
                    '<td>' + leadHtml + '</td>' +
                    '<td><button type="button" class="btn btn-sm btn-outline-danger remove-attendee-btn" data-id="' + a.id + '">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>' +
                    '</button></td>';

                tbody.appendChild(tr);
                noMsg.style.display = 'none';

                form.reset();
            }
        })
        .catch(function(err) { console.error(err); })
        .finally(function() { btn.disabled = false; });
    });

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.remove-attendee-btn');
        if (!btn) return;
        if (!confirm('{{ __("Remove this attendee?") }}')) return;

        var attendeeId = btn.getAttribute('data-id');
        var removeUrl = '{{ url("/open-house-attendees") }}/' + attendeeId;

        fetch(removeUrl, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success) {
                var row = btn.closest('tr');
                row.remove();
                if (tbody.children.length === 0) {
                    noMsg.style.display = '';
                }
            }
        })
        .catch(function(err) { console.error(err); });
    });

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
});
</script>
@endpush
@endsection
