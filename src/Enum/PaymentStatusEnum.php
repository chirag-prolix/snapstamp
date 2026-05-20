<?php

namespace App\Enum;

enum PaymentStatusEnum: string
{
    case INITIATED = 'INITIATED';
    case PENDING   = 'PENDING';
    case COMPLETED = 'COMPLETED';
    case FAILED    = 'FAILED';
    case REFUNDED  = 'REFUNDED';
}
