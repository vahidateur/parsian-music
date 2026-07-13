<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Enums\LeadStatusEnum;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Validates: Requirements 12
 * Overdue Indicator Accuracy Property Test
 * Verifies overdue indicator appears only when isOverdue() true and status not terminal.
 */
class LeadOverdueIndicatorTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => \App\Enums\RoleEnum::ADMIN->value,
            'is_active' => true,
        ]);
    }

    public function test_index_view_shows_overdue_row_styling_for_past_followup(): void
    {
        // Create a lead with past follow-up date
        $lead = Lead::factory()->create([
            'next_follow_up_at' => Carbon::now()->subDays(1),
            'status' => LeadStatusEnum::New,
        ]);

        // Verify the lead is marked as overdue
        $this->assertTrue($lead->isOverdue());

        // Test the index page
        $response = $this->actingAs($this->admin)->get(route('admin.leads.index'));
        $response->assertStatus(200);
        $response->assertSee('bg-rose-500/[0.03]');
    }

    public function test_index_view_hides_overdue_styling_for_future_followup(): void
    {
        $lead = Lead::factory()->create([
            'next_follow_up_at' => Carbon::now()->addDays(1),
            'status' => LeadStatusEnum::New,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.leads.index'));
        $response->assertStatus(200);

        // Future followup should not have overdue styling
        $html = $response->getContent();
        $this->assertStringNotContainsString('bg-rose-500/[0.03]', $html);
    }

    public function test_index_view_hides_overdue_for_terminal_status_registered(): void
    {
        $lead = Lead::factory()->create([
            'next_follow_up_at' => Carbon::now()->subDays(1),
            'status' => LeadStatusEnum::Registered,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.leads.index'));
        $response->assertStatus(200);

        // Terminal status should not show overdue styling even if past
        $html = $response->getContent();
        // Count occurrences - should not have overdue styling class for this lead
        $this->assertLessThanOrEqual(0, substr_count($html, 'bg-rose-500/[0.03]'));
    }

    public function test_index_view_hides_overdue_for_terminal_status_lost(): void
    {
        $lead = Lead::factory()->create([
            'next_follow_up_at' => Carbon::now()->subDays(1),
            'status' => LeadStatusEnum::Lost,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.leads.index'));
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertLessThanOrEqual(0, substr_count($html, 'bg-rose-500/[0.03]'));
    }

    public function test_kanban_view_shows_overdue_label_for_past_followup(): void
    {
        $lead = Lead::factory()->create([
            'next_follow_up_at' => Carbon::now()->subDays(1),
            'status' => LeadStatusEnum::New,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.leads.kanban'));
        $response->assertStatus(200);
        $response->assertSee('text-rose-400');
        $response->assertSee('دیرکرد');
    }

    public function test_kanban_view_hides_overdue_label_for_future_followup(): void
    {
        $lead = Lead::factory()->create([
            'next_follow_up_at' => Carbon::now()->addDays(1),
            'status' => LeadStatusEnum::New,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.leads.kanban'));
        $response->assertStatus(200);

        // Should not show overdue text in this specific card
        $html = $response->getContent();
        // The overdue text should not appear for future dates
        $this->assertLessThanOrEqual(0, substr_count($html, 'دیرکرد'));
    }

    public function test_kanban_view_hides_overdue_for_terminal_status(): void
    {
        $lead = Lead::factory()->create([
            'next_follow_up_at' => Carbon::now()->subDays(1),
            'status' => LeadStatusEnum::Registered,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.leads.kanban'));
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertLessThanOrEqual(0, substr_count($html, 'دیرکرد'));
    }

    public function test_show_page_displays_overdue_suffix_for_past_followup(): void
    {
        $lead = Lead::factory()->create([
            'next_follow_up_at' => Carbon::now()->subDays(1),
            'status' => LeadStatusEnum::New,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.leads.show', $lead));
        $response->assertStatus(200);
        $response->assertSee('text-rose-400');
        // Should show the overdue indicator
        $response->assertSee('دیرکرد');
    }

    public function test_show_page_hides_overdue_suffix_for_future_followup(): void
    {
        $lead = Lead::factory()->create([
            'next_follow_up_at' => Carbon::now()->addDays(1),
            'status' => LeadStatusEnum::New,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.leads.show', $lead));
        $response->assertStatus(200);

        $html = $response->getContent();
        // Should not contain overdue suffix for future dates
        $countOverdue = preg_match_all('/دیرکرد/', $html);
        $this->assertEquals(0, $countOverdue, 'Overdue suffix should not appear for future followup dates');
    }

    public function test_show_page_hides_overdue_for_terminal_status_registered(): void
    {
        $lead = Lead::factory()->create([
            'next_follow_up_at' => Carbon::now()->subDays(1),
            'status' => LeadStatusEnum::Registered,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.leads.show', $lead));
        $response->assertStatus(200);

        $html = $response->getContent();
        $countOverdue = preg_match_all('/دیرکرد/', $html);
        $this->assertEquals(0, $countOverdue, 'Overdue suffix should not appear for terminal status');
    }

    public function test_show_page_hides_overdue_for_terminal_status_lost(): void
    {
        $lead = Lead::factory()->create([
            'next_follow_up_at' => Carbon::now()->subDays(1),
            'status' => LeadStatusEnum::Lost,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.leads.show', $lead));
        $response->assertStatus(200);

        $html = $response->getContent();
        $countOverdue = preg_match_all('/دیرکرد/', $html);
        $this->assertEquals(0, $countOverdue, 'Overdue suffix should not appear for terminal status');
    }
}
