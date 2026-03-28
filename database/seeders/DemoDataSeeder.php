<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Buyer;
use App\Models\BuyerTransaction;
use App\Models\Deal;
use App\Models\DealOffer;
use App\Models\Lead;
use App\Models\LeadList;
use App\Models\OpenHouse;
use App\Models\OpenHouseAttendee;
use App\Models\Property;
use App\Models\Role;
use App\Models\Showing;
use App\Models\Task;
use App\Models\TransactionChecklist;
use App\Models\User;
use App\Services\BuyerScoreService;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed demo data for a specific tenant.
     *
     * Usage:
     *   $this->callWith(DemoDataSeeder::class, ['tenantId' => $tenant->id]);
     *
     * Data is spread across ~6 months with a realistic ramp-up.
     */
    public function run(int $tenantId): void
    {
        $faker = FakerFactory::create();
        $tenant = \App\Models\Tenant::find($tenantId);
        $isRealEstate = $tenant && $tenant->business_mode === 'realestate';

        if ($isRealEstate) {
            // Seed real estate roles if they don't exist yet
            foreach (['listing_agent' => 'Listing Agent', 'buyers_agent' => 'Buyers Agent'] as $name => $display) {
                Role::firstOrCreate(['name' => $name], ['display_name' => $display, 'is_system' => true]);
            }
            $listingAgentRole = Role::where('name', 'listing_agent')->first();
            $buyersAgentRole = Role::where('name', 'buyers_agent')->first();
            $agentRole = Role::where('name', 'agent')->first();
        } else {
            $acquisitionAgentRole = Role::where('name', 'acquisition_agent')->first();
            $dispositionAgentRole = Role::where('name', 'disposition_agent')->first();
            $fieldScoutRole = Role::where('name', 'field_scout')->first();
        }

        // Create demo team members for this tenant
        if ($isRealEstate) {
            $agent1 = User::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                'role_id' => $listingAgentRole->id,
                'name' => 'Lauren Mitchell',
                'email' => 'lauren.mitchell@demo.com',
                'password' => bcrypt('password'),
            ]);

            $agent2 = User::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                'role_id' => $listingAgentRole->id,
                'name' => 'Kevin Patel',
                'email' => 'kevin.patel@demo.com',
                'password' => bcrypt('password'),
            ]);

            $agent3 = User::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                'role_id' => $buyersAgentRole->id,
                'name' => 'Maria Santos',
                'email' => 'maria.santos@demo.com',
                'password' => bcrypt('password'),
            ]);

            User::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                'role_id' => $agentRole->id,
                'name' => 'James Cooper',
                'email' => 'james.cooper@demo.com',
                'password' => bcrypt('password'),
            ]);
        } else {
            $agent1 = User::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                'role_id' => $acquisitionAgentRole->id,
                'name' => 'Sarah Williams',
                'email' => 'sarah.williams@demo.com',
                'password' => bcrypt('password'),
            ]);

            $agent2 = User::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                'role_id' => $acquisitionAgentRole->id,
                'name' => 'David Chen',
                'email' => 'david.chen@demo.com',
                'password' => bcrypt('password'),
            ]);

            $agent3 = User::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                'role_id' => $acquisitionAgentRole->id,
                'name' => 'Jessica Martinez',
                'email' => 'jessica.martinez@demo.com',
                'password' => bcrypt('password'),
            ]);

            User::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                'role_id' => $dispositionAgentRole->id,
                'name' => 'Robert Taylor',
                'email' => 'robert.taylor@demo.com',
                'password' => bcrypt('password'),
            ]);

            User::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                'role_id' => $fieldScoutRole->id,
                'name' => 'Emily Nguyen',
                'email' => 'emily.nguyen@demo.com',
                'password' => bcrypt('password'),
            ]);
        }

        // ── Distribute 200 leads across 6 monthly cohorts ─────────────
        $agentIds = [$agent1->id, $agent2->id, $agent3->id];
        $allLeads = [];

        $monthCohorts = [
            ['start' => '2025-10-01', 'end' => '2025-10-31', 'count' => 20],
            ['start' => '2025-11-01', 'end' => '2025-11-30', 'count' => 25],
            ['start' => '2025-12-01', 'end' => '2025-12-31', 'count' => 30],
            ['start' => '2026-01-01', 'end' => '2026-01-31', 'count' => 35],
            ['start' => '2026-02-01', 'end' => '2026-02-28', 'count' => 40],
            ['start' => '2026-03-01', 'end' => '2026-03-12', 'count' => 50],
        ];

        foreach ($monthCohorts as $cohort) {
            $cohortStart = Carbon::parse($cohort['start']);
            $cohortEnd = Carbon::parse($cohort['end']);

            for ($i = 0; $i < $cohort['count']; $i++) {
                $leadCreated = $faker->dateTimeBetween($cohortStart, $cohortEnd);
                $leadUpdated = $faker->dateTimeBetween($leadCreated, min($cohortEnd->copy()->addDays(14), now()));

                $leadData = [
                    'tenant_id' => $tenantId,
                    'agent_id' => $agentIds[array_rand($agentIds)],
                    'created_at' => $leadCreated,
                    'updated_at' => $leadUpdated,
                ];

                if ($isRealEstate) {
                    $leadData['lead_source'] = $faker->randomElement([
                        'website', 'referral', 'open_house', 'sign_call',
                        'zillow', 'realtor_com', 'sphere', 'past_client', 'social_media',
                    ]);
                    // Realistic RE notes based on lead source
                    $reNotes = [
                        'website' => [
                            'Browsing listings in the Westside area. Looking for 3BR/2BA under $400K. First-time buyer.',
                            'Submitted inquiry on 742 Oak Lane listing. Wants to schedule a showing this weekend.',
                            'Downloaded our market report. Interested in investment properties near downtown.',
                            'Signed up for listing alerts — condos in the $250-350K range with HOA under $300/mo.',
                            'Viewed multiple listings online. Relocating from Chicago, needs to close by August.',
                        ],
                        'referral' => [
                            'Referred by Sarah Johnson (past client). Looking to sell their home on Maple Drive — wants a CMA.',
                            'Referred by mortgage broker at First National. Pre-approved for $525K conventional.',
                            'Friend of the Martinez family we helped last year. Downsizing from 4BR to 2BR condo.',
                            'Colleague referral. First-time seller, inherited property on Elm St, needs guidance on process.',
                            'Referred by attorney handling estate. Property in probate, needs to list once cleared.',
                        ],
                        'open_house' => [
                            'Attended open house at 108 Pine Ct. Very interested, asked about comparable sales in the area.',
                            'Walk-in at Sunday open house. Currently renting, lease up in 3 months. Pre-approved for $350K.',
                            'Couple attended open house — liked the layout but want something with a bigger yard. Budget $450K.',
                            'Serious buyer, asked detailed questions about school district and property taxes.',
                            'Visited open house twice. Ready to make an offer if price drops $10K.',
                        ],
                        'sign_call' => [
                            'Called from yard sign on 515 Cedar Ave. Wants to see the property ASAP. Sounded motivated.',
                            'Drove by listing, called from sign. Has been looking for 6 months, frustrated with market.',
                            'Called about the listing on Birch Street. Already pre-approved, wants to schedule showing today.',
                            'Sign call from neighbor — interested in selling their own home. Wants to know what it\'s worth.',
                            'Called from sign, asked about price and HOA fees. Currently renting nearby.',
                        ],
                        'zillow' => [
                            'Inquired through Zillow about 3BR listing. Budget $300-400K. Needs to be near good schools.',
                            'Zillow lead — saved 12 of our listings. Actively searching, no agent yet.',
                            'Contacted through Zillow. Relocating for work, needs to find a home within 60 days.',
                            'Zillow inquiry on 220 Walnut Dr. Asked about HOA, parking, and pet policy.',
                            'Multiple Zillow saves in our area. Investor looking for rental properties under $250K.',
                        ],
                        'realtor_com' => [
                            'Realtor.com inquiry. Moving from out of state for new job. Needs 4BR for family of 5.',
                            'Found our listing on Realtor.com. Wants virtual tour before flying in for showings.',
                            'Inquiry on luxury listing. Cash buyer, looking for waterfront property.',
                            'Realtor.com lead — comparing neighborhoods. Wants school ratings and commute times.',
                            'Submitted showing request through Realtor.com for 3 properties this weekend.',
                        ],
                        'sphere' => [
                            'Met at neighborhood block party. Thinking about selling in spring — wants to know market conditions.',
                            'Church friend mentioned they\'re outgrowing their 2BR. Interested in 3-4BR in same school district.',
                            'Gym acquaintance getting divorced, needs to sell jointly-owned property quickly.',
                            'Neighbor two doors down wants to sell — saw what similar homes sold for recently.',
                            'Connected at kids\' soccer game. Just got promoted, looking to upgrade to a bigger home.',
                        ],
                        'past_client' => [
                            'Helped them buy their first home in 2023. Now expecting twins and need more space.',
                            'Sold their condo last year. Now looking to buy a single-family home with a yard.',
                            'Previous buyer client. Wants to sell current home and move closer to parents.',
                            'Repeat client — bought investment property with us before. Looking for another rental.',
                            'Helped them sell 2 years ago. Ready to buy again after renting during renovation.',
                        ],
                        'social_media' => [
                            'DM\'d on Instagram after seeing our just-listed post. Wants more info on the property.',
                            'Facebook ad lead. Clicked on "What\'s Your Home Worth?" — wants a free valuation.',
                            'Engaged with our TikTok market update. First-time buyer, lots of questions about the process.',
                            'LinkedIn connection — relocating executive. Needs a home in the $600-800K range.',
                            'Responded to Facebook listing ad. Looking for fixer-upper under $200K.',
                        ],
                    ];
                    $sourceNotes = $reNotes[$leadData['lead_source']] ?? $reNotes['website'];
                    $leadData['notes'] = $faker->randomElement($sourceNotes);
                    // Assign realistic temperature based on source
                    $sourceTemperatureWeights = [
                        'open_house'  => ['hot' => 40, 'warm' => 50, 'cold' => 10],
                        'sign_call'   => ['hot' => 35, 'warm' => 50, 'cold' => 15],
                        'referral'    => ['hot' => 30, 'warm' => 55, 'cold' => 15],
                        'past_client' => ['hot' => 25, 'warm' => 60, 'cold' => 15],
                        'sphere'      => ['hot' => 20, 'warm' => 55, 'cold' => 25],
                        'zillow'      => ['hot' => 15, 'warm' => 45, 'cold' => 40],
                        'realtor_com' => ['hot' => 15, 'warm' => 45, 'cold' => 40],
                        'website'     => ['hot' => 10, 'warm' => 40, 'cold' => 50],
                        'social_media'=> ['hot' => 10, 'warm' => 35, 'cold' => 55],
                    ];
                    $weights = $sourceTemperatureWeights[$leadData['lead_source']] ?? ['hot' => 20, 'warm' => 50, 'cold' => 30];
                    $pool = [];
                    foreach ($weights as $temp => $weight) {
                        $pool = array_merge($pool, array_fill(0, $weight, $temp));
                    }
                    $leadData['temperature'] = $faker->randomElement($pool);
                }

                $lead = Lead::factory()->create($leadData);

                $allLeads[] = $lead;

                $property = Property::factory()->create([
                    'tenant_id' => $tenantId,
                    'lead_id' => $lead->id,
                    'created_at' => $leadCreated,
                    'updated_at' => $leadCreated,
                ]);

                // Real estate property fields
                if ($isRealEstate) {
                    $listPrice = $faker->numberBetween(150000, 800000);
                    $listingStatus = $faker->randomElement(
                        array_merge(
                            array_fill(0, 50, 'active'),
                            array_fill(0, 15, 'pending'),
                            array_fill(0, 20, 'sold'),
                            array_fill(0, 10, 'withdrawn'),
                            array_fill(0, 5, 'expired')
                        )
                    );
                    $listedAt = $faker->dateTimeBetween(
                        Carbon::parse($leadCreated)->subDays(5),
                        Carbon::parse($leadCreated)->addDays(5)
                    );
                    $soldAt = null;
                    $soldPrice = null;
                    if ($listingStatus === 'sold') {
                        $soldAt = $faker->dateTimeBetween(
                            Carbon::parse($listedAt)->addDays(14),
                            Carbon::parse($listedAt)->addDays(120)
                        );
                        $soldPrice = round($listPrice * $faker->randomFloat(2, 0.95, 1.05), 2);
                    }

                    $property->update([
                        'list_price' => $listPrice,
                        'listing_status' => $listingStatus,
                        'listed_at' => $listedAt,
                        'sold_at' => $soldAt,
                        'sold_price' => $soldPrice,
                        'distress_markers' => [],
                        'repair_estimate' => null,
                        'estimated_value' => null,
                        'our_offer' => null,
                        'after_repair_value' => null,
                        'asking_price' => $listPrice,
                    ]);
                }

                // 2-3 activities per lead, spread after lead creation
                $activityCount = rand(2, 3);
                for ($j = 0; $j < $activityCount; $j++) {
                    $actDate = $faker->dateTimeBetween($leadCreated, min(Carbon::parse($leadCreated)->addDays(30), now()));
                    $activityOverrides = [
                        'tenant_id' => $tenantId,
                        'lead_id' => $lead->id,
                        'agent_id' => $lead->agent_id,
                        'logged_at' => $actDate,
                        'created_at' => $actDate,
                        'updated_at' => $actDate,
                    ];

                    if ($isRealEstate) {
                        $reActivities = [
                            ['type' => 'call', 'subject' => 'Initial consultation call', 'body' => 'Discussed buying timeline and budget. Client is pre-approved and ready to start looking at homes.'],
                            ['type' => 'call', 'subject' => 'Follow-up on showing', 'body' => 'Client liked the property but concerned about the roof age. Wants to see 2 more options this week.'],
                            ['type' => 'call', 'subject' => 'Price reduction discussion', 'body' => 'Discussed current market conditions and recommended a 3% price reduction to attract more showings.'],
                            ['type' => 'call', 'subject' => 'Lender update call', 'body' => 'Spoke with client\'s lender — appraisal came in at asking price. Clear to close on schedule.'],
                            ['type' => 'email', 'subject' => 'New listings matching criteria', 'body' => 'Sent 4 new listings that match the client\'s requirements: 3BR, good schools, under $400K.'],
                            ['type' => 'email', 'subject' => 'CMA report sent', 'body' => 'Emailed the comparative market analysis for their home. Suggested list price: $385,000-$395,000.'],
                            ['type' => 'email', 'subject' => 'Showing confirmation', 'body' => 'Confirmed showings for Saturday: 10am at 220 Oak Dr, 11:30am at 415 Pine St, 1pm at 892 Elm Ave.'],
                            ['type' => 'email', 'subject' => 'Market update', 'body' => 'Sent monthly market stats for their neighborhood. Median price up 4% YoY, avg DOM down to 18 days.'],
                            ['type' => 'meeting', 'subject' => 'Listing presentation', 'body' => 'Met with sellers to present marketing plan. Agreed on list price of $425K. Signing listing agreement tomorrow.'],
                            ['type' => 'meeting', 'subject' => 'Buyer consultation', 'body' => 'In-person meeting to discuss wish list, budget, and timeline. Wants to be in new home before school starts.'],
                            ['type' => 'meeting', 'subject' => 'Home inspection walkthrough', 'body' => 'Attended inspection with buyers. Minor issues found: HVAC filter, loose railing. No deal-breakers.'],
                            ['type' => 'note', 'subject' => 'Client preferences updated', 'body' => 'After seeing a few homes, client now prefers single-story ranch style. Expanding search to include South side.'],
                            ['type' => 'note', 'subject' => 'Offer strategy notes', 'body' => 'Multiple offer situation likely on 108 Cedar. Recommending escalation clause up to $15K over asking.'],
                            ['type' => 'note', 'subject' => 'Staging consultation', 'body' => 'Recommended professional staging for the living room and master bedroom. Stager available next Tuesday.'],
                            ['type' => 'sms', 'subject' => 'Quick showing update', 'body' => 'Texted client: "Just left the showing at 340 Maple. Great condition, move-in ready. Want to see it?"'],
                            ['type' => 'sms', 'subject' => 'Offer update', 'body' => 'Texted seller: "Received a strong offer — $5K over asking, conventional financing, 30 day close. Let\'s discuss."'],
                        ];
                        $act = $faker->randomElement($reActivities);
                        $activityOverrides['type'] = $act['type'];
                        $activityOverrides['subject'] = $act['subject'];
                        $activityOverrides['body'] = $act['body'];
                    }

                    Activity::factory()->create($activityOverrides);
                }

                // 1-2 tasks per lead
                $taskCount = rand(1, 2);
                for ($j = 0; $j < $taskCount; $j++) {
                    $taskCreated = $faker->dateTimeBetween($leadCreated, min(Carbon::parse($leadCreated)->addDays(14), now()));
                    $dueDate = $faker->dateTimeBetween($taskCreated, Carbon::parse($taskCreated)->addDays(14));
                    $taskOverrides = [
                        'tenant_id' => $tenantId,
                        'lead_id' => $lead->id,
                        'agent_id' => $lead->agent_id,
                        'due_date' => $dueDate,
                        'is_completed' => Carbon::parse($dueDate)->isPast() ? $faker->boolean(70) : $faker->boolean(15),
                        'created_at' => $taskCreated,
                        'updated_at' => $taskCreated,
                    ];

                    if ($isRealEstate) {
                        $taskOverrides['title'] = $faker->randomElement([
                            'Schedule showing',
                            'Prepare CMA report',
                            'Follow up with buyer',
                            'Send listing agreement',
                            'Order home inspection',
                            'Review appraisal report',
                            'Coordinate with lender',
                            'Prepare for open house',
                            'Follow up on showing feedback',
                            'Send market update to seller',
                        ]);
                    }

                    Task::factory()->create($taskOverrides);
                }
            }
        }

        // ── Create 50 deals from the first 50 leads ──
        $dealStages = array_keys(\App\Services\BusinessModeService::getStages($tenant));
        for ($i = 0; $i < 50; $i++) {
            $lead = $allLeads[$i];
            $dealCreated = $faker->dateTimeBetween(
                Carbon::parse($lead->created_at)->addDays(rand(1, 10)),
                min(Carbon::parse($lead->created_at)->addDays(45), now())
            );
            $stageChanged = $faker->dateTimeBetween($dealCreated, min(Carbon::parse($dealCreated)->addDays(30), now()));

            $dealData = [
                'tenant_id' => $tenantId,
                'lead_id' => $lead->id,
                'agent_id' => $lead->agent_id,
                'stage' => $faker->randomElement($dealStages),
                'created_at' => $dealCreated,
                'updated_at' => $stageChanged,
                'stage_changed_at' => $stageChanged,
            ];

            if ($isRealEstate) {
                $dealData['listing_commission_pct'] = $faker->randomFloat(2, 2.0, 3.5);
                $dealData['buyer_commission_pct'] = $faker->randomFloat(2, 2.0, 3.0);
                $contractPrice = $faker->numberBetween(150000, 800000);
                $dealData['contract_price'] = $contractPrice;
                $dealData['total_commission'] = round($contractPrice * ($dealData['listing_commission_pct'] + $dealData['buyer_commission_pct']) / 100, 2);
                $dealData['mls_number'] = $faker->numerify('#######');
                $dealData['listing_date'] = $faker->dateTimeBetween($dealCreated, min(Carbon::parse($dealCreated)->addDays(7), now()));
                // Use property address for title, clear wholesale fields
                $dealData['title'] = $lead->property->address . ' Transaction';
                $dealData['assignment_fee'] = null;
            }

            Deal::factory()->create($dealData);
        }

        // ── Create 20 buyers with verification & transaction data ──
        $streetNames = ['Oak', 'Elm', 'Pine', 'Maple', 'Cedar', 'Birch', 'Walnut', 'Peach', 'Cherry', 'Magnolia'];
        $streetTypes = ['St', 'Ave', 'Blvd', 'Dr', 'Ln', 'Ct', 'Way', 'Pl'];
        $cities = ['Dallas, TX', 'Houston, TX', 'Austin, TX', 'Fort Worth, TX', 'Tampa, FL', 'Orlando, FL', 'Phoenix, AZ', 'Atlanta, GA'];

        for ($i = 0; $i < 20; $i++) {
            $buyerCreated = $faker->dateTimeBetween('2025-10-01', '2026-03-12');
            $buyer = Buyer::factory()->create([
                'tenant_id' => $tenantId,
                'created_at' => $buyerCreated,
                'updated_at' => $buyerCreated,
            ]);

            // ~60% of buyers get POF verified
            if ($faker->boolean(60)) {
                $buyer->pof_verified = true;
                $buyer->pof_amount = $faker->numberBetween(50000, 500000);
                $buyer->pof_verified_at = $faker->dateTimeBetween($buyerCreated, now());
                $buyer->save();
            }

            // ~70% of buyers get 1-8 transactions
            if ($faker->boolean(70)) {
                $txnCount = $faker->numberBetween(1, 8);
                for ($t = 0; $t < $txnCount; $t++) {
                    $closeDate = $faker->dateTimeBetween(Carbon::parse($buyerCreated)->subMonths(12), now());
                    $daysToClose = $faker->numberBetween(7, 90);

                    BuyerTransaction::create([
                        'tenant_id' => $tenantId,
                        'buyer_id' => $buyer->id,
                        'property_address' => $faker->numberBetween(100, 9999) . ' ' . $faker->randomElement($streetNames) . ' ' . $faker->randomElement($streetTypes) . ', ' . $faker->randomElement($cities),
                        'purchase_price' => $faker->numberBetween(40000, 350000),
                        'close_date' => $closeDate,
                        'days_to_close' => $daysToClose,
                    ]);
                }
            }

            // Recalculate score from transactions + POF
            BuyerScoreService::recalculate($buyer);
        }

        // ── Create lists and link leads (wholesale: distress lists, real estate: source lists) ──
        if ($isRealEstate) {
            $listTypes = [
                ['name' => 'Open House Attendees',   'type' => 'open_house',    'imported' => '2025-10-15'],
                ['name' => 'Website Inquiries',      'type' => 'website',       'imported' => '2025-11-08'],
                ['name' => 'Past Client Referrals',  'type' => 'referral',      'imported' => '2025-12-20'],
                ['name' => 'Zillow Leads',           'type' => 'zillow',        'imported' => '2026-01-25'],
                ['name' => 'Sphere of Influence',    'type' => 'sphere',        'imported' => '2026-02-18'],
            ];
        } else {
            $listTypes = [
                ['name' => 'Tax Delinquent List',   'type' => 'tax_delinquent',  'imported' => '2025-10-15'],
                ['name' => 'Probate List',           'type' => 'probate',         'imported' => '2025-11-08'],
                ['name' => 'Code Violation List',    'type' => 'code_violation',  'imported' => '2025-12-20'],
                ['name' => 'Pre-Foreclosure List',   'type' => 'pre_foreclosure', 'imported' => '2026-01-25'],
                ['name' => 'Absentee Owner List',    'type' => 'absentee_owner',  'imported' => '2026-02-18'],
            ];
        }

        foreach ($listTypes as $index => $listType) {
            $list = LeadList::create([
                'tenant_id' => $tenantId,
                'name' => $listType['name'],
                'type' => $listType['type'],
                'record_count' => 0,
                'imported_at' => Carbon::parse($listType['imported']),
            ]);

            $start = $index * 40;
            $listLeads = array_slice($allLeads, $start, 40);
            $leadIds = array_map(fn ($lead) => $lead->id, $listLeads);
            $list->leads()->attach(array_fill_keys($leadIds, ['tenant_id' => $tenantId]));
            $list->update(['record_count' => count($leadIds)]);
        }

        // ── Drip Sequence ──
        $sequence = \App\Models\Sequence::create([
            'tenant_id' => $tenantId,
            'name' => 'New Lead Follow-Up',
            'is_active' => true,
            'created_at' => '2025-11-01',
            'updated_at' => '2025-11-01',
        ]);

        if ($isRealEstate) {
            $steps = [
                ['order' => 1, 'delay_days' => 0, 'action_type' => 'sms', 'message_template' => "Hi {first_name}, this is {agent_name} from {company_name}. I'd love to discuss the current market value of your property at {address}. Are you considering selling?"],
                ['order' => 2, 'delay_days' => 2, 'action_type' => 'call', 'message_template' => 'Follow-up call to discuss listing opportunity for {address}'],
                ['order' => 3, 'delay_days' => 5, 'action_type' => 'email', 'message_template' => "Subject: Free Market Analysis for {address}\n\nHi {first_name},\n\nI'd like to offer you a complimentary Comparable Market Analysis for your property. This will give you an accurate picture of your home's current value. Would you like to schedule a time to meet?\n\nBest,\n{agent_name}"],
                ['order' => 4, 'delay_days' => 10, 'action_type' => 'email', 'message_template' => 'Market update and listing strategy for {address}'],
                ['order' => 5, 'delay_days' => 15, 'action_type' => 'call', 'message_template' => "Hi {first_name}, just following up on the market analysis for your property. I have some great insights to share. Please call me back at your convenience."],
            ];
        } else {
            $steps = [
                ['order' => 1, 'delay_days' => 0, 'action_type' => 'sms', 'message_template' => 'Hi {first_name}, this is {agent_name} from {company_name}. We noticed your property at {address} and would love to discuss options. Are you interested in selling?'],
                ['order' => 2, 'delay_days' => 2, 'action_type' => 'call', 'message_template' => 'Follow-up call regarding property at {address}'],
                ['order' => 3, 'delay_days' => 5, 'action_type' => 'email', 'message_template' => "Subject: Your Property at {address}\n\nHi {first_name},\n\nWe are cash buyers interested in purchasing your property. We can close in as little as 14 days. Would you like to hear our offer?\n\nBest,\n{agent_name}"],
                ['order' => 4, 'delay_days' => 10, 'action_type' => 'direct_mail', 'message_template' => 'Send yellow letter to {address}'],
                ['order' => 5, 'delay_days' => 15, 'action_type' => 'voicemail', 'message_template' => 'Hi {first_name}, just checking in one last time about your property. Call us back at any time.'],
            ];
        }
        foreach ($steps as $step) {
            \App\Models\SequenceStep::create(array_merge($step, ['sequence_id' => $sequence->id]));
        }

        // Enroll first 3 leads
        $enrollLeads = Lead::withoutGlobalScopes()->where('tenant_id', $tenantId)->take(3)->get();
        foreach ($enrollLeads as $lead) {
            \App\Models\SequenceEnrollment::create([
                'sequence_id' => $sequence->id,
                'lead_id' => $lead->id,
                'tenant_id' => $tenantId,
                'current_step' => 1,
                'status' => 'active',
                'last_step_at' => $faker->dateTimeBetween(Carbon::parse($lead->created_at), now()),
            ]);
        }

        // ── Deal-Buyer matches ──
        $matchStage = \App\Services\BusinessModeService::getBuyerMatchTriggerStage($tenant);
        $dispositionDeals = Deal::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('stage', $matchStage)
            ->get();
        $allBuyers = Buyer::withoutGlobalScopes()->where('tenant_id', $tenantId)->get();

        foreach ($dispositionDeals as $deal) {
            if ($allBuyers->isEmpty()) break;
            $matchCount = min(rand(3, 5), $allBuyers->count());
            $matchedBuyers = $allBuyers->random($matchCount);
            foreach ($matchedBuyers as $buyer) {
                $matchCreated = $faker->dateTimeBetween($deal->created_at, now());
                \App\Models\DealBuyerMatch::create([
                    'deal_id' => $deal->id,
                    'buyer_id' => $buyer->id,
                    'match_score' => rand(25, 95),
                    'status' => $faker->randomElement(['pending', 'contacted', 'interested', 'passed']),
                    'notified_at' => $faker->optional(0.6)->dateTimeBetween($matchCreated, now()),
                    'responded_at' => $faker->optional(0.3)->dateTimeBetween($matchCreated, now()),
                ]);
            }
        }

        // ── Real Estate: Showings, Open Houses, Checklists, Offers ──
        if ($isRealEstate) {

            // ── Showings (30 showings spread across recent weeks) ──
            $showingDeals = Deal::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->take(30)
                ->get();

            $showingTimes = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];
            $showingFeedback = [
                'Buyers loved the kitchen and open floor plan.',
                'Concerned about the roof age, may request inspection.',
                'Great first impression, scheduling second showing.',
                'Price seems high for the neighborhood.',
                'Buyers are very interested, discussing with their lender.',
                'Nice curb appeal but interior needs updating.',
                'Perfect size for the family, very enthusiastic.',
                'Location is ideal but want to compare with other listings.',
            ];

            foreach ($showingDeals as $idx => $deal) {
                $lead = Lead::withoutGlobalScopes()->find($deal->lead_id);
                if (!$lead) continue;
                $property = Property::withoutGlobalScopes()->where('lead_id', $lead->id)->first();
                if (!$property) continue;

                $showingDate = Carbon::now()->subWeeks(3)->addDays($idx);
                $isPast = $showingDate->isPast();

                Showing::create([
                    'tenant_id' => $tenantId,
                    'property_id' => $property->id,
                    'lead_id' => $lead->id,
                    'agent_id' => $deal->agent_id,
                    'deal_id' => $deal->id,
                    'showing_date' => $showingDate->toDateString(),
                    'showing_time' => $faker->randomElement($showingTimes),
                    'duration_minutes' => $faker->randomElement([30, 60]),
                    'status' => $isPast
                        ? $faker->randomElement(['completed', 'completed', 'completed', 'no_show'])
                        : 'scheduled',
                    'feedback' => $isPast ? $faker->randomElement($showingFeedback) : null,
                    'outcome' => $isPast
                        ? $faker->randomElement(['interested', 'not_interested', 'made_offer', 'needs_second_showing'])
                        : null,
                ]);
            }

            // ── Open Houses (8 open houses with attendees) ──
            $openHouseLeads = array_slice($allLeads, 50, 40);
            $openHouseCount = min(8, count($openHouseLeads));

            for ($oh = 0; $oh < $openHouseCount; $oh++) {
                $ohLead = $openHouseLeads[$oh * 5]; // spread across the slice
                $ohProperty = Property::withoutGlobalScopes()->where('lead_id', $ohLead->id)->first();
                if (!$ohProperty) continue;

                $eventDate = Carbon::now()->subWeeks(2)->addDays($oh * 4); // spread from -2 weeks to +2 weeks
                $isPast = $eventDate->isPast();
                $startTime = $faker->randomElement(['10:00', '13:00']);
                $endHour = ((int) substr($startTime, 0, 2)) + 2;
                $endTime = str_pad($endHour, 2, '0', STR_PAD_LEFT) . ':00';

                $openHouse = OpenHouse::create([
                    'tenant_id' => $tenantId,
                    'property_id' => $ohProperty->id,
                    'agent_id' => $faker->randomElement($agentIds),
                    'event_date' => $eventDate->toDateString(),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => $isPast ? 'completed' : 'scheduled',
                    'description' => $faker->sentence(),
                    'attendee_count' => 0,
                ]);

                // Create 3-8 attendees per open house
                $attendeeCount = rand(3, 8);
                $createdAttendees = 0;

                for ($a = 0; $a < $attendeeCount; $a++) {
                    $isInterested = $faker->boolean(40);
                    $attendee = OpenHouseAttendee::create([
                        'tenant_id' => $tenantId,
                        'open_house_id' => $openHouse->id,
                        'first_name' => $faker->firstName,
                        'last_name' => $faker->lastName,
                        'email' => $faker->unique()->safeEmail,
                        'phone' => $faker->phoneNumber,
                        'interested' => $isInterested,
                        'notes' => $faker->optional(0.5)->sentence(),
                    ]);
                    $createdAttendees++;

                    // ~40% of attendees auto-create a lead (open house lead capture)
                    if ($faker->boolean(40)) {
                        $ohNotes = [
                            'Attended open house, very interested in the property. Wants to schedule a private showing.',
                            'Walk-in from open house. Currently renting, lease ends in 2 months. Pre-approved buyer.',
                            'Visited open house with partner. Liked the kitchen and yard. Asking about comparable sales.',
                            'Open house attendee — first-time buyer, has many questions about the buying process.',
                            'Came to open house, interested but wants to see similar homes in the area before deciding.',
                            'Serious buyer from open house. Already pre-approved, looking to move quickly.',
                        ];
                        $capturedLead = Lead::create([
                            'tenant_id' => $tenantId,
                            'agent_id' => $openHouse->agent_id,
                            'first_name' => $attendee->first_name,
                            'last_name' => $attendee->last_name,
                            'email' => $attendee->email,
                            'phone' => $attendee->phone,
                            'lead_source' => 'open_house',
                            'temperature' => $faker->randomElement(['warm', 'warm', 'hot']),
                            'status' => 'new',
                            'notes' => $faker->randomElement($ohNotes),
                        ]);
                        $attendee->update(['lead_id' => $capturedLead->id]);
                    }
                }

                $openHouse->update(['attendee_count' => $createdAttendees]);
            }

            // ── Transaction Checklists (for deals in under_contract+ stages) ──
            $checklistStages = ['under_contract', 'inspection', 'appraisal', 'closing', 'closed_won'];
            $checklistDeals = Deal::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereIn('stage', $checklistStages)
                ->get();

            foreach ($checklistDeals as $deal) {
                $baseDate = $deal->contract_date
                    ? Carbon::parse($deal->contract_date)
                    : Carbon::parse($deal->created_at);

                foreach (TransactionChecklist::DEFAULT_ITEMS as $item) {
                    $deadline = $baseDate->copy()->addDays($item['sort_order'] * 5);
                    $status = 'pending';
                    $completedAt = null;

                    // Later stages have more completed items
                    if (in_array($deal->stage, ['closing', 'closed_won'])) {
                        // closing/closed_won: first 6 items completed, rest pending or in_progress
                        if ($item['sort_order'] <= 6) {
                            $status = 'completed';
                            $completedAt = $baseDate->copy()->addDays($item['sort_order'] * 4);
                        } elseif ($item['sort_order'] <= 8) {
                            $status = $faker->randomElement(['in_progress', 'completed']);
                            if ($status === 'completed') {
                                $completedAt = $baseDate->copy()->addDays($item['sort_order'] * 4);
                            }
                        }
                    } elseif (in_array($deal->stage, ['inspection', 'appraisal'])) {
                        // inspection/appraisal: first 2-3 items in_progress or completed
                        if ($item['sort_order'] <= 2) {
                            $status = $faker->randomElement(['in_progress', 'completed']);
                            if ($status === 'completed') {
                                $completedAt = $baseDate->copy()->addDays($item['sort_order'] * 4);
                            }
                        } elseif ($item['sort_order'] === 3) {
                            $status = $faker->randomElement(['pending', 'in_progress']);
                        }
                    }
                    // under_contract: all stay pending (default)

                    TransactionChecklist::create([
                        'tenant_id' => $tenantId,
                        'deal_id' => $deal->id,
                        'item_key' => $item['item_key'],
                        'label' => $item['label'],
                        'sort_order' => $item['sort_order'],
                        'status' => $status,
                        'deadline' => $deadline->toDateString(),
                        'completed_at' => $completedAt,
                    ]);
                }
            }

            // ── Offers (for deals in offer_received or active_listing stages) ──
            $offerDeals = Deal::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereIn('stage', ['offer_received', 'active_listing'])
                ->get();

            $allContingencies = ['inspection', 'appraisal', 'financing', 'sale_of_home'];

            foreach ($offerDeals as $deal) {
                $offerCount = rand(2, 4);
                $hasAccepted = false;

                for ($o = 0; $o < $offerCount; $o++) {
                    $basePrice = $deal->contract_price ?: $faker->numberBetween(200000, 600000);
                    $offerPrice = round($basePrice * $faker->randomFloat(2, 0.90, 1.05), 2);

                    // Determine status: one accepted for offer_received deals, rest mixed
                    if ($deal->stage === 'offer_received' && !$hasAccepted && $o === $offerCount - 1) {
                        // Last offer is accepted if none accepted yet
                        $offerStatus = 'accepted';
                        $hasAccepted = true;
                    } elseif ($deal->stage === 'offer_received' && !$hasAccepted && $faker->boolean(30)) {
                        $offerStatus = 'accepted';
                        $hasAccepted = true;
                    } else {
                        $offerStatus = $faker->randomElement(['pending', 'pending', 'countered', 'rejected']);
                    }

                    // Random subset of contingencies
                    $numContingencies = rand(1, 3);
                    $contingencies = $faker->randomElements($allContingencies, $numContingencies);

                    $hasBuyerAgent = $faker->boolean(70);

                    DealOffer::create([
                        'tenant_id' => $tenantId,
                        'deal_id' => $deal->id,
                        'buyer_name' => $faker->name,
                        'buyer_agent_name' => $hasBuyerAgent ? $faker->name : null,
                        'buyer_agent_phone' => $hasBuyerAgent ? $faker->phoneNumber : null,
                        'buyer_agent_email' => $hasBuyerAgent ? $faker->safeEmail : null,
                        'offer_price' => $offerPrice,
                        'earnest_money' => $faker->numberBetween(1000, 10000),
                        'financing_type' => $faker->randomElement(['cash', 'conventional', 'fha', 'va']),
                        'contingencies' => $contingencies,
                        'expiration_date' => Carbon::parse($deal->created_at)->addDays(rand(3, 7)),
                        'status' => $offerStatus,
                    ]);
                }
            }
        }

        // ── HelloWorld plugin ──
        \App\Models\Plugin::firstOrCreate(
            ['slug' => 'hello-world'],
            [
                'tenant_id' => $tenantId,
                'name' => 'Hello World',
                'version' => '1.0.0',
                'author' => 'CRM Team',
                'description' => 'A sample plugin demonstrating the plugin API.',
                'is_active' => true,
                'installed_at' => '2025-10-20',
            ]
        );
    }
}
