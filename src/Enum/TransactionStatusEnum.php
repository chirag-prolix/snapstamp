<?php

namespace App\Enum;

enum TransactionStatusEnum: string
{
    case COMPLETED = 'COMPLETED';
    case PENDING   = 'PENDING';
    case FAILED    = 'FAILED';
}
