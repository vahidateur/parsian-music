<?php

namespace App\Services;

use App\DTOs\ConvertLeadData;
use App\Enums\EnrollmentStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\StudentStatusEnum;
use App\Models\Lead;
use App\Models\Student;
use App\Models\StudentEnrollment;
use DomainException;
use Illuminate\Support\Facades\DB;

class LeadService
{
    /**
     * Convert a Lead into a Student.
     *
     * Full rollback on any failure — the lead record is never left in a
     * partially-converted state.
     *
     * @throws DomainException if the lead is already converted or the
     *                         status transition is invalid.
     */
    public function convert(Lead $lead, ConvertLeadData $data): Student
    {
        $this->assertConvertible($lead);

        return DB::transaction(function () use ($lead, $data): Student {

            // ── 1. Create Student ─────────────────────────────────────────
            $notes = collect([$lead->notes, $data->notes])
                ->filter()
                ->implode(' | ');

            $student = Student::create([
                'full_name' => $lead->full_name,
                'phone'     => $lead->phone,
                'status'    => StudentStatusEnum::Active,
                'join_date' => now()->toDateString(),
                'notes'     => $notes ?: null,
            ]);

            // ── 2. Create Enrollment (optional) ───────────────────────────
            if ($data->shouldCreateEnrollment() && $lead->preferred_instrument_id && $lead->preferred_teacher_id) {
                StudentEnrollment::create([
                    'student_id'    => $student->id,
                    'instrument_id' => $lead->preferred_instrument_id,
                    'teacher_id'    => $lead->preferred_teacher_id,   // nullable
                    'skill_level'   => $data->skillLevel,
                    'status'        => EnrollmentStatusEnum::Active,
                    'started_at'    => $data->startDate ?? now(),
                ]);
            }

            // ── 3. Mark Lead as Registered ────────────────────────────────
            $lead->transitionTo(LeadStatusEnum::Registered);

            // ── 4. Link Lead → Student ────────────────────────────────────
            $lead->update([
                'converted_student_id' => $student->id,
                'converted_at'         => now(),
            ]);

            // ── Extension points (no-ops until wired in future sprints) ──
            $this->createFirstInvoice($student, $lead);
            $this->scheduleTrial($student, $lead);
            $this->sendWelcomeNotification($student);

            return $student->fresh();
        });
    }

    // ── Extension points (stubs) ─────────────────────────────────────────────

    /**
     * Create the first invoice for the newly converted student.
     * Wire to InvoiceService::createDraft() with the enrollment monthly fee as a line item.
     */
    private function createFirstInvoice(Student $student, Lead $lead): void {}

    /**
     * Schedule a trial session for the student.
     * Wire to SessionGeneratorService using lead's preferred teacher + instrument.
     */
    private function scheduleTrial(Student $student, Lead $lead): void {}

    /**
     * Send a welcome notification via the configured channels.
     * Wire to NotificationService::notify() with NotificationEventEnum::StudentCreated.
     */
    private function sendWelcomeNotification(Student $student): void {}

    // ── Guards ───────────────────────────────────────────────────────────────

    private function assertConvertible(Lead $lead): void
    {
        if ($lead->isConverted()) {
            throw new DomainException(
                "Lead #{$lead->id} ({$lead->full_name}) has already been converted to " .
                "Student #{$lead->converted_student_id}."
            );
        }

        if ($lead->status->isTerminal() && $lead->status !== LeadStatusEnum::TrialScheduled) {
            throw new DomainException(
                "Lead #{$lead->id} has terminal status '{$lead->status->label()}' and cannot be converted."
            );
        }

        if (! $lead->status->canTransitionTo(LeadStatusEnum::Registered)) {
            throw new DomainException(
                "Lead #{$lead->id} must reach 'TrialScheduled' before conversion. " .
                "Current status: '{$lead->status->label()}'."
            );
        }
    }
}
