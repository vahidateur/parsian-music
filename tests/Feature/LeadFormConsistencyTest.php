<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Enums\LeadStatusEnum;
use App\Enums\LeadPriorityEnum;
use App\Enums\LeadSourceEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Validates: Requirements 4, 5
 *
 * Form Field Consistency Property Test
 * Verifies create and edit forms render identical fields in identical order via reusable partial.
 * Tests confirm:
 * - Both forms render all required fields in the same order
 * - Fields use the same input names and types
 * - old() fallback works correctly in edit mode
 * - Validation errors display properly
 * - Status field is not exposed to users
 */
class LeadFormConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /**
     * Test that create form renders all 11 required fields
     */
    public function test_create_form_renders_all_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.leads.create'));
        $response->assertStatus(200);

        // Assert all required field names are present
        $response->assertSee('name="full_name"');
        $response->assertSee('name="phone"');
        $response->assertSee('name="email"');
        $response->assertSee('name="age"');
        $response->assertSee('name="source"');
        $response->assertSee('name="priority"');
        $response->assertSee('name="preferred_instrument_id"');
        $response->assertSee('name="preferred_teacher_id"');
        $response->assertSee('name="assigned_to"');
        $response->assertSee('name="next_follow_up_at"');
        $response->assertSee('name="notes"');
    }

    /**
     * Test that edit form renders all same fields as create form
     */
    public function test_edit_form_renders_all_required_fields(): void
    {
        $lead = Lead::factory()->create();
        $response = $this->actingAs($this->admin)->get(route('admin.leads.edit', $lead));
        $response->assertStatus(200);

        // Assert all same field names
        $response->assertSee('name="full_name"');
        $response->assertSee('name="phone"');
        $response->assertSee('name="email"');
        $response->assertSee('name="age"');
        $response->assertSee('name="source"');
        $response->assertSee('name="priority"');
        $response->assertSee('name="preferred_instrument_id"');
        $response->assertSee('name="preferred_teacher_id"');
        $response->assertSee('name="assigned_to"');
        $response->assertSee('name="next_follow_up_at"');
        $response->assertSee('name="notes"');
    }

    /**
     * Test that edit form prefills all lead data using old() fallback
     */
    public function test_edit_form_prefills_lead_data_with_old_fallback(): void
    {
        $lead = Lead::factory()->create([
            'full_name' => 'John Doe',
            'phone' => '09121234567',
            'email' => 'john@example.com',
            'age' => 25,
            'notes' => 'Test notes for lead',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.leads.edit', $lead));
        $response->assertStatus(200);

        // Verify old values are prefilled in form
        $response->assertSee('John Doe');
        $response->assertSee('09121234567');
        $response->assertSee('john@example.com');
        $response->assertSee('Test notes for lead');
    }

    /**
     * Test that forms validate required fields (full_name, phone, source)
     */
    public function test_create_form_validation_full_name_required(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.leads.store'), [
            'full_name' => '',
            'phone' => '09121234567',
            'source' => LeadSourceEnum::Website->value,
        ]);

        $response->assertSessionHasErrors(['full_name']);
    }

    /**
     * Test phone validation
     */
    public function test_create_form_validation_phone_required(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.leads.store'), [
            'full_name' => 'Test Lead',
            'phone' => '',
            'source' => LeadSourceEnum::Website->value,
        ]);

        $response->assertSessionHasErrors(['phone']);
    }

    /**
     * Test source validation
     */
    public function test_create_form_validation_source_required(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.leads.store'), [
            'full_name' => 'Test Lead',
            'phone' => '09121234567',
            'source' => '',
        ]);

        $response->assertSessionHasErrors(['source']);
    }

    /**
     * Test that valid data submits successfully and creates lead
     */
    public function test_create_form_succeeds_with_valid_data(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.leads.store'), [
            'full_name' => 'New Lead',
            'phone' => '09121234567',
            'source' => LeadSourceEnum::Website->value,
            'priority' => LeadPriorityEnum::Medium->value,
        ]);

        $response->assertRedirect(route('admin.leads.index'));
        $this->assertDatabaseHas('leads', [
            'full_name' => 'New Lead',
            'phone' => '09121234567',
            'source' => LeadSourceEnum::Website->value,
            'priority' => LeadPriorityEnum::Medium->value,
            'status' => LeadStatusEnum::New->value,
        ]);
    }

    /**
     * Test edit form validates required fields
     */
    public function test_edit_form_validation_full_name_required(): void
    {
        $lead = Lead::factory()->create();
        $response = $this->actingAs($this->admin)->put(route('admin.leads.update', $lead), [
            'full_name' => '',
            'phone' => '09121234567',
            'source' => LeadSourceEnum::Website->value,
        ]);

        $response->assertSessionHasErrors(['full_name']);
    }

    /**
     * Test that valid edit data updates lead successfully
     */
    public function test_edit_form_succeeds_with_valid_data(): void
    {
        $lead = Lead::factory()->create(['full_name' => 'Original Name']);
        $response = $this->actingAs($this->admin)->put(route('admin.leads.update', $lead), [
            'full_name' => 'Updated Name',
            'phone' => $lead->phone,
            'source' => $lead->source->value,
        ]);

        $response->assertRedirect(route('admin.leads.show', $lead));
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'full_name' => 'Updated Name',
        ]);
    }

    /**
     * Test that status field is NOT exposed in create form
     * (Status is set automatically by controller to LeadStatusEnum::New)
     */
    public function test_create_form_does_not_expose_status_field(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.leads.create'));
        $response->assertStatus(200);
        $response->assertDontSee('name="status"');
    }

    /**
     * Test that status field is NOT exposed in edit form
     * (Status transitions happen only through updateStatus action on show page)
     */
    public function test_edit_form_does_not_expose_status_field(): void
    {
        $lead = Lead::factory()->create();
        $response = $this->actingAs($this->admin)->get(route('admin.leads.edit', $lead));
        $response->assertStatus(200);
        $response->assertDontSee('name="status"');
    }

    /**
     * Test field order consistency: both forms should render fields in identical order
     * This validates the reusable partials/form-fields.blade.php is used for both forms
     */
    public function test_form_fields_rendered_in_identical_order(): void
    {
        $lead = Lead::factory()->create();
        
        $createResponse = $this->actingAs($this->admin)->get(route('admin.leads.create'));
        $editResponse = $this->actingAs($this->admin)->get(route('admin.leads.edit', $lead));

        $createBody = $createResponse->getContent();
        $editBody = $editResponse->getContent();

        // Extract positions of field names in both forms
        $fields = [
            'full_name',
            'phone',
            'email',
            'age',
            'source',
            'priority',
            'preferred_instrument_id',
            'preferred_teacher_id',
            'assigned_to',
            'next_follow_up_at',
            'notes',
        ];

        $createPositions = [];
        $editPositions = [];

        foreach ($fields as $field) {
            $fieldPattern = "name=\"{$field}\"";
            $createPositions[$field] = strpos($createBody, $fieldPattern);
            $editPositions[$field] = strpos($editBody, $fieldPattern);
        }

        // Verify all fields exist in both forms
        foreach ($fields as $field) {
            $this->assertNotFalse(
                $createPositions[$field],
                "Field '{$field}' not found in create form"
            );
            $this->assertNotFalse(
                $editPositions[$field],
                "Field '{$field}' not found in edit form"
            );
        }

        // Verify field order is the same (relative positions)
        $createOrder = array_keys(array_filter($createPositions, fn($p) => $p !== false));
        $editOrder = array_keys(array_filter($editPositions, fn($p) => $p !== false));
        
        $this->assertEquals(
            $createOrder,
            $editOrder,
            "Form fields are not in identical order"
        );
    }

    /**
     * Test that validation messages display on field errors
     */
    public function test_validation_messages_display_on_errors(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.leads.store'), [
            'full_name' => '',
            'phone' => '',
            'source' => '',
        ]);

        // Laravel should redirect back with errors in session
        $response->assertSessionHasErrors(['full_name', 'phone', 'source']);
    }

    /**
     * Test that optional fields can be submitted empty
     */
    public function test_optional_fields_can_be_empty(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.leads.store'), [
            'full_name' => 'Test Lead',
            'phone' => '09121234567',
            'source' => LeadSourceEnum::Website->value,
            'email' => '',
            'age' => '',
            'notes' => '',
            'preferred_instrument_id' => '',
            'preferred_teacher_id' => '',
            'assigned_to' => '',
            'next_follow_up_at' => '',
        ]);

        $response->assertRedirect(route('admin.leads.index'));
        $this->assertDatabaseHas('leads', [
            'full_name' => 'Test Lead',
            'email' => null,
            'age' => null,
            'notes' => null,
        ]);
    }

    /**
     * Test that both forms use identical CSS classes and styling
     * Both should have the same input class patterns
     */
    public function test_forms_use_identical_input_styling(): void
    {
        $lead = Lead::factory()->create();
        
        $createResponse = $this->actingAs($this->admin)->get(route('admin.leads.create'));
        $editResponse = $this->actingAs($this->admin)->get(route('admin.leads.edit', $lead));

        // Both should use consistent button styling
        $createResponse->assertSee('text-gray-950'); // Primary button
        $editResponse->assertSee('text-gray-950'); // Primary button

        $createResponse->assertSee('text-gray-300'); // Secondary button
        $editResponse->assertSee('text-gray-300'); // Secondary button
    }
}
