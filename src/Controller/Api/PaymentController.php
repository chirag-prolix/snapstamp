<?php

namespace App\Controller\Api;

use App\Dto\Payment\CreateOrderDto;
use App\Dto\Payment\VerifyPaymentDto;
use App\Entity\Merchant;
use App\Service\PaymentService;
use Razorpay\Api\Errors\BadRequestError;
use Razorpay\Api\Errors\SignatureVerificationError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1')]
class PaymentController extends AbstractController
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/merchant/payment/subscribe', methods: ['POST'])]
    #[IsGranted('ROLE_MERCHANT')]
    public function subscribe(Request $request): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        $dto = $this->hydrate(new CreateOrderDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $result = $this->paymentService->createSubscriptionOrder($merchant, $dto->plan);
        } catch (BadRequestError $e) {
            return $this->json(
                ['success' => false, 'message' => 'Payment gateway error.'],
                Response::HTTP_BAD_GATEWAY
            );
        }

        return $this->json(
            ['success' => true, 'message' => 'Order created.', 'data' => $result],
            Response::HTTP_CREATED
        );
    }

    #[Route('/merchant/payment/verify', methods: ['POST'])]
    #[IsGranted('ROLE_MERCHANT')]
    public function verify(Request $request): JsonResponse
    {
        $dto = $this->hydrate(new VerifyPaymentDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $this->paymentService->verifyPayment(
                $dto->razorpay_payment_id,
                $dto->razorpay_order_id,
                $dto->razorpay_signature,
            );
        } catch (SignatureVerificationError) {
            return $this->json(
                ['success' => false, 'message' => 'Payment verification failed.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json(['success' => true, 'message' => 'Payment verified.']);
    }

    #[Route('/merchant/payments', methods: ['GET'])]
    #[IsGranted('ROLE_MERCHANT')]
    public function payments(): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        return $this->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $this->paymentService->getMerchantPayments($merchant),
        ]);
    }

    #[Route('/payment/webhook', methods: ['POST'])]
    public function webhook(Request $request): JsonResponse
    {
        $rawBody   = $request->getContent();
        $signature = $request->headers->get('X-Razorpay-Signature', '');

        try {
            $this->paymentService->handleWebhook($rawBody, $signature);
        } catch (SignatureVerificationError) {
            return $this->json(
                ['success' => false, 'message' => 'Invalid webhook signature.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json(['success' => true, 'message' => 'OK']);
    }

    private function hydrate(object $dto, Request $request): object
    {
        $data = json_decode($request->getContent(), true) ?? [];
        foreach ($data as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->$key = $value;
            }
        }
        return $dto;
    }

    private function validate(object $dto): ?JsonResponse
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) === 0) {
            return null;
        }

        $errors = [];
        foreach ($violations as $violation) {
            $field = ltrim($violation->getPropertyPath(), '.');
            $errors[$field] = $violation->getMessage();
        }

        return $this->json(
            ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors],
            Response::HTTP_BAD_REQUEST
        );
    }
}
