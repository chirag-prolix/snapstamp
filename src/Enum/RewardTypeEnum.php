<?php

namespace App\Enum;

enum RewardTypeEnum: string
{
    case DISCOUNT   = 'DISCOUNT';
    case FREE_ITEM  = 'FREE_ITEM';
    case CASHBACK   = 'CASHBACK';
    case EXPERIENCE = 'EXPERIENCE';
}
