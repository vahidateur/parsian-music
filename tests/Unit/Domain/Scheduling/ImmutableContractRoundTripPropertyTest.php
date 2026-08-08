<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling;

use App\DTOs\SessionEditResource;
use App\Domain\Scheduling\AvailabilityResult;
use App\Domain\Scheduling\AvailabilityState;
use App\Domain\Scheduling\EffectiveSchedulingRules;
use App\Domain\Scheduling\ProposalSource;
use App\Domain\Scheduling\RelationPath;
use App\Domain\Scheduling\RelationPathType;
use App\Domain\Scheduling\ScheduleProposal;
use App\Domain\Scheduling\ScheduleProposalNormalizer;
use App\Domain\Scheduling\SchedulingDecisionCode;
use App\Domain\Scheduling\SchedulingDomain;
use App\Domain\Scheduling\SchedulingValidationException;
use App\Enums\RoomResolutionEnum;
use App\Enums\SessionStatusEnum;
use App\Services\RelationPathResolver;
use DateTimeZone;
use InvalidArgumentException;
use Tests\Support\DeterministicSchedulingCases;
use Tests\TestCase;

/**
 * Feature: interactive-session-scheduling, Property 14: Immutable contract round-trip invariant
 *
 * **Validates: Requirements 2.6, 5.7, 17.5, 18.2, 21.3**
 */
final class ImmutableContractRoundTripPropertyTest extends TestCase
{
    public function test_immutable_contracts_round_trip_for_one_hundred_deterministic_cases(): void
    {
        $domain = $this->domain();
        $timezone = new DateTimeZone('Asia/Tehran');

        foreach (self::cases() as $case) {
            $proposal = $domain->normalize($this->proposalInput($case), $this->relationPath($case), $timezone);
            $proposalPayload = self::wire($proposal);
            $parsedProposal = $domain->normalize(self::proposalInputFrom($proposalPayload), RelationPath::fromArray($proposalPayload['relation']), $timezone);

            $this->assertSame($proposalPayload, self::wire($parsedProposal), self::failure($case, 'proposal', $proposalPayload, self::wire($parsedProposal)));

            $availability = $this->availability($proposal, $case);
            $availabilityPayload = self::wire($availability);
            $parsedAvailability = new AvailabilityResult(
                AvailabilityState::from($availabilityPayload['state']),
                $parsedProposal,
                SchedulingDecisionCode::from($availabilityPayload['code']),
                $availabilityPayload['details'],
            );
            $this->assertSame($availabilityPayload, self::wire($parsedAvailability), self::failure($case, 'availability/suggestion/audit', $availabilityPayload, self::wire($parsedAvailability)));

            $rules = $this->rules($case, $timezone);
            $rulesPayload = self::wire($rules);
            $parsedRules = self::rulesFrom($rulesPayload);
            $this->assertSame($rulesPayload, self::wire($parsedRules), self::failure($case, 'rules', $rulesPayload, self::wire($parsedRules)));

            $resource = $this->resource($proposal, $case);
            $resourcePayload = $resource->toArray();
            $parsedResource = self::resourceFrom($resourcePayload);
            $this->assertSame($resourcePayload, $parsedResource->toArray(), self::failure($case, 'resource', $resourcePayload, $parsedResource->toArray()));
        }
    }

    public function test_malformed_representations_have_stable_safe_errors_without_an_accepted_proposal(): void
    {
        $domain = $this->domain();
        $timezone = new DateTimeZone('Asia/Tehran');

        foreach (self::cases() as $case) {
            foreach ([
                'business_code' => ['value' => 'teacher-code', 'errors' => ['business_code' => 'protected_field']],
                'suggestion' => ['value' => ['room' => 'Room-'.$case['case']], 'errors' => ['suggestion' => 'unexpected_field']],
                'audit' => ['value' => ['session_id' => $case['case'] + 1], 'errors' => ['audit' => 'unexpected_field']],
                'status' => ['value' => 'unknown', 'errors' => ['status' => 'invalid']],
                'duration_minutes' => ['value' => 0, 'errors' => ['duration_minutes' => 'invalid']],
                'notes' => ['value' => '<b>unsafe</b>', 'errors' => ['notes' => 'invalid']],
                'session_version' => ['value' => [], 'errors' => ['session_version' => 'invalid']],
            ] as $field => $malformed) {
                $accepted = false;
                try {
                    $domain->normalize([...$this->proposalInput($case), $field => $malformed['value']], $this->relationPath($case), $timezone);
                    $accepted = true;
                } catch (SchedulingValidationException $exception) {
                    $this->assertSame($malformed['errors'], $exception->errors, self::failure($case, "malformed-$field", $malformed['errors'], $exception->errors));
                }
                $this->assertFalse($accepted, self::failure($case, "malformed-$field", 'safe validation error', 'proposal accepted'));
            }

            $proposal = $domain->normalize($this->proposalInput($case), $this->relationPath($case), $timezone);
            $this->assertRejected($case, 'availability', static fn (): AvailabilityResult => new AvailabilityResult(AvailabilityState::Available, $proposal, SchedulingDecisionCode::Conflict), InvalidArgumentException::class);
            $this->assertRejected($case, 'rules', fn (): EffectiveSchedulingRules => new EffectiveSchedulingRules('v'.$case['case'], 'test', $timezone, [1], 600, 600, 30, 60, 1, 1, null, 0, 0), InvalidArgumentException::class);

            $resource = $this->resource($proposal, $case)->toArray();
            $resource['status'] = 'unknown';
            $this->assertRejected($case, 'resource', static fn (): SessionEditResource => self::resourceFrom($resource), \ValueError::class);
        }
    }

    /** @return list<array<string, mixed>> */
    private static function cases(): array
    {
        $seed = DeterministicSchedulingCases::DEFAULT_SEED;
        $intervals = DeterministicSchedulingCases::intervals($seed);
        $rules = DeterministicSchedulingCases::rules($seed);
        $relations = DeterministicSchedulingCases::relationPaths($seed);
        $rooms = DeterministicSchedulingCases::rooms($seed);
        $cases = [];

        foreach ($intervals as $index => $interval) {
            $cases[] = ['seed' => $seed, 'case' => $index, 'interval' => $interval, 'rules' => $rules[$index], 'relation' => $relations[$index], 'room' => $rooms[$index]];
        }

        return $cases;
    }

    /** @param array<string, mixed> $case @return array<string, mixed> */
    private function proposalInput(array $case): array
    {
        $minute = $case['interval']['start_minute'];

        return [
            'student_id' => $case['relation']['relations']['student_id'],
            'teacher_id' => $case['relation']['relations']['teacher_id'],
            'instrument_id' => $case['relation']['relations']['instrument_id'],
            'session_date' => '2026-08-'.str_pad((string) (($case['case'] % 28) + 1), 2, '0', STR_PAD_LEFT),
            'start_time' => sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60),
            'duration_minutes' => $case['interval']['duration_minutes'],
            'status' => SessionStatusEnum::cases()[$case['case'] % count(SessionStatusEnum::cases())]->value,
            'room' => $case['room']['name'],
            'notes' => 'note-'.$case['case'],
            'source' => ProposalSource::cases()[$case['case'] % count(ProposalSource::cases())]->value,
        ];
    }

    /** @param array<string, mixed> $case */
    private function relationPath(array $case): RelationPath
    {
        $relations = $case['relation']['relations'];

        return new RelationPath(
            $case['case'] % 2 === 0 ? RelationPathType::Direct : RelationPathType::Enrollment,
            $case['case'] % 2 === 0 ? null : $case['case'] + 1000,
            $relations['student_id'],
            $relations['teacher_id'],
            $relations['instrument_id'],
        );
    }

    /** @param array<string, mixed> $case */
    private function availability(ScheduleProposal $proposal, array $case): AvailabilityResult
    {
        $state = AvailabilityState::cases()[$case['case'] % count(AvailabilityState::cases())];
        $code = match ($state) {
            AvailabilityState::Available => SchedulingDecisionCode::Available,
            AvailabilityState::Conflict => SchedulingDecisionCode::Conflict,
            AvailabilityState::Invalid => SchedulingDecisionCode::Invalid,
        };

        return new AvailabilityResult($state, $proposal, $code, [
            'suggestions' => [['date' => $proposal->timeRange->start->format('Y-m-d'), 'start_time' => $proposal->timeRange->start->format('H:i'), 'duration_minutes' => $proposal->timeRange->durationMinutes(), 'room' => $proposal->room, 'state' => AvailabilityState::Available->value]],
            'audit' => ['schema_version' => 1, 'session_id' => $proposal->sessionId, 'source' => $proposal->source->value, 'changed_fields' => ['room', 'notes']],
        ]);
    }

    /** @param array<string, mixed> $case */
    private function rules(array $case, DateTimeZone $timezone): EffectiveSchedulingRules
    {
        $rules = $case['rules'];

        return new EffectiveSchedulingRules('v'.$case['case'], 'property', $timezone, $rules['enabled_weekdays'], $rules['opening_minute'], $rules['closing_minute'], $rules['minimum_duration'], $rules['maximum_duration'], $rules['daily_limit'], $rules['consecutive_limit'], null, $rules['buffer_before'], $rules['buffer_after'], [(string) $case['relation']['relations']['instrument_id'] => [$case['room']['name']]]);
    }

    /** @param array<string, mixed> $case */
    private function resource(ScheduleProposal $proposal, array $case): SessionEditResource
    {
        return new SessionEditResource($case['case'] + 1, $proposal->relationPath->studentId, $proposal->relationPath->teacherId, $proposal->relationPath->instrumentId, $proposal->timeRange->start->format('Y-m-d'), $proposal->timeRange->start->format('H:i'), $proposal->timeRange->durationMinutes(), $proposal->status, $proposal->room, $proposal->notes, $proposal->relationPath->jsonSerialize(), ['enrollment_id'], RoomResolutionEnum::cases()[$case['case'] % count(RoomResolutionEnum::cases())], $case['room']['room_id'], 'version-'.$case['case']);
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private static function proposalInputFrom(array $payload): array
    {
        return ['session_id' => $payload['session_id'], 'session_version' => $payload['session_version'], 'student_id' => $payload['relation']['student_id'], 'teacher_id' => $payload['relation']['teacher_id'], 'instrument_id' => $payload['relation']['instrument_id'], 'session_date' => $payload['time_range']['date'], 'start_time' => $payload['time_range']['start_time'], 'duration_minutes' => $payload['time_range']['duration_minutes'], 'status' => $payload['status'], 'room' => $payload['room'], 'notes' => $payload['notes'], 'source' => $payload['source'], 'override' => $payload['override']];
    }

    /** @param array<string, mixed> $payload */
    private static function rulesFrom(array $payload): EffectiveSchedulingRules
    {
        return new EffectiveSchedulingRules($payload['version'], $payload['source'], new DateTimeZone($payload['timezone']), $payload['enabled_weekdays'], $payload['opening_minute'], $payload['closing_minute'], $payload['minimum_duration'], $payload['maximum_duration'], $payload['daily_session_limit'], $payload['consecutive_session_limit'], $payload['lunch'], $payload['teacher_buffer_before'], $payload['teacher_buffer_after'], $payload['room_requirements']);
    }

    /** @param array<string, mixed> $payload */
    private static function resourceFrom(array $payload): SessionEditResource
    {
        return new SessionEditResource($payload['session_id'], $payload['student_id'], $payload['teacher_id'], $payload['instrument_id'], $payload['session_date'], $payload['start_time'], $payload['duration_minutes'], $payload['status'], $payload['room'], $payload['notes'], $payload['relation'], $payload['protected_fields'], $payload['room_resolution'], $payload['room_id'], $payload['updated_at']);
    }

    /** @param array<string, mixed> $case */
    private function assertRejected(array $case, string $boundary, callable $operation, string $expectedException): void
    {
        try {
            $operation();
        } catch (\Throwable $exception) {
            $this->assertSame($expectedException, $exception::class, self::failure($case, "malformed-$boundary", $expectedException, $exception::class));

            return;
        }

        $this->fail(self::failure($case, "malformed-$boundary", $expectedException, 'representation accepted'));
    }

    /** @param array<string, mixed> $case */
    private static function failure(array $case, string $boundary, mixed $expected, mixed $observed): string
    {
        return DeterministicSchedulingCases::firstFailure("$boundary; seed={$case['seed']}; case={$case['case']}", $expected, $observed);
    }

    private static function wire(mixed $value): array
    {
        return json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    private function domain(): SchedulingDomain
    {
        return new SchedulingDomain(new ScheduleProposalNormalizer(new RelationPathResolver));
    }
}
