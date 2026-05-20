<?php

namespace App\Controller\Api;

use App\Dto\Merchant\UpdateMerchantProfileDto;
use App\Entity\Merchant;
use App\Service\MerchantOnboardingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/merchant')]
#[IsGranted('ROLE_MERCHANT')]
class MerchantController extends AbstractController
{
    public function __construct(
        private readonly MerchantOnboardingService $onboardingService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        return $this->json([
            'success' => true,
            'data'    => $this->onboardingService->serialize($merchant, includeSecret: true),
        ]);
    }

    #[Route('/profile', methods: ['PATCH'])]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        $dto = $this->hydrate(new UpdateMerchantProfileDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        $this->onboardingService->updateProfile($merchant, $dto);

        return $this->json([
            'success' => true,
            'message' => 'Profile updated.',
            'data'    => $this->onboardingService->serialize($merchant, includeSecret: true),
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
