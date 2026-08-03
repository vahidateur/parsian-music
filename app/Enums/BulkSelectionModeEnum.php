<?php

namespace App\Enums;

enum BulkSelectionModeEnum: string
{
    case CurrentPage = 'current_page';
    case AllFiltered = 'all_filtered';
}
