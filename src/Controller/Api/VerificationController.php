<?php

namespace App\Controller\Api;

use App\Dto\Verification\RequestOtpDto;
use App\Dto\Verification\VerifyOtpDto;
use App\Entity\User;
use App\Service\OtpService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/verification')]
#[IsGranted('ROLE_USER')]
class VerificationController extends AbstractController
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/request', methods: ['POST'])]
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
