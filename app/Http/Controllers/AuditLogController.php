<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->paginate(50);

        $actions = AuditLog::where('tenant_id', auth()->user()->tenant_id)
            ->distinct()
            ->pluck('action')
            ->sort()
            ->values();

        $users = \App\Models\User::withoutGlobalScopes()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('audit-log.index', compact('logs', 'actions', 'users'));
    }

    public function export(Request $request)
    {
        $query = AuditLog::with('user')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->get();

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'User', 'Action', 'Model', 'Model ID', 'Changes']);
            foreach ($logs as $log) {
                $modelShort = $log->model_type ? class_basename($log->model_type) : '';
                $changes = '';
                if ($log->new_values) {
                    $parts = [];
                    foreach ($log->new_values as $k => $v) {
                        $old = $log->old_values[$k] ?? '';
                        $parts[] = "{$k}: {$old} -> {$v}";
                    }
                    $changes = implode('; ', $parts);
                }
                fputcsv($handle, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user?->name ?? 'System',
                    $log->action,
                    $modelShort,
                    $log->model_id ?? '',
                    $changes,
                ]);
            }
            fclose($handle);
        }, 'audit-log-' . date('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
