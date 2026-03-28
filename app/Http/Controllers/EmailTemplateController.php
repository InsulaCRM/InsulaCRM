<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;
        $templates = DB::table('email_templates')
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        return view('settings.email-templates', compact('templates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:65535',
        ]);

        $tenant = auth()->user()->tenant;

        $id = DB::table('email_templates')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'subject' => $request->subject,
            'body' => $request->body,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLog::log('email_template.created', null, ['name' => $request->name]);

        return redirect()->route('email-templates.edit', $id)->with('success', 'Template created.');
    }

    public function edit($id)
    {
        $tenant = auth()->user()->tenant;
        $template = DB::table('email_templates')
            ->where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        return view('settings.email-template-edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:65535',
        ]);

        $tenant = auth()->user()->tenant;

        DB::table('email_templates')
            ->where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->update([
                'name' => $request->name,
                'subject' => $request->subject,
                'body' => $request->body,
                'updated_at' => now(),
            ]);

        AuditLog::log('email_template.updated', null, ['name' => $request->name]);

        return redirect()->route('email-templates.edit', $id)->with('success', 'Template updated.');
    }

    public function destroy($id)
    {
        $tenant = auth()->user()->tenant;

        $template = DB::table('email_templates')
            ->where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->first();

        if ($template) {
            DB::table('email_templates')
                ->where('id', $id)
                ->delete();

            AuditLog::log('email_template.deleted', null, ['name' => $template->name]);
        }

        return redirect()->route('email-templates.index')->with('success', 'Template deleted.');
    }

    public function preview($id)
    {
        $tenant = auth()->user()->tenant;
        $template = DB::table('email_templates')
            ->where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        return view('settings.email-template-preview', compact('template', 'tenant'));
    }
}
