<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling;

use App\DTOs\SessionDisplayData;
use App\DTOs\SessionEditResource;
use App\DTOs\SessionEditViewData;
use App\Domain\Scheduling\AvailabilityResult;
use App\Domain\Scheduling\AvailabilityState;
use App\Domain\Scheduling\RelationPath;
use App\Domain\Scheduling\RelationPathType;
use App\Domain\Scheduling\ScheduleProposalNormalizer;
use App\Domain\Scheduling\SchedulingDecisionCode;
use App\Domain\Scheduling\SchedulingDomain;
use App\Domain\Scheduling\SchedulingValidationException;
use App\Domain\Scheduling\SessionVersion;
use App\Enums\SessionStatusEnum;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Services\RelationPathResolver;
use DateTimeZone;
use InvalidArgumentException;
use Tests\TestCase;

final class SchedulingDomainTest extends TestCase
{
    public function test_normalizes_direct_proposals_and_exposes_the_exact_available_state(): void
    {
        $proposal = $this->domain()->normalize($this->input(['student_id' => 12, 'teacher_id' => 13, 'instrument_id' => 14]), $this->directPath(), $this->timezone());

        $this->assertSame(12, $proposal->relationPath->studentId);
        $this->assertSame('2026-08-04', $proposal->timeRange->start->format('Y-m-d'));
        $this->assertSame(AvailabilityState::Available, AvailabilityResult::available($proposal)->state);
        $this->assertSame('AVAILABLE', AvailabilityResult::available($proposal)->jsonSerialize()['state']);
    }

    public function test_availability_results_only_allow_stable_codes_for_their_single_state(): void
    {
        $proposal = $this->domain()->normalize($this->input(), $this->directPath(), $this->timezone());

        $this->assertSame(SchedulingDecisionCode::Conflict, AvailabilityResult::conflict($proposal, ['resource' => 'teacher'])->code);
        $this->assertSame(SchedulingDecisionCode::HardConstraint, AvailabilityResult::invalid($proposal, [], SchedulingDecisionCode::HardConstraint)->code);

        $this->expectException(InvalidArgumentException::class);
        new AvailabilityResult(AvailabilityState::Available, $proposal, SchedulingDecisionCode::Conflict);
    }

    public function test_protected_scheduling_fields_are_rejected_before_any_domain_path_can_continue(): void
    {
        foreach ([
            'enrollment_id', 'session_fee', 'discount', 'invoice_id', 'payment_id', 'subscription_id',
            'recurring_schedule_id', 'recurrence_identity', 'occurrence_identity', 'recurrence_scope',
            'teacher_code', 'student_code', 'business_code',
        ] as $field) {
            try {
                $this->domain()->normalize($this->input([$field => 'forbidden']), $this->directPath(), $this->timezone());
                $this->fail("$field must be rejected.");
            } catch (SchedulingValidationException $exception) {
                $this->assertSame('protected_field', $exception->errors[$field]);
            }
        }
    }

    public function test_malformed_versions_and_override_instructions_are_rejected_with_field_errors(): void
    {
        foreach ([
            ['session_id' => 20, 'session_version' => []],
            ['override' => ['confirmed' => 'true', 'reason' => 'Urgent']],
            ['override' => ['confirmed' => true, 'reason' => '<b>Urgent</b>']],
        ] as $input) {
            try {
                $this->domain()->normalize($this->input($input), $this->directPath(), $this->timezone());
                $this->fail('Malformed scheduling input must be rejected.');
            } catch (SchedulingValidationException $exception) {
                $this->assertNotEmpty($exception->errors);
            }
        }
    }

    public function test_enrollment_paths_reject_relation_mixing_while_direct_paths_can_change_the_complete_tuple(): void
    {
        $enrollment = new RelationPath(RelationPathType::Enrollment, 31, 2, 3, 4);
        try {
            $this->domain()->normalize($this->input(['student_id' => 9]), $enrollment, $this->timezone());
            $this->fail('An enrollment path must not accept a mixed tuple.');
        } catch (SchedulingValidationException $exception) {
            $this->assertSame('relation_conflict', $exception->errors['student_id']);
        }

        $direct = $this->domain()->normalize($this->input(['student_id' => 9, 'teacher_id' => 8, 'instrument_id' => 7]), $this->directPath(), $this->timezone());
        $this->assertTrue($direct->relationPath->hasTuple(9, 8, 7));
    }

    public function test_legacy_session_contracts_map_without_changing_their_public_fields(): void
    {
        $resource = new SessionEditResource(20, 2, 3, 4, '2026-08-04', '14:30', 60, 'scheduled', 'Studio', 'Scales', [
            'path_type' => 'direct', 'enrollment_id' => null, 'student_id' => 2, 'teacher_id' => 3, 'instrument_id' => 4,
        ], updated_at: 'version-20');
        $view = new SessionEditViewData(20, [
            'student_id' => 2, 'teacher_id' => 3, 'instrument_id' => 4, 'session_date' => '2026-08-04', 'start_time' => '14:30',
            'duration_minutes' => 60, 'status' => 'scheduled', 'room' => 'Studio', 'notes' => 'Scales', 'updated_at' => 'version-20',
        ], ['relation' => $resource->relation]);
        $display = new SessionDisplayData(20, 'Student', 'Teacher', 'Piano', '2026-08-04', 'Label', '14:30', 60, 'scheduled', 'Scheduled', 'Studio');

        $domain = $this->domain();
        $this->assertSame('version-20', $domain->fromSessionEditResource($resource, $this->timezone())->sessionVersion?->value);
        $this->assertSame('Scales', $domain->fromSessionEditViewData($view, $this->timezone())->notes);
        $this->assertSame(20, $domain->fromSessionDisplayData($display, $this->directPath(), new SessionVersion('version-20'), $this->timezone())->sessionId);
    }

    public function test_class_session_mapping_uses_the_existing_single_relation_path_resolver_for_direct_and_enrollment_sessions(): void
    {
        [$student, $teacher, $instrument] = $this->relationModels();
        $direct = ClassSession::make(['student_id' => 2, 'teacher_id' => 3, 'instrument_id' => 4]);
        $direct->setAttribute('id', 20);
        $direct->setRelation('enrollment', null);
        $direct->setRelation('student', $student);
        $direct->setRelation('teacher', $teacher);
        $direct->setRelation('instrument', $instrument);

        $enrollment = StudentEnrollment::make(['student_id' => 2, 'teacher_id' => 3, 'instrument_id' => 4]);
        $enrollment->setAttribute('id', 31);
        $enrollment->setRelation('student', $student);
        $enrollment->setRelation('teacher', $teacher);
        $enrollment->setRelation('instrument', $instrument);
        $enrollmentSession = ClassSession::make(['enrollment_id' => 31, 'student_id' => 2, 'teacher_id' => 3, 'instrument_id' => 4]);
        $enrollmentSession->setAttribute('id', 21);
        $enrollmentSession->setRelation('enrollment', $enrollment);

        $domain = $this->domain();
        $this->assertSame(RelationPathType::Direct, $domain->normalizeForSession($this->input(['session_id' => 20, 'session_version' => 'version-20']), $direct, $this->timezone())->relationPath->type);
        $this->assertSame(RelationPathType::Enrollment, $domain->normalizeForSession($this->input(['session_id' => 21, 'session_version' => 'version-21']), $enrollmentSession, $this->timezone())->relationPath->type);
    }

    public function test_session_bound_normalization_rejects_a_switched_session_identity_before_resolution(): void
    {
        [$student, $teacher, $instrument] = $this->relationModels();
        $session = ClassSession::make(['student_id' => 2, 'teacher_id' => 3, 'instrument_id' => 4]);
        $session->setAttribute('id', 20);
        $session->setRelation('enrollment', null);
        $session->setRelation('student', $student);
        $session->setRelation('teacher', $teacher);
        $session->setRelation('instrument', $instrument);

        try {
            $this->domain()->normalizeForSession($this->input(['session_id' => 21, 'session_version' => 'version-20']), $session, $this->timezone());
            $this->fail('A route-bound session identity must not be replaceable by payload input.');
        } catch (SchedulingValidationException $exception) {
            $this->assertSame('session_mismatch', $exception->errors['session_id']);
        }
    }

    /** @return array{Student, Teacher, Instrument} */
    private function relationModels(): array
    {
        $student = Student::make();
        $student->setAttribute('id', 2);
        $teacher = Teacher::make();
        $teacher->setAttribute('id', 3);
        $instrument = Instrument::make();
        $instrument->setAttribute('id', 4);

        return [$student, $teacher, $instrument];
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function input(array $overrides = []): array
    {
        return [...[
            'student_id' => 2, 'teacher_id' => 3, 'instrument_id' => 4, 'session_date' => '2026-08-04',
            'start_time' => '14:30', 'duration_minutes' => 60, 'status' => SessionStatusEnum::Scheduled->value,
            'room' => 'Studio', 'notes' => 'Scales', 'source' => 'form',
        ], ...$overrides];
    }

    private function directPath(): RelationPath { return new RelationPath(RelationPathType::Direct, null, 2, 3, 4); }
    private function timezone(): DateTimeZone { return new DateTimeZone('Asia/Tehran'); }

    private function domain(): SchedulingDomain
    {
        return new SchedulingDomain(new ScheduleProposalNormalizer(new RelationPathResolver));
    }
}
