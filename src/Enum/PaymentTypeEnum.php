<?php

namespace App\Enum;

enum PaymentTypeEnum: string
{
    case REWARD_CASH_OUT  = 'REWARD_CASH_OUT';
    case MERCHANT_PAYOUT  = 'MERCHANT_PAYOUT';
    case DEPOSIT          = 'DEPOSIT';
}
