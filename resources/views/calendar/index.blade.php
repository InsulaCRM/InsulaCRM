@extends('layouts.app')

@section('title', __('Calendar'))
@section('page-title', __('Calendar'))

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">{{ __('Calendar') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between w-100">
            <button class="btn btn-ghost-secondary btn-icon" id="cal-prev">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z"/><polyline points="15 6 9 12 15 18"/></svg>
            </button>
            <h3 class="card-title mb-0" id="cal-title"></h3>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" id="cal-today">{{ __('Today') }}</button>
                <a href="{{ route('calendar.sync') }}" class="btn btn-outline-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                    {{ __('Sync') }}
                </a>
                <button class="btn btn-ghost-secondary btn-icon" id="cal-next">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z"/><polyline points="9 6 15 12 9 18"/></svg>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="d-flex flex-wrap mb-2 px-3 pt-2 gap-3">
            <span class="d-flex align-items-center gap-1"><span class="cal-dot" style="background:#206bc4;"></span> <small class="text-muted">{{ __('Task') }}</small></span>
            <span class="d-flex align-items-center gap-1"><span class="cal-dot" style="background:#2fb344;"></span> <small class="text-muted">{{ __('Completed') }}</small></span>
            <span class="d-flex align-items-center gap-1"><span class="cal-dot" style="background:#d63939;"></span> <small class="text-muted">{{ __('Overdue') }}</small></span>
            <span class="d-flex align-items-center gap-1"><span class="cal-dot" style="background:#ae3ec9;"></span> <small class="text-muted">{{ __('Meeting') }}</small></span>
            <span class="d-flex align-items-center gap-1"><span class="cal-dot" style="background:#0ca678;"></span> <small class="text-muted">{{ __('Call') }}</small></span>
            @if(($businessMode ?? 'wholesale') === 'realestate')
            <span class="d-flex align-items-center gap-1"><span class="cal-dot" style="background:#f76707;"></span> <small class="text-muted">{{ __('Showing') }}</small></span>
            <span class="d-flex align-items-center gap-1"><span class="cal-dot" style="background:#0ca678;"></span> <small class="text-muted">{{ __('Open House') }}</small></span>
            @endif
        </div>
        <table class="table table-bordered mb-0" id="cal-table">
            <thead>
                <tr>
                    <th class="text-center py-2" style="width:14.28%">{{ __('Sun') }}</th>
                    <th class="text-center py-2" style="width:14.28%">{{ __('Mon') }}</th>
                    <th class="text-center py-2" style="width:14.28%">{{ __('Tue') }}</th>
                    <th class="text-center py-2" style="width:14.28%">{{ __('Wed') }}</th>
                    <th class="text-center py-2" style="width:14.28%">{{ __('Thu') }}</th>
                    <th class="text-center py-2" style="width:14.28%">{{ __('Fri') }}</th>
                    <th class="text-center py-2" style="width:14.28%">{{ __('Sat') }}</th>
                </tr>
            </thead>
            <tbody id="cal-body"></tbody>
        </table>
    </div>
</div>

@push('styles')
<style>
    .cal-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; }
    .cal-event {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 2px 6px;
        margin-bottom: 2px;
        border-radius: 4px;
        font-size: 12px;
        line-height: 1.4;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transition: background 0.15s;
    }
    .cal-event:hover { filter: brightness(0.92); }
    .cal-event-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    .cal-event-blue { background: #e7f1fd; color: #1a56a0; }
    .cal-event-blue .cal-event-dot { background: #206bc4; }
    .cal-event-green { background: #e6f7ed; color: #1a7a32; }
    .cal-event-green .cal-event-dot { background: #2fb344; }
    .cal-event-red { background: #fce8e8; color: #b42525; }
    .cal-event-red .cal-event-dot { background: #d63939; }
    .cal-event-purple { background: #f3e8f9; color: #7c2d9e; }
    .cal-event-purple .cal-event-dot { background: #ae3ec9; }
    .cal-event-cyan { background: #e5f6f1; color: #0a7d5d; }
    .cal-event-cyan .cal-event-dot { background: #0ca678; }
    .cal-event-orange { background: #fff4e6; color: #d9480f; }
    .cal-event-orange .cal-event-dot { background: #f76707; }
    .cal-event-teal { background: #e3fafc; color: #0b7285; }
    .cal-event-teal .cal-event-dot { background: #3bc9db; }
    .cal-event-line-through { text-decoration: line-through; opacity: 0.7; }
    #cal-table td { padding: 4px 6px; }
    #cal-table .cal-day-num { font-size: 13px; font-weight: 600; margin-bottom: 2px; }
    #cal-table .cal-day-today { color: #206bc4; }
    #cal-table .cal-day-muted { color: #a0a5aa; }
    .cal-more { font-size: 11px; color: #666; cursor: pointer; padding-left: 6px; }
    .cal-more:hover { color: #206bc4; }
    .cal-day-focused { outline: 2px solid var(--tblr-primary, #206bc4); outline-offset: -2px; }
</style>
@endpush

@push('scripts')
<script>
(function() {
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    let currentDate = new Date();
    let events = [];
    const MAX_VISIBLE = 3;

    function render() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        document.getElementById('cal-title').textContent = months[month] + ' ' + year;

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today = new Date();
        const todayStr = today.getFullYear() + '-' + String(today.getMonth()+1).padStart(2,'0') + '-' + String(today.getDate()).padStart(2,'0');

        let html = '';
        let day = 1;
        for (let row = 0; row < 6; row++) {
            if (day > daysInMonth) break;
            html += '<tr>';
            for (let col = 0; col < 7; col++) {
                if (row === 0 && col < firstDay || day > daysInMonth) {
                    html += '<td class="bg-light" style="height:110px;vertical-align:top;"></td>';
                } else {
                    const dateStr = year + '-' + String(month+1).padStart(2,'0') + '-' + String(day).padStart(2,'0');
                    const isToday = dateStr === todayStr;
                    const dayEvents = events.filter(e => e.date === dateStr);
                    html += '<td style="height:110px;vertical-align:top;" class="' + (isToday ? 'bg-azure-lt' : '') + '">';
                    html += '<div class="cal-day-num ' + (isToday ? 'cal-day-today' : 'cal-day-muted') + '">' + day + '</div>';

                    const visible = dayEvents.slice(0, MAX_VISIBLE);
                    const extra = dayEvents.length - MAX_VISIBLE;
                    visible.forEach(function(ev) {
                        const link = ev.url ? ' onclick="window.location=\'' + ev.url + '\'"' : '';
                        const cursor = ev.url ? 'cursor:pointer;' : '';
                        const strikethrough = ev.completed ? ' cal-event-line-through' : '';
                        html += '<div class="cal-event cal-event-' + ev.color + strikethrough + '" style="' + cursor + '"' + link + ' title="' + escapeAttr(ev.title) + '">';
                        html += '<span class="cal-event-dot"></span>';
                        html += '<span style="overflow:hidden;text-overflow:ellipsis;">' + escapeHtml(ev.title) + '</span>';
                        html += '</div>';
                    });
                    if (extra > 0) {
                        html += '<div class="cal-more" title="' + escapeAttr(dayEvents.slice(MAX_VISIBLE).map(e => e.title).join(', ')) + '">+' + extra + ' {{ __("more") }}</div>';
                    }

                    html += '</td>';
                    day++;
                }
            }
            html += '</tr>';
        }
        document.getElementById('cal-body').innerHTML = html;
    }

    function loadEvents() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const start = year + '-' + String(month+1).padStart(2,'0') + '-01';
        const end = year + '-' + String(month+1).padStart(2,'0') + '-' + new Date(year, month+1, 0).getDate();

        fetch('{{ route('calendar.events') }}?start=' + start + '&end=' + end, {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => { events = data; render(); });
    }

    document.getElementById('cal-prev').addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        loadEvents();
    });
    document.getElementById('cal-next').addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        loadEvents();
    });
    document.getElementById('cal-today').addEventListener('click', function() {
        currentDate = new Date();
        loadEvents();
    });

    function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function escapeAttr(s) { return s.replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }

    loadEvents();

    // Calendar keyboard navigation
    var calTable = document.querySelector('#cal-table tbody');
    if (calTable) {
        calTable.setAttribute('tabindex', '0');
        calTable.setAttribute('role', 'grid');
        calTable.setAttribute('aria-label', '{{ __("Calendar grid") }}');

        calTable.addEventListener('keydown', function(e) {
            var focusedCell = document.querySelector('.cal-day-focused');
            if (!focusedCell) {
                // Focus the first day cell with content
                focusedCell = calTable.querySelector('td[style*="vertical-align"]:not(.bg-light)');
                if (focusedCell) focusedCell.classList.add('cal-day-focused');
                return;
            }

            var allCells = Array.from(calTable.querySelectorAll('td[style*="vertical-align"]:not(.bg-light)'));
            var idx = allCells.indexOf(focusedCell);
            var newIdx = idx;

            if (e.key === 'ArrowRight') { newIdx = Math.min(idx + 1, allCells.length - 1); e.preventDefault(); }
            else if (e.key === 'ArrowLeft') { newIdx = Math.max(idx - 1, 0); e.preventDefault(); }
            else if (e.key === 'ArrowDown') { newIdx = Math.min(idx + 7, allCells.length - 1); e.preventDefault(); }
            else if (e.key === 'ArrowUp') { newIdx = Math.max(idx - 7, 0); e.preventDefault(); }
            else if (e.key === 'Enter') {
                var firstLink = focusedCell.querySelector('a, .cal-event[onclick]');
                if (firstLink) firstLink.click();
                e.preventDefault();
            }

            if (newIdx !== idx) {
                focusedCell.classList.remove('cal-day-focused');
                allCells[newIdx].classList.add('cal-day-focused');
                allCells[newIdx].scrollIntoView({ block: 'nearest' });
            }
        });
    }
})();
</script>
@endpush
@endsection
