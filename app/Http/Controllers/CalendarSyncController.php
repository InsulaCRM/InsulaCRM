<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CalendarSyncController extends Controller
{
    /**
     * Show calendar sync settings for the current user.
     */
    public function settings()
    {
        $user = auth()->user();
        $feedUrl = $user->calendar_feed_token
            ? url("/calendar/feed/{$user->calendar_feed_token}.ics")
            : null;

        return view('calendar.sync', [
            'feedUrl' => $feedUrl,
            'hasToken' => (bool) $user->calendar_feed_token,
        ]);
    }

    /**
     * Generate or regenerate the user's iCal feed token.
     */
    public function generateFeed()
    {
        $user = auth()->user();
        $user->calendar_feed_token = bin2hex(random_bytes(32));
        $user->save();

        $feedUrl = url("/calendar/feed/{$user->calendar_feed_token}.ics");

        return redirect()->route('calendar.sync')
            ->with('success', __('Calendar feed URL generated successfully.'))
            ->with('feedUrl', $feedUrl);
    }

    /**
     * Public iCal feed endpoint (no auth required).
     * Serves an .ics file for the user identified by token.
     */
    public function icalFeed(string $token)
    {
        $user = User::where('calendar_feed_token', $token)->first();

        if (!$user) {
            abort(404);
        }

        $now = now();
        $startDate = $now->copy()->subDays(30);
        $endDate = $now->copy()->addDays(90);

        // Fetch tasks for the user within the date range (bypass TenantScope by querying with tenant_id)
        $tasks = Task::withoutGlobalScopes()
            ->with('lead')
            ->where('tenant_id', $user->tenant_id)
            ->where('agent_id', $user->id)
            ->whereBetween('due_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        // Fetch activities (meetings & calls) for the user within the date range
        $activities = Activity::withoutGlobalScopes()
            ->with('lead')
            ->where('tenant_id', $user->tenant_id)
            ->where('agent_id', $user->id)
            ->whereIn('type', ['meeting', 'call'])
            ->whereNotNull('logged_at')
            ->whereBetween('logged_at', [$startDate, $endDate])
            ->get();

        $ical = $this->buildIcal($user, $tasks, $activities);

        return response($ical, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="insulacrm-calendar.ics"',
        ]);
    }

    /**
     * Import events from an external iCal URL.
     */
    public function importFromUrl(Request $request)
    {
        $request->validate([
            'ical_url' => 'required|url',
        ]);

        $user = auth()->user();
        $url = $request->input('ical_url');

        if (!$this->isUrlSafe($url)) {
            return redirect()->route('calendar.sync')
                ->with('error', __('The provided URL is not allowed. Only public HTTP/HTTPS URLs are accepted.'));
        }

        try {
            $content = @file_get_contents($url);
        } catch (\Throwable $e) {
            $content = false;
        }

        if ($content === false) {
            return redirect()->route('calendar.sync')
                ->with('error', __('Could not fetch the calendar URL. Please check the URL and try again.'));
        }

        $events = $this->parseIcal($content);

        if (empty($events)) {
            return redirect()->route('calendar.sync')
                ->with('error', __('No events found in the provided calendar.'));
        }

        $imported = 0;

        foreach ($events as $event) {
            if (!$event['summary'] || !$event['dtstart']) {
                continue;
            }

            Task::create([
                'tenant_id' => $user->tenant_id,
                'agent_id' => $user->id,
                'title' => Str::limit($event['summary'], 255),
                'due_date' => $event['dtstart'],
                'is_completed' => false,
            ]);

            $imported++;
        }

        return redirect()->route('calendar.sync')
            ->with('success', __(':count events imported as tasks.', ['count' => $imported]));
    }

    /**
     * Remove the user's calendar feed token (disconnect).
     */
    public function disconnect()
    {
        $user = auth()->user();
        $user->calendar_feed_token = null;
        $user->save();

        return redirect()->route('calendar.sync')
            ->with('success', __('Calendar feed disconnected.'));
    }

    /**
     * Check whether a URL is safe to fetch (not targeting private/internal networks).
     */
    protected function isUrlSafe(string $url): bool
    {
        $parsed = parse_url($url);

        if (!$parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
            return false;
        }

        // Only allow http and https schemes
        if (!in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower($parsed['host']);

        // Reject localhost hostnames
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]', '0.0.0.0'], true)) {
            return false;
        }

        // Reject .local and .internal hostnames
        if (str_ends_with($host, '.local') || str_ends_with($host, '.localhost') || str_ends_with($host, '.internal')) {
            return false;
        }

        // Resolve hostname to IP and check for private/reserved ranges
        $ip = gethostbyname($host);

        // gethostbyname returns the hostname unchanged on failure; also reject that
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Check the resolved IP against private and reserved ranges
        $filteredIp = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

        if ($filteredIp === false) {
            return false;
        }

        return true;
    }

    /**
     * Build iCal content string from tasks and activities.
     */
    protected function buildIcal(User $user, $tasks, $activities): string
    {
        $lines = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//InsulaCRM//Calendar//EN';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';
        $lines[] = 'X-WR-CALNAME:InsulaCRM - ' . $this->escapeIcalText($user->name);

        foreach ($tasks as $task) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:task-' . $task->id . '@insulacrm';
            $lines[] = 'DTSTART;VALUE=DATE:' . $task->due_date->format('Ymd');
            $lines[] = 'SUMMARY:' . $this->escapeIcalText('Task: ' . $task->title);

            $description = '';
            if ($task->lead) {
                $description = $task->lead->first_name . ' ' . $task->lead->last_name;
            }
            $lines[] = 'DESCRIPTION:' . $this->escapeIcalText($description);

            $lines[] = 'STATUS:' . ($task->is_completed ? 'COMPLETED' : 'CONFIRMED');
            $lines[] = 'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z');
            $lines[] = 'END:VEVENT';
        }

        foreach ($activities as $activity) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:activity-' . $activity->id . '@insulacrm';
            $lines[] = 'DTSTART;VALUE=DATE:' . $activity->logged_at->format('Ymd');
            $summary = ucfirst($activity->type) . ($activity->subject ? ': ' . $activity->subject : '');
            $lines[] = 'SUMMARY:' . $this->escapeIcalText($summary);

            $description = '';
            if ($activity->lead) {
                $description = $activity->lead->first_name . ' ' . $activity->lead->last_name;
            }
            $lines[] = 'DESCRIPTION:' . $this->escapeIcalText($description);

            $lines[] = 'STATUS:CONFIRMED';
            $lines[] = 'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z');
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Parse raw iCal content and extract VEVENT entries.
     * Returns an array of ['summary' => ..., 'dtstart' => ...].
     */
    protected function parseIcal(string $content): array
    {
        $events = [];

        // Unfold iCal line continuations (lines starting with space/tab are continuations)
        $content = preg_replace('/\r?\n[ \t]/', '', $content);

        // Split into lines
        $lines = preg_split('/\r?\n/', $content);

        $inEvent = false;
        $currentEvent = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === 'BEGIN:VEVENT') {
                $inEvent = true;
                $currentEvent = ['summary' => null, 'dtstart' => null];
                continue;
            }

            if ($line === 'END:VEVENT') {
                if ($inEvent && $currentEvent['dtstart']) {
                    $events[] = $currentEvent;
                }
                $inEvent = false;
                continue;
            }

            if (!$inEvent) {
                continue;
            }

            // Parse SUMMARY
            if (str_starts_with($line, 'SUMMARY:')) {
                $currentEvent['summary'] = substr($line, 8);
            }

            // Parse DTSTART (handle both date and datetime formats)
            if (str_starts_with($line, 'DTSTART')) {
                $value = $line;
                // Remove parameter prefix like DTSTART;VALUE=DATE: or DTSTART;TZID=...:
                if (($colonPos = strpos($value, ':')) !== false) {
                    $value = substr($value, $colonPos + 1);
                }

                // Parse the date value (YYYYMMDD or YYYYMMDDTHHmmss or YYYYMMDDTHHmmssZ)
                $value = trim($value);
                try {
                    if (strlen($value) === 8) {
                        // Date only: YYYYMMDD
                        $currentEvent['dtstart'] = \Carbon\Carbon::createFromFormat('Ymd', $value)->toDateString();
                    } elseif (strlen($value) >= 15) {
                        // DateTime: YYYYMMDDTHHmmss or YYYYMMDDTHHmmssZ
                        $clean = rtrim($value, 'Z');
                        $currentEvent['dtstart'] = \Carbon\Carbon::createFromFormat('Ymd\THis', $clean)->toDateString();
                    }
                } catch (\Throwable $e) {
                    // Skip events with unparseable dates
                    $currentEvent['dtstart'] = null;
                }
            }
        }

        return $events;
    }

    /**
     * Escape text for iCal format (RFC 5545).
     */
    protected function escapeIcalText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(';', '\;', $text);
        $text = str_replace(',', '\,', $text);
        $text = str_replace("\n", '\n', $text);

        return $text;
    }
}
