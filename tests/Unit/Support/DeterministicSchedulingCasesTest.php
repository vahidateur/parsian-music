<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Support\DeterministicSchedulingCases;

final class DeterministicSchedulingCasesTest extends TestCase
{
    public function test_all_case_builders_are_seeded_reproducible_and_have_at_least_one_hundred_cases(): void
    {
        $builders = [
            'interval' => [DeterministicSchedulingCases::class, 'intervals'],
            'relation-path' => [DeterministicSchedulingCases::class, 'relationPaths'],
            'rule' => [DeterministicSchedulingCases::class, 'rules'],
            'room' => [DeterministicSchedulingCases::class, 'rooms'],
            'version' => [DeterministicSchedulingCases::class, 'versions'],
            'concurrency' => [DeterministicSchedulingCases::class, 'concurrency'],
        ];
        $seed = DeterministicSchedulingCases::DEFAULT_SEED;

        foreach ($builders as $name => $builder) {
            $cases = $builder($seed, DeterministicSchedulingCases::MINIMUM_CASES);

            $this->assertCount(
                DeterministicSchedulingCases::MINIMUM_CASES,
                $cases,
                DeterministicSchedulingCases::firstFailure($name, ['count' => 100], ['count' => count($cases)]),
            );
            $this->assertSame($cases, $builder($seed, DeterministicSchedulingCases::MINIMUM_CASES));

            foreach ($cases as $index => $case) {
                $this->assertSame($seed, $case['seed']);
                $this->assertSame($index, $case['case']);
                $this->assertSame($name, $case['family']);
            }
        }
    }

    public function test_case_builders_reject_a_property_run_smaller_than_the_required_minimum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DeterministicSchedulingCases::intervals(count: 99);
    }
}
