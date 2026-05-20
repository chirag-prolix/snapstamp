<?php

namespace App\Enum;

enum StampStatusEnum: string
{
    case ACTIVE   = 'ACTIVE';
    case EXPIRED  = 'EXPIRED';
    case REDEEMED = 'REDEEMED';
}
