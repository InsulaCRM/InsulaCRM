@props(['recentLeads'])

<div class="card">
    <div class="card-header border-0">
        <h3 class="card-title">{{ __('Recent Leads') }}</h3>
        <div class="card-actions">
            <a href="{{ route('leads.index') }}" class="btn btn-link btn-sm">{{ __('View All') }}</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <tbody>
                @forelse($recentLeads as $lead)
                <tr>
                    <td>
                        <a href="{{ route('leads.show', $lead) }}" class="text-reset">
                            <div class="font-weight-medium">{{ $lead->full_name }}</div>
                            <div class="text-secondary small">{{ __(ucwords(str_replace('_', ' ', $lead->lead_source))) }}</div>
                        </a>
                    </td>
                    <td class="text-end">
                        @php $tempColors = ['hot' => 'bg-red-lt', 'warm' => 'bg-yellow-lt', 'cold' => 'bg-azure-lt']; @endphp
                        <span class="badge {{ $tempColors[$lead->temperature] ?? 'bg-secondary-lt' }}">@if($lead->temperature === 'hot')<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12c2-2.96 0-7-1-8 0 3.038-1.773 4.741-3 6-1.226 1.26-2 3.24-2 5a6 6 0 1 0 12 0c0-1.532-1.056-3.94-2-5-1.786 3-2.791 3-4 2z"/></svg>@elseif($lead->temperature === 'warm')<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="4"/><path d="M3 12h1m8-9v1m8 8h1m-9 8v1m-6.4-15.4l.7.7m12.1-.7l-.7.7m0 11.4l.7.7m-12.1-.7l-.7.7"/></svg>@elseif($lead->temperature === 'cold')<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 4l2 1l2-1"/><path d="M12 2v6.5l3 1.72"/><path d="M17.928 6.268l.134 2.232l1.866 1.232"/><path d="M20.66 7l-5.629 3.25l.01 3.458"/><path d="M19.928 14.268l-1.866 1.232l-.134 2.232"/><path d="M20.66 17l-5.629-3.25l-2.99 1.738"/><path d="M14 20l-2-1l-2 1"/><path d="M12 22v-6.5l-3-1.72"/><path d="M6.072 17.732l-.134-2.232l-1.866-1.232"/><path d="M3.34 17l5.629-3.25l-.01-3.458"/><path d="M4.072 9.732l1.866-1.232l.134-2.232"/><path d="M3.34 7l5.629 3.25l2.99-1.738"/></svg>@endif {{ __(ucfirst($lead->temperature)) }}</span>
                    </td>
                </tr>
                @empty
                <tr><td class="text-secondary text-center" colspan="2">{{ __('No leads yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
