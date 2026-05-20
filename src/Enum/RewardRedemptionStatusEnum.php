<?php

namespace App\Enum;

enum RewardRedemptionStatusEnum: string
{
    case PENDING   = 'PENDING';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
    case EXPIRED   = 'EXPIRED';
}
