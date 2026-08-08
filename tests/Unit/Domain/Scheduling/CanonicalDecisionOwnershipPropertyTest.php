<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling;

use App\Domain\Scheduling\AcademyRulesProvider;
use App\Domain\Scheduling\AvailabilityEvaluator;
use App\Domain\Scheduling\ConflictClassifier;
use App\Domain\Scheduling\ConflictFactsProvider;
use App\Domain\Scheduling\EffectiveSchedulingRules;
use App\Domain\Scheduling\ProposalSource;
use App\Domain\Scheduling\RelationPath;
use App\Domain\Scheduling\RelationPathType;
use App\Domain\Scheduling\RoomSuitabilityService;
use App\Domain\Scheduling\ScheduleProposal;
use App\Domain\Scheduling\ScheduleProposalNormalizer;
use App\Domain\Scheduling\SchedulingDomain;
use App\Enums\SessionStatusEnum;
use App\Services\ConflictDetectionService;
use App\Services\RelationPathResolver;
use App\Services\RoomOptionProvider;
use App\Services\RoomResolver;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\DeterministicSchedulingCases;
use Tests\TestCase;

/**
 * Feature: interactive-session-scheduling, Property 1: Canonical decision ownership invariant.
 *
 * **Validates: Requirements 4.1, 10.5, 13.1, 15.1, 18.1, 18.2, 18.6**
 */
final class CanonicalDecisionOwnershipPropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_equivalent_proposals_share_one_normalized_domain_decision(): void
    {
        $cases = DeterministicSchedulingCases::intervals();
        $this->assertCount(DeterministicSchedulingCases::MINIMUM_CASES, $cases);

        $domain = $this->domain();
        foreach ($cases as $case) {
            $baseline = null;
            foreach ($this->sources() as $source) {
                $proposal = $domain->normalize($this->input($case, $source), $this->path(), $this->timezone());
                $result = $domain->evaluate($proposal);

                $this->assertSame($source, $proposal->source, $this->diagnostic($case, $source, $source->value, $proposal->source->value));
                $this->assertSame($proposal, $result->proposal, $this->diagnostic($case, $source, 'normalized proposal', 'renormalized proposal'));

                if ($baseline === null) {
                    $baseline = [$proposal, $result];
                    continue;
                }

                [$referenceProposal, $referenceResult] = $baseline;
                $this->assertSame($this->canonicalProposal($referenceProposal), $this->canonicalProposal($proposal), $this->diagnostic($case, $source, $this->canonicalProposal($referenceProposal), $this->canonicalProposal($proposal)));
                $this->assertSame($referenceResult->state, $result->state, $this->diagnostic($case, $source, $referenceResult->state->value, $result->state->value));
                $this->assertSame($referenceResult->code, $result->code, $this->diagnostic($case, $source, $referenceResult->code->value, $result->code->value));
                $this->assertEquals($referenceResult->details, $result->details, $this->diagnostic($case, $source, $referenceResult->details, $result->details));
            }
        }
    }

    /** @return list<ProposalSource> */
    private function sources(): array
    {
        return [ProposalSource::Form, ProposalSource::CalendarDrag, ProposalSource::CalendarResize, ProposalSource::Recurrence, ProposalSource::BusySeed];
    }

    /** @param array<string, int|string> $case @return array<string, int|string|null> */
    private function input(array $case, ProposalSource $source): array
    {
        $start = (int) $case['start_minute'];

        return [
            'student_id' => 101,
            'teacher_id' => 102,
            'instrument_id' => 103,
            'session_date' => '2026-08-03',
            'start_time' => sprintf('%02d:%02d', intdiv($start, 60), $start % 60),
            'duration_minutes' => (int) $case['duration_minutes'],
            'status' => SessionStatusEnum::Scheduled->value,
            'room' => null,
            'notes' => null,
            'source' => $source->value,
        ];
    }

    /** @return array<string, int|string|null> */
    private function canonicalProposal(ScheduleProposal $proposal): array
    {
        return [
            'session_id' => $proposal->sessionId,
            'session_version' => $proposal->sessionVersion?->value,
            'path' => $proposal->relationPath->type->value,
            'enrollment_id' => $proposal->relationPath->enrollmentId,
            'student_id' => $proposal->relationPath->studentId,
            'teacher_id' => $proposal->relationPath->teacherId,
            'instrument_id' => $proposal->relationPath->instrumentId,
            'start' => $proposal->timeRange->start->format(DATE_ATOM),
            'end' => $proposal->timeRange->end->format(DATE_ATOM),
            'room' => $proposal->room,
            'status' => $proposal->status->value,
            'notes' => $proposal->notes,
        ];
    }

    /** @param array<string, int|string> $case */
    private function diagnostic(array $case, ProposalSource $source, mixed $expected, mixed $observed): string
    {
        return DeterministicSchedulingCases::firstFailure('SchedulingDomain source='.$source->value.' seed='.$case['seed'].' case='.$case['case'], $expected, $observed);
    }

    private function path(): RelationPath
    {
        return new RelationPath(RelationPathType::Direct, null, 101, 102, 103);
    }

    private function timezone(): DateTimeZone
    {
        return new DateTimeZone('Asia/Tehran');
    }

    private function domain(): SchedulingDomain
    {
        $facts = new ConflictFactsProvider(new ConflictDetectionService);
        $rules = new EffectiveSchedulingRules('property-v1', 'property', $this->timezone(), [1, 2, 3, 4, 5, 6, 7], 0, 1440, 15, 120, PHP_INT_MAX, PHP_INT_MAX, null, 0, 0);

        return new SchedulingDomain(
            new ScheduleProposalNormalizer(new RelationPathResolver),
            new AvailabilityEvaluator($facts, new ConflictClassifier, new AcademyRulesProvider($rules), new RoomSuitabilityService(new RoomResolver, new RoomOptionProvider, $facts)),
        );
    }
}
