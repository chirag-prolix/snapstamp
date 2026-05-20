<?php

namespace App\MessageHandler;

use App\Message\SendPushNotificationMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendPushNotificationHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(SendPushNotificationMessage $message): void
    {
        // Firebase push not yet configured — log for now
        $this->logger->info('Push notification queued (Firebase not configured)', [
            'customerId' => $message->customerId,
            'title'      => $message->title,
            'body'       => $message->body,
            'data'       => $message->data,
        ]);
    }
}
