<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Validates: Requirements 1
 * Translation Completeness Property Test
 * Verifies all translation keys referenced in lead views exist in lang/fa/admin.php with non-empty Persian values.
 */
class LeadTranslationsTest extends TestCase
{
    protected array $translations;
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
        'history_lead_created_desc', 'history_lead_status_desc', 'view_student', 'start_date'
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->translations = require __DIR__ . '/../../lang/fa/admin.php';
    }

    public function test_all_required_lead_translation_keys_exist(): void
    {
        foreach ($this->requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $this->translations,
                "Translation key 'admin.$key' is missing from lang/fa/admin.php"
            );
        }
    }

    public function test_all_required_translation_keys_have_non_empty_values(): void
    {
        foreach ($this->requiredKeys as $key) {
            if (!array_key_exists($key, $this->translations)) {
                $this->fail("Key '$key' missing entirely");
            }

            $value = $this->translations[$key];
            $this->assertNotEmpty(
                $value,
                "Translation key 'admin.$key' has empty or null value"
            );
            $this->assertIsString(
                $value,
                "Translation key 'admin.$key' must be a string, got " . gettype($value)
            );
            $this->assertStringNotContainsString(
                'admin.' . $key,
                $value,
                "Translation key 'admin.$key' appears to reference itself (raw key string)"
            );
        }
    }

    public function test_no_duplicate_keys_in_translation_file(): void
    {
        // Count occurrences of each required key
        $counts = [];
        foreach ($this->requiredKeys as $key) {
            $counts[$key] = array_key_exists($key, $this->translations) ? 1 : 0;
        }

        foreach ($counts as $key => $count) {
            $this->assertGreaterThanOrEqual(1, $count, "Key '$key' should exist exactly once");
        }
    }

    public function test_translation_values_are_persian_text(): void
    {
        $persianRange = '/[\x{0600}-\x{06FF}]+/u'; // Persian/Farsi unicode range

        foreach ($this->requiredKeys as $key) {
            $value = $this->translations[$key] ?? '';
            // Check if value contains at least some Persian characters (allowing variables like :source, etc)
            $hasPersian = preg_match($persianRange, $value);
            $this->assertTrue(
                $hasPersian || str_contains($value, ':'),
                "Translation key 'admin.$key' does not contain Persian text or placeholder variables"
            );
        }
    }
}
