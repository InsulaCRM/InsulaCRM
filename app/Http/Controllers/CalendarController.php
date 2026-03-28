<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\OpenHouse;
use App\Models\Showing;
use App\Models\Task;
use App\Services\BusinessModeService;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index()
    {
        return view('calendar.index');
    }

    /**
     * Return events as JSON for the calendar (AJAX).
     */
    public function events(Request $request)
    {
        $user = auth()->user();
        $start = $request->input('start');
        $end = $request->input('end');

        $events = collect();

        // Tasks
        $tasksQuery = Task::with('lead')
            ->whereBetween('due_date', [$start, $end]);

        if (!$user->isAdmin()) {
            $tasksQuery->where('agent_id', $user->id);
        }

        $tasks = $tasksQuery->get()->map(fn ($task) => [
            'id' => 'task-' . $task->id,
            'title' => $task->title,
            'date' => $task->due_date->format('Y-m-d'),
            'type' => 'task',
            'color' => $task->is_completed ? 'green' : ($task->is_overdue ? 'red' : 'blue'),
            'completed' => $task->is_completed,
            'url' => $task->lead_id ? url("/leads/{$task->lead_id}") : null,
        ]);

        $events = $events->merge($tasks);

        // Activities (meetings and calls only for calendar)
        $activitiesQuery = Activity::with('agent')
            ->whereIn('type', ['meeting', 'call'])
            ->whereNotNull('logged_at')
            ->whereBetween('logged_at', [$start, $end]);

        if (!$user->isAdmin()) {
            $activitiesQuery->where('agent_id', $user->id);
        }

        $activities = $activitiesQuery->get()->map(fn ($activity) => [
            'id' => 'activity-' . $activity->id,
            'title' => ucfirst($activity->type) . ($activity->subject ? ': ' . $activity->subject : ''),
            'date' => $activity->logged_at->format('Y-m-d'),
            'type' => $activity->type,
            'color' => $activity->type === 'meeting' ? 'purple' : 'cyan',
            'url' => $activity->lead_id ? url("/leads/{$activity->lead_id}") : ($activity->deal_id ? url("/pipeline/{$activity->deal_id}") : null),
        ]);

        $events = $events->merge($activities);

        // Showings (real estate mode only)
        if (BusinessModeService::isRealEstate()) {
            $showingsQuery = Showing::with('property')
                ->where('status', 'scheduled')
                ->whereBetween('showing_date', [$start, $end]);

            if (!$user->isAdmin()) {
                $showingsQuery->where('agent_id', $user->id);
            }

            $showingEvents = $showingsQuery->get()->map(fn ($s) => [
                'id' => 'showing-' . $s->id,
                'title' => __('Showing') . ': ' . ($s->property->address ?? ''),
                'date' => $s->showing_date->format('Y-m-d'),
                'type' => 'showing',
                'color' => 'orange',
                'url' => url("/showings/{$s->id}"),
            ]);

            $events = $events->merge($showingEvents);

            // Open Houses
            $openHouseQuery = OpenHouse::with('property')
                ->whereIn('status', ['scheduled', 'active'])
                ->whereBetween('event_date', [$start, $end]);

            if (!$user->isAdmin()) {
                $openHouseQuery->where('agent_id', $user->id);
            }

            $openHouseEvents = $openHouseQuery->get()->map(fn ($oh) => [
                'id' => 'openhouse-' . $oh->id,
                'title' => __('Open House') . ': ' . ($oh->property->address ?? ''),
                'date' => $oh->event_date->format('Y-m-d'),
                'type' => 'open_house',
                'color' => 'teal',
                'url' => url("/open-houses/{$oh->id}"),
            ]);

            $events = $events->merge($openHouseEvents);
        }

        return response()->json($events->values());
    }
}
