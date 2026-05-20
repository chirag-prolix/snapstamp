<?php

namespace App\Message;

readonly class SendEmailMessage
{
    public function __construct(
        public string $to,
        public string $subject,
        public string $text,
    ) {}
}
