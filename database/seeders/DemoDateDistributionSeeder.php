<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Redistributes existing demo data across Oct 2025 - Mar 2026
 * to create a realistic, visually appealing dashboard for screenshots.
 */
class DemoDateDistributionSeeder extends Seeder
{
    public function run(): void
    {
        // Date range: Oct 1, 2025 → Mar 10, 2026 (today)
        $startDate = Carbon::create(2025, 10, 1);
        $endDate = Carbon::create(2026, 3, 10);

        // ── Redistribute Leads across months with growth curve ──
        // Target distribution: gradually increasing leads per month
        // Oct: 18, Nov: 22, Dec: 15 (holidays), Jan: 28, Feb: 35, Mar: 82 (to reach ~200)
        $monthlyLeadTargets = [
            '2025-10' => 18,
            '2025-11' => 22,
            '2025-12' => 15,
            '2026-01' => 28,
            '2026-02' => 35,
            '2026-03' => 82,
        ];

        $leads = Lead::orderBy('id')->get();
        $leadIndex = 0;

        $sources = ['cold_call', 'direct_mail', 'website', 'referral', 'driving_for_dollars', 'list_import', 'other'];
        // Weighted source distribution for realism
        $sourceWeights = [
            'cold_call' => 25,
            'direct_mail' => 20,
            'website' => 15,
            'referral' => 12,
            'driving_for_dollars' => 13,
            'list_import' => 10,
            'other' => 5,
        ];
        $weightedSources = [];
        foreach ($sourceWeights as $source => $weight) {
            for ($w = 0; $w < $weight; $w++) {
                $weightedSources[] = $source;
            }
        }

        // Temperature distribution: older leads more likely cold, newer more warm/hot
        $tempDistributions = [
            'old' => ['cold' => 55, 'warm' => 30, 'hot' => 15],
            'mid' => ['cold' => 35, 'warm' => 40, 'hot' => 25],
            'new' => ['cold' => 20, 'warm' => 35, 'hot' => 45],
        ];

        // Status distribution: older leads more likely closed/dead, newer more likely active
        $statusDistributions = [
            'old' => ['new' => 2, 'prospecting' => 5, 'contacting' => 5, 'engaging' => 5, 'contacted' => 5, 'negotiating' => 8, 'offer_presented' => 10, 'under_contract' => 10, 'closed' => 30, 'dead' => 20],
            'mid' => ['new' => 5, 'prospecting' => 10, 'contacting' => 10, 'engaging' => 12, 'contacted' => 10, 'negotiating' => 15, 'offer_presented' => 12, 'under_contract' => 10, 'closed' => 10, 'dead' => 6],
            'new' => ['new' => 25, 'prospecting' => 20, 'contacting' => 15, 'engaging' => 12, 'contacted' => 8, 'negotiating' => 8, 'offer_presented' => 5, 'under_contract' => 3, 'closed' => 2, 'dead' => 2],
        ];

        foreach ($monthlyLeadTargets as $yearMonth => $count) {
            [$year, $month] = explode('-', $yearMonth);
            $monthStart = Carbon::create($year, $month, 1);
            $monthEnd = $monthStart->copy()->endOfMonth();
            if ($monthEnd->gt($endDate)) $monthEnd = $endDate->copy();

            // Determine age bracket
            $monthsAgo = Carbon::now()->diffInMonths($monthStart);
            $ageBracket = $monthsAgo >= 4 ? 'old' : ($monthsAgo >= 2 ? 'mid' : 'new');

            for ($i = 0; $i < $count && $leadIndex < $leads->count(); $i++) {
                $lead = $leads[$leadIndex];
                $createdAt = $this->randomDateInRange($monthStart, $monthEnd);

                // Pick weighted source
                $source = $weightedSources[array_rand($weightedSources)];

                // Pick weighted temperature
                $temperature = $this->weightedRandom($tempDistributions[$ageBracket]);

                // Pick weighted status
                $status = $this->weightedRandom($statusDistributions[$ageBracket]);

                // Motivation score correlates with temperature
                $motivationBase = match ($temperature) {
                    'hot' => rand(65, 100),
                    'warm' => rand(35, 70),
                    'cold' => rand(0, 40),
                };

                DB::table('leads')->where('id', $lead->id)->update([
                    'created_at' => $createdAt,
                    'updated_at' => $this->randomDateInRange($createdAt, min($endDate, $createdAt->copy()->addDays(rand(1, 30)))),
                    'lead_source' => $source,
                    'temperature' => $temperature,
                    'status' => $status,
                    'motivation_score' => $motivationBase,
                ]);

                $leadIndex++;
            }
        }

        // ── Redistribute Deals across months ──
        // Deals created after their leads, with realistic stage progression
        // Target: some closed_won in each month for the bar chart
        $deals = Deal::orderBy('id')->get();
        $dealStages = ['prospecting', 'contacting', 'engaging', 'offer_presented', 'under_contract', 'dispositions', 'assigned', 'closing', 'closed_won', 'closed_lost'];

        // Monthly deal targets with closed_won for each month
        $monthlyDealTargets = [
            '2025-10' => ['total' => 5, 'closed_won' => 2],
            '2025-11' => ['total' => 7, 'closed_won' => 3],
            '2025-12' => ['total' => 5, 'closed_won' => 2],
            '2026-01' => ['total' => 8, 'closed_won' => 3],
            '2026-02' => ['total' => 10, 'closed_won' => 4],
            '2026-03' => ['total' => 15, 'closed_won' => 5],
        ];

        $dealIndex = 0;
        foreach ($monthlyDealTargets as $yearMonth => $targets) {
            [$year, $month] = explode('-', $yearMonth);
            $monthStart = Carbon::create($year, $month, 1);
            $monthEnd = $monthStart->copy()->endOfMonth();
            if ($monthEnd->gt($endDate)) $monthEnd = $endDate->copy();

            for ($i = 0; $i < $targets['total'] && $dealIndex < $deals->count(); $i++) {
                $deal = $deals[$dealIndex];
                $createdAt = $this->randomDateInRange($monthStart, $monthEnd);

                // First N deals in each month are closed_won
                if ($i < $targets['closed_won']) {
                    $stage = 'closed_won';
                    $closingDate = $createdAt->copy()->addDays(rand(7, 45));
                    $contractDate = $createdAt->copy()->addDays(rand(1, 10));
                } elseif ($i === $targets['total'] - 1) {
                    $stage = 'closed_lost';
                    $closingDate = null;
                    $contractDate = null;
                } else {
                    // Active stages - newer months have earlier pipeline stages
                    $activeStages = ['prospecting', 'contacting', 'engaging', 'offer_presented', 'under_contract', 'dispositions', 'assigned', 'closing'];
                    $stage = $activeStages[array_rand($activeStages)];
                    $closingDate = in_array($stage, ['closing']) ? Carbon::now()->addDays(rand(5, 30)) : null;
                    $contractDate = in_array($stage, ['under_contract', 'closing', 'assigned', 'dispositions']) ? $createdAt->copy()->addDays(rand(3, 15)) : null;
                }

                $stageChangedAt = $this->randomDateInRange($createdAt, min($endDate, $createdAt->copy()->addDays(rand(2, 20))));

                // Realistic pricing
                $contractPrice = rand(45, 350) * 1000;
                $assignmentFee = rand(3, 25) * 1000;

                DB::table('deals')->where('id', $deal->id)->update([
                    'stage' => $stage,
                    'stage_changed_at' => $stageChangedAt,
                    'contract_price' => $contractPrice,
                    'assignment_fee' => $assignmentFee,
                    'contract_date' => $contractDate,
                    'closing_date' => $closingDate,
                    'created_at' => $createdAt,
                    'updated_at' => $stageChangedAt,
                ]);

                $dealIndex++;
            }
        }

        // ── Redistribute Activities across full date range ──
        $activities = Activity::orderBy('id')->get();
        foreach ($activities as $activity) {
            // Spread activities across the full range, clustering around their lead's created_at
            $lead = Lead::find($activity->lead_id);
            $baseDate = $lead ? Carbon::parse($lead->created_at) : $this->randomDateInRange($startDate, $endDate);
            $activityDate = $this->randomDateInRange(
                $baseDate,
                min($endDate, $baseDate->copy()->addDays(rand(0, 21)))
            );

            DB::table('activities')->where('id', $activity->id)->update([
                'logged_at' => $activityDate,
                'created_at' => $activityDate,
                'updated_at' => $activityDate,
            ]);
        }

        // ── Redistribute Tasks ──
        // Mix of completed (past) and upcoming (future) tasks
        $tasks = Task::orderBy('id')->get();
        $totalTasks = $tasks->count();

        foreach ($tasks as $index => $task) {
            $lead = Lead::find($task->lead_id);
            $baseDate = $lead ? Carbon::parse($lead->created_at) : $startDate;

            // 60% completed tasks in the past, 20% overdue, 20% upcoming
            $rand = $index / $totalTasks;
            if ($rand < 0.6) {
                // Completed task in the past
                $dueDate = $this->randomDateInRange($baseDate, min($endDate, $baseDate->copy()->addDays(rand(3, 30))));
                $isCompleted = true;
            } elseif ($rand < 0.8) {
                // Overdue (past due, not completed)
                $dueDate = $this->randomDateInRange(
                    Carbon::now()->subDays(14),
                    Carbon::now()->subDays(1)
                );
                $isCompleted = false;
            } else {
                // Upcoming (future due date, not completed)
                $dueDate = $this->randomDateInRange(
                    Carbon::now(),
                    Carbon::now()->addDays(14)
                );
                $isCompleted = false;
            }

            DB::table('tasks')->where('id', $task->id)->update([
                'due_date' => $dueDate->toDateString(),
                'is_completed' => $isCompleted,
                'created_at' => min($dueDate, $baseDate)->subDays(rand(0, 3)),
                'updated_at' => $isCompleted ? $dueDate : Carbon::now(),
            ]);
        }

        // ── Update Properties created_at to match their leads ──
        DB::statement('
            UPDATE properties p
            JOIN leads l ON p.lead_id = l.id
            SET p.created_at = l.created_at, p.updated_at = l.updated_at
        ');

        $this->command->info('Demo data redistributed across Oct 2025 - Mar 2026.');
    }

    private function randomDateInRange(Carbon $start, Carbon $end): Carbon
    {
        $min = $start->timestamp;
        $max = $end->timestamp;
        if ($max <= $min) return $start->copy();
        return Carbon::createFromTimestamp(rand($min, $max));
    }

    private function weightedRandom(array $weights): string
    {
        $total = array_sum($weights);
        $rand = rand(1, $total);
        $cumulative = 0;
        foreach ($weights as $key => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) return $key;
        }
        return array_key_first($weights);
    }
}
