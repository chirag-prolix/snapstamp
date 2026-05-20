<?php

namespace App\Enum;

enum PaymentMethodEnum: string
{
    case CARD          = 'CARD';
    case UPI           = 'UPI';
    case WALLET        = 'WALLET';
    case BANK_TRANSFER = 'BANK_TRANSFER';
}
