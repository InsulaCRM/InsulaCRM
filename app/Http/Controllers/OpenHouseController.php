<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\OpenHouse;
use App\Models\OpenHouseAttendee;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OpenHouseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', OpenHouse::class);

        $query = OpenHouse::with(['property', 'agent']);

        if (!auth()->user()->isAdmin()) {
            $query->where('agent_id', auth()->id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('agent')) {
            $query->where('agent_id', $request->agent);
        }

        if ($request->filled('from')) {
            $query->where('event_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('event_date', '<=', $request->to);
        }

        $openHouses = $query->orderBy('event_date', 'desc')->orderBy('start_time', 'desc')->paginate(25);

        $agents = collect();
        if (auth()->user()->isAdmin()) {
            $agents = \App\Models\User::where('tenant_id', auth()->user()->tenant_id)
                ->whereHas('role', fn ($q) => $q->whereIn('name', ['admin', 'agent', 'listing_agent', 'buyers_agent']))
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('open-houses.index', compact('openHouses', 'agents'));
    }

    public function create()
    {
        $this->authorize('create', OpenHouse::class);

        $properties = Property::orderBy('address')->get(['id', 'address', 'city', 'state']);
        $agents = \App\Models\User::where('tenant_id', auth()->user()->tenant_id)
            ->whereHas('role', fn ($q) => $q->whereIn('name', ['admin', 'agent', 'listing_agent', 'buyers_agent']))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('open-houses.create', compact('properties', 'agents'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', OpenHouse::class);

        $data = $request->validate([
            'property_id' => ['required', Rule::exists('properties', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'agent_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'event_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'status' => ['nullable', Rule::in(array_keys(OpenHouse::STATUSES))],
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['agent_id'] = $data['agent_id'] ?? auth()->id();

        $openHouse = OpenHouse::create($data);

        return redirect()->route('open-houses.show', $openHouse)->with('success', __('Open house scheduled successfully.'));
    }

    public function show(OpenHouse $openHouse)
    {
        $this->authorize('view', $openHouse);
        $openHouse->load(['property', 'agent', 'attendees.lead']);

        return view('open-houses.show', compact('openHouse'));
    }

    public function edit(OpenHouse $openHouse)
    {
        $this->authorize('update', $openHouse);

        $properties = Property::orderBy('address')->get(['id', 'address', 'city', 'state']);
        $agents = \App\Models\User::where('tenant_id', auth()->user()->tenant_id)
            ->whereHas('role', fn ($q) => $q->whereIn('name', ['admin', 'agent', 'listing_agent', 'buyers_agent']))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('open-houses.edit', compact('openHouse', 'properties', 'agents'));
    }

    public function update(Request $request, OpenHouse $openHouse)
    {
        $this->authorize('update', $openHouse);

        $data = $request->validate([
            'property_id' => ['required', Rule::exists('properties', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'agent_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'event_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'status' => ['nullable', Rule::in(array_keys(OpenHouse::STATUSES))],
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $openHouse->update($data);

        return redirect()->route('open-houses.show', $openHouse)->with('success', __('Open house updated successfully.'));
    }

    public function destroy(OpenHouse $openHouse)
    {
        $this->authorize('delete', $openHouse);

        $openHouse->delete();

        return redirect()->route('open-houses.index')->with('success', __('Open house deleted successfully.'));
    }

    public function addAttendee(Request $request, OpenHouse $openHouse)
    {
        $this->authorize('update', $openHouse);

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'interested' => 'nullable|boolean',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $data['tenant_id'] = $tenantId;
        $data['open_house_id'] = $openHouse->id;
        $data['interested'] = $request->boolean('interested');

        // Try to match existing lead by email or phone
        $lead = null;
        if (!empty($data['email'])) {
            $lead = Lead::where('tenant_id', $tenantId)->where('email', $data['email'])->first();
        }
        if (!$lead && !empty($data['phone'])) {
            $lead = Lead::where('tenant_id', $tenantId)->where('phone', $data['phone'])->first();
        }

        // Auto-create lead if no match found
        if (!$lead) {
            $lead = Lead::create([
                'tenant_id' => $tenantId,
                'agent_id' => $openHouse->agent_id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'lead_source' => 'open_house',
                'status' => 'inquiry',
                'temperature' => 'warm',
            ]);
        }

        $data['lead_id'] = $lead->id;

        $attendee = OpenHouseAttendee::create($data);

        $openHouse->increment('attendee_count');

        $attendee->load('lead');

        return response()->json([
            'success' => true,
            'attendee' => [
                'id' => $attendee->id,
                'first_name' => $attendee->first_name,
                'last_name' => $attendee->last_name,
                'email' => $attendee->email,
                'phone' => $attendee->phone,
                'interested' => $attendee->interested,
                'lead_id' => $attendee->lead_id,
                'lead_name' => $attendee->lead ? $attendee->lead->first_name . ' ' . $attendee->lead->last_name : null,
            ],
        ]);
    }

    public function removeAttendee(OpenHouseAttendee $attendee)
    {
        $openHouse = $attendee->openHouse;
        $this->authorize('update', $openHouse);

        $attendee->delete();

        $openHouse->decrement('attendee_count');

        return response()->json(['success' => true]);
    }
}
