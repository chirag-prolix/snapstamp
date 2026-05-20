<?php

namespace App\Enum;

enum RewardStatusEnum: string
{
    case ACTIVE   = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case EXPIRED  = 'EXPIRED';
    case DRAFT    = 'DRAFT';
}
