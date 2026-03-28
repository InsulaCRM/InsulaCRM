<?php

namespace App\Http\Controllers;

use App\Events\ActivityLogged;
use App\Facades\Hooks;
use App\Http\Requests\ActivityRequest;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Deal;
use App\Models\Lead;
use App\Services\CustomFieldService;
use App\Services\DncService;
use App\Services\MotivationScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ActivityController extends Controller
{
    /**
     * Store a new activity log entry for a lead.
     */
    public function store(ActivityRequest $request, Lead $lead)
    {
        $this->authorizeLead($lead);

        // Check DNC/timezone restrictions for outreach activity types
        if (in_array($request->type, CustomFieldService::$outreachActivityTypes)) {
            $dncService = app(DncService::class);
            $check = $dncService->canContact($lead);
            if (!$check['allowed']) {
                return redirect()->route('leads.show', $lead)->with('error', $check['reason']);
            }
        }

        $activity = Activity::create([
            'tenant_id' => auth()->user()->tenant_id,
            'lead_id' => $lead->id,
            'agent_id' => auth()->id(),
            'type' => $request->type,
            'subject' => $request->subject,
            'body' => $request->body,
            'logged_at' => now(),
        ]);

        app(MotivationScoreService::class)->recalculate($lead);
        event(new ActivityLogged($activity));
        Hooks::doAction('activity.logged', $activity);

        return redirect()->route('leads.show', $lead)->with('success', 'Activity logged successfully.');
    }

    /**
     * Send an email to a lead and log it as an activity.
     */
    public function sendEmail(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);

        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:65535',
        ]);

        if (empty($lead->email)) {
            return redirect()->route('leads.show', $lead)
                ->with('error', __('This lead does not have an email address.'));
        }

        // Check DNC restrictions
        $dncService = app(DncService::class);
        $check = $dncService->canContact($lead);
        if (!$check['allowed']) {
            return redirect()->route('leads.show', $lead)->with('error', $check['reason']);
        }

        $tenant = auth()->user()->tenant;
        $subject = $this->replaceMergeTags($request->subject, $lead, $tenant);
        $body = $this->replaceMergeTags($request->body, $lead, $tenant);

        try {
            // Mail transport is already configured by TenantMiddleware.
            // Here we only handle per-agent identity (From name + Reply-To).
            $agent = auth()->user();
            $fromName = $agent->email_mode === 'personal'
                ? ($agent->email_from_name ?: $agent->name)
                : config('mail.from.name');
            $replyTo = $agent->email_mode === 'personal'
                ? ($agent->email_reply_to ?: $agent->email)
                : null;

            Mail::html(nl2br(e($body)), function ($message) use ($lead, $subject, $fromName, $replyTo) {
                $message->to($lead->email, $lead->full_name)
                    ->subject($subject);

                if ($fromName) {
                    $message->from(config('mail.from.address'), $fromName);
                }

                if ($replyTo) {
                    $message->replyTo($replyTo);
                }
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Email send failed', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
            return redirect()->route('leads.show', $lead)
                ->with('error', __('Failed to send email. Please check your SMTP settings in Settings > Email.'));
        }

        // Log as activity
        $activity = Activity::create([
            'tenant_id' => auth()->user()->tenant_id,
            'lead_id' => $lead->id,
            'agent_id' => auth()->id(),
            'type' => 'email',
            'subject' => $subject,
            'body' => $body,
            'logged_at' => now(),
        ]);

        app(MotivationScoreService::class)->recalculate($lead);
        event(new ActivityLogged($activity));
        Hooks::doAction('activity.logged', $activity);

        return redirect()->route('leads.show', $lead)
            ->with('success', __('Email sent to :email.', ['email' => $lead->email]));
    }

    /**
     * Replace merge tags in email content.
     */
    private function replaceMergeTags(string $content, Lead $lead, $tenant): string
    {
        $property = $lead->property;

        return str_replace([
            '{first_name}',
            '{last_name}',
            '{full_name}',
            '{email}',
            '{phone}',
            '{address}',
            '{company_name}',
        ], [
            $lead->first_name ?? '',
            $lead->last_name ?? '',
            $lead->full_name ?? '',
            $lead->email ?? '',
            $lead->phone ?? '',
            $property->address ?? '',
            $tenant->name ?? '',
        ], $content);
    }

    /**
     * Update an activity log entry via AJAX.
     */
    public function update(Request $request, Activity $activity)
    {
        $this->authorizeActivity($activity);

        $request->validate([
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
        ]);

        $activity->update([
            'subject' => $request->subject,
            'body' => $request->body,
        ]);

        AuditLog::log('activity.updated', $activity);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('leads.show', $activity->lead_id)->with('success', 'Activity updated.');
    }

    /**
     * Delete an activity log entry via AJAX.
     */
    public function destroy(Activity $activity)
    {
        $this->authorizeActivity($activity);

        $leadId = $activity->lead_id;
        $activity->delete();

        AuditLog::log('activity.deleted', $activity);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('leads.show', $leadId)->with('success', 'Activity deleted.');
    }

    /**
     * Store a new activity log entry for a deal.
     */
    public function storeDealActivity(Request $request, Deal $deal)
    {
        $this->authorizeDeal($deal);

        $request->validate([
            'type' => 'required|string|max:50',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
        ]);

        $activity = Activity::create([
            'tenant_id' => auth()->user()->tenant_id,
            'deal_id' => $deal->id,
            'agent_id' => auth()->id(),
            'type' => $request->type,
            'subject' => $request->subject,
            'body' => $request->body,
            'logged_at' => now(),
        ]);

        event(new ActivityLogged($activity));
        Hooks::doAction('activity.logged', $activity);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'id' => $activity->id]);
        }

        return redirect()->route('deals.show', $deal)->with('success', __('Activity logged successfully.'));
    }

    private function authorizeDeal(Deal $deal): void
    {
        $this->authorize('update', $deal);
    }

    private function authorizeLead(Lead $lead): void
    {
        $this->authorize('update', $lead);
    }

    private function authorizeActivity(Activity $activity): void
    {
        $user = auth()->user();
        // Agents can only edit/delete their own activities; admins can edit/delete any
        if ($user->isAgent() && $activity->agent_id !== $user->id) {
            abort(403);
        }
    }

}
