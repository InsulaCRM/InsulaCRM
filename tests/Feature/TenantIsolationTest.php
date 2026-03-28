<?php

namespace Tests\Feature;

use App\Models\Buyer;
use App\Models\Deal;
use App\Models\DealDocument;
use App\Models\DealOffer;
use App\Models\Lead;
use App\Models\OpenHouse;
use App\Models\OpenHouseAttendee;
use App\Models\Role;
use App\Models\Showing;
use App\Models\Tenant;
use App\Models\TransactionChecklist;
use App\Models\User;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    private Tenant $tenantB;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTenantWithAdmin();

        $this->tenantB = Tenant::create([
            'name' => 'Company B',
            'slug' => 'company-b',
            'email' => 'b@test.com',
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'admin')->first();
        $this->userB = User::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_see_other_tenant_leads(): void
    {
        Lead::factory()->create([
            'tenant_id' => $this->tenant->id,
            'agent_id' => $this->adminUser->id,
            'first_name' => 'TenantA_Lead',
        ]);

        Lead::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'agent_id' => $this->userB->id,
            'first_name' => 'TenantB_Lead',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->get('/leads');
        $response->assertStatus(200);
        $response->assertSee('TenantA_Lead');
        $response->assertDontSee('TenantB_Lead');
    }

    public function test_admin_cannot_see_other_tenant_deals(): void
    {
        $leadA = Lead::factory()->create([
            'tenant_id' => $this->tenant->id,
            'agent_id' => $this->adminUser->id,
            'first_name' => 'TenantADeal',
            'last_name' => 'Owner',
        ]);
        $leadB = Lead::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'agent_id' => $this->userB->id,
            'first_name' => 'TenantBDeal',
            'last_name' => 'Owner',
        ]);

        Deal::factory()->create([
            'tenant_id' => $this->tenant->id,
            'lead_id' => $leadA->id,
            'agent_id' => $this->adminUser->id,
        ]);

        Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->get('/pipeline');
        $response->assertStatus(200);
        $response->assertSee('TenantADeal');
        $response->assertDontSee('TenantBDeal');
    }

    public function test_admin_cannot_see_other_tenant_buyers(): void
    {
        Buyer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'BuyerA',
        ]);

        Buyer::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'first_name' => 'BuyerB',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->get('/buyers');
        $response->assertStatus(200);
        $response->assertSee('BuyerA');
        $response->assertDontSee('BuyerB');
    }

    public function test_cannot_view_lead_from_other_tenant(): void
    {
        $otherLead = Lead::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'agent_id' => $this->userB->id,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->get("/leads/{$otherLead->id}");
        $response->assertStatus(404);
    }

    public function test_cannot_update_lead_from_other_tenant(): void
    {
        $otherLead = Lead::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'agent_id' => $this->userB->id,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->put("/leads/{$otherLead->id}", [
            'first_name' => 'Hacked',
            'last_name' => 'Name',
            'lead_source' => 'website',
            'status' => 'new',
            'temperature' => 'cold',
        ]);

        $response->assertStatus(404);
        $this->assertNotEquals('Hacked', $otherLead->fresh()->first_name);
    }

    public function test_cannot_delete_lead_from_other_tenant(): void
    {
        $otherLead = Lead::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'agent_id' => $this->userB->id,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->delete("/leads/{$otherLead->id}");
        $response->assertStatus(404);
    }

    public function test_settings_only_shows_own_tenant_team(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/settings');
        $response->assertStatus(200);
        $response->assertDontSee($this->userB->email);
    }

    // --- Real Estate cross-tenant isolation ---

    public function test_cannot_view_showing_from_other_tenant(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $propertyB = \App\Models\Property::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id])->id,
        ]);

        $showingB = Showing::create([
            'tenant_id' => $this->tenantB->id,
            'property_id' => $propertyB->id,
            'agent_id' => $this->userB->id,
            'showing_date' => '2026-04-15',
            'showing_time' => '14:00',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->get("/showings/{$showingB->id}");
        $response->assertStatus(404);
    }

    public function test_cannot_view_open_house_from_other_tenant(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $propertyB = \App\Models\Property::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id])->id,
        ]);

        $ohB = OpenHouse::create([
            'tenant_id' => $this->tenantB->id,
            'property_id' => $propertyB->id,
            'agent_id' => $this->userB->id,
            'event_date' => '2026-04-20',
            'start_time' => '13:00',
            'end_time' => '16:00',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->get("/open-houses/{$ohB->id}");
        $response->assertStatus(404);
    }

    public function test_cannot_modify_offer_from_other_tenant(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $leadB = Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id]);
        $dealB = Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);

        $offerB = DealOffer::create([
            'tenant_id' => $this->tenantB->id,
            'deal_id' => $dealB->id,
            'buyer_name' => 'Other Tenant Buyer',
            'offer_price' => 300000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->patchJson("/offers/{$offerB->id}", [
            'status' => 'accepted',
        ]);

        $response->assertStatus(404);
        $this->assertEquals('pending', $offerB->fresh()->status);
    }

    public function test_showings_index_excludes_other_tenant(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $propertyA = $this->createProperty();
        Showing::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $propertyA->id,
            'agent_id' => $this->adminUser->id,
            'showing_date' => '2026-04-15',
            'showing_time' => '14:00',
        ]);

        $propertyB = \App\Models\Property::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id])->id,
        ]);
        Showing::create([
            'tenant_id' => $this->tenantB->id,
            'property_id' => $propertyB->id,
            'agent_id' => $this->userB->id,
            'showing_date' => '2026-04-16',
            'showing_time' => '10:00',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->get('/showings');
        $response->assertStatus(200);
        $response->assertSee($propertyA->address);
        $response->assertDontSee($propertyB->address);
    }

    // --- Deal Document cross-tenant isolation ---

    public function test_cannot_download_deal_document_from_other_tenant(): void
    {
        $leadB = Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id]);
        $dealB = Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);

        $documentB = DealDocument::create([
            'tenant_id' => $this->tenantB->id,
            'deal_id' => $dealB->id,
            'filename' => 'secret-contract.pdf',
            'original_name' => 'secret-contract.pdf',
            'mime_type' => 'application/pdf',
            'size' => 12345,
            'path' => 'deals/' . $dealB->id . '/secret-contract.pdf',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->get("/pipeline/documents/{$documentB->id}/download");
        $response->assertStatus(404);
    }

    public function test_cannot_upload_document_to_other_tenant_deal(): void
    {
        $leadB = Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id]);
        $dealB = Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);

        $this->actingAs($this->adminUser);

        $file = \Illuminate\Http\UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');
        $response = $this->post("/pipeline/{$dealB->id}/documents", [
            'document' => $file,
        ]);

        $response->assertStatus(404);
        $this->assertEquals(0, DealDocument::withoutGlobalScopes()->where('deal_id', $dealB->id)->count());
    }

    public function test_deal_document_scoped_query_excludes_other_tenant(): void
    {
        // Create document for tenant A
        $dealA = $this->createDeal();
        DealDocument::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $dealA->id,
            'filename' => 'tenantA-doc.pdf',
            'original_name' => 'tenantA-doc.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'path' => 'deals/' . $dealA->id . '/tenantA-doc.pdf',
        ]);

        // Create document for tenant B
        $leadB = Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id]);
        $dealB = Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);
        DealDocument::create([
            'tenant_id' => $this->tenantB->id,
            'deal_id' => $dealB->id,
            'filename' => 'tenantB-doc.pdf',
            'original_name' => 'tenantB-doc.pdf',
            'mime_type' => 'application/pdf',
            'size' => 200,
            'path' => 'deals/' . $dealB->id . '/tenantB-doc.pdf',
        ]);

        $this->actingAs($this->adminUser);

        // Scoped query should only return tenant A's documents
        $documents = DealDocument::all();
        $this->assertCount(1, $documents);
        $this->assertEquals('tenantA-doc.pdf', $documents->first()->filename);
    }

    // --- Transaction Checklist cross-tenant isolation ---

    public function test_cannot_update_checklist_item_from_other_tenant(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $leadB = Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id]);
        $dealB = Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);

        $itemB = TransactionChecklist::create([
            'tenant_id' => $this->tenantB->id,
            'deal_id' => $dealB->id,
            'item_key' => 'inspection',
            'label' => 'Home Inspection',
            'status' => 'pending',
            'sort_order' => 1,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->patchJson("/checklist/{$itemB->id}", [
            'status' => 'completed',
        ]);

        $response->assertStatus(404);
        $this->assertEquals('pending', $itemB->fresh()->status);
    }

    public function test_cannot_delete_checklist_item_from_other_tenant(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $leadB = Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id]);
        $dealB = Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);

        $itemB = TransactionChecklist::create([
            'tenant_id' => $this->tenantB->id,
            'deal_id' => $dealB->id,
            'item_key' => 'title_search',
            'label' => 'Title Search & Insurance',
            'status' => 'in_progress',
            'sort_order' => 5,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->deleteJson("/checklist/{$itemB->id}");

        $response->assertStatus(404);
        $this->assertNotNull(TransactionChecklist::withoutGlobalScopes()->find($itemB->id));
    }

    public function test_cannot_add_checklist_item_to_other_tenant_deal(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $leadB = Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id]);
        $dealB = Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->postJson("/pipeline/{$dealB->id}/checklist/add", [
            'label' => 'Injected Checklist Item',
        ]);

        $response->assertStatus(404);
        $this->assertEquals(0, TransactionChecklist::withoutGlobalScopes()->where('deal_id', $dealB->id)->count());
    }

    public function test_cannot_create_checklist_on_other_tenant_deal(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $leadB = Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id]);
        $dealB = Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->post("/pipeline/{$dealB->id}/checklist");

        $response->assertStatus(404);
        $this->assertEquals(0, TransactionChecklist::withoutGlobalScopes()->where('deal_id', $dealB->id)->count());
    }

    public function test_checklist_scoped_query_excludes_other_tenant(): void
    {
        $dealA = $this->createDeal();
        TransactionChecklist::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $dealA->id,
            'item_key' => 'inspection',
            'label' => 'Home Inspection',
            'sort_order' => 1,
        ]);

        $leadB = Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id]);
        $dealB = Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);
        TransactionChecklist::create([
            'tenant_id' => $this->tenantB->id,
            'deal_id' => $dealB->id,
            'item_key' => 'appraisal',
            'label' => 'Appraisal',
            'sort_order' => 1,
        ]);

        $this->actingAs($this->adminUser);

        $items = TransactionChecklist::all();
        $this->assertCount(1, $items);
        $this->assertEquals('Home Inspection', $items->first()->label);
    }

    // --- Open House Attendee cross-tenant isolation ---

    public function test_cannot_add_attendee_to_other_tenant_open_house(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $propertyB = \App\Models\Property::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id])->id,
        ]);

        $ohB = OpenHouse::create([
            'tenant_id' => $this->tenantB->id,
            'property_id' => $propertyB->id,
            'agent_id' => $this->userB->id,
            'event_date' => '2026-04-20',
            'start_time' => '13:00',
            'end_time' => '16:00',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->postJson("/open-houses/{$ohB->id}/attendees", [
            'first_name' => 'Injected',
            'last_name' => 'Attendee',
            'email' => 'injected@example.com',
        ]);

        $response->assertStatus(404);
        $this->assertEquals(0, OpenHouseAttendee::withoutGlobalScopes()->where('open_house_id', $ohB->id)->count());
    }

    public function test_cannot_remove_attendee_from_other_tenant_open_house(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $propertyB = \App\Models\Property::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id])->id,
        ]);

        $ohB = OpenHouse::create([
            'tenant_id' => $this->tenantB->id,
            'property_id' => $propertyB->id,
            'agent_id' => $this->userB->id,
            'event_date' => '2026-04-20',
            'start_time' => '13:00',
            'end_time' => '16:00',
        ]);

        $attendeeB = OpenHouseAttendee::create([
            'tenant_id' => $this->tenantB->id,
            'open_house_id' => $ohB->id,
            'first_name' => 'Legit',
            'last_name' => 'Attendee',
            'email' => 'legit@company-b.com',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->deleteJson("/open-house-attendees/{$attendeeB->id}");

        $response->assertStatus(404);
        $this->assertNotNull(OpenHouseAttendee::withoutGlobalScopes()->find($attendeeB->id));
    }

    public function test_cannot_update_other_tenant_open_house(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $propertyB = \App\Models\Property::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id])->id,
        ]);

        $ohB = OpenHouse::create([
            'tenant_id' => $this->tenantB->id,
            'property_id' => $propertyB->id,
            'agent_id' => $this->userB->id,
            'event_date' => '2026-04-20',
            'start_time' => '13:00',
            'end_time' => '16:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->put("/open-houses/{$ohB->id}", [
            'property_id' => $propertyB->id,
            'event_date' => '2026-05-01',
            'start_time' => '10:00',
            'end_time' => '14:00',
            'status' => 'cancelled',
        ]);

        $response->assertStatus(404);
        $this->assertEquals('scheduled', $ohB->fresh()->status);
    }

    public function test_cannot_delete_other_tenant_open_house(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $propertyB = \App\Models\Property::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id])->id,
        ]);

        $ohB = OpenHouse::create([
            'tenant_id' => $this->tenantB->id,
            'property_id' => $propertyB->id,
            'agent_id' => $this->userB->id,
            'event_date' => '2026-04-20',
            'start_time' => '13:00',
            'end_time' => '16:00',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->delete("/open-houses/{$ohB->id}");

        $response->assertStatus(404);
        $this->assertNotNull(OpenHouse::withoutGlobalScopes()->find($ohB->id));
    }

    public function test_open_house_index_excludes_other_tenant(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $propertyA = $this->createProperty();
        OpenHouse::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $propertyA->id,
            'agent_id' => $this->adminUser->id,
            'event_date' => '2026-04-20',
            'start_time' => '13:00',
            'end_time' => '16:00',
        ]);

        $propertyB = \App\Models\Property::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id])->id,
        ]);
        OpenHouse::create([
            'tenant_id' => $this->tenantB->id,
            'property_id' => $propertyB->id,
            'agent_id' => $this->userB->id,
            'event_date' => '2026-04-21',
            'start_time' => '10:00',
            'end_time' => '13:00',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->get('/open-houses');
        $response->assertStatus(200);
        $response->assertSee($propertyA->address);
        $response->assertDontSee($propertyB->address);
    }

    // --- Showing cross-tenant isolation (deeper) ---

    public function test_cannot_update_showing_from_other_tenant(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $propertyB = \App\Models\Property::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id])->id,
        ]);

        $showingB = Showing::create([
            'tenant_id' => $this->tenantB->id,
            'property_id' => $propertyB->id,
            'agent_id' => $this->userB->id,
            'showing_date' => '2026-04-15',
            'showing_time' => '14:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->adminUser);

        // Create a property in tenant A to use as the property_id in the update payload
        $propertyA = $this->createProperty();

        $response = $this->put("/showings/{$showingB->id}", [
            'property_id' => $propertyA->id,
            'showing_date' => '2026-04-15',
            'showing_time' => '14:00',
            'status' => 'completed',
            'feedback' => 'Injected feedback from tenant A',
        ]);

        $response->assertStatus(404);
        $this->assertEquals('scheduled', $showingB->fresh()->status);
        $this->assertNull($showingB->fresh()->feedback);
    }

    public function test_cannot_delete_showing_from_other_tenant(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $propertyB = \App\Models\Property::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id])->id,
        ]);

        $showingB = Showing::create([
            'tenant_id' => $this->tenantB->id,
            'property_id' => $propertyB->id,
            'agent_id' => $this->userB->id,
            'showing_date' => '2026-04-15',
            'showing_time' => '14:00',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->delete("/showings/{$showingB->id}");

        $response->assertStatus(404);
        $this->assertNotNull(Showing::withoutGlobalScopes()->find($showingB->id));
    }

    public function test_cannot_update_showing_status_and_feedback_cross_tenant(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $propertyB = \App\Models\Property::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id])->id,
        ]);

        $showingB = Showing::create([
            'tenant_id' => $this->tenantB->id,
            'property_id' => $propertyB->id,
            'agent_id' => $this->userB->id,
            'showing_date' => '2026-04-15',
            'showing_time' => '14:00',
            'status' => 'scheduled',
            'feedback' => null,
            'outcome' => null,
        ]);

        $this->actingAs($this->adminUser);

        $propertyA = $this->createProperty();

        // Try to update via AJAX (the controller supports both JSON and redirect)
        $response = $this->putJson("/showings/{$showingB->id}", [
            'property_id' => $propertyA->id,
            'showing_date' => '2026-04-15',
            'showing_time' => '14:00',
            'status' => 'completed',
            'feedback' => 'Great showing, buyer very interested',
            'outcome' => 'made_offer',
        ]);

        $response->assertStatus(404);
        $fresh = $showingB->fresh();
        $this->assertEquals('scheduled', $fresh->status);
        $this->assertNull($fresh->feedback);
        $this->assertNull($fresh->outcome);
    }

    // --- Deal Offer cross-tenant isolation (deeper) ---

    public function test_cannot_accept_offer_from_other_tenant(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $leadB = Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id]);
        $dealB = Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);

        $offerB = DealOffer::create([
            'tenant_id' => $this->tenantB->id,
            'deal_id' => $dealB->id,
            'buyer_name' => 'Tenant B Buyer',
            'offer_price' => 250000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->patchJson("/offers/{$offerB->id}", [
            'status' => 'accepted',
        ]);

        $response->assertStatus(404);
        $this->assertEquals('pending', $offerB->fresh()->status);
    }

    public function test_cannot_reject_offer_from_other_tenant(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $leadB = Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id]);
        $dealB = Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);

        $offerB = DealOffer::create([
            'tenant_id' => $this->tenantB->id,
            'deal_id' => $dealB->id,
            'buyer_name' => 'Tenant B Buyer',
            'offer_price' => 275000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->patchJson("/offers/{$offerB->id}", [
            'status' => 'rejected',
            'notes' => 'Rejected by wrong tenant',
        ]);

        $response->assertStatus(404);
        $this->assertEquals('pending', $offerB->fresh()->status);
        $this->assertNull($offerB->fresh()->notes);
    }

    public function test_cannot_counter_offer_from_other_tenant(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $leadB = Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id]);
        $dealB = Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);

        $offerB = DealOffer::create([
            'tenant_id' => $this->tenantB->id,
            'deal_id' => $dealB->id,
            'buyer_name' => 'Tenant B Buyer',
            'offer_price' => 200000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->patchJson("/offers/{$offerB->id}", [
            'status' => 'countered',
            'counter_price' => 225000,
        ]);

        $response->assertStatus(404);
        $this->assertEquals('pending', $offerB->fresh()->status);
        $this->assertNull($offerB->fresh()->counter_price);
    }

    public function test_cannot_delete_offer_from_other_tenant(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $leadB = Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id]);
        $dealB = Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);

        $offerB = DealOffer::create([
            'tenant_id' => $this->tenantB->id,
            'deal_id' => $dealB->id,
            'buyer_name' => 'Tenant B Buyer',
            'offer_price' => 300000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->deleteJson("/offers/{$offerB->id}");

        $response->assertStatus(404);
        $this->assertNotNull(DealOffer::withoutGlobalScopes()->find($offerB->id));
    }

    public function test_cannot_create_offer_on_other_tenant_deal(): void
    {
        $this->tenant->update(['business_mode' => 'realestate']);
        $this->tenantB->update(['business_mode' => 'realestate']);

        $leadB = Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id]);
        $dealB = Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->post("/pipeline/{$dealB->id}/offers", [
            'buyer_name' => 'Injected Buyer',
            'offer_price' => 500000,
        ]);

        $response->assertStatus(404);
        $this->assertEquals(0, DealOffer::withoutGlobalScopes()->where('deal_id', $dealB->id)->count());
    }

    public function test_offer_scoped_query_excludes_other_tenant(): void
    {
        $dealA = $this->createDeal();
        DealOffer::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $dealA->id,
            'buyer_name' => 'Tenant A Buyer',
            'offer_price' => 150000,
            'status' => 'pending',
        ]);

        $leadB = Lead::factory()->create(['tenant_id' => $this->tenantB->id, 'agent_id' => $this->userB->id]);
        $dealB = Deal::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'lead_id' => $leadB->id,
            'agent_id' => $this->userB->id,
        ]);
        DealOffer::create([
            'tenant_id' => $this->tenantB->id,
            'deal_id' => $dealB->id,
            'buyer_name' => 'Tenant B Buyer',
            'offer_price' => 350000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser);

        $offers = DealOffer::all();
        $this->assertCount(1, $offers);
        $this->assertEquals('Tenant A Buyer', $offers->first()->buyer_name);
    }
}
