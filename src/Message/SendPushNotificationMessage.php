<?php

namespace App\Message;

readonly class SendPushNotificationMessage
{
    public function __construct(
        public string $customerId,
        public string $title,
        public string $body,
        public array  $data = [],
    ) {}
}
