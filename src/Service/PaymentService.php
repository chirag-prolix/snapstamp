<?php

namespace App\Service;

use App\Entity\Merchant;
use App\Entity\Payment;
use App\Enum\PaymentGatewayEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\PaymentTypeEnum;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class PaymentService
{
    private const PLANS = [
        'monthly' => 99900,
        'annual'  => 999900,
    ];

    public function __construct(
        private readonly string $keyId,
        private readonly string $keySecret,
        private readonly string $webhookSecret,
        private readonly EntityManagerInterface $em,
        private readonly PaymentRepository $paymentRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function createSubscriptionOrder(Merchant $merchant, string $plan): array
    {
        $amountPaise = self::PLANS[$plan];
        $amountInr   = number_format($amountPaise / 100, 2, '.', '');
        $receipt     = 'SUB-' . strtoupper(bin2hex(random_bytes(5)));

        $api   = new Api($this->keyId, $this->keySecret);
        $order = $api->order->create([
            'amount'          => $amountPaise,
            'currency'        => 'INR',
            'receipt'         => $receipt,
            'payment_capture' => 1,
        ]);

        $payment = new Payment();
        $payment->setTransactionId($receipt)
            ->setPaymentGateway(PaymentGatewayEnum::RAZORPAY)
            ->setPaymentGatewayId($order['id'])
            ->setMerchant($merchant)
            ->setAmount($amountInr)
            ->setPaymentType(PaymentTypeEnum::DEPOSIT)
            ->setStatus(PaymentStatusEnum::INITIATED)
            ->setMetadata(['plan' => $plan, 'razorpay_order_id' => $order['id']]);

        $this->em->persist($payment);
        $this->em->flush();

        $this->logger->info('Razorpay order created', [
            'merchant' => $merchant->getId(),
            'order'    => $order['id'],
            'plan'     => $plan,
            'amount'   => $amountPaise,
        ]);

        return [
            'orderId'  => $order['id'],
            'amount'   => $amountPaise,
            'currency' => 'INR',
            'keyId'    => $this->keyId,
            'receipt'  => $receipt,
            'plan'     => $plan,
        ];
    }

    public function verifyPayment(string $paymentId, string $orderId, string $signature): void
    {
        $api = new Api($this->keyId, $this->keySecret);
        $api->utility->verifyPaymentSignature([
            'razorpay_payment_id' => $paymentId,
            'razorpay_order_id'   => $orderId,
            'razorpay_signature'  => $signature,
        ]);

        $payment = $this->paymentRepository->findByGatewayId($orderId);
        if ($payment === null || $payment->getStatus() === PaymentStatusEnum::COMPLETED) {
            return;
        }

        $this->handleCaptured($payment, ['id' => $paymentId, 'order_id' => $orderId]);
        $this->em->flush();
    }

    public function handleWebhook(string $rawBody, string $signature): void
    {
        $api = new Api($this->keyId, $this->keySecret);
        $api->utility->verifyWebhookSignature($rawBody, $signature, $this->webhookSecret);

        $payload     = json_decode($rawBody, true);
        $event       = $payload['event'] ?? '';
        $paymentData = $payload['payload']['payment']['entity'] ?? null;

        if ($paymentData === null) {
            $this->logger->info('Webhook event with no payment entity', ['event' => $event]);
            return;
        }

        $gatewayOrderId = $paymentData['order_id'] ?? null;
        if ($gatewayOrderId === null) {
            $this->logger->warning('Webhook payment entity missing order_id', ['event' => $event]);
            return;
        }

        $payment = $this->paymentRepository->findByGatewayId($gatewayOrderId);
        if ($payment === null) {
            $this->logger->warning('Webhook received for unknown order', [
                'order_id' => $gatewayOrderId,
                'event'    => $event,
            ]);
            return;
        }

        if ($payment->getStatus() === PaymentStatusEnum::COMPLETED) {
            return;
        }

        $payment->markWebhookReceived();

        match ($event) {
            'payment.captured' => $this->handleCaptured($payment, $paymentData),
            'payment.failed'   => $this->handleFailed($payment, $paymentData),
            default            => $this->logger->info('Unhandled webhook event', ['event' => $event]),
        };

        $this->em->flush();
    }

    public function getMerchantPayments(Merchant $merchant): array
    {
        $payments = $this->paymentRepository->findBy(
            ['merchant' => $merchant],
            ['createdAt' => 'DESC']
        );

        return array_map(fn(Payment $p) => $this->serializePayment($p), $payments);
    }

    public function serializePayment(Payment $payment): array
    {
        return [
            'id'            => $payment->getId(),
            'transactionId' => $payment->getTransactionId(),
            'amount'        => $payment->getAmount(),
            'currency'      => $payment->getCurrency(),
            'status'        => $payment->getStatus()->value,
            'paymentType'   => $payment->getPaymentType()->value,
            'paymentMethod' => $payment->getPaymentMethod()?->value,
            'createdAt'     => $payment->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'processedAt'   => $payment->getProcessedAt()?->format(\DateTimeInterface::ATOM),
            'metadata'      => $payment->getMetadata(),
        ];
    }

    private function handleCaptured(Payment $payment, array $paymentData): void
    {
        $payment->setStatus(PaymentStatusEnum::COMPLETED)
            ->setPaymentGatewayId($paymentData['id']);

        $method = strtoupper($paymentData['method'] ?? '');
        try {
            if ($method !== '') {
                $payment->setPaymentMethod(PaymentMethodEnum::from($method));
            }
        } catch (\ValueError) {
            // Unknown payment method — skip rather than fail
        }

        $this->logger->info('Payment captured', [
            'payment_id' => $paymentData['id'],
            'order_id'   => $paymentData['order_id'],
        ]);
    }

    private function handleFailed(Payment $payment, array $paymentData): void
    {
        $payment->setStatus(PaymentStatusEnum::FAILED)
            ->setFailureReason($paymentData['error_description'] ?? 'Payment failed');

        $this->logger->warning('Payment failed', [
            'order_id' => $paymentData['order_id'],
            'reason'   => $paymentData['error_description'] ?? '',
        ]);
    }
}
