<?php

namespace App\Enums;

enum BulkResultOutcomeEnum: string
{
    case CompleteSuccess = 'complete_success';
    case PartialSuccess = 'partial_success';
}
