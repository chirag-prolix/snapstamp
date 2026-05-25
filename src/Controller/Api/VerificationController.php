<?php

namespace App\Controller\Api;

use App\Dto\Verification\RequestOtpDto;
use App\Dto\Verification\VerifyOtpDto;
use App\Entity\User;
use App\Service\OtpService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/verification')]
#[IsGranted('ROLE_USER')]
#[OA\Tag(name: 'Verification')]
class VerificationController extends AbstractController
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/request', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/verification/request',
        summary: 'Request an OTP to verify email or phone',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['email', 'phone'], example: 'email'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OTP sent'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function requestOtp(Request $request): JsonResponse
    {
        $dto = $this->hydrate(new RequestOtpDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            if ($dto->type === 'email') {
                $this->otpService->requestEmailOtp($user);
                $message = 'OTP sent to your email address.';
            } else {
                $this->otpService->requestPhoneOtp($user);
                $message = 'OTP sent to your phone number.';
            }
        } catch (\DomainException $e) {
            return $this->json(
                ['success' => false, 'message' => $e->getMessage()],
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }

        return $this->json(['success' => true, 'message' => $message]);
    }

    #[Route('/verify', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/verification/verify',
        summary: 'Submit OTP code to verify email or phone',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'code'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['email', 'phone'], example: 'email'),
                    new OA\Property(property: 'code', type: 'string', example: '123456'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Verified successfully, returns isEmailVerified and isPhoneVerified'),
            new OA\Response(response: 400, description: 'Invalid or expired OTP'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function verifyOtp(Request $request): JsonResponse
    {
        $dto = $this->hydrate(new VerifyOtpDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $this->otpService->verifyOtp($user, $dto->type, $dto->code);
        } catch (\DomainException $e) {
            return $this->json(
                ['success' => false, 'message' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        $label = $dto->type === 'email' ? 'Email' : 'Phone number';

        return $this->json([
            'success' => true,
            'message' => "{$label} verified successfully.",
            'data'    => [
                'isEmailVerified' => $user->isEmailVerified(),
                'isPhoneVerified' => $user->isPhoneVerified(),
            ],
        ]);
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
            $errors[ltrim($violation->getPropertyPath(), '.')] = $violation->getMessage();
        }

        return $this->json(
            ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors],
            Response::HTTP_BAD_REQUEST
        );
    }
}
