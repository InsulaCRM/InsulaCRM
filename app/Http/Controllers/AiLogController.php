<?php

namespace App\Http\Controllers;

use App\Models\AiLog;
use Illuminate\Http\Request;

class AiLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AiLog::with(['user', 'subject'])->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('user_id')) {
            if ($request->user_id === 'system') {
                $query->whereNull('user_id');
            } else {
                $query->where('user_id', $request->user_id);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prompt_summary', 'like', "%{$search}%")
                  ->orWhere('result', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(25);

        $types = AiLog::distinct()->pluck('type')->sort()->values();

        $users = \App\Models\User::withoutGlobalScopes()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('ai-log.index', compact('logs', 'types', 'users'));
    }

    public function show(AiLog $aiLog)
    {
        $aiLog->load(['user', 'subject']);
        return view('ai-log.show', compact('aiLog'));
    }
}
