<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ErrorLogController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $query = DB::table('error_logs')
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->orderByDesc('created_at');

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('resolved')) {
            $query->where('is_resolved', $request->resolved === 'yes');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhere('file', 'like', "%{$search}%");
            });
        }

        $errors = $query->paginate(25);

        return view('errors.log-index', compact('errors'));
    }

    public function show(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $error = DB::table('error_logs')
            ->where('id', $id)
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->first();

        if (!$error) {
            abort(404);
        }

        return view('errors.log-show', compact('error'));
    }

    public function toggleResolved(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $error = DB::table('error_logs')
            ->where('id', $id)
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->first();

        if (!$error) {
            abort(404);
        }

        DB::table('error_logs')->where('id', $id)->update(['is_resolved' => !$error->is_resolved]);

        return redirect()->back()->with('success', __('Error status updated.'));
    }

    public function export(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $error = DB::table('error_logs')
            ->where('id', $id)
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->first();

        if (!$error) {
            abort(404);
        }

        $report = [
            'product' => 'InsulaCRM',
            'version' => config('app.version', '1.0.0'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'error' => [
                'id' => $error->id,
                'level' => $error->level,
                'message' => $error->message,
                'exception' => $error->exception_class,
                'file' => $error->file,
                'line' => $error->line,
                'url' => $error->url,
                'method' => $error->method,
                'occurred_at' => $error->created_at,
            ],
            'stack_trace' => $error->trace,
            'context' => json_decode($error->context, true),
            'environment' => [
                'os' => PHP_OS,
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
                'db_driver' => config('database.default'),
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        $filename = 'bug-report-' . $error->id . '-' . date('Y-m-d') . '.json';

        return response()->json($report, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function clear()
    {
        $tenantId = auth()->user()->tenant_id;
        DB::table('error_logs')
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->where('is_resolved', true)
            ->delete();

        return redirect()->route('error-logs.index')->with('success', __('Resolved errors cleared.'));
    }
}
