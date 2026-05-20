<?php

namespace App\Enum;

enum StampCardStatusEnum: string
{
    case ACTIVE    = 'ACTIVE';
    case COMPLETED = 'COMPLETED';
    case EXPIRED   = 'EXPIRED';
    case CANCELLED = 'CANCELLED';
}
