<?php

namespace App\Enum;

enum UserStatusEnum: string
{
    case PENDING   = 'PENDING';
    case ACTIVE    = 'ACTIVE';
    case INACTIVE  = 'INACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case DELETED   = 'DELETED';
}
