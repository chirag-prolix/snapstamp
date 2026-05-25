<?php

namespace App\Controller\Api;

use App\Dto\Payment\CreateOrderDto;
use App\Dto\Payment\VerifyPaymentDto;
use App\Entity\Merchant;
use App\Service\PaymentService;
use OpenApi\Attributes as OA;
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
#[OA\Tag(name: 'Payments')]
class PaymentController extends AbstractController
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/merchant/payment/subscribe', methods: ['POST'])]
    #[IsGranted('ROLE_MERCHANT')]
    #[OA\Post(
        path: '/api/v1/merchant/payment/subscribe',
        summary: 'Create a Razorpay subscription order',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['plan'],
                properties: [
                    new OA\Property(property: 'plan', type: 'string', enum: ['monthly', 'annual'], example: 'monthly'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Order created — returns orderId, amount, currency, keyId, receipt, plan'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 502, description: 'Payment gateway error'),
        ]
    )]
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
        } catch (BadRequestError) {
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
    #[OA\Post(
        path: '/api/v1/merchant/payment/verify',
        summary: 'Verify a completed Razorpay payment',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['razorpay_payment_id', 'razorpay_order_id', 'razorpay_signature'],
                properties: [
                    new OA\Property(property: 'razorpay_payment_id', type: 'string', example: 'pay_XXXXXXXXXXXXXX'),
                    new OA\Property(property: 'razorpay_order_id', type: 'string', example: 'order_XXXXXXXXXXXXXX'),
                    new OA\Property(property: 'razorpay_signature', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Payment verified and subscription activated'),
            new OA\Response(response: 400, description: 'Signature verification failed'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
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
    #[OA\Get(
        path: '/api/v1/merchant/payments',
        summary: 'List all payments for the authenticated merchant',
        responses: [
            new OA\Response(response: 200, description: 'List of payment records'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
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
    #[OA\Post(
        path: '/api/v1/payment/webhook',
        summary: 'Razorpay webhook receiver (no auth required)',
        security: [],
        parameters: [
            new OA\Parameter(name: 'X-Razorpay-Signature', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Webhook processed'),
            new OA\Response(response: 400, description: 'Invalid signature'),
        ]
    )]
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
