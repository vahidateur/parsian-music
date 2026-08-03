<?php

namespace App\Enums;

enum RoomResolutionEnum: string
{
    case ResolvedActive = 'resolved_active';
    case ResolvedInactive = 'resolved_inactive';
    case UnresolvedLegacy = 'unresolved_legacy';
}
