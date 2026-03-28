<?php

namespace App\Http\Controllers;

use App\Http\Requests\SequenceRequest;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\Sequence;
use App\Models\SequenceEnrollment;
use App\Models\SequenceStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SequenceController extends Controller
{
    /**
     * List all sequences with name, step count, enrollment count, and active status.
     */
    public function index()
    {
        $sequences = Sequence::withCount(['steps', 'enrollments'])->latest()->get();

        return view('sequences.index', compact('sequences'));
    }

    /**
     * Show the form for creating a new sequence.
     */
    public function create()
    {
        return view('sequences.create');
    }

    /**
     * Store a new sequence with steps.
     */
    public function store(SequenceRequest $request)
    {
        $data = $request->validated();

        $sequence = Sequence::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        foreach ($data['steps'] as $step) {
            SequenceStep::create([
                'sequence_id' => $sequence->id,
                'order' => $step['order'],
                'delay_days' => $step['delay_days'],
                'action_type' => $step['action_type'],
                'message_template' => $step['message_template'] ?? null,
            ]);
        }

        AuditLog::log('sequence.created', $sequence);

        return redirect()->route('sequences.show', $sequence)->with('success', 'Sequence created successfully.');
    }

    /**
     * Show a sequence with its steps and enrolled leads.
     */
    public function show(Sequence $sequence)
    {
        $sequence->load(['steps', 'enrollments.lead']);

        $enrolledLeadIds = $sequence->enrollments->pluck('lead_id')->toArray();
        $leads = Lead::whereNotIn('id', $enrolledLeadIds)->get();

        return view('sequences.show', compact('sequence', 'leads'));
    }

    /**
     * Show the form for editing a sequence.
     */
    public function edit(Sequence $sequence)
    {
        $sequence->load('steps');

        return view('sequences.edit', compact('sequence'));
    }

    /**
     * Update a sequence and its steps (delete old steps, create new ones).
     */
    public function update(SequenceRequest $request, Sequence $sequence)
    {
        $data = $request->validated();

        $sequence->update([
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? $sequence->is_active,
        ]);

        // Delete old steps and create new ones
        $sequence->steps()->delete();

        foreach ($data['steps'] as $step) {
            SequenceStep::create([
                'sequence_id' => $sequence->id,
                'order' => $step['order'],
                'delay_days' => $step['delay_days'],
                'action_type' => $step['action_type'],
                'message_template' => $step['message_template'] ?? null,
            ]);
        }

        AuditLog::log('sequence.updated', $sequence);

        return redirect()->route('sequences.show', $sequence)->with('success', 'Sequence updated successfully.');
    }

    /**
     * Delete a sequence.
     */
    public function destroy(Sequence $sequence)
    {
        AuditLog::log('sequence.deleted', $sequence);

        $sequence->steps()->delete();
        $sequence->enrollments()->delete();
        $sequence->delete();

        return redirect()->route('sequences.index')->with('success', 'Sequence deleted successfully.');
    }

    /**
     * Enroll a lead in the sequence.
     */
    public function enroll(Request $request, Sequence $sequence)
    {
        $data = $request->validate([
            'lead_id' => 'required|exists:leads,id',
        ]);

        $enrollment = DB::transaction(function () use ($data, $sequence) {
            // Lock to prevent duplicate enrollment race condition
            $existing = SequenceEnrollment::where('sequence_id', $sequence->id)
                ->where('lead_id', $data['lead_id'])
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return null;
            }

            return SequenceEnrollment::create([
                'tenant_id' => auth()->user()->tenant_id,
                'sequence_id' => $sequence->id,
                'lead_id' => $data['lead_id'],
                'current_step' => 1,
                'last_step_at' => now(),
                'status' => 'active',
            ]);
        });

        if (!$enrollment) {
            return redirect()->back()->with('error', 'Lead is already enrolled in this sequence.');
        }

        AuditLog::log('sequence.enrolled', $enrollment);

        return redirect()->back()->with('success', 'Lead enrolled in sequence successfully.');
    }

    /**
     * Remove a lead enrollment from the sequence.
     */
    public function unenroll(Sequence $sequence, Lead $lead)
    {
        AuditLog::log('sequence.unenrolled', $sequence);

        SequenceEnrollment::where('sequence_id', $sequence->id)
            ->where('lead_id', $lead->id)
            ->delete();

        return redirect()->back()->with('success', 'Lead unenrolled from sequence successfully.');
    }
}
