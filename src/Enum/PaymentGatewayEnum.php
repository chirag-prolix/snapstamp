<?php

namespace App\Enum;

enum PaymentGatewayEnum: string
{
    case RAZORPAY  = 'RAZORPAY';
    case INSTAMOJO = 'INSTAMOJO';
    case PAYPAL    = 'PAYPAL';
}
