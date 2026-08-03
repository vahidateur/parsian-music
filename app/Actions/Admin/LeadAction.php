<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\LeadPriorityEnum;
use App\Enums\LeadStatusEnum;
use App\Models\Lead;
use App\Support\PersianTextNormalizer;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Lead mutations other than conversion.
 *
 * A new lead always starts in the `New` status with a default priority, and every
 * later status move goes through the enum state machine — never through the form.
 * Conversion stays owned by LeadService because it creates a student, an optional
 * enrollment and the link back to the lead in one transaction.
 *
 * Requirements: 6.4, 6.6, 6.9, 6.10, 6.13, 16.3
 */
final class LeadAction
{
    /**
     * Canonical form of every persisted text field, shared with LeadRequest.
     *
     * @var array<string, string>
     */
    public const NORMALIZED_FIELDS = [
        'full_name' => PersianTextNormalizer::TEXT,
        'phone' => PersianTextNormalizer::TEXT,
        'email' => PersianTextNormalizer::TEXT,
        'notes' => PersianTextNormalizer::MULTILINE,
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Lead
    {
        $data = PersianTextNormalizer::fields($data, self::NORMALIZED_FIELDS);

        $data['status'] = LeadStatusEnum::New->value;
        $data['priority'] = $data['priority'] ?? LeadPriorityEnum::Medium->value;

        return Lead::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Lead $lead, array $data): Lead
    {
        $lead->update(PersianTextNormalizer::fields($data, self::NORMALIZED_FIELDS));

        return $lead;
    }

    public function delete(Lead $lead): void
    {
        DB::transaction(static function () use ($lead): void {
            $lead->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assign(Lead $lead, array $data): Lead
    {
        $lead->update(['assigned_to' => $data['assigned_to'] ?? null]);

        return $lead;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function scheduleFollowUp(Lead $lead, array $data): Lead
    {
        $lead->update(['next_follow_up_at' => $data['next_follow_up_at']]);

        return $lead;
    }

    /**
     * @throws DomainException when the transition is rejected by the state machine.
     */
    public function changeStatus(Lead $lead, string $status): Lead
    {
        DB::transaction(static function () use ($lead, $status): void {
            $lead->transitionTo(LeadStatusEnum::from($status));
        });

        return $lead;
    }
}
