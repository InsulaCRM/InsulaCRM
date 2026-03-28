<?php

namespace Tests\Feature;

use App\Models\Tag;
use Tests\TestCase;

class TagTest extends TestCase
{
    public function test_tags_page_loads(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('tags.index'));

        $response->assertStatus(200);
        $response->assertSee('Tag Management');
    }

    public function test_create_tag(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('tags.store'), [
            'name' => 'VIP',
            'color' => 'red',
        ]);

        $response->assertRedirect(route('tags.index'));
        $this->assertDatabaseHas('tags', [
            'tenant_id' => $this->tenant->id,
            'name' => 'VIP',
            'color' => 'red',
        ]);
    }

    public function test_delete_tag(): void
    {
        $this->actingAsAdmin();

        $tag = Tag::create(['tenant_id' => $this->tenant->id, 'name' => 'Temp', 'color' => 'blue']);

        $response = $this->delete(route('tags.destroy', $tag));

        $response->assertRedirect(route('tags.index'));
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_attach_tag_to_lead(): void
    {
        $this->actingAsAdmin();

        $tag = Tag::create(['tenant_id' => $this->tenant->id, 'name' => 'Hot', 'color' => 'red']);
        $lead = $this->createLead();

        $response = $this->postJson(route('tags.attach'), [
            'tag_id' => $tag->id,
            'taggable_type' => 'lead',
            'taggable_id' => $lead->id,
        ]);

        $response->assertJson(['success' => true]);
        $this->assertTrue($lead->tags->contains($tag));
    }

    public function test_detach_tag_from_lead(): void
    {
        $this->actingAsAdmin();

        $tag = Tag::create(['tenant_id' => $this->tenant->id, 'name' => 'Hot', 'color' => 'red']);
        $lead = $this->createLead();
        $lead->tags()->attach($tag->id);

        $response = $this->postJson(route('tags.detach'), [
            'tag_id' => $tag->id,
            'taggable_type' => 'lead',
            'taggable_id' => $lead->id,
        ]);

        $response->assertJson(['success' => true]);
        $this->assertFalse($lead->fresh()->tags->contains($tag));
    }

    public function test_non_admin_cannot_manage_tags(): void
    {
        $this->actingAsRole('agent');

        $response = $this->get(route('tags.index'));
        $response->assertStatus(403);
    }

    public function test_duplicate_tag_name_reuses_existing(): void
    {
        $this->actingAsAdmin();

        Tag::create(['tenant_id' => $this->tenant->id, 'name' => 'Dupe', 'color' => 'blue']);

        $response = $this->post(route('tags.store'), [
            'name' => 'Dupe',
            'color' => 'green',
        ]);

        $this->assertEquals(1, Tag::where('name', 'Dupe')->count());
    }
}
