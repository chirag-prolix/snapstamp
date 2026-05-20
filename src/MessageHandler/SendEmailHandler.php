<?php

namespace App\MessageHandler;

use App\Message\SendEmailMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class SendEmailHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $mailerFrom,
    ) {}

    public function __invoke(SendEmailMessage $message): void
    {
        try {
            $email = (new Email())
                ->from($this->mailerFrom)
                ->to($message->to)
                ->subject($message->subject)
                ->text($message->text);

            $this->mailer->send($email);
            $this->logger->info('Email sent', ['to' => $message->to, 'subject' => $message->subject]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send email', [
                'to'    => $message->to,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
