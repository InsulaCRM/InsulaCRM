@extends('layouts.app')

@section('title', __('Properties'))
@section('page-title', __('Properties'))

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('All Properties') }}</h3>
    </div>
    <div class="card-body border-bottom py-3">
        <form method="GET" action="{{ route('properties.index') }}" class="row g-2">
            <div class="col-md-3">
                <label for="property-search" class="visually-hidden">{{ __('Search address, city, zip') }}</label>
                <input type="text" id="property-search" name="search" class="form-control" placeholder="{{ __('Search address, city, zip...') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label for="property-type-filter" class="visually-hidden">{{ __('Property type') }}</label>
                <select id="property-type-filter" name="property_type" class="form-select">
                    <option value="">{{ __('All Types') }}</option>
                    @foreach(\App\Services\CustomFieldService::getOptions('property_type') as $val => $label)
                        <option value="{{ $val }}" {{ request('property_type') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="property-condition-filter" class="visually-hidden">{{ __('Property condition') }}</label>
                <select id="property-condition-filter" name="condition" class="form-select">
                    <option value="">{{ __('All Conditions') }}</option>
                    @foreach(\App\Services\CustomFieldService::getOptions('property_condition') as $val => $label)
                        <option value="{{ $val }}" {{ request('condition') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if(($businessMode ?? 'wholesale') === 'wholesale')
            <div class="col-md-3">
                <label for="property-distress-filter" class="visually-hidden">{{ __('Distress markers') }}</label>
                <select id="property-distress-filter" name="distress[]" class="form-select" multiple size="1" title="{{ __('Distress Markers') }}">
                    @foreach(\App\Services\CustomFieldService::getOptions('distress_markers') as $val => $label)
                        <option value="{{ $val }}" {{ is_array(request('distress')) && in_array($val, request('distress')) ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <small class="text-secondary">{{ __('Hold Ctrl to select multiple') }}</small>
            </div>
            @else
            <div class="col-md-2">
                <label for="property-listing-status-filter" class="visually-hidden">{{ __('Listing Status') }}</label>
                <select id="property-listing-status-filter" name="listing_status" class="form-select">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach(['active' => __('Active'), 'pending' => __('Pending'), 'sold' => __('Sold'), 'withdrawn' => __('Withdrawn'), 'expired' => __('Expired')] as $val => $label)
                        <option value="{{ $val }}" {{ request('listing_status') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">{{ __('Filter') }}</button>
            </div>
            @if(request()->hasAny(['search', 'property_type', 'condition', 'distress', 'listing_status']))
            <div class="col-auto">
                <a href="{{ route('properties.index') }}" class="btn btn-outline-secondary">{{ __('Clear') }}</a>
            </div>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('Address') }}</th>
                    <th>{{ __('Lead') }}</th>
                    <th>{{ __('Type') }}</th>
                    @if(($businessMode ?? 'wholesale') === 'wholesale')
                    <th>{{ __('Est. Value') }}</th>
                    <th>{{ __('Repair Est.') }}</th>
                    <th>{{ __('ARV') }}</th>
                    <th>{{ __('Our Offer') }}</th>
                    <th>{{ __('Assignment Fee') }}</th>
                    <th>{{ __('Distress Markers') }}</th>
                    @else
                    <th>{{ __('List Price') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Beds') }}</th>
                    <th>{{ __('Baths') }}</th>
                    <th>{{ __('Sq Ft') }}</th>
                    <th>{{ __('Year Built') }}</th>
                    @endif
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($properties as $property)
                <tr>
                    <td>{{ $property->full_address }}</td>
                    <td>
                        <a href="{{ route('leads.show', $property->lead_id) }}">{{ $property->lead->full_name ?? '-' }}</a>
                    </td>
                    <td>{{ __(ucwords(str_replace('_', ' ', $property->property_type))) }}</td>
                    @if(($businessMode ?? 'wholesale') === 'wholesale')
                    <td>{{ Fmt::currency($property->estimated_value) }}</td>
                    <td>{{ Fmt::currency($property->repair_estimate) }}</td>
                    <td>{{ Fmt::currency($property->after_repair_value) }}</td>
                    <td>{{ Fmt::currency($property->our_offer) }}</td>
                    <td>
                        @if($property->assignment_fee !== null)
                            <span class="{{ $property->assignment_fee >= 0 ? 'text-green' : 'text-red' }}">
                                {{ Fmt::currency($property->assignment_fee) }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($property->distress_markers && count($property->distress_markers))
                            @foreach($property->distress_markers as $marker)
                                <span class="badge bg-red-lt me-1 mb-1">{{ __(ucwords(str_replace('_', ' ', $marker))) }}</span>
                            @endforeach
                        @else
                            <span class="text-secondary">-</span>
                        @endif
                    </td>
                    @else
                    <td>{{ Fmt::currency($property->list_price) }}</td>
                    <td>
                        @if($property->listing_status)
                            @php
                                $statusColors = ['active' => 'bg-green-lt', 'pending' => 'bg-yellow-lt', 'sold' => 'bg-blue-lt', 'withdrawn' => 'bg-secondary-lt', 'expired' => 'bg-red-lt'];
                            @endphp
                            <span class="badge {{ $statusColors[$property->listing_status] ?? 'bg-secondary-lt' }}">{{ __(ucfirst($property->listing_status)) }}</span>
                        @else
                            <span class="text-secondary">-</span>
                        @endif
                    </td>
                    <td>{{ $property->bedrooms ?? '-' }}</td>
                    <td>{{ $property->bathrooms ?? '-' }}</td>
                    <td>{{ $property->square_footage ? number_format($property->square_footage) : '-' }}</td>
                    <td>{{ $property->year_built ?? '-' }}</td>
                    @endif
                    <td>
                        <a href="{{ route('properties.show', $property) }}" class="btn btn-ghost-secondary btn-sm">{{ __('View') }}</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-secondary py-4">
                        <p class="mb-1">{{ __('No properties found.') }}</p>
                        <small>{{ __('Try adjusting your filters or check back later as new properties are added.') }}</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex align-items-center">
        <p class="m-0 text-secondary">{{ __('Showing') }} <span>{{ $properties->firstItem() ?? 0 }}</span> {{ __('to') }} <span>{{ $properties->lastItem() ?? 0 }}</span> {{ __('of') }} <span>{{ $properties->total() }}</span> {{ __('entries') }}</p>
        <div class="ms-auto">
            {{ $properties->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
