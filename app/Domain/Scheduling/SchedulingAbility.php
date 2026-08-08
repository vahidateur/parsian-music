<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

/** Named SessionPolicy abilities available to scheduling-domain callers. */
enum SchedulingAbility: string
{
    case Update = 'update';
    case Preview = 'preview';
    case Suggest = 'suggest';
    case AuditHistory = 'viewAuditHistory';
    case Recurrence = 'generate';
    case Rules = 'manageSchedulingRules';
    case Override = 'override';
}
