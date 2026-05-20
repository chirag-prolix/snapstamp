<?php

namespace App\Enum;

enum MerchantStatusEnum: string
{
    case PENDING  = 'PENDING';
    case VERIFIED = 'VERIFIED';
    case REJECTED = 'REJECTED';
    case ACTIVE   = 'ACTIVE';
}
