<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

enum RelationPathType: string
{
    case Direct = 'direct';
    case Enrollment = 'enrollment';
}
