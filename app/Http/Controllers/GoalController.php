<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Goal;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    /**
     * Display active goals with progress bars and pace indicators.
     */
    public function index()
    {
        $user = auth()->user();

        // Team goals (no user_id assigned)
        $teamGoals = Goal::whereNull('user_id')
            ->where('is_active', true)
            ->orderBy('end_date')
            ->get();

        // Personal goals for the current user
        $myGoals = Goal::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('end_date')
            ->get();

        // For admin: also show all user goals grouped
        $allUserGoals = collect();
        if ($user->isAdmin()) {
            $allUserGoals = Goal::whereNotNull('user_id')
                ->where('is_active', true)
                ->with('user')
                ->orderBy('end_date')
                ->get()
                ->groupBy('user_id');
        }

        // Agents list for the create form (admin only)
        $agents = collect();
        if ($user->isAdmin()) {
            $agents = User::where('tenant_id', $user->tenant_id)
                ->where('is_active', true)
                ->get();
        }

        $aiEnabled = $user->tenant->ai_enabled ?? false;

        return view('goals.index', compact('teamGoals', 'myGoals', 'allUserGoals', 'agents', 'aiEnabled'));
    }

    /**
     * Show the form for creating a new goal.
     */
    public function create()
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            abort(403);
        }

        $agents = User::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->get();

        return view('goals.create', compact('agents'));
    }

    /**
     * Store a new goal.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            abort(403);
        }

        $data = $request->validate([
            'metric' => 'required|string|in:' . implode(',', Goal::METRICS),
            'target_value' => 'required|numeric|min:1',
            'period' => 'required|string|in:' . implode(',', Goal::PERIODS),
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $dates = $this->calculateDateRange($data['period']);

        Goal::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $data['user_id'] ?? null,
            'metric' => $data['metric'],
            'target_value' => $data['target_value'],
            'period' => $data['period'],
            'start_date' => $dates['start'],
            'end_date' => $dates['end'],
            'is_active' => true,
        ]);

        return redirect()->route('goals.index')->with('success', __('Goal created successfully.'));
    }

    /**
     * Show the form for editing a goal.
     */
    public function edit(Goal $goal)
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            abort(403);
        }

        $agents = User::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->get();

        return view('goals.edit', compact('goal', 'agents'));
    }

    /**
     * Update an existing goal.
     */
    public function update(Request $request, Goal $goal)
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            abort(403);
        }

        $data = $request->validate([
            'metric' => 'required|string|in:' . implode(',', Goal::METRICS),
            'target_value' => 'required|numeric|min:1',
            'period' => 'required|string|in:' . implode(',', Goal::PERIODS),
            'user_id' => 'nullable|integer|exists:users,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $dates = $this->calculateDateRange($data['period']);

        $goal->update([
            'user_id' => $data['user_id'] ?? null,
            'metric' => $data['metric'],
            'target_value' => $data['target_value'],
            'period' => $data['period'],
            'start_date' => $dates['start'],
            'end_date' => $dates['end'],
            'is_active' => $data['is_active'] ?? $goal->is_active,
        ]);

        return redirect()->route('goals.index')->with('success', __('Goal updated successfully.'));
    }

    /**
     * Delete a goal.
     */
    public function destroy(Goal $goal)
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            abort(403);
        }

        $goal->delete();

        return redirect()->route('goals.index')->with('success', __('Goal deleted successfully.'));
    }

    /**
     * AI Forecast endpoint (AJAX).
     */
    public function forecast(Request $request)
    {
        $request->validate([
            'goal_id' => 'required|integer|exists:goals,id',
        ]);

        $goal = Goal::findOrFail($request->goal_id);
        $currentValue = $goal->getCurrentValue();
        $targetValue = (float) $goal->target_value;
        $progressPct = $goal->getProgressPercentage();

        $totalDays = $goal->start_date->diffInDays($goal->end_date);
        $daysElapsed = max(1, $goal->start_date->diffInDays(now()->startOfDay()));
        $daysRemaining = max(0, now()->startOfDay()->diffInDays($goal->end_date));

        // Linear projection
        $dailyRate = $daysElapsed > 0 ? $currentValue / $daysElapsed : 0;
        $projectedValue = round($currentValue + ($dailyRate * $daysRemaining), 2);

        // Projected completion date
        $projectedCompletionDate = null;
        if ($dailyRate > 0 && $currentValue < $targetValue) {
            $daysNeeded = ceil(($targetValue - $currentValue) / $dailyRate);
            $projectedCompletionDate = now()->addDays($daysNeeded)->format('Y-m-d');
        } elseif ($currentValue >= $targetValue) {
            $projectedCompletionDate = now()->format('Y-m-d');
        }

        $result = [
            'projected_value' => $projectedValue,
            'projected_completion_date' => $projectedCompletionDate,
            'current_value' => $currentValue,
            'target_value' => $targetValue,
            'daily_rate' => round($dailyRate, 2),
            'days_remaining' => $daysRemaining,
            'ai_insight' => null,
        ];

        // Try AI forecast if available
        $tenant = auth()->user()->tenant;
        if ($tenant->ai_enabled) {
            try {
                $aiService = new AiService($tenant);
                if ($aiService->isAvailable()) {
                    $metricLabel = Goal::metricLabel($goal->metric);
                    $paceStatus = $goal->getPaceStatus();

                    $kpiData = [
                        'metric' => $metricLabel,
                        'current_value' => $currentValue,
                        'target_value' => $targetValue,
                        'progress_pct' => round($progressPct, 1),
                        'pace_status' => $paceStatus,
                        'projected_value' => $projectedValue,
                        'days_remaining' => $daysRemaining,
                        'daily_rate' => round($dailyRate, 2),
                        'period' => $goal->period,
                    ];

                    $result['ai_insight'] = $aiService->generateGoalForecast($kpiData);
                }
            } catch (\Throwable $e) {
                // AI unavailable, continue with linear projection
            }
        }

        return response()->json($result);
    }

    /**
     * Store goals from AI recommendations (AJAX).
     */
    public function storeFromAi(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            abort(403);
        }

        $data = $request->validate([
            'goals' => 'required|array|min:1',
            'goals.*.metric' => 'required|string|in:' . implode(',', Goal::METRICS),
            'goals.*.target_value' => 'required|numeric|min:1',
            'goals.*.period' => 'required|string|in:' . implode(',', Goal::PERIODS),
        ]);

        $created = 0;
        foreach ($data['goals'] as $goalData) {
            $dates = $this->calculateDateRange($goalData['period']);

            Goal::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => null,
                'metric' => $goalData['metric'],
                'target_value' => $goalData['target_value'],
                'period' => $goalData['period'],
                'start_date' => $dates['start'],
                'end_date' => $dates['end'],
                'is_active' => true,
            ]);
            $created++;
        }

        return response()->json([
            'success' => true,
            'message' => __(':count goal(s) created successfully.', ['count' => $created]),
        ]);
    }

    /**
     * Calculate start and end dates for a given period.
     */
    private function calculateDateRange(string $period): array
    {
        $now = now();

        return match ($period) {
            'weekly' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'monthly' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'quarterly' => [
                'start' => $now->copy()->firstOfQuarter(),
                'end' => $now->copy()->lastOfQuarter(),
            ],
            'yearly' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
        };
    }
}
