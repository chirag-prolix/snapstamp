<?php

namespace App\Enum;

enum NotificationTypeEnum: string
{
    case STAMP_RECEIVED   = 'STAMP_RECEIVED';
    case REWARD_AVAILABLE = 'REWARD_AVAILABLE';
    case REWARD_REDEEMED  = 'REWARD_REDEEMED';
    case PROMOTION        = 'PROMOTION';
    case SYSTEM           = 'SYSTEM';
}
