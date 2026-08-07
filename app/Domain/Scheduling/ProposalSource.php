<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

/** Declares the caller context without coupling the proposal to transport. */
enum ProposalSource: string
{
    case Form = 'form';
    case CalendarDrag = 'calendar_drag';
    case CalendarResize = 'calendar_resize';
    case Preview = 'preview';
    case Suggestion = 'suggestion';
    case Recurrence = 'recurrence';
    case BusySeed = 'busy_seed';
    case Legacy = 'legacy';
}
