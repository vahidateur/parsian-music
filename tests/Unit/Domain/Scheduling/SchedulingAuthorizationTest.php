<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling;

use App\Domain\Scheduling\SchedulingAbility;
use App\Domain\Scheduling\SchedulingAuthorization;
use App\Enums\RoleEnum;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\TestCase;

/**
 * Focused regression coverage for gate-first scheduling authorization.
 *
 * Validates: Requirements 3.5-3.6, 5.3-5.4, 8.6, 12.5-12.6, 16.1-16.2, 16.7.
 */
final class SchedulingAuthorizationTest extends TestCase
{
    public function test_admin_receives_each_distinct_named_scheduling_authority(): void
    {
        $authorization = new SchedulingAuthorization;
        $admin = $this->actor(RoleEnum::ADMIN);
        $session = $this->scheduledSession();

        foreach ([SchedulingAbility::Update, SchedulingAbility::Preview, SchedulingAbility::AuditHistory, SchedulingAbility::Override] as $ability) {
            $authorization->authorizeSession($admin, $ability, $session);
            $this->addToAssertionCount(1);
        }

        foreach ([SchedulingAbility::Suggest, SchedulingAbility::Recurrence, SchedulingAbility::Rules] as $ability) {
            $authorization->authorizeCollection($admin, $ability);
            $this->addToAssertionCount(1);
        }
    }

    public function test_unauthorized_actor_cannot_run_protected_fact_evaluation(): void
    {
        $evaluated = false;

        try {
            (new SchedulingAuthorization)->evaluateSessionFacts(
                $this->actor(RoleEnum::TEACHER),
                SchedulingAbility::Preview,
                $this->scheduledSession(),
                function () use (&$evaluated): string {
                    $evaluated = true;

                    return 'protected facts';
                },
            );
            $this->fail('Preview facts must not be evaluated for an unauthorized actor.');
        } catch (AuthorizationException) {
            $this->assertFalse($evaluated);
        }
    }

    public function test_business_codes_reuse_the_corresponding_resource_view_policy(): void
    {
        $authorization = new SchedulingAuthorization;
        $admin = $this->actor(RoleEnum::ADMIN);

        $authorization->authorizeBusinessCodeView($admin, new Teacher);
        $authorization->authorizeBusinessCodeView($admin, new Student);
        $this->addToAssertionCount(2);

        $this->expectException(AuthorizationException::class);
        $authorization->authorizeBusinessCodeView($this->actor(RoleEnum::STUDENT), new Teacher);
    }

    private function actor(RoleEnum $role): User
    {
        return new User(['role' => $role]);
    }

    private function scheduledSession(): ClassSession
    {
        return new ClassSession(['teacher_id' => 1]);
    }
}
