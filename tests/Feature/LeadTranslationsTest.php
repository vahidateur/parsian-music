<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

/**
 * Validates: Requirements 1
 * Translation Completeness Property Test
 * Verifies every translation key referenced in lead views resolves to a non-empty
 * localized Persian value through the application translator — not to the raw key.
 */
class LeadTranslationsTest extends TestCase
{
    /** Raw source of the fa admin lang file, used for duplicate-key detection. */
    protected string $source;

    protected array $requiredKeys = [
        'leads', 'manage_leads', 'new_lead', 'kanban_view', 'list_view',
        'leads_kanban', 'kanban_subtitle', 'lead_information', 'lead_timeline',
        'lead_created', 'lead_last_updated', 'lead_converted', 'back_to_leads',
        'edit_lead', 'delete_lead_confirm', 'lead_created_successfully',
        'lead_updated_successfully', 'lead_deleted_successfully', 'lead_assigned_successfully',
        'lead_followup_scheduled_successfully', 'lead_status_updated_successfully',
        'lead_converted_successfully', 'create_lead', 'update_lead', 'register_lead_desc',
        'update_lead_desc', 'lead_full_name_placeholder', 'update_status', 'assign_lead',
        'unassigned', 'schedule_follow_up', 'convert_lead', 'convert_enrollment_hint',
        'overdue', 'no_leads_found', 'no_leads_in_column', 'all_priorities', 'all_sources',
        'all_admins', 'assigned_admin', 'preferred_instrument', 'preferred_teacher',
        'next_follow_up', 'source', 'priority', 'email', 'age',
        'history_lead_created_desc', 'history_lead_status_desc', 'view_student', 'start_date',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = file_get_contents(lang_path('fa/admin.php'));
    }

    /** Every required key must be registered for the fa locale. */
    public function test_all_required_lead_translation_keys_exist(): void
    {
        foreach ($this->requiredKeys as $key) {
            $this->assertTrue(
                Lang::has('admin.' . $key, 'fa'),
                "Translation key 'admin.$key' is missing from lang/fa/admin.php"
            );
        }
    }

    /**
     * The translator must return the LOCALIZED value, never the raw key.
     * A view rendering __('admin.overdue') must not output 'admin.overdue'.
     */
    public function test_all_required_keys_resolve_to_localized_values(): void
    {
        foreach ($this->requiredKeys as $key) {
            $value = trans('admin.' . $key, [], 'fa');

            $this->assertIsString(
                $value,
                "Translation key 'admin.$key' must resolve to a string, got " . gettype($value)
            );
            $this->assertNotSame(
                'admin.' . $key,
                $value,
                "Translation key 'admin.$key' resolves to the raw key instead of a localized value"
            );
            $this->assertNotEmpty(
                trim($value),
                "Translation key 'admin.$key' has an empty localized value"
            );
        }
    }

    /**
     * The same top-level key declared twice is silently overwritten by PHP, so the
     * loaded array can never reveal it. Duplicates are detected in the file source.
     */
    public function test_no_duplicate_keys_in_translation_file(): void
    {
        foreach ($this->requiredKeys as $key) {
            // Top-level keys are indented with exactly four spaces; nested keys use more.
            $occurrences = preg_match_all(
                '/^ {4}\'' . preg_quote($key, '/') . '\'\s*=>/m',
                $this->source
            );

            $this->assertSame(
                1,
                $occurrences,
                "Key '$key' must be declared exactly once at top level, found {$occurrences}"
            );
        }
    }

    /** Localized values must be Persian text (or carry placeholder variables). */
    public function test_translation_values_are_persian_text(): void
    {
        $persianRange = '/[\x{0600}-\x{06FF}]+/u';

        foreach ($this->requiredKeys as $key) {
            $value = (string) trans('admin.' . $key, [], 'fa');

            $this->assertTrue(
                (bool) preg_match($persianRange, $value) || str_contains($value, ':'),
                "Translation key 'admin.$key' does not contain Persian text or placeholder variables"
            );
        }
    }
}
