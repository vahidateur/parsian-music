<?php

namespace App\Enums;

enum BulkActionEnum: string
{
    case Activate = 'activate';
    case Deactivate = 'deactivate';
    case Delete = 'delete';
}
