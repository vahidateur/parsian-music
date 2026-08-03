<?php

namespace App\Enums;

enum BulkItemResultStatusEnum: string
{
    case Succeeded = 'succeeded';
    case Skipped = 'skipped';
    case Failed = 'failed';
}
