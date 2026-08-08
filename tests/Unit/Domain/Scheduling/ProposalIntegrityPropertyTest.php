<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling;

use App\Domain\Scheduling\RelationPath;
use App\Domain\Scheduling\RelationPathType;
use App\Domain\Scheduling\ScheduleProposalNormalizer;
use App\Domain\Scheduling\SchedulingAbility;
use App\Domain\Scheduling\SchedulingAuthorization;
use App\Domain\Scheduling\SchedulingDomain;
use App\Domain\Scheduling\SchedulingValidationException;
use App\Enums\RoleEnum;
use App\Enums\SessionStatusEnum;
use App\Models\ClassSession;
use App\Models\User;
use App\Services\RelationPathResolver;
use DateTimeZone;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\Support\DeterministicSchedulingCases;
use Tests\TestCase;

/**
 * Feature: interactive-session-scheduling, Property 2: Proposal integrity and rejection preservation invariant.
 *
 * **Validates: Requirements 2.3, 2.4, 2.5, 2.7, 8.3, 8.4, 16.3, 16.4, 21.3**
 */
final class ProposalIntegrityPropertyTest extends TestCase
{
    private const CATEGORIES = ['permitted', 'protected', 'malformed', 'mixed-path', 'unauthorized', 'disallowed'];

    public function test_generated_proposals_have_stable_rejections_without_changing_authoritative_state(): void
    {
        $seed = DeterministicSchedulingCases::DEFAULT_SEED;
        $cases = DeterministicSchedulingCases::relationPaths($seed, DeterministicSchedulingCases::MINIMUM_CASES);
        $versions = DeterministicSchedulingCases::versions($seed, DeterministicSchedulingCases::MINIMUM_CASES);

        $this->assertCount(DeterministicSchedulingCases::MINIMUM_CASES, $cases);

        foreach ($cases as $index => $case) {
            $category = self::CATEGORIES[$index % count(self::CATEGORIES)];
            $state = $this->authoritativeState($case, $versions[$index]);
            $before = $state;
            [$input, $path, $expected] = $this->proposalCase($category, $case, $versions[$index]);

            if ($category === 'permitted') {
                $proposal = $this->domain()->normalize($input, $path, $this->timezone());

                $this->assertSame($input['student_id'], $proposal->relationPath->studentId, $this->diagnostic($case, $category, 'accepted relation path', $input, $proposal->jsonSerialize()));
                $this->assertSame($before, $state, $this->diagnostic($case, $category, 'authoritative state preservation', $before, $state));

                continue;
            }

            $first = $this->rejection($category, $input, $path, $state);
            $second = $this->rejection($category, $input, $path, $state);

            $this->assertRejectionSemantics($expected, $first, $this->diagnostic($case, $category, 'rejection semantics', $expected, $first));
            $this->assertRejectionSemantics($first, $second, $this->diagnostic($case, $category, 'stable repeated rejection semantics', $first, $second));
            $this->assertSame($before, $state, $this->diagnostic($case, $category, 'session/code/recurrence/version/counter/audit preservation', $before, $state));
        }
    }

    /** @param array<string, mixed> $case @param array<string, mixed> $version @return array{array<string, mixed>, RelationPath, array<string, string>} */
    private function proposalCase(string $category, array $case, array $version): array
    {
        $studentId = $case['relations']['student_id'];
        $teacherId = $case['relations']['teacher_id'];
        $instrumentId = $case['relations']['instrument_id'];
        $input = [
            'student_id' => $studentId,
            'teacher_id' => $teacherId,
            'instrument_id' => $instrumentId,
            'session_date' => '2026-08-04',
            'start_time' => '14:30',
            'duration_minutes' => 60,
            'status' => SessionStatusEnum::Scheduled->value,
            'room' => 'Studio',
            'notes' => 'Scales',
            'source' => 'form',
        ];
        $direct = new RelationPath(RelationPathType::Direct, null, $studentId, $teacherId, $instrumentId);

        return match ($category) {
            'permitted' => [$input, $case['case'] % 2 === 0
                ? $direct
                : new RelationPath(RelationPathType::Enrollment, $case['enrollment_id'] ?: 1, $studentId, $teacherId, $instrumentId), []],
            'protected' => $this->protectedCase($input, $direct, $case),
            'malformed' => [[...$input, 'duration_minutes' => 0], $direct, ['duration_minutes' => 'invalid']],
            'mixed-path' => [[...$input, 'teacher_id' => $teacherId + 1], new RelationPath(RelationPathType::Enrollment, $case['enrollment_id'] ?: 1, $studentId, $teacherId, $instrumentId), [
                'student_id' => 'relation_conflict', 'teacher_id' => 'relation_conflict', 'instrument_id' => 'relation_conflict',
            ]],
            'unauthorized' => [$input, $direct, ['authorization' => 'denied', 'facts_evaluated' => 'no']],
            'disallowed' => [[...$input, 'unexpected_payload_key' => 'attempt'], $direct, ['unexpected_payload_key' => 'unexpected_field']],
        };
    }

    /** @param array<string, mixed> $input @param array<string, mixed> $case @return array{array<string, mixed>, RelationPath, array<string, string>} */
    private function protectedCase(array $input, RelationPath $path, array $case): array
    {
        $field = ['session_fee', 'recurring_schedule_id', 'teacher_code', 'student_code'][$case['case'] % 4];

        return [[...$input, $field => 'protected-attempt'], $path, [$field => 'protected_field']];
    }

    /** @param array<string, mixed> $input @return array<string, string> */
    private function rejection(string $category, array $input, RelationPath $path, array &$state): array
    {
        if ($category === 'unauthorized') {
            $evaluated = false;

            try {
                (new SchedulingAuthorization)->evaluateSessionFacts(
                    new User(['role' => RoleEnum::TEACHER]),
                    SchedulingAbility::Preview,
                    ClassSession::make($state['session']),
                    function () use (&$evaluated, $input, $path): void {
                        $evaluated = true;
                        $this->domain()->normalize($input, $path, $this->timezone());
                    },
                );
            } catch (AuthorizationException) {
                return ['authorization' => 'denied', 'facts_evaluated' => $evaluated ? 'yes' : 'no'];
            }

            return ['authorization' => 'accepted', 'facts_evaluated' => $evaluated ? 'yes' : 'no'];
        }

        try {
            $this->domain()->normalize($input, $path, $this->timezone());
        } catch (SchedulingValidationException $exception) {
            return $exception->errors;
        }

        return ['proposal' => 'accepted'];
    }

    /** @param array<string, mixed> $case @param array<string, mixed> $version @return array<string, mixed> */
    private function authoritativeState(array $case, array $version): array
    {
        return [
            'session' => [
                'id' => 1000 + $case['case'],
                'student_id' => $case['relations']['student_id'],
                'teacher_id' => $case['relations']['teacher_id'],
                'instrument_id' => $case['relations']['instrument_id'],
                'recurring_schedule_id' => 2000 + $case['case'],
                'session_fee' => 500000,
                'discount' => 25000,
                'notes' => 'Persisted notes',
            ],
            'business_codes' => ['teacher' => 'T-'.str_pad((string) $case['relations']['teacher_id'], 5, '0', STR_PAD_LEFT), 'student' => 'S-'.str_pad((string) $case['relations']['student_id'], 5, '0', STR_PAD_LEFT)],
            'recurrence_association' => ['recurring_schedule_id' => 2000 + $case['case'], 'occurrence' => '2026-08-04T14:30'],
            'version' => $version['persisted_version'],
            'counters' => ['accepted_mutations' => 4, 'resource_version' => 9],
            'accepted_audit_history' => [['id' => 3000 + $case['case'], 'resulting_version' => $version['persisted_version']]],
        ];
    }

    /** @param array<string, string> $expected @param array<string, string> $observed */
    private function assertRejectionSemantics(array $expected, array $observed, string $message): void
    {
        $this->assertCount(count($expected), $observed, $message);

        foreach ($expected as $field => $reason) {
            $this->assertArrayHasKey($field, $observed, $message);
            $this->assertSame($reason, $observed[$field], $message);
        }
    }

    /** @param array<string, mixed> $case */
    private function diagnostic(array $case, string $category, string $boundary, mixed $expected, mixed $observed): string
    {
        return DeterministicSchedulingCases::firstFailure(
            "Property 2/$boundary; seed={$case['seed']}; case={$case['case']}; category=$category",
            $expected,
            $observed,
        );
    }

    private function domain(): SchedulingDomain
    {
        return new SchedulingDomain(new ScheduleProposalNormalizer(new RelationPathResolver));
    }

    private function timezone(): DateTimeZone
    {
        return new DateTimeZone('Asia/Tehran');
    }
}
