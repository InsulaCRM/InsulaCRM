<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GdprController extends Controller
{
    /**
     * Export all data associated with a user as a JSON download.
     * Admin-only action for GDPR data subject access requests.
     */
    public function exportData(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $tenantId = auth()->user()->tenant_id;

        $user = User::withoutGlobalScopes()
            ->where('id', $request->user_id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $user) {
            return back()->with('error', __('User not found within your tenant.'));
        }

        // Gather user profile
        $profile = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];

        // Leads created by or assigned to this user
        $leads = Lead::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('agent_id', $user->id)
            ->get()
            ->toArray();

        // Activities logged by this user
        $activities = Activity::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('agent_id', $user->id)
            ->get()
            ->toArray();

        // Tasks created/assigned to this user
        $tasks = Task::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('agent_id', $user->id)
            ->get()
            ->toArray();

        // Audit log entries for this user
        $auditLogs = DB::table('audit_log')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->get()
            ->toArray();

        $export = [
            'export_date' => now()->toIso8601String(),
            'tenant_id' => $tenantId,
            'user_profile' => $profile,
            'leads' => $leads,
            'activities' => $activities,
            'tasks' => $tasks,
            'audit_log' => $auditLogs,
        ];

        AuditLog::log('gdpr.user_data_exported', $user, null, [
            'exported_user_id' => $user->id,
        ]);

        $filename = 'gdpr-export-' . $user->id . '-' . now()->format('Y-m-d') . '.json';

        return response()->streamDownload(function () use ($export) {
            echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Anonymize and deactivate a user account.
     * Admin-only action for GDPR right-to-erasure requests.
     */
    public function deleteData(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'confirm' => 'required|accepted',
        ]);

        $tenantId = auth()->user()->tenant_id;

        $user = User::withoutGlobalScopes()
            ->where('id', $request->user_id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $user) {
            return back()->with('error', __('User not found within your tenant.'));
        }

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', __('You cannot anonymize your own account.'));
        }

        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
        ];

        // Anonymize personal data
        $anonymizedName = 'Deleted User #' . $user->id;
        $anonymizedEmail = 'deleted-user-' . $user->id . '@anonymized.local';

        $user->update([
            'name' => $anonymizedName,
            'email' => $anonymizedEmail,
            'password' => Hash::make(Str::random(64)), // Invalidate password with unmatchable hash
            'is_active' => false,
            'remember_token' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled' => false,
        ]);

        AuditLog::log('gdpr.user_data_deleted', $user, $oldValues, [
            'anonymized_user_id' => $user->id,
            'anonymized_name' => $anonymizedName,
        ]);

        return back()->with('success', __('User data has been anonymized and account deactivated.'));
    }

    /**
     * Export all data for a specific lead/contact as a JSON download.
     * Admin-only action for GDPR data subject access requests on contacts.
     */
    public function exportContactData(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer',
        ]);

        $tenantId = auth()->user()->tenant_id;

        $lead = Lead::withoutGlobalScopes()
            ->where('id', $request->lead_id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $lead) {
            return back()->with('error', __('Lead not found within your tenant.'));
        }

        // Lead profile data
        $leadData = $lead->toArray();

        // Property data
        $property = DB::table('properties')
            ->where('tenant_id', $tenantId)
            ->where('lead_id', $lead->id)
            ->first();

        // Activities for this lead
        $activities = Activity::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('lead_id', $lead->id)
            ->get()
            ->toArray();

        // Tasks for this lead
        $tasks = Task::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('lead_id', $lead->id)
            ->get()
            ->toArray();

        // Notes are stored in the lead's notes field, but also check activities with type 'note'
        $notes = Activity::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('lead_id', $lead->id)
            ->where('type', 'note')
            ->get()
            ->toArray();

        // Audit log entries referencing this lead
        $auditLogs = DB::table('audit_log')
            ->where('tenant_id', $tenantId)
            ->where('model_type', Lead::class)
            ->where('model_id', $lead->id)
            ->get()
            ->toArray();

        $export = [
            'export_date' => now()->toIso8601String(),
            'tenant_id' => $tenantId,
            'lead' => $leadData,
            'property' => $property,
            'activities' => $activities,
            'tasks' => $tasks,
            'notes' => $notes,
            'audit_log' => $auditLogs,
        ];

        AuditLog::log('gdpr.contact_data_exported', $lead, null, [
            'exported_lead_id' => $lead->id,
        ]);

        $filename = 'gdpr-contact-export-' . $lead->id . '-' . now()->format('Y-m-d') . '.json';

        return response()->streamDownload(function () use ($export) {
            echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Anonymize a lead/contact record, removing PII but keeping the record for stats.
     * Admin-only action for GDPR right-to-erasure on contacts.
     */
    public function deleteContactData(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer',
            'confirm' => 'required|accepted',
        ]);

        $tenantId = auth()->user()->tenant_id;

        $lead = Lead::withoutGlobalScopes()
            ->where('id', $request->lead_id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $lead) {
            return back()->with('error', __('Lead not found within your tenant.'));
        }

        $oldValues = [
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'phone' => $lead->phone,
            'email' => $lead->email,
        ];

        // Anonymize PII fields on the lead
        $lead->update([
            'first_name' => 'Anonymized',
            'last_name' => 'Contact #' . $lead->id,
            'phone' => null,
            'email' => null,
            'notes' => null,
        ]);

        // Anonymize property address if present
        DB::table('properties')
            ->where('tenant_id', $tenantId)
            ->where('lead_id', $lead->id)
            ->update([
                'address' => 'Anonymized',
                'city' => 'Anonymized',
                'state' => '',
                'zip_code' => '',
                'notes' => null,
                'updated_at' => now(),
            ]);

        AuditLog::log('gdpr.contact_data_deleted', $lead, $oldValues, [
            'anonymized_lead_id' => $lead->id,
        ]);

        return back()->with('success', __('Contact data has been anonymized. The record is kept for statistical purposes.'));
    }
}
