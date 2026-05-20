<?php

namespace App\MessageHandler;

use App\Message\SendSmsMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twilio\Rest\Client as TwilioClient;

#[AsMessageHandler]
class SendSmsHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $twilioSid,
        private readonly string $twilioToken,
        private readonly string $twilioFrom,
    ) {}

    public function __invoke(SendSmsMessage $message): void
    {
        if ($this->twilioSid === '' || $this->twilioSid === 'your_account_sid') {
            $this->logger->info('SMS not sent (Twilio not configured)', [
                'to'   => $message->to,
                'body' => $message->body,
            ]);
            return;
        }

        try {
            $client = new TwilioClient($this->twilioSid, $this->twilioToken);
            $client->messages->create($message->to, [
                'from' => $this->twilioFrom,
                'body' => $message->body,
            ]);
            $this->logger->info('SMS sent', ['to' => $message->to]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send SMS', [
                'to'    => $message->to,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
