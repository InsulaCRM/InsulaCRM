<?php

namespace App\Http\Controllers;

use App\Events\DealStageChanged;
use App\Facades\Hooks;
use App\Http\Requests\DealRequest;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Deal;
use App\Models\DealDocument;
use App\Models\DealOffer;
use App\Models\Role;
use App\Models\TransactionChecklist;
use App\Models\User;
use App\Notifications\BuyerMatchFound;
use App\Notifications\DealStageChanged as DealStageChangedNotification;
use App\Services\BuyerScoreService;
use App\Services\OpenSignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class DealController extends Controller
{
    public function intake(Request $request, ?\App\Models\Lead $lead = null)
    {
        $this->authorize('create', Deal::class);

        $leads = $lead ? collect([$lead]) : \App\Models\Lead::with('property')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereNotIn('status', ['dead'])
            ->latest()
            ->limit(250)
            ->get();

        $states = collect([
            'AL'=>'Alabama','AK'=>'Alaska','AZ'=>'Arizona','AR'=>'Arkansas','CA'=>'California','CO'=>'Colorado','CT'=>'Connecticut','DE'=>'Delaware','FL'=>'Florida','GA'=>'Georgia','HI'=>'Hawaii','ID'=>'Idaho','IL'=>'Illinois','IN'=>'Indiana','IA'=>'Iowa','KS'=>'Kansas','KY'=>'Kentucky','LA'=>'Louisiana','ME'=>'Maine','MD'=>'Maryland','MA'=>'Massachusetts','MI'=>'Michigan','MN'=>'Minnesota','MS'=>'Mississippi','MO'=>'Missouri','MT'=>'Montana','NE'=>'Nebraska','NV'=>'Nevada','NH'=>'New Hampshire','NJ'=>'New Jersey','NM'=>'New Mexico','NY'=>'New York','NC'=>'North Carolina','ND'=>'North Dakota','OH'=>'Ohio','OK'=>'Oklahoma','OR'=>'Oregon','PA'=>'Pennsylvania','RI'=>'Rhode Island','SC'=>'South Carolina','SD'=>'South Dakota','TN'=>'Tennessee','TX'=>'Texas','UT'=>'Utah','VT'=>'Vermont','VA'=>'Virginia','WA'=>'Washington','WV'=>'West Virginia','WI'=>'Wisconsin','WY'=>'Wyoming',
        ]);

        $structures = ['assignment' => __('Assignment'), 'double_close' => __('Double Close'), 'novation' => __('Novation')];
        $templates = [
            'NC-PURCHASE-v1-ATTORNEY-APPROVED' => __('NC Purchase & Sale'),
            'NC-ASSIGNMENT-v1-ATTORNEY-APPROVED' => __('NC Assignment'),
            'NC-DOUBLE-CLOSE-v1-ATTORNEY-APPROVED' => __('NC Double Close'),
            'NC-NOVATION-v1-ATTORNEY-APPROVED' => __('NC Novation'),
        ];
        $closingTypes = [
            'closing_attorney' => __('Closing Attorney'),
            'title_company' => __('Title Company'),
            'escrow_company' => __('Escrow Company'),
            'settlement_company' => __('Settlement Company'),
            'other_permitted' => __('Other Permitted'),
        ];

        return view('deals.intake', compact('lead', 'leads', 'states', 'structures', 'templates', 'closingTypes'));
    }

    public function storeIntake(Request $request)
    {
        $this->authorize('create', Deal::class);

        $data = $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'title' => 'required|string|max:255',
            'property_state' => 'required|string|size:2',
            'property_county' => 'nullable|string|max:255',
            'contract_price' => 'nullable|numeric|min:0',
            'buyer_type' => 'required|in:individual,entity',
            'buyer_legal_name' => 'nullable|string|max:255',
            'transaction_structure' => 'required|in:assignment,double_close,novation',
            'attorney_approved_template_id' => 'required|string|max:255',
            'executed_contract_status' => 'required|in:not_executed,pending_review,executed',
            'assignment_rights_confirmed' => 'nullable|boolean',
            'earnest_money' => 'nullable|numeric|min:0',
            'emd_required' => 'nullable|boolean',
            'emd_source' => 'nullable|in:wholesaler,end_buyer,other_approved',
            'buyer_covers_emd' => 'nullable|boolean',
            'emd_funding_confirmed' => 'nullable|boolean',
            'emd_exception_reason' => 'nullable|string|max:500',
            'closing_provider_type' => 'nullable|in:closing_attorney,title_company,escrow_company,settlement_company,other_permitted',
            'buyer_preferred_closing_provider' => 'nullable|string|max:255',
            'seller_preferred_closing_provider' => 'nullable|string|max:255',
            'confirmed_closing_provider' => 'nullable|string|max:255',
            'contract_review_status' => 'nullable|in:pending,approved,needs_attention',
            'no_buyer_status' => 'nullable|in:not_applicable,active,review,extension_review,termination_review',
            'extension_termination_review_status' => 'nullable|in:not_needed,pending,approved',
            'contract_date' => 'nullable|date',
            'due_diligence_end_date' => 'nullable|date',
            'closing_date' => 'nullable|date',
        ]);

        if (($data['earnest_money'] ?? 0) > 50 && empty($data['emd_exception_reason'])) {
            return back()->withInput()->withErrors(['earnest_money' => __('EMD above $50 requires an exception reason.')]);
        }

        if (($data['transaction_structure'] ?? null) === 'assignment' && ($data['assignment_rights_confirmed'] ?? null) !== true) {
            return back()->withInput()->withErrors(['assignment_rights_confirmed' => __('Assignment requires confirmed assignment rights before contract handoff.')]);
        }

        $lead = \App\Models\Lead::findOrFail($data['lead_id']);
        if ($lead->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $activeDeal = Deal::where('lead_id', $lead->id)
            ->whereNotIn('stage', ['closed_won', 'closed_lost', 'termination_review'])
            ->first();
        if ($activeDeal) {
            return redirect()->route('deals.show', $activeDeal)->with('info', __('An active deal already exists for this lead.'));
        }

        $stage = ($data['no_buyer_status'] ?? 'not_applicable') !== 'not_applicable'
            ? 'no_buyer'
            : (($data['executed_contract_status'] ?? 'not_executed') === 'pending_review'
                || ($data['contract_review_status'] ?? 'pending') === 'pending'
                ? 'contract_pending_review'
                : 'offer_presented');

        $deal = Deal::create([
            'tenant_id' => auth()->user()->tenant_id,
            'lead_id' => $lead->id,
            'agent_id' => auth()->id(),
            'stage' => $stage,
            ...$data,
        ]);

        \App\Models\Activity::create([
            'tenant_id' => auth()->user()->tenant_id,
            'lead_id' => $lead->id,
            'deal_id' => $deal->id,
            'agent_id' => auth()->id(),
            'type' => 'note',
            'subject' => __('Deal / contract intake completed'),
            'body' => __('Structure: :structure; template: :template; closing: :closing', [
                'structure' => $data['transaction_structure'],
                'template' => $data['attorney_approved_template_id'],
                'closing' => $data['closing_provider_type'] ?? 'not selected',
            ]),
            'logged_at' => now(),
        ]);

        \App\Services\WebhookService::dispatch('deal.intake.created', [
            'deal_id' => $deal->id,
            'lead_id' => $deal->lead_id,
            'stage' => $deal->stage,
            'property_state' => $deal->property_state,
            'property_county' => $deal->property_county,
            'buyer_type' => $deal->buyer_type,
            'buyer_legal_name' => $deal->buyer_legal_name,
            'transaction_structure' => $deal->transaction_structure,
            'attorney_approved_template_id' => $deal->attorney_approved_template_id,
            'executed_contract_status' => $deal->executed_contract_status,
            'assignment_rights_confirmed' => $deal->assignment_rights_confirmed,
            'earnest_money' => $deal->earnest_money,
            'emd_required' => $deal->emd_required,
            'emd_source' => $deal->emd_source,
            'buyer_covers_emd' => $deal->buyer_covers_emd,
            'emd_funding_confirmed' => $deal->emd_funding_confirmed,
            'emd_exception_reason' => $deal->emd_exception_reason,
            'closing_provider_type' => $deal->closing_provider_type,
            'buyer_preferred_closing_provider' => $deal->buyer_preferred_closing_provider,
            'seller_preferred_closing_provider' => $deal->seller_preferred_closing_provider,
            'confirmed_closing_provider' => $deal->confirmed_closing_provider,
            'contract_review_status' => $deal->contract_review_status,
            'no_buyer_status' => $deal->no_buyer_status,
            'extension_termination_review_status' => $deal->extension_termination_review_status,
            'contract_price' => $deal->contract_price,
            'contract_date' => $deal->contract_date?->toDateString(),
            'due_diligence_end_date' => $deal->due_diligence_end_date?->toDateString(),
            'closing_date' => $deal->closing_date?->toDateString(),
        ], auth()->user()->tenant_id);

        if (
            $deal->executed_contract_status === 'executed'
            && $deal->contract_review_status === 'approved'
            && $deal->due_diligence_end_date
            && $deal->closing_date
        ) {
            \App\Services\WebhookService::dispatch('deal.contract.ready', [
                'deal_id' => $deal->id,
                'lead_id' => $deal->lead_id,
                'transaction_structure' => $deal->transaction_structure,
                'attorney_approved_template_id' => $deal->attorney_approved_template_id,
                'executed_contract' => true,
                'assignment_rights_confirmed' => $deal->assignment_rights_confirmed,
                'emd_amount' => $deal->earnest_money,
                'emd_required' => $deal->emd_required,
                'emd_source' => $deal->emd_source,
                'buyer_covers_emd_confirmed' => $deal->buyer_covers_emd,
                'emd_funding_confirmed' => $deal->emd_funding_confirmed,
                'emd_exception_reason' => $deal->emd_exception_reason,
                'closing_provider_type' => $deal->closing_provider_type,
                'closing_provider_confirmed' => !empty($deal->confirmed_closing_provider),
                'confirmed_closing_provider' => $deal->confirmed_closing_provider,
                'buyer_preferred_closing_provider' => $deal->buyer_preferred_closing_provider,
                'seller_preferred_closing_provider' => $deal->seller_preferred_closing_provider,
                'due_diligence_end_date' => $deal->due_diligence_end_date?->toDateString(),
                'closing_date' => $deal->closing_date?->toDateString(),
                'contract_date' => $deal->contract_date?->toDateString(),
            ], auth()->user()->tenant_id);
        }

        return redirect()->route('deals.show', $deal)->with('success', __('Deal intake saved. Contract gate remains required before Under Contract.'));
    }

    public function pipeline(Request $request)
    {
        $this->authorize('viewAny', Deal::class);

        $query = Deal::with(['lead.property', 'agent']);

        if (auth()->user()->isAgent()) {
            $query->where('agent_id', auth()->id());
        }

        // Filters
        if ($request->filled('agent')) {
            $query->where('agent_id', $request->agent);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('lead', function ($lq) use ($search) {
                      $lq->where(function ($inner) use ($search) {
                          $inner->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                      });
                  })
                  ->orWhereHas('lead.property', function ($pq) use ($search) {
                      $pq->where('address', 'like', "%{$search}%");
                  });
            });
        }

        $deals = $query->get()->groupBy('stage');
        $stages = Deal::stageLabels();

        // Agents for filter dropdown (admin only)
        $agents = collect();
        if (auth()->user()->isAdmin()) {
            $agents = \App\Models\User::where('tenant_id', auth()->user()->tenant_id)
                ->whereHas('role', fn($q) => $q->whereIn('name', ['admin', 'agent', 'acquisition_agent', 'disposition_agent', 'listing_agent', 'buyers_agent']))
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('deals.pipeline', compact('deals', 'stages', 'agents'));
    }

    public function updateStage(Request $request, Deal $deal)
    {
        $this->authorize('changeStage', $deal);

        $request->validate([
            'stage' => 'required|in:' . implode(',', array_keys(Deal::stages())),
        ]);

        if ($request->stage === 'under_contract'
            && !\App\Services\BusinessModeService::isRealEstate()
            && $deal->contract_gate_status !== 'approved') {
            return response()->json([
                'success' => false,
                'error' => __('The wholesale Contract Protection Gate must be approved before a deal can move to Under Contract.'),
            ], 422);
        }

        $oldStage = $deal->stage;
        $updateData = [
            'stage' => $request->stage,
            'stage_changed_at' => now(),
        ];

        // Auto-calculate due_diligence_end_date when moving to under_contract
        if ($request->stage === 'under_contract' && $deal->contract_date && $deal->inspection_period_days > 0) {
            $updateData['due_diligence_end_date'] = $deal->contract_date->copy()->addDays($deal->inspection_period_days);
        }

        $deal->update($updateData);

        Activity::create([
            'tenant_id' => auth()->user()->tenant_id,
            'lead_id' => $deal->lead_id,
            'agent_id' => auth()->id(),
            'type' => 'stage_change',
            'subject' => 'Deal stage changed',
            'body' => 'Stage changed from "' . Deal::stageLabel($oldStage) . '" to "' . Deal::stageLabel($request->stage) . '"',
            'logged_at' => now(),
        ]);

        event(new DealStageChanged($deal, $oldStage));
        AuditLog::log('deal.stage_changed', $deal, ['stage' => $oldStage], ['stage' => $request->stage]);
        Hooks::doAction('deal.stage_changed', $deal, $oldStage);

        \App\Services\WebhookService::dispatch('deal.stage_changed', [
            'deal_id' => $deal->id,
            'title' => $deal->title,
            'old_stage' => $oldStage,
            'new_stage' => $request->stage,
            'agent_id' => $deal->agent_id,
        ], auth()->user()->tenant_id);

        // Notify deal agent of stage change
        $tenant = auth()->user()->tenant;
        if ($tenant->wantsNotification('deal_stage_changed') && $deal->agent_id) {
            $deal->load('lead');
            $deal->agent->notify(new DealStageChangedNotification($deal, $oldStage, $tenant));
        }

        // Dispatch buyer matching when deal moves to the mode-appropriate trigger stage
        $matchTrigger = \App\Services\BusinessModeService::getBuyerMatchTriggerStage();
        if ($request->stage === $matchTrigger) {
            app(\App\Services\BuyerMatchService::class)->matchForDeal($deal);

            // Notify admins and relevant agents if buyer matches found
            if ($tenant->wantsNotification('buyer_matched')) {
                $deal->load('buyerMatches');
                $matches = $deal->buyerMatches;
                if ($matches->count() > 0) {
                    $topScore = $matches->max('score') ?? 0;
                    $adminRoleId = Role::where('name', 'admin')->value('id');

                    $isRE = \App\Services\BusinessModeService::isRealEstate($tenant);
                    $notifyRoles = $isRE
                        ? ['listing_agent', 'buyers_agent']
                        : ['disposition_agent'];
                    $extraRoleIds = Role::whereIn('name', $notifyRoles)->pluck('id')->all();

                    $recipients = User::where('tenant_id', $tenant->id)
                        ->whereIn('role_id', array_merge([$adminRoleId], $extraRoleIds))
                        ->where('is_active', true)
                        ->get();
                    if ($recipients->isNotEmpty()) {
                        Notification::send($recipients, new BuyerMatchFound($deal, $matches->count(), $topScore, $tenant));
                    }
                }
            }
        }

        // Decrease buyer reliability if deal reverts back to match trigger stage (buyer backed out)
        if ($oldStage !== $matchTrigger && $request->stage === $matchTrigger) {
            $assignedMatch = $deal->buyerMatches()->where('status', 'interested')->first();
            if ($assignedMatch && $assignedMatch->buyer) {
                $assignedMatch->update(['status' => 'passed']);
                BuyerScoreService::recalculate($assignedMatch->buyer);
            }
        }

        // Auto-create transaction checklist when entering under_contract in realestate mode
        if ($request->stage === 'under_contract' && \App\Services\BusinessModeService::isRealEstate()) {
            if ($deal->checklistItems()->count() === 0) {
                foreach (TransactionChecklist::DEFAULT_ITEMS as $item) {
                    TransactionChecklist::create([
                        'tenant_id' => auth()->user()->tenant_id,
                        'deal_id' => $deal->id,
                        ...$item,
                    ]);
                }
            }
        }

        return response()->json(['success' => true]);
    }


    public function contractReview(Request $request, Deal $deal)
    {
        $this->authorize('update', $deal);

        $data = $request->validate([
            'property_state' => 'required|string|size:2',
            'property_county' => 'nullable|string|max:255',
            'buyer_type' => 'required|in:individual,entity',
            'buyer_legal_name' => 'nullable|string|max:255',
            'transaction_structure' => 'required|in:assignment,double_close,novation',
            'attorney_approved_template_id' => 'required|string|max:255',
            'executed_contract_status' => 'required|in:not_executed,pending_review,executed',
            'assignment_rights_confirmed' => 'nullable|boolean',
            'earnest_money' => 'nullable|numeric|min:0',
            'emd_required' => 'nullable|boolean',
            'emd_source' => 'nullable|in:wholesaler,end_buyer,other_approved',
            'buyer_covers_emd' => 'nullable|boolean',
            'emd_funding_confirmed' => 'nullable|boolean',
            'emd_exception_reason' => 'nullable|string|max:500',
            'closing_provider_type' => 'nullable|in:closing_attorney,title_company,escrow_company,settlement_company,other_permitted',
            'buyer_preferred_closing_provider' => 'nullable|string|max:255',
            'seller_preferred_closing_provider' => 'nullable|string|max:255',
            'confirmed_closing_provider' => 'nullable|string|max:255',
            'contract_review_status' => 'required|in:pending,approved,needs_attention',
            'no_buyer_status' => 'required|in:not_applicable,active,review,extension_review,termination_review',
            'extension_termination_review_status' => 'required|in:not_needed,pending,approved',
            'contract_date' => 'nullable|date',
            'due_diligence_end_date' => 'nullable|date',
            'closing_date' => 'nullable|date',
        ]);

        if (($data['earnest_money'] ?? 0) > 50 && empty($data['emd_exception_reason'])) {
            return back()->withInput()->withErrors(['earnest_money' => __('EMD above $50 requires an exception reason.')]);
        }

        if (($data['transaction_structure'] ?? null) === 'assignment' && ($data['assignment_rights_confirmed'] ?? false) !== true) {
            return back()->withInput()->withErrors(['assignment_rights_confirmed' => __('Assignment requires confirmed assignment rights before contract handoff.')]);
        }

        if (($data['buyer_covers_emd'] ?? false) && !($data['emd_funding_confirmed'] ?? false)) {
            return back()->withInput()->withErrors(['emd_funding_confirmed' => __('Buyer-funded EMD must be confirmed before contract handoff.')]);
        }

        $deal->update($data);

        // A new readiness request invalidates any prior gate result until n8n returns.
        $deal->update([
            'contract_gate_status' => 'pending',
            'contract_gate_reason' => null,
            'contract_gate_blockers' => null,
            'contract_gate_checked_at' => null,
        ]);

        $ready = $deal->executed_contract_status === 'executed'
            && $deal->contract_review_status === 'approved'
            && !empty($deal->attorney_approved_template_id)
            && !empty($deal->due_diligence_end_date)
            && !empty($deal->closing_date)
            && !empty($deal->closing_provider_type)
            && !empty($deal->confirmed_closing_provider)
            && (($deal->transaction_structure !== 'assignment') || $deal->assignment_rights_confirmed);

        if ($ready) {
            \App\Services\WebhookService::dispatch('deal.contract.ready', [
                'deal_id' => $deal->id,
                'lead_id' => $deal->lead_id,
                'transaction_structure' => $deal->transaction_structure,
                'attorney_approved_template_id' => $deal->attorney_approved_template_id,
                'executed_contract' => true,
                'assignment_rights_confirmed' => $deal->assignment_rights_confirmed,
                'emd_amount' => $deal->earnest_money,
                'emd_required' => $deal->emd_required,
                'emd_source' => $deal->emd_source,
                'buyer_covers_emd_confirmed' => $deal->buyer_covers_emd,
                'emd_funding_confirmed' => $deal->emd_funding_confirmed,
                'emd_exception_reason' => $deal->emd_exception_reason,
                'closing_provider_type' => $deal->closing_provider_type,
                'closing_provider_confirmed' => true,
                'confirmed_closing_provider' => $deal->confirmed_closing_provider,
                'buyer_preferred_closing_provider' => $deal->buyer_preferred_closing_provider,
                'seller_preferred_closing_provider' => $deal->seller_preferred_closing_provider,
                'due_diligence_end_date' => $deal->due_diligence_end_date?->toDateString(),
                'closing_date' => $deal->closing_date?->toDateString(),
                'contract_date' => $deal->contract_date?->toDateString(),
            ], auth()->user()->tenant_id);

            AuditLog::log('deal.contract_gate.requested', $deal);
            Activity::create([
                'tenant_id' => $deal->tenant_id,
                'lead_id' => $deal->lead_id,
                'deal_id' => $deal->id,
                'agent_id' => auth()->id(),
                'type' => 'note',
                'subject' => __('Contract protection gate requested'),
                'body' => __('Deal passed the local readiness checks and was sent to the configured contract protection workflow.'),
                'logged_at' => now(),
            ]);

            return redirect()->back()->with('success', __('Contract readiness saved and protection gate requested.'));
        }

        return redirect()->back()->with('warning', __('Contract details saved, but the protection gate is not ready to run. Review the highlighted requirements.'));
    }


    public function show(Deal $deal)
    {
        $this->authorize('view', $deal);
        $deal->load(['lead.property', 'agent', 'documents', 'generatedDocuments', 'signatureRequests', 'buyerMatches.buyer', 'activities.agent']);

        if (\App\Services\BusinessModeService::isRealEstate()) {
            $deal->load(['offers', 'checklistItems']);
        }

        if (request()->ajax()) {
            return response()->json($deal);
        }

        return view('deals.show', compact('deal'));
    }

    public function update(DealRequest $request, Deal $deal)
    {
        $this->authorize('update', $deal);

        $deal->update($request->validated());

        // Recalculate due_diligence_end_date if relevant fields changed
        if ($deal->contract_date && $deal->inspection_period_days > 0 && $deal->stage === 'under_contract') {
            $deal->update([
                'due_diligence_end_date' => $deal->contract_date->copy()->addDays($deal->inspection_period_days),
            ]);
        }

        return response()->json(['success' => true, 'deal' => $deal->fresh()]);
    }

    public function sendOpenSign(Request $request, Deal $deal, OpenSignService $service)
    {
        $this->authorize('update', $deal);

        if (!\App\Services\BusinessModeService::isWholesale()) {
            return back()->withErrors(['signature' => __('OpenSign contract sending is currently enabled for wholesale deals.')]);
        }

        if ($deal->contract_gate_status !== 'approved') {
            return back()->withErrors(['signature' => __('The Contract Protection Gate must be approved before sending a document for signature.')]);
        }

        $data = $request->validate([
            'generated_document_id' => 'required|integer',
            'include_seller' => 'nullable|boolean',
            'include_buyer' => 'nullable|boolean',
        ]);

        $document = $deal->generatedDocuments()->findOrFail($data['generated_document_id']);
        $signers = [];

        if (($data['include_seller'] ?? false) && $deal->lead?->email) {
            $signers[] = [
                'role' => 'seller',
                'name' => $deal->lead->full_name,
                'email' => $deal->lead->email,
                'phone' => $deal->lead->phone,
            ];
        }

        if (($data['include_buyer'] ?? false) && $deal->committedBuyer?->email) {
            $signers[] = [
                'role' => 'buyer',
                'name' => $deal->committedBuyer->full_name,
                'email' => $deal->committedBuyer->email,
                'phone' => $deal->committedBuyer->phone,
            ];
        }

        if (count($signers) === 0) {
            return back()->withErrors(['signature' => __('Select at least one signer with a valid email address.')]);
        }

        try {
            $service->createSignatureRequest(
                $deal,
                $document,
                $signers,
                [],
                [
                    'title' => $document->name,
                    'document_type' => 'wholesale_contract',
                    'enable_otp' => true,
                    'send_in_order' => count($signers) > 1,
                ]
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['signature' => $e->getMessage()]);
        }

        return back()->with('success', __('Signature request sent through OpenSign.'));
    }

    public function uploadDocument(Request $request, Deal $deal)
    {
        $this->authorize('uploadDocument', $deal);

        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file = $request->file('document');
        $path = $file->store("deals/{$deal->id}", 'local');

        DealDocument::create([
            'tenant_id' => $deal->tenant_id,
            'deal_id' => $deal->id,
            'filename' => basename($path),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
        ]);

        return redirect()->back()->with('success', 'Document uploaded successfully.');
    }

    public function downloadDocument(DealDocument $document)
    {
        $deal = Deal::findOrFail($document->deal_id);
        $this->authorize('view', $deal);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function notifyBuyer(Deal $deal, \App\Models\DealBuyerMatch $match)
    {
        $this->authorize('notifyBuyer', $deal);

        $match->update(['notified_at' => now()]);

        event(new \App\Events\BuyerNotified($match->buyer, $deal));
        Hooks::doAction('buyer.notified', $match->buyer, $deal);

        return redirect()->back()->with('success', 'Buyer notified successfully.');
    }

    public function export(Request $request)
    {
        $this->authorize('export', Deal::class);

        $query = Deal::with(['lead', 'agent']);

        if (auth()->user()->isAgent()) {
            $query->where('agent_id', auth()->id());
        }

        if ($request->filled('agent')) {
            $query->where('agent_id', $request->agent);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('lead', function ($lq) use ($search) {
                      $lq->where(function ($inner) use ($search) {
                          $inner->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                      });
                  });
            });
        }

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            $terms = \App\Services\BusinessModeService::getTerminology();
            $feeColumn = \App\Services\BusinessModeService::getDashboardKpiConfig()['fee_column'];
            fputcsv($handle, [
                __('Title'), __('Lead Name'), __('Stage'), __('Contract Price'),
                $terms['money_label'], __('Agent'), __('Days in Stage'), __('Created Date'),
            ]);
            foreach ($query->with(['lead', 'agent'])->latest()->cursor() as $deal) {
                $daysInStage = $deal->stage_changed_at
                    ? (int) now()->diffInDays($deal->stage_changed_at, true)
                    : '';
                fputcsv($handle, [
                    $deal->title,
                    $deal->lead ? $deal->lead->first_name . ' ' . $deal->lead->last_name : '',
                    Deal::stageLabel($deal->stage),
                    $deal->contract_price,
                    $deal->{$feeColumn},
                    $deal->agent->name ?? '',
                    $daysInStage,
                    $deal->created_at?->format('Y-m-d'),
                ]);
            }
            fclose($handle);
        }, 'deals-export-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // ── Transaction Checklist ─────────────────────────────

    public function storeChecklist(Deal $deal)
    {
        $this->authorize('update', $deal);

        if ($deal->checklistItems()->count() > 0) {
            return response()->json(['message' => __('Checklist already exists.')], 422);
        }

        foreach (TransactionChecklist::DEFAULT_ITEMS as $item) {
            TransactionChecklist::create([
                'tenant_id' => auth()->user()->tenant_id,
                'deal_id' => $deal->id,
                ...$item,
            ]);
        }

        return redirect()->back()->with('success', __('Transaction checklist created.'));
    }

    public function updateChecklistItem(Request $request, TransactionChecklist $item)
    {
        $deal = Deal::findOrFail($item->deal_id);
        $this->authorize('update', $deal);

        $request->validate([
            'status' => 'nullable|in:' . implode(',', array_keys(TransactionChecklist::STATUSES)),
            'deadline' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $data = $request->only(['status', 'deadline', 'notes']);
        if (($data['status'] ?? null) === 'completed' && !$item->completed_at) {
            $data['completed_at'] = now();
        }
        if (($data['status'] ?? null) !== 'completed') {
            $data['completed_at'] = null;
        }

        $item->update($data);
        return response()->json(['success' => true, 'item' => $item->fresh()]);
    }

    public function addChecklistItem(Request $request, Deal $deal)
    {
        $this->authorize('update', $deal);

        $request->validate([
            'label' => 'required|string|max:255',
            'deadline' => 'nullable|date',
        ]);

        $maxOrder = $deal->checklistItems()->max('sort_order') ?? 0;

        $item = TransactionChecklist::create([
            'tenant_id' => auth()->user()->tenant_id,
            'deal_id' => $deal->id,
            'item_key' => 'custom_' . time(),
            'label' => $request->label,
            'deadline' => $request->deadline,
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json(['success' => true, 'item' => $item]);
    }

    public function removeChecklistItem(TransactionChecklist $item)
    {
        $deal = Deal::findOrFail($item->deal_id);
        $this->authorize('update', $deal);

        $item->delete();
        return response()->json(['success' => true]);
    }

    // ── Offer Management ──────────────────────────────────

    public function storeOffer(Request $request, Deal $deal)
    {
        $this->authorize('update', $deal);

        $request->validate([
            'buyer_name' => 'required|string|max:255',
            'buyer_agent_name' => 'nullable|string|max:255',
            'buyer_agent_phone' => 'nullable|string|max:50',
            'buyer_agent_email' => 'nullable|email|max:255',
            'offer_price' => 'required|numeric|min:0',
            'earnest_money' => 'nullable|numeric|min:0',
            'financing_type' => 'nullable|in:' . implode(',', array_keys(DealOffer::FINANCING_TYPES)),
            'contingencies' => 'nullable|array',
            'contingencies.*' => 'string',
            'expiration_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $offer = DealOffer::create([
            'tenant_id' => auth()->user()->tenant_id,
            'deal_id' => $deal->id,
            ...$request->only([
                'buyer_name', 'buyer_agent_name', 'buyer_agent_phone', 'buyer_agent_email',
                'offer_price', 'earnest_money', 'financing_type', 'contingencies',
                'expiration_date', 'notes',
            ]),
        ]);

        Activity::create([
            'tenant_id' => auth()->user()->tenant_id,
            'lead_id' => $deal->lead_id,
            'deal_id' => $deal->id,
            'agent_id' => auth()->id(),
            'type' => 'note',
            'subject' => __('Offer received'),
            'body' => __(':buyer offered :price', [
                'buyer' => $request->buyer_name,
                'price' => '$' . number_format($request->offer_price, 2),
            ]),
            'logged_at' => now(),
        ]);

        return redirect()->back()->with('success', __('Offer recorded successfully.'));
    }

    public function updateOffer(Request $request, DealOffer $offer)
    {
        $deal = Deal::findOrFail($offer->deal_id);
        $this->authorize('update', $deal);

        $request->validate([
            'status' => 'nullable|in:' . implode(',', array_keys(DealOffer::STATUSES)),
            'counter_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $oldStatus = $offer->status;
        $offer->update($request->only(['status', 'counter_price', 'notes']));

        if ($oldStatus !== $offer->status) {
            Activity::create([
                'tenant_id' => auth()->user()->tenant_id,
                'lead_id' => $deal->lead_id,
                'deal_id' => $deal->id,
                'agent_id' => auth()->id(),
                'type' => 'note',
                'subject' => __('Offer status changed'),
                'body' => __('Offer from :buyer changed from :old to :new', [
                    'buyer' => $offer->buyer_name,
                    'old' => __(DealOffer::STATUSES[$oldStatus] ?? $oldStatus),
                    'new' => __(DealOffer::STATUSES[$offer->status] ?? $offer->status),
                ]),
                'logged_at' => now(),
            ]);
        }

        return response()->json(['success' => true, 'offer' => $offer->fresh()]);
    }

    public function destroyOffer(DealOffer $offer)
    {
        $deal = Deal::findOrFail($offer->deal_id);
        $this->authorize('update', $deal);

        $offer->delete();
        return response()->json(['success' => true]);
    }

}
