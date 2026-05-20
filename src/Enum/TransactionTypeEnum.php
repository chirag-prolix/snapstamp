<?php

namespace App\Enum;

enum TransactionTypeEnum: string
{
    case STAMP_ISSUED       = 'STAMP_ISSUED';
    case STAMP_EXPIRED      = 'STAMP_EXPIRED';
    case REWARD_REDEEMED    = 'REWARD_REDEEMED';
    case PAYMENT_RECEIVED   = 'PAYMENT_RECEIVED';
    case PAYOUT_ISSUED      = 'PAYOUT_ISSUED';
    case REFUND             = 'REFUND';
    case REFERRAL_BONUS     = 'REFERRAL_BONUS';
}
