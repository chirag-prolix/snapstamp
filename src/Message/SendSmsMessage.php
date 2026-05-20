<?php

namespace App\Message;

readonly class SendSmsMessage
{
    public function __construct(
        public string $to,
        public string $body,
    ) {}
}
