<?php

namespace App\Dto\Payment;

use Symfony\Component\Validator\Constraints as Assert;

class VerifyPaymentDto
{
    #[Assert\NotBlank]
    public string $razorpay_payment_id = '';

    #[Assert\NotBlank]
    public string $razorpay_order_id = '';

    #[Assert\NotBlank]
    public string $razorpay_signature = '';
}
